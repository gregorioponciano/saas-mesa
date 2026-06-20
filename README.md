<p align="center">
  <img src="public/image/imgem.png" alt="MarioBET Logo" width="500">
</p>


# SaaS Mesa

Sistema multi-tenant de gestão de mesas para restaurantes com 3 camadas de pagamento via EfiBank.

---

## Instalação

### Pré-requisitos

- PHP 8.3+ com extensões: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO (MySQL), Redis, XML, GD, Sodium
- Composer 2.x
- MySQL 8+ (ou PostgreSQL 15+)
- Redis 6+
- Node.js 20+ e npm
- Supervisor (para queue worker em produção)
- Certificado EfiBank (.p12) para processar pagamentos

### Passo a Passo

```bash
# 1. Clonar o repositório
git clone <repo-url> saas-mesa
cd saas-mesa

# 2. Configurar variáveis de ambiente
cp .env.example .env

# 3. Edite o .env com seus dados
#    DB_DATABASE, DB_USERNAME, DB_PASSWORD, etc.
#    Veja a seção "Variáveis de Ambiente" abaixo
nano .env  # ou vi, vscode, etc.

# 4. Instalar dependências PHP
composer install --no-interaction

# 5. Gerar a APP_KEY (chave de criptografia da aplicação)
php artisan key:generate
#    Para apenas exibir a chave sem alterar o .env:
#   php artisan key:generate --show

# 6. Gerar a chave de criptografia das credenciais dos tenants
php artisan tenant:generate-encryption-key
#    Cole o resultado em TENANT_CREDENTIAL_ENCRYPTION_KEY no .env

# 7. Criar o banco de dados MySQL
mysql -u root -p -e "CREATE DATABASE saas_mesa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 8. Executar as migrations
php artisan migrate --force

# 9. (Opcional) Popular com dados de teste
php artisan db:seed --force

# 10. Instalar dependências Node.js e buildar assets
npm install
npm run build

# 11. Criar link simbólico do storage
php artisan storage:link

# 12. Configurar Supervisor (queue worker) — veja seção Deploy abaixo
#     Após configurar, iniciar o worker:
# sudo supervisorctl start saas-mesa-worker:*

# 13. Configurar crontab para o agendador
#     Adicione ao crontab -e:
# * * * * * cd /caminho/para/saas-mesa && php artisan schedule:run >> /dev/null 2>&1

# 14. Criar o superadmin (dono da plataforma)
php artisan saas:create-superadmin

# 15. Testar se tudo está funcionando
php artisan test
```

### Ambiente de Desenvolvimento

```bash
# Iniciar servidor de desenvolvimento (Vite + Laravel + Queue + Logs)
composer run dev
#    Isso executa simultaneamente:
#   - php artisan serve
#   - php artisan queue:listen --tries=1 --timeout=0
#   - php artisan pail --timeout=0
#   - npm run dev
```

Ou manualmente em terminais separados:

```bash
# Terminal 1 — Servidor Laravel
php artisan serve

# Terminal 2 — Queue worker
php artisan queue:listen --tries=1 --timeout=0

# Terminal 3 — Logs em tempo real
php artisan pail

# Terminal 4 — Vite (hot reload)
npm run dev
```

### Comandos Úteis

```bash
# Exibir a APP_KEY atual
php artisan key:generate --show

# Limpar cache de configuração
php artisan config:clear

# Limpar cache de rotas
php artisan route:clear

# Listar todas as rotas
php artisan route:list

# Criar migration
php artisan make:migration create_nome_tabela

# Executar migrations em produção com confirmação
php artisan migrate --force

# Reverter última migration
php artisan migrate:rollback

# Verificar status das migrations
php artisan migrate:status

# Criar novo superadmin
php artisan saas:create-superadmin

# Verificar assinaturas manualmente
php artisan saas:check-subscriptions

# Relatório financeiro
php artisan saas:financial-report --month=2026-06
```

---

## Arquitetura

