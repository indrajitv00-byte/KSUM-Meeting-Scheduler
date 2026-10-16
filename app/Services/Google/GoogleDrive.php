<?php

namespace App\Services\Google;

use App\Exceptions\MeetingException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;

/**
 * Minimal Google Drive client for the recordings job: uploads big files in chunks
 * (resumable upload) and streams them back for downloads through the portal.
 * Signs in with the refresh token of one Google account (GOOGLE_DRIVE_REFRESH_TOKEN).
 */
class GoogleDrive
{
    private const TOKEN_KEY = 'google.drive_token';
    private const FOLDER_KEY = 'google.drive_folder';
    private const API = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';
    private const CHUNK = 8 * 1024 * 1024; // must be a multiple of 256 KiB

    /** Access token (valid ~1 hour, cached for 50 minutes). */
    public function token(bool $fresh = false): string
    {
        if ($fresh) {
            Cache::forget(self::TOKEN_KEY);
        }

        return Cache::remember(self::TOKEN_KEY, now()->addMinutes(50), function () {
            $c = config('services.google_drive');
            if (blank($c['client_id']) || blank($c['client_secret']) || blank($c['refresh_token'])) {
                throw new MeetingException('Google Drive is not set up. Add GOOGLE_DRIVE_REFRESH_TOKEN (and GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET) to the .env file.');
            }

            $res = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
                'client_id' => $c['client_id'],
                'client_secret' => $c['client_secret'],
                'refresh_token' => $c['refresh_token'],
                'grant_type' => 'refresh_token',
            ]);

            $token = $res->json('access_token');
            if ($res->failed() || ! $token) {
                throw new MeetingException('Google auth failed: '.($res->json('error_description') ?? $res->json('error') ?? $res->status()));
            }

            return $token;
        });
    }

    /** Sends a request; if Google says the token is invalid, gets a new one and retries once. */
    private function send(callable $call): Response
    {
        $res = $call($this->http($this->token()));
        if ($res->status() === 401) {
            $res = $call($this->http($this->token(fresh: true)));
        }

        return $res;
    }

    private function http(string $token): PendingRequest
    {
        return Http::withToken($token)->acceptJson()->timeout(60);
    }

    private function fail(string $what, Response $res): never
    {
        throw new MeetingException("Google Drive {$what} failed: ".($res->json('error.message') ?? $res->status()));
    }

    /** Folder that holds all recordings: GOOGLE_DRIVE_FOLDER_ID, or one found/created by name. */
    public function folderId(): string
    {
        $configured = config('services.google_drive.folder_id');
        if (filled($configured)) {
            return $configured;
        }

        return Cache::rememberForever(self::FOLDER_KEY, function () {
            $name = config('services.google_drive.folder_name');

            $found = $this->send(fn (PendingRequest $h) => $h->get(self::API.'/files', [
                'q' => "mimeType='application/vnd.google-apps.folder' and name='".str_replace(['\\', "'"], ['\\\\', "\\'"], $name)."' and 'root' in parents and trashed=false",
                'fields' => 'files(id)',
                'pageSize' => 1,
            ]));
            $found->successful() || $this->fail('folder lookup', $found);

            if ($id = $found->json('files.0.id')) {
                return $id;
            }

            $made = $this->send(fn (PendingRequest $h) => $h->post(self::API.'/files?fields=id', [
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
            ]));
            $made->successful() || $this->fail('folder create', $made);

            return $made->json('id');
        });
    }

    /**
     * Uploads a local file to the recordings folder and checks Drive stored every byte.
     *
     * @return array{id:string,size:int}
     */
    public function upload(string $path, string $name, string $mime): array
    {
        $size = filesize($path);
        if ($size === false || $size < 1) {
            throw new MeetingException('Cannot upload an empty file.');
        }

        $folder = $this->folderId();

        $init = $this->send(fn (PendingRequest $h) => $h
            ->withHeaders(['X-Upload-Content-Type' => $mime, 'X-Upload-Content-Length' => (string) $size])
            ->post(self::UPLOAD.'?uploadType=resumable&supportsAllDrives=true&fields=id,size', [
                'name' => $name,
                'parents' => [$folder],
            ]));
        $init->successful() || $this->fail('upload start', $init);

        $session = $init->header('Location');
        if (! $session) {
            throw new MeetingException('Google Drive did not return an upload address.');
        }

        $fh = fopen($path, 'rb');
        $offset = 0;
        $stalled = 0;
        $done = null;

        try {
            while ($done === null) {
                fseek($fh, $offset);
                $chunk = (string) fread($fh, self::CHUNK);
                $end = $offset + strlen($chunk) - 1;

                $res = $this->putChunk($session, $chunk, $offset, $end, $size);

                if ($res->status() === 308) {
                    // Drive says how much it has stored so far
                    $range = $res->header('Range');
                    $next = ($range && preg_match('/-(\d+)$/', $range, $m)) ? ((int) $m[1]) + 1 : 0;
                    $stalled = $next > $offset ? 0 : $stalled + 1;
                    if ($stalled > 5) {
                        throw new MeetingException('Google Drive upload is not making progress.');
                    }
                    $offset = $next;

                    continue;
                }

                $done = $res->json() ?? [];
            }
        } finally {
            fclose($fh);
        }

        $id = $done['id'] ?? null;
        if (! $id) {
            throw new MeetingException('Google Drive did not confirm the upload.');
        }

        if ((int) ($done['size'] ?? -1) !== $size) {
            $this->delete($id);
            throw new MeetingException('Google Drive stored a different size than was sent; the upload was discarded.');
        }

        return ['id' => $id, 'size' => $size];
    }

    /** One chunk of a resumable upload, retried on network/server errors. Returns a 308 (more to send) or a success. */
    private function putChunk(string $session, string $chunk, int $start, int $end, int $total): Response
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                $res = $this->putRaw($session, $chunk, "bytes {$start}-{$end}/{$total}");
                if ($res->status() === 308 || $res->successful()) {
                    return $res;
                }
                if ($res->status() === 401) {
                    Cache::forget(self::TOKEN_KEY);
                } elseif ($res->status() < 500 && $res->status() !== 429) {
                    $this->fail('upload', $res);
                }
            } catch (ConnectionException) {
                // network blip: fall through to retry
            }

            if ($attempt >= 5) {
                throw new MeetingException('Google Drive upload failed after several attempts.');
            }

            sleep(min(60, 2 ** $attempt));

            // ask Drive how much of the file it already has, then carry on from there
            try {
                $status = $this->putRaw($session, '', "bytes */{$total}");
                if ($status->status() === 308 || $status->successful()) {
                    return $status;
                }
            } catch (ConnectionException) {
            }
        }
    }

    private function putRaw(string $session, string $body, string $contentRange): Response
    {
        return Http::withToken($this->token())
            ->withOptions(['allow_redirects' => false])
            ->timeout(300)
            ->withBody($body, 'application/octet-stream')
            ->withHeaders(['Content-Range' => $contentRange])
            ->put($session);
    }

    /** Moves a file to Drive's trash (recoverable for 30 days). Already gone counts as done. */
    public function trash(string $fileId): void
    {
        $res = $this->send(fn (PendingRequest $h) => $h->patch(
            self::API.'/files/'.$fileId.'?supportsAllDrives=true',
            ['trashed' => true]
        ));

        if ($res->failed() && $res->status() !== 404) {
            $this->fail('trash', $res);
        }
    }

    /** Best effort. */
    public function delete(string $fileId): void
    {
        try {
            $this->send(fn (PendingRequest $h) => $h->delete(self::API.'/files/'.$fileId.'?supportsAllDrives=true'));
        } catch (\Throwable) {
        }
    }

    /** Body of a Drive file as a stream, for passing straight through to the browser. */
    public function stream(string $fileId): StreamInterface
    {
        $res = $this->send(fn (PendingRequest $h) => $h
            ->withOptions(['stream' => true])
            ->timeout(0)
            ->get(self::API.'/files/'.$fileId, ['alt' => 'media', 'supportsAllDrives' => 'true']));
        $res->successful() || $this->fail('download', $res);

        return $res->toPsrResponse()->getBody();
    }
}
