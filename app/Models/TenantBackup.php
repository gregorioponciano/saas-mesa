<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantBackup extends Model
{
    protected $fillable = [
        'tenant_id',
        'uuid',
        'filename',
        'disk',
        'size_bytes',
        'status',
        'type',
        'metadata',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExpiring(): bool
    {
        return $this->expires_at !== null && ! $this->isExpired();
    }

    public function sizeLabel(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.').' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.').' KB';
        }

        return $bytes.' B';
    }
}
