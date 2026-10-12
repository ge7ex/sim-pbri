<?php

namespace Tests\Feature\Access;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_user_in_own_college_and_receives_uncached_temporary_password_once(): void
    {
        [$admin, $college] = $this->admin();
        $this->assertTrue($admin->is_active);
        $otherCollege = College::factory()->create();

        $response = $this->actingAs($admin)->postJson('/app/users', [
            'name' => 'New Lecturer',
            'email' => 'new-lecturer@example.test',
            'role' => UserRole::Lecturer->value,
            'college_id' => $otherCollege->id,
        ]);

        $response->assertCreated()->assertHeader('Cache-Control', 'no-store, private');
        $temporaryPassword = $response->json('temporary_password');
        $user = User::query()->where('email', 'new-lecturer@example.test')->firstOrFail();
        $this->assertSame($college->id, $user->college_id);
        $this->assertSame(UserRole::Lecturer->value, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertIsString($temporaryPassword);
        $this->assertGreaterThanOrEqual(12, strlen($temporaryPassword));
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->assertDatabaseHas('user_access_events', [
            'actor_user_id' => $admin->id,
            'subject_user_id' => $user->id,
            'event' => 'created',
        ]);
        $this->assertDatabaseMissing('user_access_events', ['details' => $temporaryPassword]);
        $response->assertSessionMissing('temporary_password');
    }

    public function test_non_admin_roles_cannot_list_or_manage_users(): void
    {
        foreach ([UserRole::Staff, UserRole::Lecturer] as $role) {
            [$user] = $this->userWithRole($role);
            $this->actingAs($user)->get('/app/users')->assertForbidden();
            $this->actingAs($user)->postJson('/app/users', [
                'name' => 'Forbidden',
                'email' => 'forbidden-'.$role->value.'@example.test',
                'role' => UserRole::Admin->value,
            ])->assertForbidden();
        }
    }

    public function test_user_management_page_shares_the_current_post_login_csrf_token(): void
    {
        [$admin] = $this->admin();

        $this->get('/login')->assertOk();
        $csrfTokenBeforeLogin = session()->token();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/app');

        $csrfTokenAfterLogin = session()->token();
        $this->assertNotSame($csrfTokenBeforeLogin, $csrfTokenAfterLogin);

        $this->get('/app/users')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Modules/Access/Pages/Users/Index')
            ->where('csrfToken', $csrfTokenAfterLogin));
    }

    public function test_duplicate_email_error_is_localized_for_the_admin_form(): void
    {
        [$admin, $college] = $this->admin();
        User::factory()->create([
            'college_id' => $college->id,
            'email' => 'existing-user@example.test',
        ]);

        $this->actingAs($admin)->postJson('/app/users', [
            'name' => 'Duplicate User',
            'email' => 'existing-user@example.test',
            'role' => UserRole::Lecturer->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'อีเมลนี้มีบัญชีอยู่แล้ว');
    }

    public function test_admin_user_list_and_mutations_are_scoped_to_own_college(): void
    {
        [$admin, $college] = $this->admin();
        $foreign = User::factory()->create([
            'college_id' => College::factory()->create()->id,
            'role' => UserRole::Lecturer->value,
            'name' => 'Foreign Private Person',
        ]);

        $this->actingAs($admin)->get('/app/users')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Modules/Access/Pages/Users/Index')
            ->where('collegeName', $college->name)
            ->has('users.data', 1)
            ->where('users.data.0.id', $admin->id)
            ->missing('users.data.1'));

        $this->actingAs($admin)->get('/app/users?q=Foreign%20Private')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('users.total', 0)
            ->where('filters.q', 'Foreign Private'));

        $this->actingAs($admin)->put('/app/users/'.$foreign->id, [
            'name' => 'Changed',
            'role' => UserRole::Admin->value,
            'is_active' => false,
        ])->assertNotFound();

        $this->assertSame('Foreign Private Person', $foreign->fresh()->name);
        $this->assertNotSame($college->id, $foreign->fresh()->college_id);
    }

    public function test_admin_can_update_other_account_and_records_non_secret_audit_details(): void
    {
        [$admin, $college] = $this->admin();
        $user = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Lecturer->value,
        ]);

        $this->actingAs($admin)->put('/app/users/'.$user->id, [
            'name' => 'Updated Staff',
            'role' => UserRole::Staff->value,
            'is_active' => false,
            'college_id' => College::factory()->create()->id,
        ])->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Updated Staff', $user->name);
        $this->assertSame(UserRole::Staff->value, $user->role);
        $this->assertFalse($user->is_active);
        $this->assertSame($college->id, $user->college_id);
        $event = DB::table('user_access_events')->where('subject_user_id', $user->id)->where('event', 'updated')->first();
        $this->assertNotNull($event);
        $this->assertStringContainsString(UserRole::Lecturer->value, $event->details);
        $this->assertStringContainsString(UserRole::Staff->value, $event->details);
        $this->assertStringNotContainsString($user->email, $event->details);
    }

    public function test_admin_cannot_demote_or_deactivate_self_or_last_active_admin(): void
    {
        [$admin] = $this->admin();

        $this->actingAs($admin)->put('/app/users/'.$admin->id, [
            'name' => $admin->name,
            'role' => UserRole::Staff->value,
            'is_active' => true,
        ])->assertSessionHasErrors('role');

        $this->actingAs($admin)->put('/app/users/'.$admin->id, [
            'name' => $admin->name,
            'role' => UserRole::Admin->value,
            'is_active' => false,
        ])->assertSessionHasErrors('role');

        $otherAdmin = User::factory()->create([
            'college_id' => $admin->college_id,
            'role' => UserRole::Admin->value,
        ]);
        $this->actingAs($admin)->put('/app/users/'.$otherAdmin->id, [
            'name' => $otherAdmin->name,
            'role' => UserRole::Admin->value,
            'is_active' => false,
        ])->assertSessionHas('success');

        $this->assertFalse($otherAdmin->fresh()->is_active);
        $this->assertSame(1, User::query()->where('college_id', $admin->college_id)->where('role', UserRole::Admin->value)->where('is_active', true)->count());
    }

    public function test_inactive_account_cannot_sign_in_and_existing_session_is_logged_out(): void
    {
        [$user] = $this->userWithRole(UserRole::Staff);
        $user->forceFill(['is_active' => false])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $user->forceFill(['is_active' => true])->save();
        $this->actingAs($user)->get('/app')->assertOk();
        $user->forceFill(['is_active' => false])->save();

        $this->get('/app')->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_temporary_password_requires_change_and_user_can_set_a_strong_password(): void
    {
        [$user, $college] = $this->userWithRole(UserRole::Lecturer);
        $user->forceFill(['password' => 'TemporaryPass123', 'must_change_password' => true])->save();

        $this->actingAs($user)->get('/app')->assertRedirect('/app/account/password');
        $this->actingAs($user)->get('/app/account/password')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Modules/Access/Pages/Account/Password')
            ->where('auth.user.must_change_password', true));

        $this->actingAs($user)->put('/app/account/password', [
            'current_password' => 'TemporaryPass123',
            'password' => 'NewStrongPassword123',
            'password_confirmation' => 'NewStrongPassword123',
        ])->assertRedirect('/app');

        $this->assertTrue(Hash::check('NewStrongPassword123', $user->fresh()->password));
        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertDatabaseHas('user_access_events', [
            'actor_user_id' => $user->id,
            'subject_user_id' => $user->id,
            'event' => 'password_changed',
        ]);
    }

    public function test_admin_password_reset_reauthenticates_and_returns_a_one_time_secret(): void
    {
        [$admin, $college] = $this->admin();
        $target = User::factory()->create([
            'college_id' => $college->id,
            'role' => UserRole::Lecturer->value,
            'password' => 'PreviousPassword123',
        ]);

        $this->actingAs($admin)->postJson('/app/users/'.$target->id.'/password', [
            'current_password' => 'wrong',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertTrue(Hash::check('PreviousPassword123', $target->fresh()->password));

        $response = $this->actingAs($admin)->postJson('/app/users/'.$target->id.'/password', [
            'current_password' => 'password',
        ])->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $temporaryPassword = $response->json('temporary_password');
        $target->refresh();
        $this->assertTrue($target->must_change_password);
        $this->assertTrue(Hash::check($temporaryPassword, $target->password));
        $response->assertSessionMissing('temporary_password');
        $this->assertDatabaseHas('user_access_events', [
            'actor_user_id' => $admin->id,
            'subject_user_id' => $target->id,
            'event' => 'password_reset',
        ]);
    }

    public function test_first_admin_can_be_bootstrapped_interactively_without_printing_password(): void
    {
        $this->artisan('sim:bootstrap-admin')
            ->expectsQuestion('College name', 'QA Bootstrap College')
            ->expectsQuestion('Admin name', 'First Admin')
            ->expectsQuestion('Admin email', 'first-admin@example.test')
            ->expectsQuestion('Initial password (at least 12 characters, mixed case and a number)', 'BootstrapPassword123')
            ->expectsQuestion('Confirm initial password', 'BootstrapPassword123')
            ->expectsOutputToContain('First Admin created. No password was printed or sent.')
            ->doesntExpectOutputToContain('BootstrapPassword123')
            ->assertExitCode(0);

        $admin = User::query()->where('email', 'first-admin@example.test')->firstOrFail();
        $this->assertSame(UserRole::Admin->value, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertFalse($admin->must_change_password);
        $this->assertSame('QA Bootstrap College', $admin->college->name);
        $this->assertDatabaseHas('user_access_events', ['subject_user_id' => $admin->id, 'event' => 'bootstrap_created']);
    }

    public function test_bootstrap_refuses_to_create_a_second_first_admin(): void
    {
        [$admin] = $this->admin();

        $this->artisan('sim:bootstrap-admin')
            ->expectsOutputToContain('An Admin account already exists.')
            ->assertExitCode(1);

        $this->assertSame(1, User::query()->where('role', UserRole::Admin->value)->count());
    }

    public function test_console_recovery_resets_only_existing_active_admin_and_forces_change(): void
    {
        [$admin] = $this->admin();

        $this->artisan('sim:recover-admin-password', ['email' => $admin->email])
            ->expectsQuestion('Temporary password (at least 12 characters, mixed case and a number)', 'RecoveredAdmin123')
            ->expectsQuestion('Confirm temporary password', 'RecoveredAdmin123')
            ->expectsOutputToContain('the Admin must change it after sign-in')
            ->doesntExpectOutputToContain('RecoveredAdmin123')
            ->assertExitCode(0);

        $admin->refresh();
        $this->assertTrue(Hash::check('RecoveredAdmin123', $admin->password));
        $this->assertTrue($admin->must_change_password);
        $this->assertDatabaseHas('user_access_events', ['subject_user_id' => $admin->id, 'event' => 'admin_password_recovered']);
    }

    private function admin(): array
    {
        return $this->userWithRole(UserRole::Admin);
    }

    private function userWithRole(UserRole $role): array
    {
        $college = College::factory()->create();
        $user = User::factory()->create([
            'college_id' => $college->id,
            'role' => $role->value,
        ]);

        return [$user, $college];
    }
}
