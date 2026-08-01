<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BlockSuperadminFromTenantPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isSuperAdmin()) {
            $switchedFromCompany = $request->session()->get('superadmin.switched_from_company');

            if ($switchedFromCompany !== null && $switchedFromCompany !== Auth::user()->tenant_id) {
                abort(403, 'Acesso restrito: o painel da empresa é exclusivo para contas de empresas.');
            }
        }

        return $next($request);
    }
}