### Stack
- **Backend**: Laravel 13 (PHP 8.3+)
- **Frontend**: Livewire 4, Alpine.js 3, Tailwind CSS 4
- **Database**: MySQL 8+ (SQLite para testes)
- **Cache/Queue**: Redis
- **Pagamentos**: EfiBank (antiga Gerencianet) - Pix, Boleto, Cartão

### Multi-tenancy

Isolamento completo via `TenantScope` global scope:

```
Subdomínio: empresa1.saasmesa.com.br → Tenant::where('slug', 'empresa1')
Middleware: ResolveTenant → CheckTenantSubscription → Auth
Scope:      BelongsToTenant trait → WHERE tenant_id = ? em TODAS as queries
```

Roles: `superadmin` | `admin` | `atendente` | `cliente`

---

## 3 Camadas de Pagamento

### Camada 1 — Assinatura do Dono do SaaS (Gregório)

| Entidade | Descrição |
|----------|-----------|
| `SaasPlans` | Planos (Free R$0, Premium R$97,90, Enterprise R$199,90) |
| `SaasSubscriptions` | Assinatura por tenant (trial → active → past_due → suspended → cancelled) |
| `SaasPaymentHistory` | Histórico de pagamentos da assinatura |

**Fluxo:**
1. Tenant registra → `SaasSubscription` criada em trial (7 dias)
2. Admin escolhe plano → status `active`
3. Vencimento → status `past_due` → 5 dias → `suspended` (automático via Job)
4. Webhook confirma pagamento → status `active` + tenant reativado

### Camada 2 — Pagamentos Internos (clientes pagam comanda)

| Entidade | Descrição |
|----------|-----------|
| `TenantEfiCredentials` | Credenciais EfiBank criptografadas (AES-256-GCM) por tenant |
| `OrderPayment` | Pagamento de pedido (Pix/Boleto) com idempotência |
| `Order.payment_status` | pending | paid (na própria ordem) |

**Fluxo:**
1. Cliente solicita pagamento → `OrderPayment` criado (status: processing)
2. `EfiBankClient::forTenant()` gera cobrança PIX com credenciais do tenant
3. Webhook EfiBank → `ProcessEfiBankWebhook` job → marca como `paid`
4. Evento `OrderPaid` disparado → broadcast WebSocket

### Camada 3 — Billing Interno da Empresa

| Entidade | Descrição |
|----------|-----------|
| `TenantBillingConfig` | Configuração de faturamento (fixo ou por transação) |
| `TenantInvoices` | Faturas geradas para a empresa |

---

## Segurança

### 10 Pontos Críticos Corrigidos

| # | Problema | Correção |
|---|----------|----------|
| 1 | Vazamento cross-tenant | `BelongsToTenant` trait + `TenantScope` global |
| 2 | Tokens sem expiração | JWT 15min + Refresh 7 dias + rate limiting |
| 3 | Webhook sem validação | HMAC-SHA256 obrigatório + queue + idempotência |
| 4 | Credenciais em texto plano | AES-256-GCM (`EncryptedCredentialService`) |
| 5 | Força bruta no login | `throttle:5,1` nas rotas de auth |
| 6 | ID sequencial exposto | UUID v4 em todos os recursos |
| 7 | Timeout EfiBank | Toda operação via Job na queue (retry: 1min, 5min, 15min) |
| 8 | Cobrança duplicada | `idempotency_key` unique em toda criação |
| 9 | Certificado expirado | Log de auditoria + alerta 30 dias antes |
| 10 | Tenant suspenso acessa | `CheckTenantSubscription` → 402 Payment Required |

### Headers HTTP

```
Content-Security-Policy: default-src 'self'; ...
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Strict-Transport-Security: max-age=31536000; includeSubDomains
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
```

---

## API — Rotas Principais

### Autenticação
```
POST /api/auth/login          → Login (throttle:5,1)
POST /api/auth/refresh        → Refresh token
POST /api/auth/logout         → Logout
```

