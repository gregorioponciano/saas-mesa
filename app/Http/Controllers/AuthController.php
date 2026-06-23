<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
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
            'accept_terms' => ['required', 'accepted'],
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

    private function applyTenantMailConfig(Tenant $tenant): void
    {
        if ($tenant->mail_host) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $tenant->mail_host,
                'mail.mailers.smtp.port' => $tenant->mail_port,
                'mail.mailers.smtp.username' => $tenant->mail_username,
                'mail.mailers.smtp.password' => $tenant->mail_password,
                'mail.mailers.smtp.encryption' => $tenant->mail_encryption,
                'mail.from.address' => $tenant->mail_from_address,
                'mail.from.name' => $tenant->mail_from_name ?? $tenant->name,
            ]);
        }
    }

    public function forgotPasswordForm(Tenant $slug): View
    {
        return view('auth.forgot-password', ['tenant' => $slug]);
    }

    public function sendResetLink(Request $request, Tenant $slug): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        if (!$slug->mail_host) {
            return back()->withErrors(['email' => 'Restaurante não configurou envio de email. O administrador precisa configurar em Configurar Email.']);
        }

        $user = User::where('email', $request->email)
            ->where('tenant_id', $slug->id)
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email nao encontrado neste restaurante.']);
        }

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email, 'tenant_id' => $slug->id],
            ['token' => $token, 'created_at' => now()]
        );

        try {
            $this->applyTenantMailConfig($slug);
            Mail::to($request->email)->send(new ResetPasswordMail($slug, $token, $request->email));
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de reset: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Erro ao enviar email. Verifique as configurações de SMTP do restaurante.']);
        }

        return back()->with('status', 'Link de redefinição enviado para seu email!');
    }

    public function resetPasswordForm(Tenant $slug, string $token): View|RedirectResponse
    {
        $reset = DB::table('password_resets')
            ->where('token', $token)
            ->where('tenant_id', $slug->id)
            ->first();

        if (!$reset || now()->diffInMinutes($reset->created_at) > 60) {
            return redirect()->route('waiter.forgot.form', $slug->slug)
                ->withErrors(['email' => 'Link expirado ou invalido. Solicite novamente.']);
        }

        return view('auth.reset-password', [
            'tenant' => $slug,
            'token' => $token,
            'email' => $reset->email,
        ]);
    }

    public function resetPassword(Request $request, Tenant $slug, string $token): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $token)
            ->where('tenant_id', $slug->id)
            ->first();

        if (!$reset || now()->diffInMinutes($reset->created_at) > 60) {
            return redirect()->route('waiter.forgot.form', $slug->slug)
                ->withErrors(['email' => 'Link expirado ou invalido. Solicite novamente.']);
        }

        $user = User::where('email', $request->email)
            ->where('tenant_id', $slug->id)
            ->first();

        if (!$user) {
            return redirect()->route('waiter.forgot.form', $slug->slug)
                ->withErrors(['email' => 'Usuario nao encontrado.']);
        }

        $user->update(['password' => $request->password]);

        DB::table('password_resets')
            ->where('email', $request->email)
            ->where('tenant_id', $slug->id)
            ->delete();

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    public function adminForgotPasswordForm(): View
    {
        return view('auth.admin-forgot-password');
    }

    public function adminSendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email nao encontrado.']);
        }

        if (!$user->isAdmin()) {
            return back()->withErrors(['email' => 'Apenas administradores podem recuperar senha por aqui.']);
        }

        $tenant = $user->tenant;
        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email, 'tenant_id' => $tenant->id],
            ['token' => $token, 'created_at' => now()]
        );

        try {
            Mail::to($request->email)->send(new ResetPasswordMail($tenant, $token, $request->email, isAdmin: true));
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de reset admin: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Erro ao enviar email. Tente novamente mais tarde.']);
        }

        return back()->with('status', 'Link de redefinição enviado para seu email!');
    }

    public function adminResetPasswordForm(string $token): View|RedirectResponse
    {
        $reset = DB::table('password_resets')
            ->where('token', $token)
            ->first();

        if (!$reset || now()->diffInMinutes($reset->created_at) > 60) {
            return redirect()->route('admin.forgot.form')
                ->withErrors(['email' => 'Link expirado ou inválido. Solicite novamente.']);
        }

        return view('auth.admin-reset-password', [
            'token' => $token,
            'email' => $reset->email,
        ]);
    }

    public function adminResetPassword(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $token)
            ->first();

        if (!$reset || now()->diffInMinutes($reset->created_at) > 60) {
            return redirect()->route('admin.forgot.form')
                ->withErrors(['email' => 'Link expirado ou inválido. Solicite novamente.']);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->route('admin.forgot.form')
                ->withErrors(['email' => 'Usuário não encontrado.']);
        }

        $user->update(['password' => $request->password]);

        DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $token)
            ->delete();

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user);
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
