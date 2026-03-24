<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @method mixed getJWTIdentifier()
 * @method array getJWTCustomClaims()
 */
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, SoftDeletes, HasRoles;

    protected $fillable = [
        'name', 'email', 'password',
        'employee_code', 'phone', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // JWT interface methods
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'roles' => $this->getRoleNames(),
            'stores' => $this->stores()->pluck('stores.id'),
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'user_store_assignments')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryStore(): ?Store
    {
        return $this->stores()->wherePivot('is_primary', true)->first();
    }

    public function canAccessStore(string $storeId): bool
    {
        return $this->stores()->where('stores.id', $storeId)->exists()
            || $this->hasRole('SUPER_ADMIN');
    }
}
