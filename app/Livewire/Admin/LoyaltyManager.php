<?php

namespace App\Livewire\Admin;

use App\Models\CustomerPoint;
use App\Models\LoyaltyConfig;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LoyaltyManager extends Component
{
    use WithPagination;

    public ?Tenant $tenant;
    public bool $points_enabled;
    public int $points_percentage;
    public ?string $points_to_money_rate = null;
    public float $min_points_order_value;

    public function mount(): void
    {
        $this->tenant = Auth::user()->tenant;

        if (!$this->tenant || !$this->tenant->isPaid()) {
            $this->points_enabled = false;
            $this->points_percentage = 5;
            $this->points_to_money_rate = '0.01';
            $this->min_points_order_value = 10.0;
        } else {
            $config = LoyaltyConfig::forTenant($this->tenant);
            $this->points_enabled = (bool) $config->points_enabled;
            $this->points_percentage = (int) ($config->points_percentage ?? 5);
            $this->points_to_money_rate = (string) ($config->points_to_money_rate ?? '0.01');
            $this->min_points_order_value = (float) ($config->min_points_order_value ?? 10.00);
        }
    }

    public function saveLoyaltyConfig(): void
    {
        if (!$this->tenant || !$this->tenant->isPaid()) {
            $this->dispatch('notify', message: 'O programa de fidelidade é um recurso exclusivo do plano Premium.', type: 'alert');
            return;
        }

        $this->validate([
            'points_enabled' => 'required|boolean',
            'points_percentage' => 'required|integer|min:0|max:100',
            'points_to_money_rate' => 'required|numeric|min:0.0001',
            'min_points_order_value' => 'required|numeric|min:0',
        ]);

        LoyaltyConfig::forTenant($this->tenant)->update([
            'points_enabled' => $this->points_enabled,
            'points_percentage' => $this->points_percentage,
            'points_to_money_rate' => $this->points_to_money_rate,
            'min_points_order_value' => $this->min_points_order_value,
        ]);

        $this->dispatch('notify', message: 'Configurações de fidelidade salvas com sucesso!');
    }

    #[Computed]
    public function customerRanking()
    {
        if (!$this->tenant) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1);
        }

        return CustomerPoint::where('tenant_id', $this->tenant->id)
            ->with('user')
            ->where('balance', '>', 0)
            ->orderBy('balance', 'desc')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.loyalty-manager', [
            'customerRanking' => $this->customerRanking,
        ])->extends('layouts.admin');
    }
}