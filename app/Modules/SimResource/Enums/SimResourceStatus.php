<?php

namespace App\Modules\SimResource\Enums;

enum SimResourceStatus: string
{
    case Ready = 'ready';
    case Pending = 'pending';
    case Maintenance = 'maintenance';
    case Disabled = 'disabled';

    public function isBookable(): bool
    {
        return $this === self::Ready;
    }

    public function label(): string
    {
        return match ($this) {
            self::Ready => 'พร้อมใช้งาน',
            self::Pending => 'รอตรวจสอบ',
            self::Maintenance => 'ปิดปรับปรุง',
            self::Disabled => 'ปิดการใช้งาน',
        };
    }
}
