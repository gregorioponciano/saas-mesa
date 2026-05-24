# BACKEND — ROTAS, CONTROLLERS, MIDDLEWARE, SCOPES E PROVIDERS

## 6. ROTAS

### 6.1 Rotas Públicas (sem autenticação)

| Método | URI | Name | Middleware | Handler |
|--------|-----|------|-----------|---------|
| GET | / | — | web | view: welcome |
| GET | /login | login | throttle:10,1 | AuthController@loginForm |
| POST | /login | login | throttle:10,1 | AuthController@login |
| GET | /register | register.tenant | throttle:10,1 | AuthController@registerTenantForm |
| POST | /register | register.tenant.store | throttle:10,1 | AuthController@registerTenant |
| POST | /logout | logout | web | AuthController@logout |

### 6.2 Rotas do Cardápio Público (sem autenticação)

| Método | URI | Name | Handler |
|--------|-----|------|---------|
| GET | /cardapio/{slug:slug} | menu.show | view: menu-page (Tenant $slug) |
| GET | /cardapio/{slug:slug}/acesso | waiter.login.form | AuthController@waiterLoginForm |
| POST | /cardapio/{slug:slug}/acesso | waiter.login | AuthController@waiterLogin |
| GET | /cardapio/{slug:slug}/cadastro | waiter.register.form | AuthController@waiterRegisterForm |
| POST | /cardapio/{slug:slug}/cadastro | waiter.register | AuthController@waiterRegister |

### 6.3 Rotas Protegidas (auth + tenant.scope + check.subscription + check.admin)

| Método | URI | Name | Handler |
|--------|-----|------|---------|
| GET | /dashboard | dashboard | Dashboard (Livewire) |
| GET | /dashboard/tables | dashboard.tables | TablesPage (Livewire) |
| GET | /dashboard/menu | dashboard.menu | MenuManager (Livewire) |
| GET | /dashboard/users | dashboard.users | UserManager (Livewire) |
| GET | /subscription | subscription.checkout | SubscriptionCheckout (Livewire) |
| POST | /subscription | subscription.checkout.store | SubscriptionController@store |
| POST | /subscription/cancel | subscription.cancel | SubscriptionController@cancel |

### 6.4 Rotas Console (artisan)

| Comando | Descrição |
|---------|-----------|
| app:create-tenant | (definido em routes/console.php) |

---

## 7. CONTROLLERS

### 7.1 `AuthController`

**Namespace**: `App\Http\Controllers\AuthController`

**Métodos**:

| Método | Assinatura | Retorno | Descrição |
|--------|-----------|---------|-----------|
| loginForm | () | View | Exibe formulário de login |
| login | (Request) | RedirectResponse | Autentica e redireciona admin → /dashboard, atendente → /cardapio/{slug} |
| registerTenantForm | () | View | Exibe formulário de registro |
| registerTenant | (Request) | RedirectResponse | Cria Tenant + User admin + Mesas em transação, faz login |
| waiterLoginForm | (Tenant $slug) | View | Exibe login de atendente |
| waiterLogin | (Request, Tenant $slug) | RedirectResponse | Autentica atendente com validação de tenant |
| companyLoginForm | (Tenant $slug) | View | Exibe login da empresa |
| companyLogin | (Request, Tenant $slug) | RedirectResponse | Autentica admin com validação de tenant e role |
| waiterRegisterForm | (Tenant $slug) | View | Exibe cadastro de atendente |
| waiterRegister | (Request, Tenant $slug) | RedirectResponse | Cria User atendente e faz login |
| logout | (Request) | RedirectResponse | Faz logout, invalida sessão, redireciona para / |

**Validações**:

| Rota | Regras |
|------|--------|
| POST /login | email: required, email; password: required |
| POST /register | tenant_name: required, string, max:255; tenant_email: required, email, unique:tenants; slug: required, max:60, unique:tenants, alpha_dash; name: required, string, max:255; email: required, email, unique:users; password: required, confirmed, min:8 |
| POST /cardapio/{slug}/acesso | email: required, email; password: required |
| POST /cardapio/{slug}/cadastro | name: required, string, max:255; email: required, email, unique:users; password: required, confirmed, min:8 |

### 7.2 `SubscriptionController`

**Namespace**: `App\Http\Controllers\SubscriptionController`

**Métodos**:

| Método | Assinatura | Retorno | Descrição |
|--------|-----------|---------|-----------|
| store | (Request) | RedirectResponse | Upgrade para Premium (gera sub_ID, atualiza plan/max_tables/status) |
| cancel | () | RedirectResponse | Cancela Premium (volta para Free, limpa subscription_id) |

