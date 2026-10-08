<?php

namespace App\Modules\SimResource\Actions;

use App\Models\User;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\SimResource\Services\RoomImageStorage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class DeleteSimResourceAction
{
    public function __construct(private RoomImageStorage $images) {}

    public function execute(SimResource $resource, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $resource);
        try {
            DB::transaction(function () use ($resource, $actor): void {
                $locked = SimResource::query()->whereKey($resource->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($actor)->authorize('delete', $locked);
                if ($locked->bookings()->exists()
                    || DB::table('sim_resource_image_changes')->where('sim_resource_id', $locked->id)->exists()
                    || DB::table('scenario_resource_templates')->where('sim_resource_id', $locked->id)->exists()) {
                    throw $this->protectedHistory();
                }
                $path = $locked->image_path;
                $collegeId = $locked->college_id;
                if ($path !== null && ! $this->images->validPath($path, $collegeId)) {
                    throw ValidationException::withMessages(['resource' => 'ข้อมูลรูปของทรัพยากรไม่ถูกต้อง กรุณาแจ้งผู้ดูแลระบบก่อนลบ']);
                }
                $locked->delete();
                if ($path !== null) {
                    DB::afterCommit(fn () => $this->images->delete($path, $collegeId));
                }
            }, attempts: 3);
        } catch (QueryException $exception) {
            // Restrictive foreign keys remain the final guard if a dependency appears concurrently.
            if (in_array($exception->getCode(), ['23000', '23503'], true)) {
                throw $this->protectedHistory();
            }
            throw $exception;
        }
    }

    private function protectedHistory(): ValidationException
    {
        return ValidationException::withMessages(['resource' => 'ไม่สามารถลบทรัพยากรนี้ได้ เนื่องจากมีประวัติการใช้งาน การจอง รูปห้อง หรือข้อมูลชุดแนะนำที่ต้องเก็บรักษา']);
    }
}
