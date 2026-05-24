<?php

namespace App\Livewire\Admin;

use App\Models\DeliveryPerson;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryPeopleManager extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $phone = '';
    public string $status = 'active';
    public string $search = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'status' => 'required|in:active,inactive',
        ];
    }

    protected $messages = [
        'name.required' => 'O nome e obrigatorio.',
        'phone.required' => 'O telefone e obrigatorio.',
    ];

    public function openModal(?int $id = null): void
    {
        if ($id) {
            $delivery = DeliveryPerson::findOrFail($id);
            $this->editingId = $delivery->id;
            $this->name = $delivery->name;
            $this->phone = $delivery->phone;
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
            'phone' => $this->phone,
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

        $this->dispatch('notify', message: 'Token gerado! Copie o token agora (nao sera exibido novamente).');
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
        $this->phone = '';
        $this->status = 'active';
    }

    public function render()
    {
        $query = DeliveryPerson::where('tenant_id', Auth::user()->tenant_id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        $deliveryPeople = $query->orderBy('name')->paginate(20);

        return view('livewire.admin.delivery-people-manager', [
            'deliveryPeople' => $deliveryPeople,
        ])->layout('layouts.admin');
    }
}
