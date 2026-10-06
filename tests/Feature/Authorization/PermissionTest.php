<?php

namespace Tests\Feature\Authorization;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_use_booking_workspace_but_cannot_review_or_manage_resources(): void
    {
        $user = $this->userWithRole(UserRole::Lecturer);

        $this->actingAs($user)->get('/app/bookings')->assertOk();
        $this->actingAs($user)->get('/app/bookings/create')->assertOk();
        $this->actingAs($user)->get('/app/calendar')->assertOk();

        $this->actingAs($user)->get('/app/review')->assertForbidden();
        $this->actingAs($user)->get('/app/resources')->assertForbidden();
    }

    public function test_staff_can_review_bookings_but_cannot_manage_resources(): void
    {
        $user = $this->userWithRole(UserRole::Staff);

        $this->actingAs($user)->get('/app/review')->assertOk();
        $this->actingAs($user)->get('/app/resources')->assertForbidden();
    }

    public function test_admin_can_review_bookings_and_manage_resources(): void
    {
        $user = $this->userWithRole(UserRole::Admin);

        $this->actingAs($user)->get('/app/review')->assertOk();
        $this->actingAs($user)->get('/app/resources')->assertOk();
    }

    public function test_dashboard_shares_only_permissions_granted_to_role(): void
    {
        $user = $this->userWithRole(UserRole::Lecturer);

        $this
            ->actingAs($user)
            ->get('/app')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('auth.permissions', [
                    'booking.view',
                    'booking.create',
                    'booking.cancel',
                ]));
    }

    private function userWithRole(UserRole $role): User
    {
        $college = College::factory()->create();

        return User::factory()->create([
            'college_id' => $college->id,
            'role' => $role->value,
        ]);
    }
}
