<?php

namespace App\Core\Enums;

enum UserRole: string
{
    case Lecturer = 'lecturer';
    case Staff = 'staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Lecturer => 'อาจารย์ / ผู้สอน',
            self::Staff => 'เจ้าหน้าที่',
            self::Admin => 'ผู้ดูแลระบบ',
        };
    }
}
