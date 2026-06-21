<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Settings extends Component
{
    use WithFileUploads;

    public string $tenantName = '';
    public string $tenantEmail = '';
    public string $whatsapp = '';
    public string $openingTime = '';
    public string $closingTime = '';
    public string $deliveryCostPerOrder = '0';
    public $logo = null;
    public int $logoWidth = 44;
    public int $logoHeight = 44;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    public bool $showAccountDeleteConfirm = false;
    public bool $showTenantDeleteConfirm = false;
    public string $deleteConfirmation = '';
    public string $deleteTenantConfirmation = '';

     protected function rules(): array
     {
         return [
             'tenantName' => 'required|string|max:255',
             'tenantEmail' => 'required|email|max:255',
             'whatsapp' => 'nullable|string|max:20',
             'openingTime' => 'nullable|date_format:H:i',
             'closingTime' => 'nullable|date_format:H:i',
             'deliveryCostPerOrder' => 'nullable|numeric|min:0|max:999999',
             'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
             'logoWidth' => 'required|integer|min:20|max:120',
             'logoHeight' => 'required|integer|min:20|max:120',
         ];
     }

    protected $messages = [
        'tenantName.required' => 'O nome do restaurante é obrigatório.',
        'tenantEmail.required' => 'O email do restaurante é obrigatório.',
        'tenantEmail.email' => 'Informe um email válido para o restaurante.',
        'name.required' => 'O nome do usuário é obrigatório.',
        'email.required' => 'O email do usuário é obrigatório.',
        'email.email' => 'Informe um email válido para o usuário.',
        'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
        'passwordConfirmation.same' => 'As senhas não conferem.',
    ];

    public function mount(): void
    {
        $user = Auth::user()->load('tenant');
        $tenant = $user->tenant;

        $this->tenantName = $tenant->name;
        $this->tenantEmail = $tenant->email;
        $this->whatsapp = $tenant->whatsapp ?? '';
        $this->logoWidth = $tenant->logo_width ?? 44;
        $this->logoHeight = $tenant->logo_height ?? 44;
          if ($tenant->opening_time) {
              $this->openingTime = is_string($tenant->opening_time)
                  ? substr($tenant->opening_time, 0, 5)
                  : date('H:i', strtotime($tenant->opening_time));
          } else {
              $this->openingTime = '08:00'; // Default opening time
          }

          if ($tenant->closing_time) {
              $this->closingTime = is_string($tenant->closing_time)
                  ? substr($tenant->closing_time, 0, 5)
                  : date('H:i', strtotime($tenant->closing_time));
          } else {
              $this->closingTime = '22:00'; // Default closing time
          }

          $this->deliveryCostPerOrder = (string) ($tenant->delivery_cost_per_order ?? 0);
          
          $this->name = $user->name;
          $this->email = $user->email;
     }

     public function updatedOpeningTime($value)
     {
         $this->openingTime = trim($value);
         // Parse time and format as H:i (allow 1-2 digits for hours/minutes)
         if ($this->openingTime && preg_match('/^([0-9]{1,2}):([0-9]{1,2})(?::([0-9]{1,2}))?$/', $this->openingTime, $matches)) {
             $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
             $minutes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
             $this->openingTime = $hours . ':' . $minutes;
         }
     }

     public function updatedClosingTime($value)
     {
         $this->closingTime = trim($value);
         // Parse time and format as H:i (allow 1-2 digits for hours/minutes)
         if ($this->closingTime && preg_match('/^([0-9]{1,2}):([0-9]{1,2})(?::([0-9]{1,2}))?$/', $this->closingTime, $matches)) {
             $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
             $minutes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
             $this->closingTime = $hours . ':' . $minutes;
         }
     }

     public function saveTenant(): void
     {
         $this->validate();

         $tenant = Auth::user()->tenant;

         $data = [
             'name' => $this->tenantName,
             'email' => $this->tenantEmail,
             'whatsapp' => $this->whatsapp,
             'opening_time' => $this->openingTime ? \DateTime::createFromFormat('H:i', $this->openingTime)->format('H:i:s') : null,
             'closing_time' => $this->closingTime ? \DateTime::createFromFormat('H:i', $this->closingTime)->format('H:i:s') : null,
             'delivery_cost_per_order' => (float) $this->deliveryCostPerOrder,
             'logo_width' => $this->logoWidth,
             'logo_height' => $this->logoHeight,
         ];

         if ($this->logo) {
             if ($tenant->logo) {
                 Storage::disk('public')->delete($tenant->logo);
             }
             $data['logo'] = $this->logo->store('logos/' . $tenant->id, 'public');
         }

         $tenant->update($data);

         $this->dispatch('notify', message: 'Dados do restaurante atualizados!');
     }

     public function removeLogo(): void
     {
         $tenant = Auth::user()->tenant;
         if ($tenant->logo) {
             Storage::disk('public')->delete($tenant->logo);
             $tenant->update(['logo' => null]);
         }
         $this->logo = null;
         $this->dispatch('notify', message: 'Logo removida!');
     }

    public function saveProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:6',
            'passwordConfirmation' => 'nullable|same:password',
        ]);

        $user = Auth::user();
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        $user->update($data);
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->dispatch('notify', message: 'Perfil atualizado!');
    }

    public function exportData()
    {
        $user = Auth::user()->load('tenant');
        $tenant = $user->tenant;

        $data = [
            'exportado_em' => now()->toIso8601String(),
            'usuario' => [
                'nome' => $user->name,
                'email' => $user->email,
                'funcao' => $user->roleLabel(),
                'membro_desde' => $user->created_at->toIso8601String(),
            ],
            'restaurante' => [
                'nome' => $tenant->name,
                'email' => $tenant->email,
                'whatsapp' => $tenant->whatsapp,
                'slug' => $tenant->slug,
                'plano' => $tenant->planLabel(),
                'max_mesas' => $tenant->maxTablesAllowed(),
                'status' => $tenant->status,
                'criado_em' => $tenant->created_at->toIso8601String(),
            ],
            'total_usuarios' => $tenant->users()->count(),
            'total_pedidos' => $tenant->orders()->count(),
            'total_mesas' => $tenant->tables()->count(),
            'total_produtos' => $tenant->products()->count(),
            'total_categorias' => $tenant->categories()->count(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $path = tempnam(sys_get_temp_dir(), 'lgpd-');
        file_put_contents($path, $json);
        $filename = 'dados-lgpd-' . $tenant->slug . '-' . now()->format('Y-m-d') . '.json';

        return response()->download($path, $filename, ['Content-Type' => 'application/json'])->deleteFileAfterSend();
    }

    public function openAccountDelete(): void
    {
        $this->deleteConfirmation = '';
        $this->showAccountDeleteConfirm = true;
    }

    public function cancelAccountDelete(): void
    {
        $this->deleteConfirmation = '';
        $this->showAccountDeleteConfirm = false;
    }

    public function deleteAccount(): void
    {
        $this->validate(['deleteConfirmation' => 'required|in:EXCLUIR']);

        $user = Auth::user();
        $tenant = $user->tenant;
        $adminCount = $tenant->users()->where('role', User::ROLE_ADMIN)->count();

        if ($adminCount <= 1 && $tenant->users()->count() > 1) {
            $this->addError('deleteConfirmation', 'Você é o único administrador. Transfira o cargo para outro usuário antes de excluir.');
            return;
        }

        DB::transaction(function () use ($user) {
            $user->orders()->update(['user_id' => null]);
            $user->delete();
        });

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect('/login', navigate: false);
    }

    public function openTenantDelete(): void
    {
        $this->deleteTenantConfirmation = '';
        $this->showTenantDeleteConfirm = true;
    }

    public function cancelTenantDelete(): void
    {
        $this->deleteTenantConfirmation = '';
        $this->showTenantDeleteConfirm = false;
    }

    public function deleteTenant(): void
    {
        $this->validate(['deleteTenantConfirmation' => 'required|in:EXCLUIR TUDO']);

        $user = Auth::user();
        $tenant = $user->tenant;

        DB::transaction(function () use ($tenant) {
            $tenant->orders()->each(fn($o) => $o->items()->delete());
            $tenant->orders()->delete();
            $tenant->products()->delete();
            $tenant->categories()->delete();
            $tenant->tables()->delete();
            $tenant->users()->delete();
            $tenant->delete();
        });

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect('/', navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'tenant' => Auth::user()->tenant,
            'user' => Auth::user(),
        ])->extends('layouts.admin');
    }
}
