<?php

namespace App\Services\Zoom;

use App\Exceptions\MeetingException;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over the Zoom REST API using one Server-to-Server OAuth app
 * for the whole organisation (ZOOM_ACCOUNT_ID / ZOOM_CLIENT_ID / ZOOM_CLIENT_SECRET).
 */
class ZoomClient
{
    private const TOKEN_KEY = 'zoom.s2s_token';
    private const API = 'https://api.zoom.us/v2';

    /** Server-to-Server OAuth token (valid ~1 hour, cached for 55 minutes). */
    public function token(bool $fresh = false): string
    {
        if ($fresh) {
            Cache::forget(self::TOKEN_KEY);
        }

        return Cache::remember(self::TOKEN_KEY, now()->addMinutes(55), function () {
            $c = config('services.zoom');
            if (blank($c['account_id']) || blank($c['client_id']) || blank($c['client_secret'])) {
                throw new MeetingException('Zoom credentials are not set. Add ZOOM_ACCOUNT_ID, ZOOM_CLIENT_ID and ZOOM_CLIENT_SECRET to the .env file.');
            }

            $res = Http::withBasicAuth($c['client_id'], $c['client_secret'])
                ->asForm()
                ->timeout(20)
                ->post('https://zoom.us/oauth/token?'.http_build_query([
                    'grant_type' => 'account_credentials',
                    'account_id' => $c['account_id'],
                ]));

            $token = $res->json('access_token');
            if ($res->failed() || ! $token) {
                throw new MeetingException('Zoom auth failed: '.($res->json('reason') ?? $res->json('error') ?? $res->status()));
            }

            return $token;
        });
    }

    /** Sends a request; if Zoom says the token is invalid, gets a new one and retries once. */
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
        return Http::withToken($token)->acceptJson()->timeout(20);
    }

    private function fail(string $what, Response $res): never
    {
        throw new MeetingException("Zoom {$what} failed: ".($res->json('message') ?? $res->status()));
    }

    /** @return array{id:int|string, join_url:string, start_url:string, password?:string} */
    public function createMeeting(string $hostEmail, string $title, ?string $agenda, CarbonInterface $start, int $durationMin): array
    {
        $res = $this->send(fn (PendingRequest $h) => $h->post(self::API.'/users/'.rawurlencode($hostEmail).'/meetings', [
            'topic' => $title,
            'agenda' => $agenda ?? '',
            'type' => 2, // scheduled meeting
            'start_time' => $start->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'duration' => $durationMin,
            'timezone' => config('app.timezone'),
            'settings' => ['waiting_room' => true, 'join_before_host' => false, 'mute_upon_entry' => true],
        ]));

        $res->successful() || $this->fail('create', $res);

        return $res->json();
    }

    /** Moves an existing meeting to a new time; the join link stays the same. */
    public function updateMeeting(string $meetingId, CarbonInterface $start, int $durationMin): void
    {
        $res = $this->send(fn (PendingRequest $h) => $h->patch(self::API.'/meetings/'.$meetingId, [
            'type' => 2,
            'start_time' => $start->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'duration' => $durationMin,
            'timezone' => config('app.timezone'),
        ]));

        $res->successful() || $this->fail('update', $res);
    }

    public function getMeeting(string $meetingId): array
    {
        $res = $this->send(fn (PendingRequest $h) => $h->get(self::API.'/meetings/'.$meetingId));
        $res->successful() || $this->fail('fetch', $res);

        return $res->json();
    }

    public function getUser(string $email): array
    {
        $res = $this->send(fn (PendingRequest $h) => $h->get(self::API.'/users/'.rawurlencode($email)));
        $res->successful() || $this->fail('user lookup', $res);

        return $res->json();
    }

    /** Best effort: callers decide whether a failure matters. */
    public function deleteMeeting(string $meetingId): void
    {
        $res = $this->send(fn (PendingRequest $h) => $h->delete(self::API.'/meetings/'.$meetingId));
        if ($res->failed() && $res->status() !== 404) {
            $this->fail('delete', $res);
        }
    }

    /**
     * Cloud recordings of a meeting, or null when Zoom has none (yet).
     * `download_access_token` is short-lived, so the browser can fetch the large files straight from Zoom.
     *
     * @return array{download_access_token?:string, recording_files?:array<int,array<string,mixed>>}|null
     */
    public function recordings(string $meetingId, int $ttl = 3600): ?array
    {
        $res = $this->send(fn (PendingRequest $h) => $h->get(
            self::API.'/meetings/'.$meetingId.'/recordings',
            ['include_fields' => 'download_access_token', 'ttl' => $ttl]
        ));

        if ($res->status() === 404) {
            return null;
        }
        $res->successful() || $this->fail('recording lookup', $res);

        return $res->json();
    }

    /**
     * Removes all recording files of a meeting. `trash` keeps them recoverable in Zoom for 30 days,
     * `delete` removes them for good. A meeting with no recordings (404) is fine.
     */
    public function deleteRecordings(string $meetingId, string $action = 'trash'): void
    {
        $action = $action === 'delete' ? 'delete' : 'trash';

        $res = $this->send(fn (PendingRequest $h) => $h->delete(
            self::API.'/meetings/'.$meetingId.'/recordings?'.http_build_query(['action' => $action])
        ));

        if ($res->failed() && $res->status() !== 404) {
            $this->fail('recording delete', $res);
        }
    }

    /** Streams one recording file straight to disk (files can be gigabytes, so never into memory). */
    public function downloadTo(string $downloadUrl, string $path, ?string $downloadToken = null): void
    {
        $url = $downloadUrl.(str_contains($downloadUrl, '?') ? '&' : '?').'access_token='.($downloadToken ?: $this->token());

        $res = Http::withOptions(['sink' => $path])
            ->connectTimeout(30)
            ->timeout(0)
            ->retry(3, 5000, throw: false)
            ->get($url);

        if ($res->failed()) {
            @unlink($path);
            throw new MeetingException('Zoom recording download failed: HTTP '.$res->status());
        }
    }
}
