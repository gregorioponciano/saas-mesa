<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TenantBackupService;
use Illuminate\Console\Command;

class PurgeBackups extends Command
{
    protected $signature = 'backups:purge';

    protected $description = 'Remove backups expirados de todas as empresas';

    public function handle(TenantBackupService $backupService): int
    {
        $deleted = $backupService->deleteExpired();

        $this->info("Backups expirados removidos: {$deleted}.");

        return self::SUCCESS;
    }
}
