<?php

namespace App\Livewire\Admin;

use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Services\DeliveryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryPeopleManager extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $showInviteModal = false;
    public bool $showPerformance = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $cpf = '';
    public string $status = 'active';
    public string $search = '';

    public string $inviteLink = '';
    public ?int $performanceId = null;
    public array $performanceData = [];
    public string $reportPeriod = 'all';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:delivery_people,email,' . ($this->editingId ?? 'NULL') . ',id,tenant_id,' . auth()->user()->tenant_id,
            'phone' => 'required|string|max:20',
            'cpf' => 'nullable|string|max:14',
            'status' => 'required|in:active,inactive',
        ];
    }

    protected $messages = [
        'name.required' => 'O nome é obrigatório.',
        'phone.required' => 'O telefone é obrigatório.',
    ];

    public function openModal(?int $id = null): void
    {
        if ($id) {
            $delivery = DeliveryPerson::findOrFail($id);
            $this->editingId = $delivery->id;
            $this->name = $delivery->name;
            $this->email = $delivery->email ?? '';
            $this->phone = $delivery->phone;
            $this->cpf = $delivery->cpf ?? '';
            $this->status = $delivery->status;
        } else {
            $this->resetFields();
        }
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetFields();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $this->name,
            'email' => $this->email ?: null,
            'phone' => $this->phone,
            'cpf' => $this->cpf ?: null,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            DeliveryPerson::where('id', $this->editingId)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->update($data);
            $this->dispatch('notify', message: 'Entregador atualizado!');
        } else {
            DeliveryPerson::create($data);
            $this->dispatch('notify', message: 'Entregador cadastrado!');
        }

        $this->closeModal();
    }

    public function generateToken(int $id): void
    {
        $delivery = DeliveryPerson::where('id', $id)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->firstOrFail();

        $delivery->update(['api_token' => \Illuminate\Support\Str::random(60)]);

        $this->dispatch('notify', message: 'Token gerado! Copie o token agora (não será exibido novamente).');
    }

    public function generateInvite(int $id): void
    {
        $delivery = DeliveryPerson::where('id', $id)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->firstOrFail();

        $inviteToken = DeliveryPerson::generateInviteToken();

        $delivery->update([
            'invite_token' => $inviteToken,
            'invite_expires_at' => now()->addHours(48),
            'invited_at' => now(),
        ]);

        $this->inviteLink = url("/convidar/entregador/{$inviteToken}");
        $this->showInviteModal = true;
    }

    public function closeInviteModal(): void
    {
        $this->showInviteModal = false;
        $this->inviteLink = '';
    }

    public function copyInviteLink(): void
    {
        $this->dispatch('notify', message: 'Link copiado!');
    }

    public function resendInvite(int $id): void
    {
        $this->generateInvite($id);
    }

    public function showPerformance(int $id): void
    {
        $this->performanceId = $id;
        $this->reportPeriod = 'all';
        $this->loadPerformance();
        $this->showPerformance = true;
    }

    public function loadPerformance(): void
    {
        if (!$this->performanceId) {
            return;
        }

        $delivery = DeliveryPerson::where('id', $this->performanceId)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->firstOrFail();

        $startDate = null;
        $endDate = null;

        switch ($this->reportPeriod) {
            case 'today':
                $startDate = now()->startOfDay()->toDateTimeString();
                $endDate = now()->endOfDay()->toDateTimeString();
                break;
            case 'week':
                $startDate = now()->startOfWeek()->toDateTimeString();
                $endDate = now()->endOfDay()->toDateTimeString();
                break;
            case 'month':
                $startDate = now()->startOfMonth()->toDateTimeString();
                $endDate = now()->endOfDay()->toDateTimeString();
                break;
        }

        $service = app(DeliveryService::class);
        $profile = $service->getProfile($delivery, $startDate, $endDate);

        $orderQuery = Order::where('tenant_id', Auth::user()->tenant_id)
            ->where('delivery_person_id', $delivery->id)
            ->whereIn('status', ['entregue', 'fechado', 'cancelado', 'saiu_entrega', 'coletado']);

        if ($startDate) {
            $orderQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $orderQuery->where('created_at', '<=', $endDate);
        }

        $recentOrders = $orderQuery->with('items')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'customer_name' => $o->customer_name ?? 'Cliente',
                'total' => (float) $o->total,
                'status' => $o->status,
                'status_label' => $o->statusLabel(),
                'created_at' => $o->created_at->format('d/m/Y H:i'),
                'items_count' => $o->items->count(),
            ])
            ->toArray();

        $this->performanceData = array_merge($profile, ['recent_orders' => $recentOrders]);
    }

    public function closePerformance(): void
    {
        $this->showPerformance = false;
        $this->performanceId = null;
        $this->performanceData = [];
    }

    public function delete(int $id): void
    {
        DeliveryPerson::where('id', $id)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->delete();

        $this->dispatch('notify', message: 'Entregador removido.');
    }

    public function resetFields(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->cpf = '';
        $this->status = 'active';
    }

    public function render()
    {
        $tenantId = Auth::user()->tenant_id;

        $query = DeliveryPerson::where('tenant_id', $tenantId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        $deliveryPeople = $query->orderBy('name')->paginate(20);

        $stats = [
            'total' => DeliveryPerson::where('tenant_id', $tenantId)->count(),
            'active' => DeliveryPerson::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'activated' => DeliveryPerson::where('tenant_id', $tenantId)->whereNotNull('activated_at')->count(),
            'pending_invite' => DeliveryPerson::where('tenant_id', $tenantId)
                ->whereNotNull('invite_token')
                ->whereNull('activated_at')
                ->count(),
        ];

        return view('livewire.admin.delivery-people-manager', [
            'deliveryPeople' => $deliveryPeople,
            'stats' => $stats,
        ])->extends('layouts.admin');
    }
}
