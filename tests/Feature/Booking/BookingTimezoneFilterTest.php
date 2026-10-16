<?php

namespace Tests\Feature\Booking;

use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Queries\BookingCalendarQuery;
use App\Modules\Booking\Queries\BookingIndexQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class BookingTimezoneFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_list_and_calendar_filters_use_bangkok_day_boundaries(): void
    {
        $college = College::factory()->create();
        $actor = User::factory()->create(['college_id' => $college->id, 'role' => 'staff']);

        $this->booking($actor, '2026-09-30 16:00:00', '2026-09-30 16:59:59'); // Before Bangkok day.
        $this->booking($actor, '2026-09-30 17:00:00', '2026-09-30 18:00:00'); // Bangkok midnight.
        $this->booking($actor, '2026-10-01 16:59:59', '2026-10-01 17:00:00'); // Last second of Bangkok day.
        $this->booking($actor, '2026-10-01 17:00:00', '2026-10-01 18:00:00'); // Next Bangkok day.

        $filters = ['date_from' => '2026-10-01', 'date_to' => '2026-10-01'];

        $this->assertSame(2, app(BookingIndexQuery::class)->paginate($actor, $filters)->total());
        $this->assertCount(2, app(BookingCalendarQuery::class)->get($actor, $filters));
    }

    public function test_calendar_defaults_to_current_bangkok_month_at_utc_month_boundary(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-31T18:00:00Z'));
        $actor = User::factory()->create(['college_id' => College::factory()->create()->id, 'role' => 'staff']);
        $this->booking($actor, '2026-10-15 09:00:00', '2026-10-15 10:00:00');
        $inside = $this->booking($actor, '2026-10-31 18:00:00', '2026-10-31 19:00:00');
        $this->booking($actor, '2026-11-30 17:00:00', '2026-11-30 18:00:00');
        foreach (['/app/calendar', '/app/calendar?date_from=&date_to='] as $url) {
            $this->actingAs($actor)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('filters.date_from', '2026-11-01')->where('filters.date_to', '2026-11-30')
                ->has('events', 1)->where('events.0.id', $inside->id));
        }
        $this->actingAs($actor)->get('/app/calendar?date_from=2026-10-01&date_to=2026-10-31')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.date_from', '2026-10-01')->where('filters.date_to', '2026-10-31')->has('events', 1));
        $this->travelBack();
    }

    private function booking(User $actor, string $startsAt, string $endsAt): Booking
    {
        return Booking::create([
            'college_id' => $actor->college_id,
            'requested_by_user_id' => $actor->id,
            'requester_name' => $actor->name,
            'requester_phone' => '0812345678',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'participant_count' => 1,
            'status' => 'pending',
        ]);
    }
}
