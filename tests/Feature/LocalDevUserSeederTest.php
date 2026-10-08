<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\User;
use Database\Seeders\LocalDevUserSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

final class LocalDevUserSeederTest extends TestCase
{
    use RefreshDatabase;

    private array $previousEnvironment;

    private string $password;

    protected function setUp(): void
    {
        parent::setUp();
        $this->password = bin2hex(random_bytes(16));
        foreach (['SIM_PBRI_DEV_PASSWORD', 'SIM_PBRI_DEV_COLLEGE_ID'] as $key) {
            $this->previousEnvironment[$key] = getenv($key);
            putenv($key);
        }
        putenv('SIM_PBRI_DEV_PASSWORD='.$this->password);
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnvironment as $key => $value) {
            putenv($value === false ? $key : $key.'='.$value);
        }
        parent::tearDown();
    }

    public function test_idempotent_seeding_uses_hashed_cast_and_preserves_existing_data(): void
    {
        $college = College::factory()->create();
        $unrelated = User::factory()->create(['college_id' => $college->id]);
        $admin = User::factory()->create(['email' => 'admin@sim-pbri.local', 'college_id' => $college->id]);
        $this->seed(LocalDevUserSeeder::class);
        $ids = User::where('email', 'like', '%@sim-pbri.local')->orderBy('email')->pluck('id')->all();
        $this->seed(LocalDevUserSeeder::class);
        $this->assertSame($ids, User::where('email', 'like', '%@sim-pbri.local')->orderBy('email')->pluck('id')->all());
        foreach (['admin', 'staff', 'lecturer'] as $role) {
            $user = User::where('email', $role.'@sim-pbri.local')->sole();
            $this->assertSame($role, $user->role);
            $this->assertSame($college->id, $user->college_id);
            $this->assertSame('SIM PBRI '.ucfirst($role), $user->name);
            $this->assertTrue(Hash::check($this->password, $user->password));
            $this->assertNotSame($this->password, $user->password);
        }
        $this->assertSame($admin->id, User::where('email', 'admin@sim-pbri.local')->sole()->id);
        $this->assertSame($unrelated->email, $unrelated->refresh()->email);
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('colleges', 1);
        $this->assertDatabaseCount('sim_resources', 0);
    }

    public function test_production_guard_fires_before_any_database_write(): void
    {
        app()->instance('env', 'production');
        try {
            app(LocalDevUserSeeder::class)->run();
            $this->fail('Expected production guard');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('local/testing', $exception->getMessage());
            $this->assertDatabaseCount('users', 0);
        } finally {
            app()->instance('env', 'testing');
        }
    }

    public function test_missing_or_short_password_refuses_to_seed(): void
    {
        College::factory()->create();
        foreach ([null, 'short'] as $password) {
            putenv($password === null ? 'SIM_PBRI_DEV_PASSWORD' : 'SIM_PBRI_DEV_PASSWORD='.$password);
            try {
                $this->seed(LocalDevUserSeeder::class);
                $this->fail('Expected password guard');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('SIM_PBRI_DEV_PASSWORD', $exception->getMessage());
                $this->assertDatabaseCount('users', 0);
            }
        }
    }

    public function test_zero_or_multiple_colleges_requires_selection_without_creating_data(): void
    {
        foreach ([0, 2] as $count) {
            if ($count) {
                College::factory()->count($count)->create();
            }
            try {
                $this->seed(LocalDevUserSeeder::class);
                $this->fail('Expected College selection guard');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('explicitly', $exception->getMessage());
                $this->assertDatabaseCount('users', 0);
                $this->assertDatabaseCount('colleges', $count);
            }
        }
    }

    public function test_explicit_existing_college_resolves_ambiguity(): void
    {
        College::factory()->create();
        $selected = College::factory()->create();
        putenv('SIM_PBRI_DEV_COLLEGE_ID='.$selected->id);
        $this->seed(LocalDevUserSeeder::class);
        $this->assertSame(3, User::where('college_id', $selected->id)->count());
        $this->assertDatabaseCount('colleges', 2);
    }

    public function test_nonexistent_explicit_college_never_creates_one(): void
    {
        College::factory()->create();
        putenv('SIM_PBRI_DEV_COLLEGE_ID=999999');
        try {
            app(LocalDevUserSeeder::class)->run();
            $this->fail('Expected missing College guard');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('colleges', 1);
            $this->assertDatabaseCount('users', 0);
        }
    }

    public function test_invalid_college_selection_refuses_to_write(): void
    {
        College::factory()->create();
        putenv('SIM_PBRI_DEV_COLLEGE_ID=invalid');
        $this->expectException(RuntimeException::class);
        $this->seed(LocalDevUserSeeder::class);
    }
}
