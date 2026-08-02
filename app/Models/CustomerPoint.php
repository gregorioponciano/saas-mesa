<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPoint extends Model
{
    use BelongsToTenant;

    protected $table = 'customer_points';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getBalance(Tenant $tenant, User $user): int
    {
        $record = static::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        return $record ? $record->balance : 0;
    }
}
