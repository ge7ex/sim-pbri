<?php

namespace App\Modules\Scenario\Actions;

use App\Modules\Scenario\Models\Scenario;

final class SyncScenarioEquipmentTemplateAction
{
    /**
     * @param list<array{id: int, quantity: int}> $equipment
     */
    public function execute(Scenario $scenario, array $equipment): Scenario
    {
        $sync = [];

        foreach ($equipment as $item) {
            $sync[$item['id']] = [
                'quantity' => $item['quantity'],
            ];
        }

        $scenario->recommendedResources()->sync($sync);

        return $scenario->refresh()->load('recommendedResources');
    }
}
