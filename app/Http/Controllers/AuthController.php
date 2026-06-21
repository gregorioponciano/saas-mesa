<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TenantBillingConfig;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function loginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user()->load('tenant');
            return $this->redirectByRole($user);
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user()->load('tenant');
            return $this->redirectByRole($user);
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas nao correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    public function registerTenantForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        return view('auth.register-tenant');
    }

    public function registerTenant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:tenants,email'],
            'slug' => ['required', 'string', 'max:60', 'unique:tenants,slug', 'alpha_dash:ascii'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $freePlan = SaasPlan::where('slug', 'free')->first();

        DB::transaction(function () use ($validated, $freePlan) {
            $tenant = Tenant::create([
                'name' => $validated['tenant_name'],
                'email' => $validated['tenant_email'],
                'slug' => $validated['slug'],
                'plan' => Tenant::PLAN_FREE,
                'max_tables' => Tenant::PLAN_MAX_TABLES[Tenant::PLAN_FREE],
                'status' => 'trial',
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'admin',
            ]);

            if ($freePlan) {
                SaasSubscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $freePlan->id,
                    'status' => 'trial',
                    'trial_ends_at' => now()->addDays(7),
                    'current_period_start' => now(),
                    'current_period_end' => now()->addMonth(),
                    'next_billing_date' => now()->addMonth(),
                ]);
            }

            TenantBillingConfig::create([
                'tenant_id' => $tenant->id,
                'billing_type' => 'fixed',
                'monthly_fee_cents' => 0,
                'billing_day' => 1,
                'is_active' => false,
            ]);

            $tableCount = min($tenant->maxTablesAllowed(), 10);
            for ($i = 1; $i <= $tableCount; $i++) {
                Table::create([
                    'tenant_id' => $tenant->id,
                    'number' => (string) $i,
                    'capacity' => 4,
                    'status' => 'free',
                ]);
            }

            Auth::login($user);
        });

        return redirect('/dashboard');
    }

    public function waiterLoginForm(Tenant $slug): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->tenant_id === $slug->id) {
                return $this->redirectByRole($user);
            }
            return redirect()->route('menu.show', $slug->slug);
        }
        return view('auth.waiter-login', ['tenant' => $slug]);
    }

    public function waiterLogin(Request $request, Tenant $slug): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if ($user->tenant_id !== $slug->id) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Voce nao possui acesso a este restaurante.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            if ($request->has('redirect')) {
                return redirect($request->input('redirect'));
            }

            return $this->redirectByRole($user);
        }

        return back()->withErrors([
            'email' => 'Credenciais invalidas.',
        ])->onlyInput('email');
    }

    public function waiterRegisterForm(Tenant $slug): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->tenant_id === $slug->id) {
                return $this->redirectByRole($user);
            }
            return redirect()->route('menu.show', $slug->slug);
        }
        return view('auth.waiter-register', ['tenant' => $slug]);
    }

    public function waiterRegister(Request $request, Tenant $slug): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('tenant_id', $slug->id)),
            ],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'tenant_id' => $slug->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'cliente',
            'is_staff' => false,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('menu.show', $slug->slug);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Token refreshed',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    private function redirectByRole($user): RedirectResponse
    {
        return match ($user->role) {
            'admin' => redirect()->intended('/dashboard'),
            'atendente' => redirect()->route('waiter.panel', $user->tenant->slug),
            default => redirect()->route('menu.show', $user->tenant->slug),
        };
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