**Validações**:

| Rota | Regras |
|------|--------|
| POST /subscription | plan: required, in:free,paid |

### 7.3 `Controller` (base)

**Namespace**: `App\Http\Controllers\Controller`

Classe abstrata vazia. Serve como base para herança.

---

## 9. MIDDLEWARE

### 9.1 `App\Http\Middleware\CheckAdminRole`

| Atributo | Valor |
|----------|-------|
| Nome registrado | check.admin |
| Finalidade | Bloquear acesso de atendentes a rotas admin |
| Ação | Se role !== 'admin', redireciona para /cardapio/{slug} |
| Prioridade | Executado após auth e tenant.scope |

**Código**:
```php
public function handle(Request $request, Closure $next): Response
{
    if (Auth::check() && Auth::user()->role !== 'admin') {
        return redirect()->route('menu.show', Auth::user()->tenant->slug)
            ->with('error', 'Acesso restrito a administradores.');
    }
    return $next($request);
}
```

### 9.2 `App\Http\Middleware\CheckSubscription`

| Atributo | Valor |
|----------|-------|
| Nome registrado | check.subscription |
| Finalidade | Verificar status da assinatura do tenant |
| Ação | Redireciona para /subscription se status for suspended, cancelled ou trial expirado |
| Exceção | Rota subscription.checkout é ignorada |

**Código**:
```php
public function handle(Request $request, Closure $next): Response
{
    if (Auth::check() && Auth::user()->tenant) {
        $tenant = Auth::user()->tenant;
        $isSubscriptionRoute = $request->routeIs('subscription.checkout');
        if (!$isSubscriptionRoute) {
            if ($tenant->status === 'suspended') { redirect }
            if ($tenant->status === 'cancelled') { redirect }
            if ($tenant->status === 'trial' && $tenant->trial_ends_at?->isPast()) {
                $tenant->update(['status' => 'suspended']);
                redirect
            }
        }
    }
    return $next($request);
}
```

### 9.3 `App\Http\Middleware\TenantScopeMiddleware`

| Atributo | Valor |
|----------|-------|
| Nome registrado | tenant.scope |
| Finalidade | Injetar tenant_id na request para uso em controllers |
| Ação | Se autenticado, merge `current_tenant_id` na request |

**Código**:
```php
public function handle(Request $request, Closure $next): Response
{
    if (Auth::check()) {
        $request->merge(['current_tenant_id' => Auth::user()->tenant_id]);
    }
    return $next($request);
}
```

### 9.4 Ordem de Execução

```
web → auth → tenant.scope → check.subscription → check.admin → Controller
```

---

## 10. GLOBAL SCOPES

### 10.1 `App\Scopes\TenantScope`

| Atributo | Valor |
|----------|-------|
| Modelos afetados | Category, Product, ProductAttribute, Order, Table |
| Condição | Auth::check() && Auth::user()->tenant_id |
| Ação | `$builder->where('tenant_id', Auth::user()->tenant_id)` |
| Registro | Via atributo PHP 8 `#[ScopedBy([TenantScope::class])]` nos modelos |

**Modelos com TenantScope**:

| Modelo | Atributo |
|--------|----------|
| Category | `#[ScopedBy([TenantScope::class])]` |
| Product | `#[ScopedBy([TenantScope::class])]` |
| ProductAttribute | `#[ScopedBy([TenantScope::class])]` |
| Order | `#[ScopedBy([TenantScope::class])]` |
| Table | `#[ScopedBy([TenantScope::class])]` |

**Modelos SEM TenantScope**:

| Modelo | Motivo |
|--------|--------|
| Tenant | Entidade raiz, não tem tenant_id |
| User | Escopo manual por tenant_id em consultas |
| ProductAttributeOption | Relacionado via attribute → product, sem tenant_id direto |
| OrderItem | Relacionado via order, sem tenant_id direto |

---

## 11. SERVICE PROVIDERS

### 11.1 `App\Providers\AppServiceProvider`

| Método | Ação |
|--------|------|
| register() | Vazio |
| boot() | `Model::shouldBeStrict(!$this->app->isProduction())` |

**Efeitos de `shouldBeStrict` em não-produção**:

| Comportamento | Descrição |
|---------------|-----------|
| Lazy loading prevention | Lança exceção se tentar acessar relacionamento não-carregado |
| Discarded attribute prevention | Lança exceção se tentar preencher atributo não-fillable |
| Silently discarded attribute prevention | Impede atributos não-fillable silenciosos |
