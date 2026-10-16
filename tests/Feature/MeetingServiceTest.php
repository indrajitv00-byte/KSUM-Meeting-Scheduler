<?php

namespace Tests\Feature;

use App\Exceptions\MeetingException;
use App\Mail\MeetingNotification;
use App\Models\Meeting;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Services\MeetingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MeetingServiceTest extends TestCase
{
    use RefreshDatabase;

    private MeetingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-12 09:00:00'));
        config(['services.zoom' => ['account_id' => 'acc', 'client_id' => 'id', 'client_secret' => 'secret']]);

        Mail::fake();
        Http::fake([
            'zoom.us/oauth/token*' => Http::response(['access_token' => 'tok']),
            'api.zoom.us/v2/users/*/meetings' => Http::response([
                'id' => 123456789, 'join_url' => 'https://zoom.us/j/123456789',
                'start_url' => 'https://zoom.us/s/123456789', 'password' => 'abc123',
            ], 201),
            'api.zoom.us/v2/meetings/*' => Http::response([], 204),
        ]);

        $this->service = app(MeetingService::class);
    }

    private function account(string $label, int $priority): ZoomAccount
    {
        return ZoomAccount::create(['label' => $label, 'host_email' => "$label@example.com", 'priority' => $priority]);
    }

    private function request(array $over = []): array
    {
        return $over + [
            'title' => 'Project review',
            'start_time' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
            'duration_min' => 60,
        ];
    }

    private function busy(ZoomAccount $acc, string $time = '10:00', int $minutes = 60): Meeting
    {
        $start = now()->addDay()->setTimeFromTimeString($time);

        return Meeting::create([
            'user_id' => User::factory()->create()->id, 'title' => 'Existing',
            'start_time' => $start, 'end_time' => $start->copy()->addMinutes($minutes),
            'block_start' => $start, 'block_end' => $start->copy()->addMinutes($minutes + 10),
            'status' => 'scheduled', 'zoom_account_id' => $acc->id,
        ]);
    }

    public function test_books_the_lowest_priority_free_account_and_sends_confirmation(): void
    {
        $this->account('second', 20);
        $first = $this->account('first', 10);
        $user = User::factory()->create();

        $res = $this->service->schedule($user, $this->request());

        $this->assertSame('scheduled', $res['status']);
        $this->assertSame($first->id, $res['meeting']->zoom_account_id);
        $this->assertSame('https://zoom.us/j/123456789', $res['meeting']->join_url);
        $this->assertSame('abc123', $res['meeting']->passcode);
        Mail::assertSent(MeetingNotification::class, fn ($m) => $m->hasTo($user->email));
    }

    public function test_falls_back_to_the_next_account_when_the_first_is_busy(): void
    {
        $first = $this->account('first', 10);
        $second = $this->account('second', 20);
        $this->busy($first);

        $res = $this->service->schedule(User::factory()->create(), $this->request());

        $this->assertSame('scheduled', $res['status']);
        $this->assertSame($second->id, $res['meeting']->zoom_account_id);
    }

    public function test_the_buffer_after_a_meeting_blocks_back_to_back_bookings(): void
    {
        $acc = $this->account('only', 10);
        $this->busy($acc, '10:00', 60); // blocks until 11:10

        $res = $this->service->schedule(User::factory()->create(), $this->request([
            'start_time' => now()->addDay()->setTime(11, 0)->toDateTimeString(),
        ]));

        $this->assertSame('unavailable', $res['status']);
    }

    public function test_suggests_other_slots_and_records_a_failed_request_when_everything_is_busy(): void
    {
        $acc = $this->account('only', 10);
        $this->busy($acc);

        $res = $this->service->schedule(User::factory()->create(), $this->request());

        $this->assertSame('unavailable', $res['status']);
        $this->assertNotEmpty($res['suggestions']);
        $this->assertSame(1, Meeting::where('status', 'failed')->count());
        Mail::assertNothingSent();
    }

    public function test_releases_the_slot_when_zoom_refuses(): void
    {
        // start from a clean fake: with Http::fake() the first matching stub wins
        Http::swap(new HttpFactory());
        Http::fake([
            'zoom.us/oauth/token*' => Http::response(['access_token' => 'tok']),
            'api.zoom.us/v2/users/*/meetings' => Http::response(['message' => 'Invalid host'], 400),
        ]);
        $this->account('only', 10);

        $res = $this->service->schedule(User::factory()->create(), $this->request());

        $this->assertSame('error', $res['status']);
        $this->assertStringContainsString('Invalid host', $res['error']);
        $this->assertSame(0, Meeting::where('status', 'scheduled')->count());
    }

    public function test_rejects_a_start_time_in_the_past_and_short_titles(): void
    {
        $this->account('only', 10);
        $user = User::factory()->create();

        $past = $this->service->schedule($user, $this->request(['start_time' => now()->subHour()->toDateTimeString()]));
        $short = $this->service->schedule($user, $this->request(['title' => 'ab']));

        $this->assertSame('error', $past['status']);
        $this->assertSame('error', $short['status']);
    }

    public function test_says_so_when_no_zoom_accounts_exist(): void
    {
        $res = $this->service->schedule(User::factory()->create(), $this->request());

        $this->assertSame('error', $res['status']);
    }

    public function test_only_the_owner_can_cancel_and_cancelling_frees_the_slot(): void
    {
        $this->account('only', 10);
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $meeting = $this->service->schedule($owner, $this->request())['meeting'];

        try {
            $this->service->cancel($meeting, $other);
            $this->fail('A different user must not be able to cancel.');
        } catch (MeetingException $e) {
            $this->assertSame('Not allowed.', $e->getMessage());
        }

        $this->service->cancel($meeting, $owner);
        $this->assertSame('cancelled', $meeting->fresh()->status);

        // the slot is free again
        $again = $this->service->schedule($other, $this->request());
        $this->assertSame('scheduled', $again['status']);
    }
}
