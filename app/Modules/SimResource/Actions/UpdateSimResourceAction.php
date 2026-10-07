<?php

namespace App\Modules\SimResource\Actions;

use App\Core\Enums\AppPermission;
use App\Core\Enums\UserRole;
use App\Models\User;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateSimResourceAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SimResource $resource, array $data, User $actor): SimResource
    {
        return DB::transaction(function () use ($resource, $data, $actor): SimResource {
            abort_unless($actor->canAccess(AppPermission::ResourceUpdate), 403);
            $locked = SimResource::query()->whereKey($resource->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->college_id === $actor->college_id, 403);

            $targetKind = SimResourceKind::from($data['kind']);
            if ($locked->kind !== $targetKind && $locked->bookings()->exists()) {
                throw ValidationException::withMessages(['kind' => 'เปลี่ยนประเภททรัพยากรที่มีประวัติการจองไม่ได้']);
            }

            if ($targetKind === SimResourceKind::Room) {
                $data['quantity_total'] = 1;
                $data['is_exclusive'] = true;
                if ($locked->kind !== SimResourceKind::Room && empty($data['capacity'])) {
                    throw ValidationException::withMessages(['capacity' => 'กรุณาระบุความจุของห้อง']);
                }

                if (! empty($data['responsible_staff_user_id'])) {
                    $responsibleStaff = User::query()->whereKey($data['responsible_staff_user_id'])
                        ->where('college_id', $actor->college_id)
                        ->where('role', UserRole::Staff->value)->exists();
                    abort_unless($responsibleStaff, 403);
                }
            } elseif ($locked->kind === SimResourceKind::Room) {
                $data['building'] = null;
                $data['floor'] = null;
                $data['capacity'] = null;
                $data['responsible_staff_user_id'] = null;
            }

            $locked->update($data);

            return $locked->refresh();
        }, attempts: 3);
    }
}
