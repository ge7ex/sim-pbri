<?php

namespace Tests\Feature\Booking;

use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Queries\BookingCalendarQuery;
use App\Modules\Booking\Queries\BookingIndexQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
