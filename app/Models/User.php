<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'tenant_id', 'role', 'is_staff', 'passkey_credentials'])]
#[Hidden(['password', 'remember_token', 'passkey_credentials'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ATENDENTE = 'atendente';
    public const ROLE_CLIENTE = 'cliente';

    public const ROLE_LABELS = [
        self::ROLE_SUPERADMIN => 'Super Admin',
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_ATENDENTE => 'Atendente',
        self::ROLE_CLIENTE => 'Cliente',
    ];

    public const ROLE_COLORS = [
        self::ROLE_SUPERADMIN => 'bg-red-500/10 text-red-400 border border-red-500/20',
        self::ROLE_ADMIN => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
        self::ROLE_ATENDENTE => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
        self::ROLE_CLIENTE => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'passkey_credentials' => 'array',
            'is_staff' => 'boolean',
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

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'user_favorites')
            ->withTimestamps();
    }

    public function favorites()
    {
        return $this->hasMany(UserFavorite::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPERADMIN]);
    }

    public function isAtendente(): bool
    {
        return $this->role === self::ROLE_ATENDENTE;
    }

    public function isCliente(): bool
    {
        return $this->role === self::ROLE_CLIENTE;
    }

    public function isStaff(): bool
    {
        return $this->is_staff || $this->isAdmin() || $this->isAtendente();
    }

    public function scopeStaff($query)
    {
        return $query->where('is_staff', true)
            ->orWhereIn('role', [self::ROLE_ADMIN, self::ROLE_SUPERADMIN]);
    }

    public function scopeClients($query)
    {
        return $query->where('role', self::ROLE_CLIENTE);
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    public function roleColor(): string
    {
        return self::ROLE_COLORS[$this->role] ?? 'bg-neutral-500/10 text-neutral-400 border border-neutral-500/20';
    }
}
