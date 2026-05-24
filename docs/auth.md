# AUTH — SISTEMA DE AUTENTICAÇÃO

## 17. SISTEMA DE AUTENTICAÇÃO

### 17.1 Fluxo de Login

```
Usuário acessa /login
  → AuthController@loginForm
  → POST /login (email + password)
    → Auth::attempt()
    → Se role === 'atendente': redirect para /cardapio/{tenant.slug}
    → Se role === 'admin': redirect para /dashboard
    → Falha: back com erro
```

### 17.2 Fluxo de Registro de Tenant

```
Usuário acessa /register
  → AuthController@registerTenantForm
  → POST /register
    → Valida: tenant_name, tenant_email, slug, name, email, password
    → DB::transaction:
        1. Tenant::create (plan='free', max_tables=2, status='active')
        2. User::create (role='admin')
        3. Table::create (min(max_tables, 10) mesas)
    → Auth::login(user)
    → redirect /dashboard
```

### 17.3 Fluxo de Login por Slug

```
GET /login/{slug} (company) → login com validação: user.tenant_id === slug.id AND role === 'admin'
GET /cardapio/{slug}/acesso (waiter) → login com validação: user.tenant_id === slug.id
```

### 17.4 Papéis (Roles)

| Role | Permissões |
|------|------------|
| admin | Dashboard completo, gestão de mesas/cardápio/usuários/planos |
| atendente | Acesso apenas ao cardápio público e registro de pedidos |

### 17.5 Proteção de Rotas

| Middleware | Rotas |
|------------|-------|
| auth | /dashboard*, /subscription* |
| throttle:10,1 | /login*, /register* |
| tenant.scope | /dashboard*, /subscription* |
| check.subscription | /dashboard*, /subscription* (exceto subscription.checkout) |
| check.admin | /dashboard*, /subscription* |
