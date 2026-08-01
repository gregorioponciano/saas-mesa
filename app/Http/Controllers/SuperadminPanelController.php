<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\View\View;

class SuperadminPanelController extends Controller
{
    public function dashboard(): View
    {
        return view('superadmin.dashboard');
    }

    public function tenants(): View
    {
        return view('superadmin.tenants');
    }

    public function plans(): View
    {
        return view('superadmin.plans');
    }

    public function financial(): View
    {
        return view('superadmin.financial');
    }

    public function loyalty(): View
    {
        return view('superadmin.loyalty');
    }

    public function backups(): View
    {
        return view('superadmin.backups');
    }

    public function tenantSettings(Tenant $tenant): View
    {
        return view('superadmin.tenant-settings', ['tenant' => $tenant]);
    }

    public function users(): View
    {
        return view('superadmin.users');
    }

    public function webhooks(): View
    {
        return view('superadmin.webhooks');
    }

    public function audit(): View
    {
        return view('superadmin.audit');
    }

    public function privacy(): View
    {
        return view('superadmin.privacy');
    }
}
