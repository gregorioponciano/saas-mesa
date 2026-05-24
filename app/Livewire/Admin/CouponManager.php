<?php

namespace App\Livewire\Admin;

use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CouponManager extends Component
{
    use WithPagination;

    public string $tab = 'list';

    public ?int $editingCouponId = null;

    public string $code = '';
    public string $discountType = 'percentage';
    public float $discountValue = 0;
    public ?float $minOrderValue = null;
    public ?int $maxUses = null;
    public bool $active = true;
    public ?string $expiresAt = null;

    protected function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:coupons,code,' . ($this->editingCouponId ?: 'NULL') . ',id,tenant_id,' . Auth::user()->tenant_id,
            'discountType' => 'required|in:percentage,fixed',
            'discountValue' => 'required|numeric|min:0.01|max:' . ($this->discountType === 'percentage' ? 100 : 999999),
            'minOrderValue' => 'nullable|numeric|min:0',
            'maxUses' => 'nullable|integer|min:1',
            'active' => 'boolean',
            'expiresAt' => 'nullable|date',
        ];
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingCouponId = null;
        $this->code = '';
        $this->discountType = 'percentage';
        $this->discountValue = 0;
        $this->minOrderValue = null;
        $this->maxUses = null;
        $this->active = true;
        $this->expiresAt = null;
    }

    public function edit(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $this->editingCouponId = $coupon->id;
        $this->code = $coupon->code;
        $this->discountType = $coupon->discount_type;
        $this->discountValue = (float) $coupon->discount_value;
        $this->minOrderValue = $coupon->min_order_value ? (float) $coupon->min_order_value : null;
        $this->maxUses = $coupon->max_uses;
        $this->active = $coupon->active;
        $this->expiresAt = $coupon->expires_at?->format('Y-m-d\TH:i');
        $this->tab = 'form';
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'tenant_id' => Auth::user()->tenant_id,
            'code' => strtoupper($this->code),
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'min_order_value' => $this->minOrderValue,
            'max_uses' => $this->maxUses,
            'active' => $this->active,
            'expires_at' => $this->expiresAt,
        ];

        if ($this->editingCouponId) {
            Coupon::findOrFail($this->editingCouponId)->update($data);
            $this->dispatch('notify', message: 'Cupom atualizado com sucesso!');
        } else {
            Coupon::create($data);
            $this->dispatch('notify', message: 'Cupom criado com sucesso!');
        }

        $this->resetForm();
        $this->tab = 'list';
    }

    public function toggleActive(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['active' => !$coupon->active]);
    }

    public function delete(int $id): void
    {
        Coupon::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Cupom removido.');
    }

    public function render()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.admin.coupon-manager', [
            'coupons' => $coupons,
        ])->layout('layouts.admin');
    }
}
