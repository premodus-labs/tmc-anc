<?php

namespace App\Models;

use Database\Factories\AppUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AppUser extends Authenticatable
{
    /** @use HasFactory<AppUserFactory> */
    use HasApiTokens, HasFactory;

    protected $table = 'app_user';

    const UPDATED_AT = null;

    protected $authPasswordName = 'password_hash';

    // app_user has no remember_token column
    protected $rememberTokenName = '';

    protected $fillable = [
        'staff_id',
        'username',
        'password_hash',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role', 'user_id');
    }

    /**
     * Permission codes granted through the user's roles, e.g. "patient.create".
     *
     * @return list<string>
     */
    public function permissionCodes(): array
    {
        return once(fn () => Permission::query()
            ->whereHas('roles.users', fn ($q) => $q->whereKey($this->getKey()))
            ->pluck('code')
            ->all());
    }

    public function hasPermission(string $code): bool
    {
        return in_array($code, $this->permissionCodes(), true);
    }
}
