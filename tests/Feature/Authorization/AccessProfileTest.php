<?php

namespace Tests\Feature\Authorization;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AccessProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_authenticated_app(): void
    {
        $this->get('/app')->assertRedirect('/login');
    }

    public function test_authenticated_user_without_access_profile_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/app')
            ->assertForbidden();
    }

    public function test_unknown_role_is_forbidden(): void
    {
        $college = College::factory()->create();
        $user = User::factory()->create([
            'college_id' => $college->id,
            'role' => 'student',
        ]);

        $this
            ->actingAs($user)
            ->get('/app')
            ->assertForbidden();
    }

    /**
     * @dataProvider authorizedRoleProvider
     */
    public function test_authorized_personnel_with_college_can_access_app(UserRole $role): void
    {
        $college = College::factory()->create();
        $user = User::factory()->create([
            'college_id' => $college->id,
            'role' => $role->value,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/app');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('auth.user.role', $role->value)
                ->where('auth.user.role_label', $role->label())
                ->where('auth.user.college.id', $college->id)
                ->where('auth.user.college.name', $college->name)
                ->where('auth.user.has_access_profile', true));
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function authorizedRoleProvider(): array
    {
        return [
            'lecturer' => [UserRole::Lecturer],
            'staff' => [UserRole::Staff],
            'admin' => [UserRole::Admin],
        ];
    }
}
