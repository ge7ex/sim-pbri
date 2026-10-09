<?php

namespace Tests\Feature\Booking;

use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BookingRelationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_foreign_hydrated_relations_are_hidden_without_changing_booking_history(): void
    {
        $college = College::factory()->create();
        $actor = User::factory()->create(['college_id' => $college->id, 'role' => 'staff', 'name' => 'OWN ACTOR']);
        $foreign = User::factory()->create(['college_id' => College::factory()->create()->id, 'role' => 'staff', 'name' => 'FOREIGN SECRET USER']);
        $ownResource = $this->resource($actor, 'OWN RESOURCE');
        $foreignResource = $this->resource($foreign, 'FOREIGN SECRET RESOURCE');
        $own = $this->booking($actor, $actor);
        $malformed = $this->booking($actor, $foreign);
        foreach ([$own, $malformed] as $booking) {
            $booking->resources()->attach([$ownResource->id => ['quantity' => 1], $foreignResource->id => ['quantity' => 1]]);
            foreach ([$actor, $foreign] as $user) {
                $booking->statusTransitions()->create(['from_status' => null, 'to_status' => 'pending', 'actor_user_id' => $user->id]);
                $booking->participantAmendments()->create(['previous_count' => null, 'participant_count' => 10, 'actor_user_id' => $user->id, 'reason' => 'Historical correction']);
            }
        }
        $this->actingAs($actor);
        foreach (['/app/bookings', '/app/review', '/app/calendar', '/app/bookings/'.$malformed->id, '/app/bookings/'.$own->id] as $url) {
            $response = $this->get($url)->assertOk();
            $payload = json_encode($response->inertiaProps());
            $this->assertStringNotContainsString('FOREIGN SECRET', $payload, $url);
            $this->assertStringContainsString('OWN RESOURCE', $payload, $url);
            $this->assertStringContainsString('Preserved requester snapshot', $payload, $url);
            if ($url !== '/app/calendar') {
                $this->assertStringContainsString('OWN ACTOR', $payload, $url);
            }
        }
        $ownDetail = $this->get('/app/bookings/'.$own->id)->inertiaProps('booking');
        $this->assertSame('OWN ACTOR', $ownDetail['requested_by']['name']);
        $this->assertSame('OWN ACTOR', $ownDetail['reviewed_by']['name']);
        $this->assertSame('OWN ACTOR', $ownDetail['status_transitions'][0]['actor']['name']);
        $this->assertSame('OWN ACTOR', $ownDetail['participant_amendments'][0]['actor']['name']);
        $detail = $this->get('/app/bookings/'.$malformed->id)->inertiaProps('booking');
        $this->assertNull($detail['requested_by']);
        $this->assertNull($detail['reviewed_by']);
        $this->assertNull($detail['status_transitions'][1]['actor']);
        $this->assertNull($detail['participant_amendments'][1]['actor']);
        $this->assertSame($foreign->id, $malformed->fresh()->requested_by_user_id);
        $this->assertSame(2, $malformed->resources()->count());
        $this->assertSame(2, $malformed->statusTransitions()->count());
        $this->assertSame(2, $malformed->participantAmendments()->count());
    }

    private function resource(User $owner, string $name): SimResource
    {
        return SimResource::create(['college_id' => $owner->college_id, 'name' => $name, 'kind' => 'room', 'status' => 'ready', 'quantity_total' => 1, 'is_exclusive' => true, 'capacity' => 20]);
    }

    private function booking(User $owner, User $requester): Booking
    {
        return Booking::create(['college_id' => $owner->college_id, 'requested_by_user_id' => $requester->id, 'reviewed_by_user_id' => $requester->id, 'requester_name' => 'Preserved requester snapshot', 'starts_at' => '2026-10-01 09:00:00', 'ends_at' => '2026-10-01 10:00:00', 'status' => 'pending']);
    }
}
