<?php

namespace Tests\Feature\Simulator;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Enums\SimulatorAssetStatus;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SimulatorBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_accepts_one_active_asset_and_asset_remains_optional(): void
    {
        [$user, $type, $asset, $room] = $this->context();
        $this->actingAs($user)->post('/app/bookings', $this->payload($room, $asset))->assertRedirect();
        $booking = Booking::query()->sole();
        $this->assertSame($asset->id, $booking->simulator_asset_id);
        $this->assertSame(BookingStatus::Pending, $booking->status);

        $this->post('/app/bookings', [
            ...$this->payload($room), 'starts_at' => '2026-10-12 11:00:00', 'ends_at' => '2026-10-12 12:00:00',
        ])->assertRedirect();
        $this->assertNull(Booking::query()->latest('id')->firstOrFail()->simulator_asset_id);
    }

    public function test_create_form_only_exposes_available_own_college_assets_without_financial_data(): void
    {
        [$user, $type, $asset] = $this->context();
        $this->context();
        $this->asset($type, ['asset_name' => 'Disabled simulator', 'status' => SimulatorAssetStatus::Disabled]);
        $this->asset($type, ['asset_name' => 'Under maintenance', 'status' => SimulatorAssetStatus::Maintenance]);
        $inactive = SimulatorType::query()->create([
            'college_id' => $user->college_id, 'name' => 'Inactive type', 'is_active' => false,
        ]);
        $this->asset($inactive);

        $this->actingAs($user)->get('/app/bookings/create')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Modules/Booking/Pages/Create')
                ->has('simulatorTypes', 1)
                ->where('simulatorTypes.0.id', $type->id)
                ->has('simulatorAssets', 1)
                ->where('simulatorAssets.0.id', $asset->id)
                ->where('simulatorAssets.0.asset_name', $asset->asset_name)
                ->missing('simulatorAssets.0.purchase_price')
                ->missing('simulatorAssets.0.purchase_year')
                ->missing('simulatorAssets.0.useful_life_years')
                ->missing('simulatorAssets.0.maintenance_records'));
    }

    public function test_foreign_disabled_maintenance_and_inactive_type_assets_cannot_be_booked(): void
    {
        [$user, $type, $asset, $room] = $this->context();
        [, , $foreignAsset] = $this->context();
        $this->actingAs($user)->postJson('/app/bookings', $this->payload($room, $foreignAsset))
            ->assertUnprocessable()->assertJsonValidationErrors('simulator_asset_id');
        foreach ([SimulatorAssetStatus::Disabled, SimulatorAssetStatus::Maintenance] as $status) {
            $asset->update(['status' => $status]);
            $this->postJson('/app/bookings', $this->payload($room, $asset))
                ->assertUnprocessable()->assertJsonValidationErrors('simulator_asset_id');
        }
        $asset->update(['status' => SimulatorAssetStatus::Active]);
        $type->update(['is_active' => false]);
        $this->postJson('/app/bookings', $this->payload($room, $asset))
            ->assertUnprocessable()->assertJsonValidationErrors('simulator_asset_id');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_simulator_is_exclusive_for_pending_and_approved_bookings_even_in_different_rooms(): void
    {
        [$user, , $asset, $room] = $this->context();
        $otherRoom = $this->room($user->college_id, 'Second room');
        $expectedCount = 0;
        foreach ([BookingStatus::Pending, BookingStatus::Approved] as $status) {
            $existing = $this->booking($user, $asset, $room, $status);
            $expectedCount++;
            $this->actingAs($user)->postJson('/app/bookings', $this->payload($otherRoom, $asset))
                ->assertUnprocessable();
            $this->assertDatabaseCount('bookings', $expectedCount);
            $existing->update(['status' => BookingStatus::Cancelled]);
        }
    }

    public function test_rejected_cancelled_and_adjacent_bookings_do_not_block_simulator(): void
    {
        [$user, , $asset, $room] = $this->context();
        foreach ([BookingStatus::Rejected, BookingStatus::Cancelled] as $status) {
            $existing = $this->booking($user, $asset, $room, $status);
            $this->actingAs($user)->post('/app/bookings', $this->payload($room, $asset))->assertRedirect();
            $newBooking = Booking::query()->latest('id')->firstOrFail();
            $this->assertSame($asset->id, $newBooking->simulator_asset_id);
            $newBooking->update(['status' => BookingStatus::Cancelled]);
            $existing->update(['status' => BookingStatus::Cancelled]);
        }
        $this->booking($user, $asset, $room, BookingStatus::Approved);
        $this->post('/app/bookings', [
            ...$this->payload($room, $asset), 'starts_at' => '2026-10-12 11:00:00', 'ends_at' => '2026-10-12 12:00:00',
        ])->assertRedirect();
        $this->assertDatabaseCount('bookings', 6);
    }

    public function test_approval_rechecks_asset_readiness_and_type_activation(): void
    {
        [$user, $type, $asset, $room] = $this->context();
        $staff = $this->staff($user->college_id);
        $booking = $this->booking($user, $asset, $room, BookingStatus::Pending);
        foreach ([SimulatorAssetStatus::Disabled, SimulatorAssetStatus::Maintenance] as $status) {
            $asset->update(['status' => $status]);
            $this->actingAs($staff)->postJson("/app/bookings/{$booking->id}/approve")->assertUnprocessable();
            $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
            $this->assertNull($booking->reviewed_by_user_id);
        }
        $asset->update(['status' => SimulatorAssetStatus::Active]);
        $type->update(['is_active' => false]);
        $this->postJson("/app/bookings/{$booking->id}/approve")->assertUnprocessable();
        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
        $type->update(['is_active' => true]);
        $this->post("/app/bookings/{$booking->id}/approve")->assertRedirect();
        $this->assertSame(BookingStatus::Approved, $booking->refresh()->status);
    }

    public function test_approval_rechecks_foreign_college_and_concurrent_simulator_conflicts(): void
    {
        [$user, , $asset, $room] = $this->context();
        [, , $foreignAsset] = $this->context();
        $staff = $this->staff($user->college_id);
        $booking = $this->booking($user, $foreignAsset, $room, BookingStatus::Pending);
        $this->actingAs($staff)->postJson("/app/bookings/{$booking->id}/approve")->assertUnprocessable();
        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);

        $booking->update(['simulator_asset_id' => $asset->id]);
        $other = $this->booking($user, $asset, $this->room($user->college_id, 'Other room'), BookingStatus::Pending);
        $this->postJson("/app/bookings/{$booking->id}/approve")->assertUnprocessable();
        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
        $other->update(['status' => BookingStatus::Cancelled]);
        $this->post("/app/bookings/{$booking->id}/approve")->assertRedirect();
        $this->assertSame(BookingStatus::Approved, $booking->refresh()->status);
    }

    public function test_historical_asset_remains_readable_without_financial_information(): void
    {
        [$user, $type, $asset, $room] = $this->context();
        $booking = $this->booking($user, $asset, $room, BookingStatus::Approved);
        $asset->maintenanceRecords()->create([
            'maintenance_date' => '2026-10-01', 'description' => 'Confidential service',
            'cost' => 3500, 'note' => 'Private note', 'created_by_user_id' => $user->id,
        ]);
        $type->update(['is_active' => false]);
        foreach ([SimulatorAssetStatus::Disabled, SimulatorAssetStatus::Maintenance] as $status) {
            $asset->update(['status' => $status]);
            $this->actingAs($user)->get("/app/bookings/{$booking->id}")->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('booking.simulator_asset.id', $asset->id)
                    ->where('booking.simulator_asset.asset_name', $asset->asset_name)
                    ->where('booking.simulator_asset.status', $status->value)
                    ->where('booking.simulator_asset.simulator_type.name', $type->name)
                    ->where('booking.simulator_asset.simulator_type.is_active', false)
                    ->missing('booking.simulator_asset.purchase_price')
                    ->missing('booking.simulator_asset.maintenance_records'));
        }
        $booking->update(['status' => BookingStatus::Pending]);
        $this->actingAs($this->staff($user->college_id))->get('/app/review')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('bookings.data.0.simulator_asset.id', $asset->id)
                ->missing('bookings.data.0.simulator_asset.purchase_price')
                ->missing('bookings.data.0.simulator_asset.maintenance_records'));
    }

    public function test_foreign_asset_relation_is_not_exposed_in_booking_detail_or_review(): void
    {
        [$user, , , $room] = $this->context();
        [, , $foreign] = $this->context();
        $booking = $this->booking($user, $foreign, $room, BookingStatus::Pending);
        $this->actingAs($user)->get("/app/bookings/{$booking->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('booking.simulator_asset', null));
        $this->actingAs($this->staff($user->college_id))->get('/app/review')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('bookings.data.0.simulator_asset', null));
    }

    public function test_recall_rechecks_simulator_conflict_and_readiness_before_reserving_slot(): void
    {
        [$user, $type, $asset, $room] = $this->context();
        $booking = $this->booking($user, $asset, $room, BookingStatus::Rejected);
        $conflict = $this->booking($user, $asset, $this->room($user->college_id, 'Different room'), BookingStatus::Pending);
        $payload = ['confirmed' => true, 'reason' => 'Reconsider request'];
        $this->actingAs($this->staff($user->college_id))->postJson("/app/bookings/{$booking->id}/recall", $payload)
            ->assertUnprocessable();
        $this->assertSame(BookingStatus::Rejected, $booking->refresh()->status);

        $conflict->update(['status' => BookingStatus::Cancelled]);
        $asset->update(['status' => SimulatorAssetStatus::Maintenance]);
        $this->postJson("/app/bookings/{$booking->id}/recall", $payload)->assertUnprocessable();
        $this->assertSame(BookingStatus::Rejected, $booking->refresh()->status);
        $asset->update(['status' => SimulatorAssetStatus::Active]);
        $type->update(['is_active' => false]);
        $this->postJson("/app/bookings/{$booking->id}/recall", $payload)->assertUnprocessable();
        $this->assertSame(BookingStatus::Rejected, $booking->refresh()->status);
        $type->update(['is_active' => true]);
        $this->post("/app/bookings/{$booking->id}/recall", $payload)->assertRedirect();
        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
    }

    public function test_scenario_type_recommendation_does_not_restrict_choice_of_another_active_asset(): void
    {
        [$user, $recommendedType, , $room] = $this->context();
        $alternativeType = SimulatorType::create([
            'college_id' => $user->college_id, 'name' => 'Alternative type', 'is_active' => true,
        ]);
        $alternativeAsset = $this->asset($alternativeType);
        $course = Course::create(['college_id' => $user->college_id, 'name' => 'Clinical course']);
        $scenario = Scenario::create(['course_id' => $course->id, 'name' => 'Recommended scenario', 'is_active' => true]);
        $scenario->recommendedSimulatorTypes()->attach($recommendedType->id);
        $this->actingAs($user)->post('/app/bookings', [
            ...$this->payload($room, $alternativeAsset), 'scenario_id' => $scenario->id,
        ])->assertRedirect();
        $booking = Booking::query()->sole();
        $this->assertSame($scenario->id, $booking->scenario_id);
        $this->assertSame($alternativeAsset->id, $booking->simulator_asset_id);
    }

    public function test_availability_is_scoped_operational_and_accounts_for_pending_approved_and_adjacent_times(): void
    {
        [$user, $type, $asset, $room] = $this->context();
        $approvedAsset = $this->asset($type, ['asset_name' => 'Approved asset']);
        $freeAsset = $this->asset($type, ['asset_name' => 'Free asset']);
        $this->booking($user, $asset, $room, BookingStatus::Pending);
        $this->booking($user, $approvedAsset, $this->room($user->college_id, 'Other room'), BookingStatus::Approved);
        $this->context();
        $this->asset($type, ['status' => SimulatorAssetStatus::Disabled]);
        $this->asset($type, ['status' => SimulatorAssetStatus::Maintenance]);
        $inactiveType = SimulatorType::create(['college_id' => $user->college_id, 'name' => 'Inactive type', 'is_active' => false]);
        $this->asset($inactiveType);

        $response = $this->actingAs($user)->getJson($this->availabilityUrl('2026-10-12 09:30:00', '2026-10-12 10:00:00'))
            ->assertOk()->assertJsonCount(3, 'assets');
        $this->assertEqualsCanonicalizing([
            ['id' => $asset->id, 'available' => false],
            ['id' => $approvedAsset->id, 'available' => false],
            ['id' => $freeAsset->id, 'available' => true],
        ], $response->json('assets'));
        foreach ($response->json('assets') as $entry) {
            $this->assertEqualsCanonicalizing(['id', 'available'], array_keys($entry));
        }
        foreach ([
            ['2026-10-12 11:00:00', '2026-10-12 12:00:00'],
            ['2026-10-13 09:00:00', '2026-10-13 10:00:00'],
        ] as [$start, $end]) {
            $adjacent = $this->getJson($this->availabilityUrl($start, $end))->assertOk()->assertJsonCount(3, 'assets');
            $this->assertEqualsCanonicalizing([
                ['id' => $asset->id, 'available' => true],
                ['id' => $approvedAsset->id, 'available' => true],
                ['id' => $freeAsset->id, 'available' => true],
            ], $adjacent->json('assets'));
        }
    }

    public function test_availability_requires_authenticated_valid_booking_actor_and_valid_time_range(): void
    {
        $url = $this->availabilityUrl('2026-10-12 09:00:00', '2026-10-12 10:00:00');
        $this->getJson($url)->assertUnauthorized();
        [$user] = $this->context();
        $this->actingAs($user)->getJson('/app/simulators/availability')->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at', 'ends_at']);
        $this->getJson($this->availabilityUrl('invalid', '2026-10-12 10:00:00'))
            ->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->getJson($this->availabilityUrl('2026-10-12 10:00:00', '2026-10-12 09:00:00'))
            ->assertUnprocessable()->assertJsonValidationErrors('ends_at');
        $this->getJson($this->availabilityUrl('2026-10-12 10:00:00', '2026-10-12 10:00:00'))
            ->assertUnprocessable()->assertJsonValidationErrors('ends_at');
        $unassigned = User::factory()->create(['college_id' => null, 'role' => UserRole::Lecturer->value]);
        $this->actingAs($unassigned)->getJson($url)->assertForbidden();
    }

    private function availabilityUrl(string $start, string $end): string
    {
        return '/app/simulators/availability?'.http_build_query(['starts_at' => $start, 'ends_at' => $end]);
    }

    private function context(): array
    {
        $college = College::factory()->create();
        $user = User::factory()->create(['college_id' => $college->id, 'role' => UserRole::Lecturer->value]);
        $type = SimulatorType::query()->create(['college_id' => $college->id, 'name' => 'Clinical simulator', 'is_active' => true]);

        return [$user, $type, $this->asset($type), $this->room($college->id, 'Clinical room')];
    }

    private function asset(SimulatorType $type, array $overrides = []): SimulatorAsset
    {
        return SimulatorAsset::query()->create([
            'college_id' => $type->college_id, 'simulator_type_id' => $type->id,
            'asset_name' => 'Clinical manikin', 'status' => SimulatorAssetStatus::Active,
            'purchase_price' => 200000, 'purchase_year' => 2025, 'useful_life_years' => 10,
            ...$overrides,
        ]);
    }

    private function room(int $collegeId, string $name): SimResource
    {
        return SimResource::query()->create([
            'college_id' => $collegeId, 'name' => $name, 'kind' => SimResourceKind::Room,
            'status' => SimResourceStatus::Ready, 'quantity_total' => 1, 'is_exclusive' => true,
        ]);
    }

    private function staff(int $collegeId): User
    {
        return User::factory()->create(['college_id' => $collegeId, 'role' => UserRole::Staff->value]);
    }

    private function payload(SimResource $room, ?SimulatorAsset $asset = null): array
    {
        return [
            'resources' => [['id' => $room->id, 'quantity' => 1]],
            'simulator_asset_id' => $asset?->id,
            'starts_at' => '2026-10-12 09:00:00', 'ends_at' => '2026-10-12 11:00:00',
        ];
    }

    private function booking(User $user, SimulatorAsset $asset, SimResource $room, BookingStatus $status): Booking
    {
        $booking = Booking::query()->create([
            'college_id' => $user->college_id, 'requested_by_user_id' => $user->id,
            'requester_name' => $user->name, 'simulator_asset_id' => $asset->id,
            'starts_at' => '2026-10-12 09:00:00', 'ends_at' => '2026-10-12 11:00:00', 'status' => $status,
        ]);
        $booking->resources()->attach($room->id, ['quantity' => 1]);

        return $booking;
    }
}
