<?php

namespace App\Modules\SimResource\Services;

use App\Core\Enums\AppPermission;
use App\Models\User;
use App\Modules\SimResource\Actions\CreateSimResourceAction;
use App\Modules\SimResource\Actions\UpdateSimResourceAction;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class SaveResourceWithImage
{
    public function __construct(private RoomImageStorage $images, private CreateSimResourceAction $create, private UpdateSimResourceAction $update) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, User $actor, ?SimResource $resource = null): SimResource
    {
        abort_unless($actor->college_id !== null && $actor->canAccess($resource ? AppPermission::ResourceUpdate : AppPermission::ResourceCreate), 403);
        if ($resource) {
            abort_unless($actor->can('update', $resource), 403);
        }
        $file = $data['image'] ?? null;
        $remove = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image'], $data['image_path']);
        abort_if($file !== null && (! $file instanceof UploadedFile || $data['kind'] !== 'room' || $remove), 422);
        $newPath = $file ? $this->images->store($file, $actor->college_id) : null;
        try {
            return DB::transaction(function () use ($data, $actor, $resource, $remove, $newPath): SimResource {
                $locked = $resource ? SimResource::whereKey($resource->id)->lockForUpdate()->firstOrFail() : null;
                if ($locked) {
                    abort_unless($actor->can('update', $locked), 403);
                }
                $oldPath = $locked?->image_path;
                if ($newPath !== null || $remove || $data['kind'] !== 'room') {
                    $data['image_path'] = $newPath;
                }
                $saved = $locked ? $this->update->execute($locked, $data, $actor) : $this->create->execute($data, $actor);
                if ($oldPath !== $saved->image_path) {
                    DB::table('sim_resource_image_changes')->insert([
                        'sim_resource_id' => $saved->id, 'actor_user_id' => $actor->id,
                        'action' => $saved->image_path === null ? 'removed' : ($oldPath === null ? 'uploaded' : 'replaced'),
                        'created_at' => now(),
                    ]);
                    if ($oldPath) {
                        DB::afterCommit(fn () => $this->images->delete($oldPath, $actor->college_id));
                    }
                }

                return $saved;
            }, attempts: 3);
        } catch (\Throwable $exception) {
            $this->images->delete($newPath, $actor->college_id);
            throw $exception;
        }
    }
}
