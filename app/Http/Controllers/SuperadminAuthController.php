<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SuperadminAuthController extends Controller
{
    public function loginForm(): View|RedirectResponse
    {
        if ($this->isSuperadminAuth()) {
            return redirect()->route('superadmin.dashboard');
        }

        return view('auth.superadmin-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        $switchedFromCompanyTenantId = null;
        if (Auth::check() && ! Auth::user()->isSuperAdmin()) {
            $switchedFromCompanyTenantId = Auth::user()->tenant_id;
        }

        if ($user && $user->role === 'superadmin' && Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if ($switchedFromCompanyTenantId !== null) {
                $request->session()->put('superadmin.switched_from_company', $switchedFromCompanyTenantId);
            }

            return redirect()->intended(route('superadmin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Credenciais inválidas ou sem permissão de superadministrador.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login');
    }

    private function isSuperadminAuth(): bool
    {
        return Auth::check() && Auth::user()->role === 'superadmin';
    }
}
