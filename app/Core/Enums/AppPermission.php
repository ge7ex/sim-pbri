<?php

namespace App\Core\Enums;

enum AppPermission: string
{
    case BookingView = 'booking.view';
    case BookingCreate = 'booking.create';
    case BookingCancel = 'booking.cancel';
    case BookingApprove = 'booking.approve';
    case ResourceView = 'sim-resource.view';
    case ResourceManage = 'sim-resource.manage';
}
