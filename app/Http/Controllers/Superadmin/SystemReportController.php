<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryPerson;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\SaasPaymentHistory;
use App\Models\SaasSubscription;
use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Models\TenantEfiCredentials;
use App\Models\TenantInvoice;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemReportController extends Controller
{
    public function report(): JsonResponse
    {
        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'system' => $this->system(),
            'connections' => $this->connections(),
            'errors' => $this->errors(),
            'status' => $this->status(),
            'resources' => $this->resources(),
            'recent_audit' => $this->recentAudit(),
            'scheduler' => $this->scheduler(),
        ]);
    }

    private function system(): array
    {
        $uptime = null;
        if (is_readable('/proc/uptime')) {
            $seconds = (float) explode(' ', (string) file_get_contents('/proc/uptime'))[0];
            $uptime = (int) $seconds;
        }

        return [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'app_url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'hostname' => gethostname() ?: null,
            'uptime_seconds' => $uptime,
            'memory_limit' => ini_get('memory_limit'),
            'last_migration' => $this->lastMigration(),
        ];
    }

    private function lastMigration(): ?string
    {
        try {
            return DB::table('migrations')->orderByDesc('id')->value('migration');
        } catch (\Throwable) {
            return null;
        }
    }

    private function connections(): array
    {
        $dbOk = true;
        $dbError = null;

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbOk = false;
            $dbError = $e->getMessage();
        }

        $cacheOk = false;
        try {
            Cache::put('__health_check', true, 10);
            $cacheOk = Cache::get('__health_check') === true;
        } catch (\Throwable) {
            $cacheOk = false;
        }

        return [
            'database' => [
                'ok' => $dbOk,
                'driver' => config('database.default'),
                'name' => config('database.connections.'.config('database.default').'.database'),
                'error' => $dbError,
            ],
            'cache' => [
                'ok' => $cacheOk,
                'driver' => config('cache.default'),
            ],
            'session_driver' => config('session.driver'),
            'queue' => [
                'driver' => config('queue.default'),
                'connection' => config('queue.default'),
            ],
            'mail' => [
                'default' => config('mail.default'),
                'from_address' => config('mail.from.address'),
            ],
            'storage' => [
                'disk' => config('filesystems.default'),
                'writable' => is_writable(storage_path()) && is_writable(storage_path('logs')),
            ],
            'integrations' => [
                'efi_configured_tenants' => TenantEfiCredentials::where('is_active', true)->count(),
                'smtp_configured_tenants' => Tenant::whereNotNull('mail_host')->count(),
                'webhook_secret_tenants' => TenantEfiCredentials::whereNotNull('webhook_secret_encrypted')->count(),
            ],
        ];
    }

    private function errors(): array
    {
        $failedJobs = 0;
        try {
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $failedJobs = 0;
        }

        return [
            'failed_jobs' => $failedJobs,
            'failed_webhooks_24h' => WebhookLog::where('created_at', '>=', now()->subDay())
                ->where('is_valid', false)
                ->count(),
            'webhooks_24h' => WebhookLog::where('created_at', '>=', now()->subDay())->count(),
            'recent_log_errors' => $this->recentLogErrors(),
        ];
    }

    private function recentLogErrors(int $limit = 8): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_readable($path)) {
            return [];
        }

        $content = file($path);
        if ($content === false) {
            return [];
        }

        $errors = [];
        $last = count($content);
        $start = max(0, $last - 2000);

        foreach (array_slice($content, $start) as $line) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2}[^\]]+)\] (\S+)\.(\S+):\s(.+)/', $line, $m)) {
                if (in_array($m[2], ['ERROR', 'CRITICAL', 'EMERGENCY'], true)) {
                    $errors[] = [
                        'time' => $m[1],
                        'level' => $m[2],
                        'message' => mb_strimwidth($m[4], 0, 220, '…'),
                    ];
                }
            }

            if (count($errors) >= $limit) {
                break;
            }
        }

        return $errors;
    }

    private function status(): array
    {
        return [
            'tenants_by_status' => Tenant::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($total, $status) => ['status' => $status, 'total' => $total])
                ->values(),
            'tenants_by_plan' => Tenant::query()
                ->selectRaw('plan, COUNT(*) as total')
                ->groupBy('plan')
                ->pluck('total', 'plan')
                ->map(fn ($total, $plan) => ['plan' => $plan, 'total' => $total])
                ->values(),
            'subscriptions_by_status' => SaasSubscription::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($total, $status) => ['status' => $status, 'total' => $total])
                ->values(),
            'payments_by_status' => SaasPaymentHistory::query()
                ->selectRaw('status, COUNT(*) as total, SUM(amount_cents) as total_cents')
                ->groupBy('status')
                ->get()
                ->map(fn ($row) => ['status' => $row->status, 'total' => $row->total, 'total_cents' => (int) $row->total_cents])
                ->values(),
            'totals' => [
                'tenants' => Tenant::count(),
                'users' => User::count(),
                'delivery_people' => DeliveryPerson::count(),
                'orders' => Order::count(),
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'order_payments' => OrderPayment::count(),
                'tenant_payments' => Payment::count(),
                'invoices' => TenantInvoice::count(),
                'subscriptions' => SaasSubscription::count(),
                'backups' => TenantBackup::count(),
                'audit_logs' => AuditLog::count(),
                'webhook_logs' => WebhookLog::count(),
            ],
        ];
    }

    private function resources(): array
    {
        $backupsSize = (int) TenantBackup::sum('size_bytes');

        $diskFree = null;
        $diskTotal = null;

        try {
            $free = disk_free_space(base_path());
            $total = disk_total_space(base_path());
            $diskFree = $free === false ? null : (int) $free;
            $diskTotal = $total === false ? null : (int) $total;
        } catch (\Throwable) {
            $diskFree = null;
            $diskTotal = null;
        }

        return [
            'backups_size_bytes' => $backupsSize,
            'disk_free_bytes' => $diskFree,
            'disk_total_bytes' => $diskTotal,
            'disk_used_percent' => ($diskFree !== null && $diskTotal !== null && $diskTotal > 0)
                ? (int) round((1 - $diskFree / $diskTotal) * 100)
                : null,
        ];
    }

    private function recentAudit(): array
    {
        return AuditLog::with('admin')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'description' => $log->description,
                'admin_name' => $log->admin?->name,
                'tenant_id' => $log->tenant_id,
                'created_at' => $log->created_at,
            ])
            ->all();
    }

    private function scheduler(): array
    {
        $commands = [
            'check-subscriptions' => 'saas:check-subscriptions',
            'financial-report' => 'saas:financial-report',
            'backups-purge' => 'backups:purge',
        ];

        return collect($commands)
            ->mapWithKeys(function (string $command, string $key) {
                $path = storage_path("logs/scheduler-{$key}.log");

                return [$key => [
                    'command' => $command,
                    'last_run_at' => is_file($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
                    'status' => is_file($path) ? 'ran' : 'never',
                ]];
            })
            ->all();
    }
}
