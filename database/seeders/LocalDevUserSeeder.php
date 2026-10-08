<?php

namespace Database\Seeders;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LocalDevUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('LocalDevUserSeeder is allowed only in local/testing.');
        }
        $password = getenv('SIM_PBRI_DEV_PASSWORD');
        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set SIM_PBRI_DEV_PASSWORD (at least 12 characters) for this process before seeding.');
        }
        $collegeId = getenv('SIM_PBRI_DEV_COLLEGE_ID');
        if ($collegeId !== false && $collegeId !== '') {
            if (! ctype_digit($collegeId) || (int) $collegeId < 1) {
                throw new RuntimeException('Invalid SIM_PBRI_DEV_COLLEGE_ID.');
            }
            $college = College::findOrFail((int) $collegeId);
        } else {
            if (College::count() !== 1) {
                throw new RuntimeException('Select an existing College explicitly with SIM_PBRI_DEV_COLLEGE_ID; no College will be created.');
            }
            $college = College::sole();
        }
        DB::transaction(function () use ($college, $password): void {
            foreach (UserRole::cases() as $role) {
                User::updateOrCreate(['email' => $role->value.'@sim-pbri.local'], [
                    'name' => 'SIM PBRI '.ucfirst($role->value), 'role' => $role->value,
                    'college_id' => $college->id, 'password' => $password,
                ]);
            }
        });
        $this->command?->info('Local development users saved for College #'.$college->id.'.');
    }
}
