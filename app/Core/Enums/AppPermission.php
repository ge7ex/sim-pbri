<?php

namespace App\Core\Enums;

enum AppPermission: string
{
    case BookingView = 'booking.view';
    case BookingCreate = 'booking.create';
    case BookingCancel = 'booking.cancel';
    case BookingApprove = 'booking.approve';

    case ResourceView = 'sim-resource.view';
    case ResourceCreate = 'sim-resource.create';
    case ResourceUpdate = 'sim-resource.update';
    case ResourceDelete = 'sim-resource.delete';

    case SimulatorView = 'simulator.view';
    case SimulatorCreate = 'simulator.create';
    case SimulatorUpdate = 'simulator.update';
    case SimulatorMaintenance = 'simulator.maintenance';

    case ScenarioView = 'scenario.view';
    case ScenarioCreate = 'scenario.create';
    case ScenarioUpdate = 'scenario.update';
}