### Superadmin (role: superadmin)
```
GET    /api/superadmin/plans          → Listar planos
POST   /api/superadmin/plans          → Criar plano
PUT    /api/superadmin/plans/{id}     → Atualizar plano
DELETE /api/superadmin/plans/{id}     → Desativar plano
GET    /api/superadmin/tenants        → Listar tenants
GET    /api/superadmin/tenants/{id}   → Detalhes do tenant
POST   /api/superadmin/tenants/{id}/suspend     → Suspender
POST   /api/superadmin/tenants/{id}/reactivate  → Reativar
PUT    /api/superadmin/tenants/{id}/plan        → Mudar plano
GET    /api/superadmin/financial/overview       → Dashboard financeiro
GET    /api/superadmin/financial/payments       → Extrato de cobranças
```

### Tenant (API autenticada)
```
POST   /api/orders/{order}/pay                → Iniciar pagamento (Pix/Boleto)
GET    /api/orders/{order}/payment/status      → Status do pagamento
GET    /api/orders/{order}/payment/qrcode      → QR Code Pix
GET    /api/settings/efi-credentials           → Ver credenciais EfiBank
PUT    /api/settings/efi-credentials           → Atualizar credenciais
POST   /api/settings/efi-credentials/test      → Testar conexão
GET    /api/financial/payments                 → Histórico de pagamentos
GET    /api/financial/summary                  → Resumo financeiro
```

### Webhooks (sem CSRF, com HMAC)
```
POST /webhook/efi/saas              → Webhook Camada 1 (assinatura)
POST /webhook/efi/tenant/{id}       → Webhook Camada 2 (pagamentos)
```

---

## Comandos Artisan

```bash
# Criar superadmin (Gregório)
php artisan saas:create-superadmin

# Verificar assinaturas (agendado: hourly)
php artisan saas:check-subscriptions

# Relatório financeiro mensal
php artisan saas:financial-report --month=2026-06
```

---

## Variáveis de Ambiente

```env
# EfiBank - Credenciais do SaaS
EFI_CLIENT_ID=
EFI_CLIENT_SECRET=
EFI_PIX_KEY=
EFI_SANDBOX=false
EFI_CERTIFICATE_PATH=/var/www/certs/efi-producao.p12
EFI_CERT_PASSWORD=
EFI_WEBHOOK_SECRET=
EFI_SUSPENSION_AFTER_DAYS=5

# Multi-tenancy
SAAS_MAIN_DOMAIN=saasmesa.com.br
TENANT_SUBDOMAIN_PATTERN=*.saasmesa.com.br

# Redis (obrigatório para queue e cache)
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

---

## Testes

```bash
php artisan test
# 64 tests, 116 assertions — all green
```

### Cobertura

| Categoria | Testes | O que cobre |
|-----------|--------|-------------|
| **TenantIsolationTest** | 7 | Isolamento cross-tenant em todas as entidades |
| **SubscriptionTest** | 6 | Ciclo de vida da assinatura, suspensão, reativação |
| **PaymentTest** | 6 | Idempotência, criptografia, webhook, status |
| **SecurityTest** | 6 | Mass assignment, HMAC, SQL injection, fillable |
| **WebhookTest** | 8 | Validação HMAC, processamento via queue, OrderPaid event |
| **EncryptedCredentialServiceTest** | 5 | AES-256-GCM encrypt/decrypt, tamper detection |
| **EfiBankServiceTest** | 7 | Processamento de webhooks Saas e Tenant |
| **AuthTest** | 6 | Login, registro, validação |
| **TableTest** | 5 | CRUD mesas, limite por plano |
| **MenuTest** | 4 | Cardápio público |
| **DashboardTest** | 2 | Acesso ao dashboard |
| **ExampleTest** | 2 | Smoke tests |

### CI/CD

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  pest:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: 8.3 }
      - run: composer install --no-interaction
      - run: php artisan key:generate
      - run: php artisan test
```

---

## Deploy

### Requisitos
- PHP 8.3+ com extensões: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Redis
- MySQL 8+ ou PostgreSQL 15+
- Redis 6+
- Supervisor (para queue worker)
- Certificado EfiBank (.p12) em `/var/www/certs/`

