<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPoint extends Model
{
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

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
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
