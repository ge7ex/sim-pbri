<?php

namespace App\Models;

use App\Core\Enums\AppPermission;
use App\Core\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'college_id',
        'role',
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return BelongsTo<College, $this>
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function accessRole(): ?UserRole
    {
        return UserRole::tryFrom((string) $this->role);
    }

    public function hasAccessProfile(): bool
    {
        return $this->college_id !== null
            && $this->accessRole() !== null;
    }

    public function canAccess(AppPermission $permission): bool
    {
        return $this->hasAccessProfile()
            && $this->accessRole()?->allows($permission) === true;
    }

    /**
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return array_map(
            static fn (AppPermission $permission): string => $permission->value,
            $this->accessRole()?->permissions() ?? [],
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
