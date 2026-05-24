# MULTI-TENANT — ARQUITETURA E PLANOS

## 18. SISTEMA MULTI-TENANT

### 18.1 Arquitetura

| Característica | Implementação |
|----------------|---------------|
| Estratégia | Single database, scoped queries |
| Identificação | `tenant_id` FK em todas as tabelas de dados |
| Aplicação automática | Global Scope `TenantScope` via PHP attribute `#[ScopedBy]` |
| Criação | Via registro (registerTenant) ou manualmente |
| Isolamento | Por slug na URL pública, por tenant_id em consultas |

### 18.2 Fluxo de Isolamento

```
1. User faz login → Auth::user()->tenant_id disponível
2. Request chega ao middleware tenant.scope → current_tenant_id injetado
3. Models com #[ScopedBy([TenantScope::class])] automaticamente filtram:
   → WHERE tenant_id = {current_user_tenant_id}
4. Rotas públicas (/cardapio/{slug}) usam Route Model Binding por slug
```

### 18.3 Modelos Escopados vs Não-Escopados

| Escopado | Motivo |
|----------|--------|
| Category | Dados do tenant |
| Product | Dados do tenant |
| ProductAttribute | Dados do tenant |
| Order | Dados do tenant |
| Table | Dados do tenant |

| Não escopado | Estratégia de isolamento |
|--------------|--------------------------|
| Tenant | Entidade raiz, buscada por slug na URL |
| User | `where('tenant_id', auth()->user()->tenant_id)` em consultas manuais |
| ProductAttributeOption | Acessado via relacionamento (attribute → options), não tem tenant_id direto |
| OrderItem | Acessado via relacionamento (order → items), não tem tenant_id direto |

---

## 19. SISTEMA DE PLANOS E ASSINATURAS

### 19.1 Planos Disponíveis

| Atributo | Gratuito (free) | Premium (paid) |
|----------|-----------------|----------------|
| Preço | R$ 0 | R$ 97,90/mês |
| Mesas | 2 | 50 |
| Status padrão | active | active |
| subscription_id | null | sub_{random} |

### 19.2 Fluxo de Upgrade

```
User clica "Assinar Premium"
  → POST /subscription (plan=paid)
    → SubscriptionController@store
    → Valida plan in:free,paid
    → gera subscription_id = 'sub_' . str()->random(16)
    → Atualiza: plan=paid, max_tables=50, status=active
    → subscription_ends_at = now()->addMonth()
    → trial_ends_at = null
    → redirect /dashboard com success message
```

### 19.3 Fluxo de Cancelamento

```
User clica "Cancelar Assinatura"
  → POST /subscription/cancel
    → SubscriptionController@cancel
    → Atualiza: plan=free, max_tables=2
    → subscription_id = null, subscription_ends_at = null
    → redirect /dashboard com info message
```

### 19.4 Verificações (CheckSubscription Middleware)

| Status do Tenant | Ação |
|-----------------|------|
| active | Permite acesso |
| trial (não expirado) | Permite acesso |
| trial (expirado) | Muda para suspended, redireciona para /subscription |
| suspended | Redireciona para /subscription |
| cancelled | Redireciona para /subscription |

### 19.5 Gatilhos de Suspensão

- Trial expirado: verificado a cada request pelo middleware
- Aplicação manual: admin pode suspender via banco

### 19.6 Constantes do Modelo Tenant

```php
PLAN_FREE = 'free'
PLAN_PAID = 'paid'
PLAN_LABELS = ['free' => 'Gratuito', 'paid' => 'Premium']
PLAN_PRICES = ['free' => 0, 'paid' => 97.90]
PLAN_MAX_TABLES = ['free' => 2, 'paid' => 50]
```
