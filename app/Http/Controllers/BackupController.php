<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TenantBackup;
use App\Services\TenantBackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function download(Request $request, TenantBackup $backup, TenantBackupService $backupService): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user && $user->tenant_id === $backup->tenant_id, 403, 'Este backup pertence a outra empresa.');

        abort_if($backup->status !== 'ready', 404);

        return $backupService->streamDownload($backup);
    }
}
