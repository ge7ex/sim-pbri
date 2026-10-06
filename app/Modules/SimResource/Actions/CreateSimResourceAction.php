<?php

namespace App\Modules\SimResource\Actions;

use App\Models\User;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Models\SimResource;

final class CreateSimResourceAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data, User $actor): SimResource
    {
        abort_if($actor->college_id === null, 403);

        if ($data['kind'] === SimResourceKind::Room->value) {
            $data['quantity_total'] = 1;
            $data['is_exclusive'] = true;
        }

        return SimResource::query()->create([
            ...$data,
            'college_id' => $actor->college_id,
        ]);
    }
}
