<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\TenantBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantBackupService
{
    public const DISK = 'backups';

    public const FREE_RETENTION_DAYS = 7;

    /**
     * Tabelas por empresa (tenant) incluídas no backup, na ordem de escrita.
     * `order_items` não tem tenant_id e é exportado via pedidos da empresa.
     */
    private const TENANT_TABLES = [
        'users',
        'user_addresses',
        'user_favorites',
        'categories',
        'ingredients',
        'products',
        'product_attributes',
        'product_attribute_options',
        'tables',
        'orders',
        'payments',
        'order_payments',
        'coupons',
        'delivery_people',
        'delivery_earnings',
        'tenant_loyalty_configs',
        'customer_points',
        'points_transactions',
        'stock_movements',
        'notifications',
        'support_tickets',
        'support_ticket_messages',
        'tenant_efi_credentials',
        'tenant_billing_configs',
        'tenant_invoices',
    ];

    public function retentionDaysForTenant(Tenant $tenant): ?int
    {
        // Plano premium (ativo): os backups não expiram — os dias de retenção não resetam/apagam os dados.
        if ($tenant->isPaid()) {
            return null;
        }

        $plan = $tenant->activeSubscription?->plan;
        $days = $plan?->features_json['backup_retention_days'] ?? null;

        return is_numeric($days) ? (int) $days : self::FREE_RETENTION_DAYS;
    }

    public function maxBackupsForTenant(Tenant $tenant): int
    {
        $plan = $tenant->activeSubscription?->plan ?? $tenant->currentPlan();
        $max = $plan?->features_json['backup_max_count'] ?? null;

        return is_numeric($max) ? (int) $max : 3;
    }

    public function retentionLabel(Tenant $tenant): string
    {
        $days = $this->retentionDaysForTenant($tenant);

        return $days === null
            ? 'Ilimitado (plano Premium)'
            : "{$days} dias (plano Gratuito)";
    }

    public function createBackup(Tenant $tenant, string $type = 'manual'): TenantBackup
    {
        $payload = $this->collectTenantData($tenant);

        $uuid = (string) Str::uuid();
        $filename = 'backup-'.now()->format('Ymd-His').'-'.$uuid.'.json';
        $path = $this->pathFor($tenant, $filename);

        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if (! Storage::disk(self::DISK)->put($path, $json)) {
            throw new \RuntimeException('Falha ao gravar o backup no armazenamento.');
        }

        $retentionDays = $this->retentionDaysForTenant($tenant);
        $maxBackups = $this->maxBackupsForTenant($tenant);

        $backup = TenantBackup::create([
            'tenant_id' => $tenant->id,
            'uuid' => $uuid,
            'filename' => $filename,
            'disk' => self::DISK,
            'size_bytes' => strlen($json),
            'status' => 'ready',
            'type' => $type,
            'metadata' => [
                'tables' => array_keys($payload['data'] ?? []),
                'retention_days' => $retentionDays,
                'max_backups' => $maxBackups,
                'generated_at' => now()->toIso8601String(),
            ],
            'expires_at' => $retentionDays !== null ? now()->addDays($retentionDays) : null,
        ]);

        $this->pruneOverLimit($tenant, $maxBackups);

        return $backup;
    }

    public function pruneOverLimit(Tenant $tenant, int $maxBackups): void
    {
        // `with('tenant')` é necessário: deleteBackup() acessa $backup->tenant.
        // Coleção é pequena (retenção por plano); SQLite não aceita OFFSET sem LIMIT.
        $oldest = TenantBackup::with('tenant')->where('tenant_id', $tenant->id) // @phpstan-ignore larastan.noUnnecessaryCollectionCall
            ->orderBy('created_at', 'desc')
            ->get()
            ->slice($maxBackups);

        foreach ($oldest as $backup) {
            $this->deleteBackup($backup);
        }
    }

    public function pathFor(Tenant $tenant, string $filename): string
    {
        return 'tenant-'.$tenant->id.'/'.$filename;
    }

    public function streamDownload(TenantBackup $backup): StreamedResponse
    {
        $path = $this->pathFor($backup->tenant, $backup->filename);

        return Storage::disk(self::DISK)->download($path, $backup->filename);
    }

    public function deleteBackup(TenantBackup $backup): void
    {
        $path = $this->pathFor($backup->tenant, $backup->filename);
        Storage::disk(self::DISK)->delete($path);
        $backup->delete();
    }

    public function deleteExpired(): int
    {
        $deleted = 0;

        TenantBackup::with('tenant')->where('expires_at', '<=', now())->chunkById(50, function ($backups) use (&$deleted) {
            foreach ($backups as $backup) {
                try {
                    $this->deleteBackup($backup);
                    $deleted++;
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });

        return $deleted;
    }

    public function totalSizeForTenant(Tenant $tenant): int
    {
        return (int) TenantBackup::where('tenant_id', $tenant->id)->sum('size_bytes');
    }

    public function collectTenantData(Tenant $tenant): array
    {
        $data = [];

        foreach (self::TENANT_TABLES as $table) {
            $rows = DB::table($table)->where('tenant_id', $tenant->id)->get();
            if ($rows->isNotEmpty()) {
                $data[$table] = $rows->toArray();
            }
        }

        $orderIds = Order::where('tenant_id', $tenant->id)->pluck('id');
        if ($orderIds->isNotEmpty()) {
            $data['order_items'] = DB::table('order_items')
                ->whereIn('order_id', $orderIds)
                ->get()
                ->toArray();
        }

        $tenantData = $tenant->toArray();
        unset($tenantData['logo']);

        return [
            'app' => config('app.name'),
            'generated_at' => now()->toIso8601String(),
            'tenant' => $tenantData,
            'data' => $data,
        ];
    }
}
