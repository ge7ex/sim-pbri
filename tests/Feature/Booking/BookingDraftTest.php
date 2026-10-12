<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingDraft;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class BookingDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_save_incomplete_draft_without_creating_a_booking(): void
    {
        $user = $this->lecturer();

        $this->actingAs($user)->post('/app/booking-drafts', [
            'requester_name' => 'ผู้ประสานงาน',
            'college_id' => 999999,
            'user_id' => 999999,
            'requested_by_user_id' => 999999,
        ])->assertRedirect();

        $draft = BookingDraft::query()->sole();
        $this->assertSame($user->college_id, $draft->college_id);
        $this->assertSame($user->id, $draft->user_id);
        $this->assertSame(['requester_name' => 'ผู้ประสานงาน'], $draft->payload);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_resource', 0);
    }

    public function test_draft_create_update_and_delete_are_limited_to_the_owner(): void
    {
        $owner = $this->lecturer();
        $other = $this->lecturer();
        $draft = BookingDraft::query()->create([
            'college_id' => $owner->college_id,
            'user_id' => $owner->id,
            'payload' => ['note' => 'private'],
        ]);

        $this->actingAs($other)->get('/app/bookings/create?draft='.$draft->id)->assertNotFound();
        $this->actingAs($other)->put('/app/booking-drafts/'.$draft->id, ['note' => 'tamper'])->assertNotFound();
        $this->actingAs($other)->delete('/app/booking-drafts/'.$draft->id)->assertNotFound();
        $this->assertSame(['note' => 'private'], $draft->fresh()->payload);

        $this->actingAs($owner)->put('/app/booking-drafts/'.$draft->id, ['note' => 'updated'])->assertRedirect();
        $this->assertSame(['note' => 'updated'], $draft->fresh()->payload);
        $this->actingAs($owner)->delete('/app/booking-drafts/'.$draft->id)->assertRedirect();
        $this->assertDatabaseCount('booking_drafts', 0);
    }

    public function test_owner_can_resume_draft_and_only_receives_their_own_draft_list(): void
    {
        $owner = $this->lecturer();
        $other = $this->lecturer();
        $draft = BookingDraft::query()->create([
            'college_id' => $owner->college_id,
            'user_id' => $owner->id,
            'payload' => ['requester_name' => 'ผู้ประสานงาน'],
        ]);
        BookingDraft::query()->create([
            'college_id' => $other->college_id,
            'user_id' => $other->id,
            'payload' => ['requester_name' => 'ข้อมูลส่วนบุคคล'],
        ]);

        $this->actingAs($owner)->get('/app/bookings/create?draft='.$draft->id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Modules/Booking/Pages/Create')
            ->where('draft.id', $draft->id)
            ->where('draft.payload.requester_name', 'ผู้ประสานงาน')
            ->has('drafts', 1)
            ->missing('drafts.0.payload'));
    }

    public function test_draft_rejects_foreign_college_resource_ids(): void
    {
        $owner = $this->lecturer();
        $foreign = $this->room($this->lecturer());

        $this->actingAs($owner)->post('/app/booking-drafts', [
            'resources' => [['id' => $foreign->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('resources.0.id');

        $this->assertDatabaseCount('booking_drafts', 0);
    }

    public function test_user_cannot_create_more_than_ten_drafts(): void
    {
        $user = $this->lecturer();
        foreach (range(1, 10) as $_) {
            BookingDraft::query()->create(['college_id' => $user->college_id, 'user_id' => $user->id, 'payload' => []]);
        }

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/booking-drafts', ['note' => 'one too many'])
            ->assertSessionHasErrors('drafts');

        $this->assertDatabaseCount('booking_drafts', 10);
    }

    public function test_submitting_a_saved_draft_revalidates_creates_pending_booking_and_deletes_draft(): void
    {
        $user = $this->lecturer();
        $room = $this->room($user);
        $draft = BookingDraft::query()->create([
            'college_id' => $user->college_id,
            'user_id' => $user->id,
            'payload' => ['note' => 'Draft note'],
        ]);

        $this->actingAs($user)->post('/app/bookings', [
            'draft_id' => $draft->id,
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-15 09:00:00',
            'ends_at' => '2026-10-15 10:00:00',
            'participant_count' => 10,
            'requester_phone' => '0812345678',
            'note' => 'Draft note',
        ])->assertRedirect(route('bookings.index'));

        $this->assertSame(BookingStatus::Pending, Booking::query()->sole()->status);
        $this->assertDatabaseCount('booking_resource', 1);
        $this->assertDatabaseCount('booking_drafts', 0);
    }

    public function test_failed_submission_keeps_draft_and_does_not_reserve_resources(): void
    {
        $user = $this->lecturer();
        $room = $this->room($user);
        $this->actingAs($user)->post('/app/bookings', [
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-15 09:00:00',
            'ends_at' => '2026-10-15 10:00:00',
            'participant_count' => 10,
            'requester_phone' => '0812345678',
        ])->assertRedirect();
        $draft = BookingDraft::query()->create([
            'college_id' => $user->college_id,
            'user_id' => $user->id,
            'payload' => ['note' => 'preserve on conflict'],
        ]);

        $this->actingAs($user)->from('/app/bookings/create')->post('/app/bookings', [
            'draft_id' => $draft->id,
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'starts_at' => '2026-10-15 09:30:00',
            'ends_at' => '2026-10-15 10:30:00',
            'participant_count' => 10,
            'requester_phone' => '0812345678',
        ])->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertSame(['note' => 'preserve on conflict'], $draft->fresh()->payload);
    }

    private function lecturer(): User
    {
        $college = College::factory()->create();

        return User::factory()->create(['college_id' => $college->id, 'role' => UserRole::Lecturer->value]);
    }

    private function room(User $user): SimResource
    {
        return SimResource::query()->create([
            'college_id' => $user->college_id,
            'name' => 'ห้องทดสอบ',
            'kind' => SimResourceKind::Room,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 1,
            'is_exclusive' => true,
            'capacity' => 40,
        ]);
    }
}
