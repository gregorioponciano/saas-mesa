<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeliveryPerson;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\TenantBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LgpdService
{
    /**
     * Reúne todos os dados de uma empresa para exportação (art. 18, LGPD).
     * Reaproveita a coleta do backup (que já cobre as tabelas por tenant).
     */
    public function exportTenantData(Tenant $tenant): array
    {
        $data = app(TenantBackupService::class)->collectTenantData($tenant);

        $data['purpose'] = 'solicitacao-lgpd-exportacao';
        $data['requested_by'] = auth()->user()?->email;

        return $data;
    }

    /**
     * Anonimiza e encerra uma empresa (art. 18, VI da LGPD — eliminação/anonimização).
     * Dados operacionais da empresa são mantidos para fins contábeis/antifraude,
     * porém sem qualquer dado pessoal identificável.
     */
    public function anonymizeTenant(Tenant $tenant): void
    {
        $anonId = Str::uuid();

        DB::transaction(function () use ($tenant, $anonId) {
            $tenant->backups()->get()->each(fn (TenantBackup $b) => app(TenantBackupService::class)->deleteBackup($b));

            $tenant->users()->update([
                'name' => 'Usuário Removido',
                'email' => "removido-{$anonId}@anonimo.invalid",
                'phone' => null,
                'password' => Str::random(40),
                'passkey_credentials' => null,
            ]);

            DeliveryPerson::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->update([
                    'name' => 'Entregador Removido',
                    'email' => null,
                    'phone' => 'Sem telefone',
                    'cpf' => null,
                    'cnh' => null,
                    'vehicle_plate' => null,
                    'password' => Str::random(40),
                    'api_token' => null,
                ]);

            SaasSubscription::where('tenant_id', $tenant->id)
                ->whereIn('status', ['trial', 'active', 'past_due', 'suspended'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

            $tenant->update([
                'name' => "Empresa Removida ({$tenant->id})",
                'email' => "removida-{$anonId}@anonimo.invalid",
                'slug' => 'removida-'.$anonId,
                'whatsapp' => null,
                'logo' => null,
                'status' => 'cancelled',
                'address' => null,
                'number' => null,
                'neighborhood' => null,
                'city' => null,
                'state' => null,
                'zipcode' => null,
                'latitude' => null,
                'longitude' => null,
            ]);
        });
    }

    public function anonymizedUserEmail(string $originalEmail): string
    {
        return 'removido-'.Str::uuid().'@anonimo.invalid';
    }

    public static function anonymizeEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        $parts = explode('@', $email);
        $local = $parts[0] ?? 'usuario';

        return substr($local, 0, 1).str_repeat('*', max(3, strlen($local) - 1)).'@'.($parts[1] ?? 'anonimo.invalid');
    }
}
