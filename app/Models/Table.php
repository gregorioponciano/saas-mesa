<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Database\Factories\TableFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[ScopedBy([TenantScope::class])]
class Table extends Model
{
    /** @use HasFactory<TableFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'token', 'number', 'capacity', 'status', 'observation',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'string',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $table): void {
            $table->token = (string) Str::uuid();
        });
    }

    public function isAvailable(): bool
    {
        return $this->status === 'free';
    }

    public function hasActiveOrdersExcluding(int $orderId): bool
    {
        return $this->orders()
            ->where('id', '!=', $orderId)
            ->whereIn('status', Order::STATUS_ACTIVE)
            ->exists();
    }

    public static function hasOtherActiveOrders(int $tableId, int $orderId): bool
    {
        return static::where('id', $tableId)->first()?->hasActiveOrdersExcluding($orderId) ?? false;
    }

    public function hasOpenBillableOrders(): bool
    {
        return $this->orders()
            ->whereNotIn('status', ['fechado', 'cancelado'])
            ->exists();
    }

    public static function tableHasOpenBillableOrders(int $tableId): bool
    {
        return static::where('id', $tableId)->first()?->hasOpenBillableOrders() ?? false;
    }

    public static function tryFreeTable(int $tableId): bool
    {
        $table = static::find($tableId);
        if (!$table) return false;        
        if (!$table->hasOpenBillableOrders()) {
            $table->update(['status' => 'free']);
            return true;
        }
        return false;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
