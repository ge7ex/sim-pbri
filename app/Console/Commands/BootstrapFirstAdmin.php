<?php

namespace App\Console\Commands;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class BootstrapFirstAdmin extends Command
{
    protected $signature = 'sim:bootstrap-admin';

    protected $description = 'Create the first SIM PBRI College Admin interactively';

    public function handle(): int
    {
        if (User::query()->where('role', UserRole::Admin->value)->exists()) {
            $this->error('An Admin account already exists. Use the controlled admin recovery command if needed.');

            return self::FAILURE;
        }

        $colleges = College::query()->orderBy('name')->get(['id', 'name']);
        $college = null;
        $collegeName = null;

        if ($colleges->isEmpty()) {
            $collegeName = trim((string) $this->ask('College name'));
        } elseif ($colleges->count() === 1) {
            $college = $colleges->first();
            $this->line('Using existing College: '.$college->name);
        } else {
            $choices = $colleges->mapWithKeys(fn (College $item): array => [$item->id => '#'.$item->id.' · '.$item->name])->all();
            $selected = $this->choice('Select the Admin College', array_values($choices));
            $college = $colleges->firstWhere('id', (int) explode(' · ', substr($selected, 1), 2)[0]);
        }

        $name = trim((string) $this->ask('Admin name'));
        $email = trim((string) $this->ask('Admin email'));
        $password = (string) $this->secret('Initial password (at least 12 characters, mixed case and a number)');
        $confirmation = (string) $this->secret('Confirm initial password');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'same:password_confirmation', Password::min(12)->letters()->mixedCase()->numbers()],
            'password_confirmation' => ['required', 'string'],
        ];
        if ($collegeName !== null) {
            $rules['college_name'] = ['required', 'string', 'max:255', Rule::unique('colleges', 'name')];
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
            'college_name' => $collegeName,
        ], $rules);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        DB::transaction(function () use ($college, $collegeName, $name, $email, $password): void {
            if (User::query()->where('role', UserRole::Admin->value)->exists()) {
                throw new \RuntimeException('An Admin account was created while bootstrap was running.');
            }

            $college = $college ?? College::query()->create(['name' => $collegeName]);
            $admin = User::query()->forceCreate([
                'college_id' => $college->id,
                'role' => UserRole::Admin->value,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_active' => true,
                'must_change_password' => false,
            ]);

            DB::table('user_access_events')->insert([
                'actor_user_id' => $admin->id,
                'subject_user_id' => $admin->id,
                'event' => 'bootstrap_created',
                'details' => json_encode(['role' => UserRole::Admin->value], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        });

        $this->info('First Admin created. No password was printed or sent.');

        return self::SUCCESS;
    }
}
