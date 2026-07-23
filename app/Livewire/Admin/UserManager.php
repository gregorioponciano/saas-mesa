<?php

namespace App\Livewire\Admin;

use App\Models\CustomerPoint;
use App\Models\User;
use Livewire\Component;

class UserManager extends Component
{
    public bool $showForm = false;
    public ?int $editingUserId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public string $role = 'atendente';
    public string $phone = '';

    public ?int $confirmDeleteUserId = null;

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,atendente,cliente',
        ];

        if (!$this->editingUserId) {
            $rules['password'] = 'required|string|min:6';
            $rules['passwordConfirmation'] = 'required|same:password';
        } else {
            $rules['password'] = 'nullable|string|min:6';
            $rules['passwordConfirmation'] = 'nullable|same:password';
        }

        return $rules;
    }

    protected $messages = [
        'name.required' => 'O nome é obrigatório.',
        'email.required' => 'O email é obrigatório.',
        'email.email' => 'Informe um email válido.',
        'password.required' => 'A senha é obrigatória.',
        'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
        'passwordConfirmation.same' => 'As senhas não conferem.',
    ];

    public function resetForm(): void
    {
        $this->reset([
            'showForm', 'editingUserId', 'name', 'email', 'phone', 'password',
            'passwordConfirmation', 'role', 'confirmDeleteUserId',
        ]);
        $this->resetValidation();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->role = $user->role;
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $tenant = auth()->user()->tenant;

        $existing = User::where('email', $this->email)
            ->where('tenant_id', $tenant->id)
            ->where('id', '!=', $this->editingUserId)
            ->first();

        if ($existing) {
            $this->addError('email', 'Já existe um usuário com este email neste restaurante.');
            return;
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_staff' => in_array($this->role, ['admin', 'atendente']),
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        if ($this->editingUserId) {
            $user = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->editingUserId);
            if ($user->isAdmin() && $this->role !== 'admin' && $user->id === auth()->id()) {
                $this->addError('role', 'Você não pode rebaixar seu próprio cargo de administrador.');
                return;
            }
            $user->update($data);
            $this->dispatch('notify', message: 'Usuário "' . $user->name . '" atualizado!');
        } else {
            $data['tenant_id'] = $tenant->id;
            User::create($data);
            $this->dispatch('notify', message: 'Usuário "' . $this->name . '" criado!');
        }

        $this->showForm = false;
        $this->editingUserId = null;
    }

    public function toggleStaff(int $id): void
    {
        $user = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        if ($user->isAdmin()) {
            $this->dispatch('notify', message: 'Administradores não podem ser alterados.');
            return;
        }
        $user->update([
            'is_staff' => !$user->is_staff,
            'role' => $user->is_staff ? 'cliente' : 'atendente',
        ]);
        $label = $user->is_staff ? 'atendente' : 'cliente';
        $this->dispatch('notify', message: '"' . $user->name . '" agora é ' . $label . '.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteUserId = $id;
    }

    public function delete(int $id): void
    {
        $user = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $name = $user->name;
        $user->delete();
        $this->confirmDeleteUserId = null;
        $this->dispatch('notify', message: 'Usuário "' . $name . '" excluído!');
    }

    public function getUsersProperty()
    {
        $tenantId = auth()->user()?->tenant_id;
        if (!$tenantId) {
            return collect();
        }
        return User::where('tenant_id', $tenantId)
            ->orderByRaw("FIELD(role, 'admin', 'atendente', 'cliente')")
            ->orderBy('name')
            ->get();
    }

    public function getUserPointsProperty(): array
    {
        $tenantId = auth()->user()?->tenant_id;
        if (!$tenantId) {
            return [];
        }
        return CustomerPoint::where('tenant_id', $tenantId)
            ->pluck('balance', 'user_id')
            ->toArray();
    }

    public function render()
    {
        $tenantId = auth()->user()?->tenant_id;
        $userPoints = $tenantId
            ? CustomerPoint::where('tenant_id', $tenantId)->pluck('balance', 'user_id')->toArray()
            : [];

        return view('livewire.admin.user-manager', [
            'users' => $this->users,
            'userPoints' => $userPoints,
        ])->extends('layouts.admin');
    }
}
