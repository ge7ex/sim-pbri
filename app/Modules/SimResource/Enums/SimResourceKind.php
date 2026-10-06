<?php

namespace App\Modules\SimResource\Enums;

enum SimResourceKind: string
{
    case Room = 'room';
    case Equipment = 'equipment';

    public function label(): string
    {
        return match ($this) {
            self::Room => 'ห้องปฏิบัติการ',
            self::Equipment => 'อุปกรณ์เสริม',
        };
    }
}
