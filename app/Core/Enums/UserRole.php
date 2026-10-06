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

    /**
     * @return list<AppPermission>
     */
    public function permissions(): array
    {
        $booking = [
            AppPermission::BookingView,
            AppPermission::BookingCreate,
            AppPermission::BookingCancel,
        ];

        return match ($this) {
            self::Lecturer => $booking,
            self::Staff => [
                ...$booking,
                AppPermission::BookingApprove,
                AppPermission::ResourceView,
            ],
            self::Admin => [
                ...$booking,
                AppPermission::BookingApprove,
                AppPermission::ResourceView,
                AppPermission::ResourceCreate,
                AppPermission::ResourceUpdate,
                AppPermission::ResourceDelete,
            ],
        };
    }

    public function allows(AppPermission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