### Worker (Supervisor)

```ini
[program:saas-mesa-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/saas-mesa/artisan queue:work redis --queue=webhooks,subscriptions,default --tries=3 --sleep=3
autostart=true
autorestart=true
numprocs=2
user=www-data
```

### Schedule (Crontab)

```cron
* * * * * cd /var/www/saas-mesa && php artisan schedule:run >> /dev/null 2>&1
```

---

## Estrutura de Diretórios

```
app/
├── Console/Commands/
│   ├── CheckSubscriptions.php       # Verificação de assinaturas (hourly)
│   ├── CreateSuperAdmin.php         # Criar usuário superadmin
│   └── GenerateFinancialReport.php  # Relatório financeiro mensal
├── Events/
│   └── OrderPaid.php                # Evento de pagamento confirmado
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php       # Login, registro multi-tenant
│   │   ├── SubscriptionController.php # Ativação/cancelamento de planos
│   │   ├── Superadmin/              # CRUD planos, gestão tenants, financeiro
│   │   ├── Tenant/                  # Pagamentos, credenciais EfiBank
│   │   └── Webhook/                 # Webhooks Saas + Tenant
│   ├── Middleware/
│   │   ├── CheckSubscription.php     # Verificação de assinatura (web)
│   │   ├── CheckTenantSubscription.php # Verificação de assinatura (API)
│   │   ├── ResolveTenant.php         # Resolução por subdomínio
│   │   ├── SecurityHeaders.php       # Headers HTTP de segurança
│   │   ├── ValidateWebhookSignature.php # HMAC-SHA256
│   │   └── TenantScopeMiddleware.php # Escopo de tenant na request
├── Jobs/
│   ├── ProcessEfiBankWebhook.php     # Processamento de webhook via queue
│   ├── CreateTenantSubscription.php  # Criar assinatura async
│   ├── SuspendTenantAccess.php       # Suspender tenant inadimplente
│   ├── RenewTenantSubscription.php   # Renovar cobrança mensal
│   └── UpdateOrderPaymentStatus.php  # Atualizar status pagamento
├── Listeners/
│   └── NotifyOrderPaid.php           # Notificações de pagamento
├── Models/
│   ├── Traits/BelongsToTenant.php    # Global Scope + automatic tenant_id
│   ├── Tenant.php, User.php          # Modelos existentes
│   ├── SaasPlan.php, SaasSubscription.php, SaasPaymentHistory.php
│   ├── TenantEfiCredentials.php      # Criptografia automática
│   ├── OrderPayment.php              # Pagamentos de comandas
│   ├── TenantBillingConfig.php, TenantInvoice.php
│   └── WebhookLog.php                # Log de todos os webhooks
├── Observers/
│   └── SaasSubscriptionObserver.php  # Suspensão/reativação automática
├── Policies/
│   ├── SaasSubscriptionPolicy.php
│   └── OrderPaymentPolicy.php
├── Scopes/
│   └── TenantScope.php               # Global scope de tenant
└── Services/
    ├── EncryptedCredentialService.php # AES-256-GCM
    ├── TenantResolverService.php      # Cache + subdomínio
    ├── SubscriptionService.php        # Ciclo de vida da assinatura
    └── EfiBank/
        ├── EfiBankClient.php          # Factory por contexto (SaaS/Tenant)
        ├── SaasEfiBankService.php     # Operações Camada 1
        ├── TenantEfiBankService.php   # Operações Camada 2
        └── WebhookValidatorService.php # HMAC + validação IP

config/
├── efibank.php     # Configurações EfiBank centralizadas
└── tenancy.php     # Configurações de multi-tenancy

routes/
├── api.php         # API (auth, superadmin, tenant)
├── web.php         # Web (auth, subscription, webhooks)
└── console.php     # Tarefas agendadas

database/
└── migrations/     # 11 novas + 17 existentes = 28 migrations
