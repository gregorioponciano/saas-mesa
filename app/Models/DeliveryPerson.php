<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[ScopedBy([TenantScope::class])]
class DeliveryPerson extends Authenticatable
{
    use HasApiTokens;

    protected string $guard_name = 'delivery';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'status',
        'api_token',
        'password',
        'cpf',
        'cnh',
        'vehicle_plate',
        'vehicle_model',
        'avatar_path',
        'invite_token',
        'invite_expires_at',
        'invited_at',
        'activated_at',
        'is_online',
    ];

    protected $hidden = [
        'password',
        'api_token',
        'invite_token',
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'string',
            'is_online' => 'boolean',
            'invite_expires_at' => 'datetime',
            'invited_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function earnings()
    {
        return $this->hasMany(DeliveryEarning::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActivated(): bool
    {
        return $this->activated_at !== null;
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function hasValidInvite(): bool
    {
        return $this->invite_token !== null
            && $this->invite_expires_at !== null
            && $this->invite_expires_at->isFuture();
    }

    public static function generateInviteToken(): string
    {
        return Str::random(60);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
