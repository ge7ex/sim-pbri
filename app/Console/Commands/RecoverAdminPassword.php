<?php

namespace App\Console\Commands;

use App\Core\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class RecoverAdminPassword extends Command
{
    protected $signature = 'sim:recover-admin-password {email}';

    protected $description = 'Set a one-time password for an existing active Admin from the trusted console';

    public function handle(): int
    {
        $admin = User::query()
            ->where('email', $this->argument('email'))
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->first();

        if ($admin === null) {
            $this->error('No active Admin account matched that email.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Temporary password (at least 12 characters, mixed case and a number)');
        $confirmation = (string) $this->secret('Confirm temporary password');
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'password' => ['required', 'same:password_confirmation', Password::min(12)->letters()->mixedCase()->numbers()],
            'password_confirmation' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        DB::transaction(function () use ($admin, $password): void {
            $admin->forceFill([
                'password' => $password,
                'must_change_password' => true,
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('user_access_events')->insert([
                'actor_user_id' => $admin->id,
                'subject_user_id' => $admin->id,
                'event' => 'admin_password_recovered',
                'details' => null,
                'created_at' => now(),
            ]);
        });

        $this->info('Temporary password saved. Deliver it through an approved private channel; the Admin must change it after sign-in.');

        return self::SUCCESS;
    }
}
