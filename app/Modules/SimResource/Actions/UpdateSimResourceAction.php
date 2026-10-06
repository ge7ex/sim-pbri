<?php

namespace App\Modules\SimResource\Actions;

use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Models\SimResource;

final class UpdateSimResourceAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(SimResource $resource, array $data): SimResource
    {
        if ($data['kind'] === SimResourceKind::Room->value) {
            $data['quantity_total'] = 1;
            $data['is_exclusive'] = true;
        }

        $resource->update($data);

        return $resource->refresh();
    }
}
