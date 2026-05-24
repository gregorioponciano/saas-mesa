<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([TenantScope::class])]
class UserAddress extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'label',
        'address',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zipcode',
        'reference',
        'is_default',
    ];

    protected $appends = ['full_address', 'summary'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];

        $street = $this->address;
        if ($this->number) {
            $street .= ', ' . $this->number;
        }
        $parts[] = $street;

        if ($this->complement) {
            $parts[] = $this->complement;
        }
        if ($this->neighborhood) {
            $parts[] = $this->neighborhood;
        }

        $cityState = $this->city;
        if ($this->state) {
            $cityState .= ' - ' . $this->state;
        }
        $parts[] = $cityState;

        return implode(', ', $parts);
    }

    public function getSummaryAttribute(): string
    {
        $parts = [];

        $street = $this->address;
        if ($this->number) {
            $street .= ', ' . $this->number;
        }
        $parts[] = $street;

        if ($this->neighborhood) {
            $parts[] = $this->neighborhood;
        }

        return implode(', ', $parts);
    }
}
