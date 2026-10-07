<?php

namespace App\Modules\Simulator\Enums;

enum SimulatorAssetStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case Maintenance = 'maintenance';
}
