<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'source',
        'tenant_id',
        'payload_json',
        'signature',
        'is_valid',
        'processed',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'processed' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
