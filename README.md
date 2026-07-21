<p align="center">
  <img src="public/image/imagem.png" alt="SaaS Mesa Logo" width="500">
</p>

# SaaS Mesa

Sistema multi-tenant de gestão de mesas e pedidos para restaurantes, com cardápio digital, painel de garçom, fidelidade/cupons, delivery e **3 camadas de pagamento via EfiBank**.

> ⚠️ **Status**: o backend (API, regras de negócio, multi-tenancy, pagamentos) está maduro. O **painel do dono da plataforma (superadmin)** existe apenas como API JSON — ainda não tem interface web. Veja [Pendências](#pendências--roadmap).

---

## Sumário

- [Visão geral](#visão-geral)
- [Stack](#stack)
- [Arquitetura multi-tenant](#arquitetura-multi-tenant)
- [Papéis de usuário](#papéis-de-usuário)
- [Módulos e funcionalidades](#módulos-e-funcionalidades)
- [3 camadas de pagamento](#3-camadas-de-pagamento)
- [Segurança](#segurança)
- [Instalação](#instalação)
- [Ambiente de desenvolvimento](#ambiente-de-desenvolvimento)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Rotas — Web](#rotas--web)
- [Rotas — API](#rotas--api)
- [Comandos Artisan](#comandos-artisan)
- [Testes](#testes)
- [Deploy](#deploy)
- [Estrutura de diretórios](#estrutura-de-diretórios)
- [Pendências / Roadmap](#pendências--roadmap)

---

## Visão geral

O SaaS Mesa permite que restaurantes assinem a plataforma e, cada um em seu próprio subdomínio, operem:

- Cardápio digital público, com carrinho e pedido pelo cliente
- Painel administrativo do restaurante (mesas, cardápio, usuários, cupons, fidelidade, entregadores, financeiro, suporte)
- Painel do garçom para atender mesas presencialmente
- Área do cliente final para acompanhar pedidos e pontos de fidelidade
- App/API de entregadores para delivery
- Cobrança do cliente final via Pix/Boleto (EfiBank), e cobrança do próprio restaurante pela assinatura da plataforma

Quem administra a plataforma como um todo (o dono do SaaS) gerencia planos, assinaturas dos tenants, financeiro consolidado e liga/desliga o programa de fidelidade por tenant — hoje só via API.

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Livewire 4, Alpine.js 3, Tailwind CSS 4 (Vite) |
| Banco de dados | MySQL 8+ (SQLite em testes) |
| Cache / Fila | Redis |
| Pagamentos | EfiBank (ex-Gerencianet) — Pix, Boleto, Cartão |
| QR Code | endroid/qr-code |
| Markdown (termos de uso) | league/commonmark |
| Testes | Pest 4 |

## Arquitetura multi-tenant

Isolamento completo via `TenantScope` (global scope) + trait `BelongsToTenant`:

```
Subdomínio:  empresa1.saasmesa.com.br → Tenant::where('slug', 'empresa1')
Middleware:  ResolveTenant → CheckTenantSubscription / CheckSubscription → Auth
Scope:       BelongsToTenant trait → WHERE tenant_id = ? em TODAS as queries do tenant
```

Cada `Tenant` tem: `plan` (`free` | `paid`), `max_tables`, `status`, período de trial, além de configurações próprias (horário de funcionamento, WhatsApp, logo, SMTP, cupons habilitados, custo de entrega).

## Papéis de usuário

| Papel | Onde atua | Acesso |
|---|---|---|
| **superadmin** | Dono da plataforma (Gregório) | API `/api/superadmin/*` — gestão de planos, tenants e financeiro consolidado |
| **admin** | Dono/gerente do restaurante (tenant) | `/dashboard/*` — painel completo do estabelecimento |
| **atendente / garçom** | Staff do restaurante | `/painel/{slug}` — atendimento de mesas |
| **entregador** | Delivery | `/api/delivery/*` — API mobile |
| **cliente** | Cliente final do restaurante | `/cardapio/{slug}` (público) e `/conta/{slug}` (logado) |

## Módulos e funcionalidades

### Painel do restaurante (`/dashboard`, role `admin`)
- **Dashboard** — visão geral do estabelecimento
- **Mesas** (`TablesPage` / `TableGrid`) — grade de mesas, status, comandas
- **Cardápio** (`MenuManager`) — categorias, produtos, atributos/variações, estoque (`Ingredient`, `StockMovement`)
- **Usuários** (`UserManager`) — equipe do restaurante
- **Cupons** (`CouponManager`) — cupons de desconto
- **Fidelidade** (`LoyaltyManager`) — pontos por compra, resgates (`CustomerPoint`, `PointsTransaction`, `LoyaltyConfig`)
- **Entregadores** (`DeliveryPeopleManager`) — cadastro de entregadores
- **Financeiro do tenant** (`Tenant\FinancialController`) — extrato de pagamentos recebidos, resumo
- **Credenciais EfiBank** (`EfiCredentialsManager`) — chaves Pix/certificado do próprio restaurante, criptografadas
- **Configuração de e-mail SMTP** (`SmtpSettings`) — envio de e-mails com servidor próprio do tenant
- **Configurações gerais** (`Settings`) — dados da empresa, horários, logo
- **Suporte** (`SupportManager`) — tickets de suporte (`SupportTicket`, `SupportTicketMessage`)
- **Assinatura da plataforma** (`SubscriptionCheckout` + `SubscriptionController`) — contratar/cancelar plano do SaaS

### Cardápio público e pedidos (`/cardapio/{slug}`)
- Cardápio público (`Livewire\Public\Menu`) e carrinho (`Livewire\Public\Cart`)
- Login/cadastro/recuperação de senha de garçom e cliente por tenant

### Painel do garçom (`/painel/{slug}`, requer plano Premium)
- `WaiterDashboard` — atendimento de mesas e pedidos em tempo real
- `WaiterSupport` — suporte

### Área do cliente final (`/conta/{slug}`)
- `ClientDashboard` — histórico de pedidos, pontos de fidelidade
- `SupportPage` — suporte

### Painel do superadmin (dono do SaaS) — **somente API, sem UI**
- Planos (`SaasPlan`): Free (R$ 0), Premium (R$ 97,90), Enterprise (R$ 199,90)
- Gestão de tenants: listar, ver detalhes, suspender, reativar, trocar plano, forçar cobrança
- Financeiro consolidado: MRR, tenants ativos/suspensos/trial, receita últimos 12 meses, extrato de pagamentos
- Fidelidade: habilitar/desabilitar o módulo de pontos por tenant

## 3 camadas de pagamento

### Camada 1 — Assinatura do dono do SaaS

| Entidade | Descrição |
|---|---|
| `SaasPlan` | Planos (Free, Premium, Enterprise) |
| `SaasSubscription` | Assinatura por tenant (`trial` → `active` → `past_due` → `suspended` → `cancelled`) |
| `SaasPaymentHistory` | Histórico de pagamentos da assinatura |

**Fluxo**: tenant se registra → `SaasSubscription` em trial (7 dias) → admin escolhe plano → `active` → vencimento sem pagamento → `past_due` → após `EFI_SUSPENSION_AFTER_DAYS` (padrão 5) → `suspended` (job automático) → webhook confirma pagamento → `active` novamente e tenant reativado.

### Camada 2 — Pagamentos internos (cliente paga a comanda)

| Entidade | Descrição |
|---|---|
| `TenantEfiCredentials` | Credenciais EfiBank do tenant, criptografadas (AES-256-GCM) |
| `OrderPayment` | Pagamento do pedido (Pix/Boleto), com chave de idempotência |
| `Order.payment_status` | `pending` \| `paid` |

**Fluxo**: cliente solicita pagamento → `OrderPayment` criado (`processing`) → `EfiBankClient::forTenant()` gera cobrança Pix com as credenciais do tenant → webhook EfiBank → job `ProcessEfiBankWebhook` marca como `paid` → evento `OrderPaid` disparado (broadcast).

### Camada 3 — Billing interno da empresa

| Entidade | Descrição |
|---|---|
| `TenantBillingConfig` | Configuração de faturamento (fixo ou por transação) |
| `TenantInvoice` | Faturas geradas para o restaurante |

## Segurança

| # | Ponto | Mitigação |
|---|---|---|
| 1 | Vazamento cross-tenant | `BelongsToTenant` trait + `TenantScope` global em todas as queries |
| 2 | Credenciais em texto plano | AES-256-GCM (`EncryptedCredentialService`) |
| 3 | Webhook sem validação | HMAC-SHA256 obrigatório (`ValidateWebhookSignature`) + fila + idempotência |
| 4 | Força bruta no login | `throttle:5,1` a `throttle:10,1` nas rotas de auth (web e API) |
| 5 | Timeout EfiBank | Toda operação sensível via Job na fila, com retries |
| 6 | Cobrança duplicada | `idempotency_key` único na criação de pagamentos |
| 7 | Tenant suspenso acessando o sistema | `CheckTenantSubscription` / `CheckSubscription` bloqueiam o acesso |
| 8 | Acesso indevido entre papéis | Middlewares dedicados: `CheckAdminRole`, `CheckRole`, `CheckStaffRole`, `EnsureStaffAccess` |
| 9 | Headers HTTP | `SecurityHeaders` aplica CSP, HSTS, X-Frame-Options, etc. |
| 10 | Garçom/cliente acessando dados de outro tenant | Verificação explícita `tenant_id` nas rotas de painel/conta, além do scope global |

```
Content-Security-Policy: default-src 'self'; ...
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Strict-Transport-Security: max-age=31536000; includeSubDomains
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
```

> ⚠️ **Gap conhecido**: as rotas `/api/superadmin/*` não têm `throttle` dedicado (diferente das rotas de auth), e não há log de auditoria de ações do superadmin (quem suspendeu/trocou plano de qual tenant e quando).

## Instalação

### Pré-requisitos

- PHP 8.3+ com extensões: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO (MySQL), Redis, XML, GD, Sodium
- Composer 2.x
- MySQL 8+ (ou PostgreSQL 15+)
- Redis 6+
- Node.js 20+ e npm
- Supervisor (para o queue worker em produção)
- Certificado EfiBank (.p12) para processar pagamentos

### Passo a passo

```bash
# 1. Clonar o repositório
git clone https://github.com/gregorioponciano/saas-mesa.git
cd saas-mesa

# 2. Configurar variáveis de ambiente
cp .env.example .env
nano .env   # preencha DB_*, EFI_*, TENANT_*, etc.

# 3. Instalar dependências PHP
composer install --no-interaction

# 4. Gerar a APP_KEY
php artisan key:generate

# 5. Gerar a chave de criptografia das credenciais dos tenants
php artisan tenant:generate-encryption-key
# Cole o resultado em TENANT_CREDENTIAL_ENCRYPTION_KEY no .env

# 6. Criar o banco de dados
mysql -u root -p -e "CREATE DATABASE saas_mesa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. Rodar as migrations (43 arquivos)
php artisan migrate --force

# 8. (Opcional) Popular com dados de teste
php artisan db:seed --force

# 9. Instalar dependências Node e buildar assets
npm install
npm run build

# 10. Criar link simbólico do storage
php artisan storage:link

# 11. Configurar Supervisor (queue worker) — ver seção Deploy
# sudo supervisorctl start saas-mesa-worker:*

# 12. Configurar o crontab para o agendador
# * * * * * cd /caminho/para/saas-mesa && php artisan schedule:run >> /dev/null 2>&1

# 13. Criar o superadmin (dono da plataforma)
php artisan saas:create-superadmin

# 14. Rodar os testes
php artisan test
```

## Ambiente de desenvolvimento

```bash
composer run dev
# Roda em paralelo:
#   - php artisan serve
#   - php artisan queue:listen --tries=1 --timeout=0
#   - php artisan pail --timeout=0
#   - npm run dev
```

Ou manualmente, em terminais separados: `php artisan serve`, `php artisan queue:listen --tries=1 --timeout=0`, `php artisan pail`, `npm run dev`.

### Comandos úteis

```bash
php artisan key:generate --show     # exibir a APP_KEY atual
php artisan config:clear            # limpar cache de configuração
php artisan route:clear             # limpar cache de rotas
php artisan route:list              # listar todas as rotas
php artisan migrate:rollback        # reverter última migration
php artisan migrate:status          # status das migrations
```

## Variáveis de ambiente

```env
# App
APP_NAME="SaaS Mesa"
APP_URL=https://app.saasmesa.com.br
APP_LOCALE=pt_BR

# EfiBank — credenciais do SaaS (camada 1)
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
TENANT_CREDENTIAL_ENCRYPTION_KEY=

# Redis (obrigatório para fila e cache)
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

> Cada tenant também pode configurar **suas próprias** credenciais EfiBank (camada 2) e servidor SMTP pelo próprio painel — não vão no `.env`.

## Rotas — Web

### Institucionais e autenticação
```
GET  /                              → Landing page
GET  /termos-de-uso                 → Termos (renderizado a partir de docs/*.md)
GET  /politica-de-privacidade       → Política de privacidade
GET  /login | POST /login           → Login do admin do tenant   (throttle:10,1)
GET  /register | POST /register     → Cadastro de nova empresa   (throttle:10,1)
POST /logout
GET|POST /login/recuperar-senha, /login/redefinir-senha/{token}   (throttle:5,1)
```

### Painel do restaurante (`admin`, prefixo `/dashboard`)
Protegido por `auth`, `tenant.scope`, `check.subscription`, `check.admin`.
```
GET  /dashboard                     → Dashboard
GET  /dashboard/tables              → Mesas
GET  /dashboard/menu                → Cardápio
GET  /dashboard/users               → Usuários da equipe
GET  /dashboard/cupons              → Cupons
GET  /dashboard/entregadores        → Entregadores
GET  /dashboard/pontos              → Fidelidade
GET  /dashboard/configuracoes       → Configurações da empresa
GET  /dashboard/efi-credentials     → Credenciais EfiBank do tenant
GET  /dashboard/configurar-email    → SMTP do tenant
GET  /dashboard/suporte             → Suporte
GET|POST /subscription              → Checkout da assinatura da plataforma
POST /subscription/cancel           → Cancelar assinatura
```

### Cardápio e acesso de garçom/cliente (`/cardapio/{slug}`)
```
GET  /cardapio/{slug}                          → Cardápio público
GET|POST /cardapio/{slug}/acesso               → Login do garçom          (throttle:10,1)
GET|POST /cardapio/{slug}/cadastro             → Auto-registro de garçom  (throttle:10,1)
GET|POST /cardapio/{slug}/recuperar-senha      → Recuperação de senha     (throttle:5,1)
GET|POST /cardapio/{slug}/redefinir-senha/{t}  → Redefinição de senha     (throttle:5,1)
```

### Painel do garçom (`/painel/{slug}`, requer plano Premium)
```
GET  /painel/{slug}                 → Atendimento de mesas
GET  /painel/{slug}/configuracoes   → Configurações
GET  /painel/{slug}/suporte         → Suporte
```

### Área do cliente final (`/conta/{slug}`)
```
GET  /conta/{slug}                  → Painel do cliente (pedidos, pontos)
GET  /conta/{slug}/suporte          → Suporte
```

### Webhooks (sem CSRF, com HMAC)
```
POST /webhook/efi/saas              → Webhook camada 1 (assinatura da plataforma)
POST /webhook/efi/tenant/{tenantId} → Webhook camada 2 (pagamento de pedidos)
```

## Rotas — API

### Autenticação
```
POST /api/auth/login      (throttle:5,1)
POST /api/auth/refresh    (throttle:5,1)
POST /api/auth/logout
```

### Delivery (mobile)
```
POST /api/delivery/login              (throttle:10,1)
GET  /api/delivery/orders
GET  /api/delivery/my-orders
POST /api/delivery/orders/{order}/accept
POST /api/delivery/orders/{order}/status
GET  /api/delivery/profile
```

### Superadmin (`role:superadmin`) — sem UI web, apenas API
```
GET|POST|PUT|DELETE /api/superadmin/plans[/{id}]     → CRUD de planos
GET  /api/superadmin/tenants                         → Listar tenants (sem paginação atualmente)
GET  /api/superadmin/tenants/{id}                     → Detalhes do tenant
POST /api/superadmin/tenants/{id}/suspend
POST /api/superadmin/tenants/{id}/reactivate
PUT  /api/superadmin/tenants/{id}/plan
POST /api/superadmin/tenants/{id}/force-charge
GET  /api/superadmin/financial/overview               → MRR, tenants ativos/suspensos, receita 12 meses
GET  /api/superadmin/financial/payments                → Extrato geral de pagamentos (filtros: status, tenant_id, datas, método)
GET  /api/superadmin/financial/tenant/{tenant}          → Extrato de um tenant específico
GET  /api/superadmin/loyalty                            → Status do módulo de fidelidade por tenant
POST /api/superadmin/loyalty/{tenant}/toggle             → Habilitar/desabilitar fidelidade
```

### Tenant (autenticado, escopo do tenant)
```
POST /api/orders/{order}/pay                → Iniciar pagamento (Pix/Boleto)
GET  /api/orders/{order}/payment/status
GET  /api/orders/{order}/payment/qrcode
GET|PUT /api/settings/efi-credentials        → Credenciais EfiBank do tenant (admin)
POST /api/settings/efi-credentials/test      → Testar conexão (admin)
GET  /api/financial/payments                 → Histórico de pagamentos (admin)
GET  /api/financial/summary                  → Resumo financeiro (admin)
```

## Comandos Artisan

```bash
php artisan saas:create-superadmin              # Criar o superadmin (dono da plataforma)
php artisan saas:check-subscriptions            # Verificar/suspender assinaturas vencidas (agendado: hourly)
php artisan saas:financial-report --month=YYYY-MM  # Relatório financeiro (agendado: dia 1 às 06:00)
php artisan tenant:generate-encryption-key      # Gerar chave de criptografia de credenciais dos tenants
```

## Testes

```bash
php artisan test
```

| Categoria | Cobre |
|---|---|
| `TenantIsolationTest` | Isolamento cross-tenant em todas as entidades |
| `SubscriptionTest` | Ciclo de vida da assinatura, suspensão, reativação |
| `PaymentTest` | Idempotência, criptografia, status de pagamento |
| `SecurityTest` | Mass assignment, HMAC, SQL injection, fillable |
| `WebhookTest` | Validação HMAC, processamento via fila, evento `OrderPaid` |
| `EncryptedCredentialServiceTest` | AES-256-GCM encrypt/decrypt, detecção de adulteração |
| `EfiBankServiceTest` | Processamento de webhooks camada 1 e 2 |
| `AuthTest` | Login, registro, validação |
| `TableTest` | CRUD de mesas, limite por plano |
| `MenuTest` | Cardápio público |
| `DashboardTest` | Acesso ao dashboard |
| `PointsTest` | Regras do programa de fidelidade/pontos |

> ⚠️ **Gap conhecido**: não há testes cobrindo os controllers `Superadmin\PlansController`, `Superadmin\TenantsController`, `Superadmin\FinancialController` e `Superadmin\LoyaltyController`, apesar de manipularem suspensão, troca de plano e cobrança de tenants.

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

## Deploy

### Requisitos
- PHP 8.3+ (BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Redis)
- MySQL 8+ ou PostgreSQL 15+
- Redis 6+
- Supervisor (queue worker)
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

## Estrutura de diretórios

```
app/
├── Console/Commands/
│   ├── CheckSubscriptions.php        # Verificação de assinaturas (hourly)
│   ├── CreateSuperAdmin.php          # Criar usuário superadmin
│   └── GenerateFinancialReport.php   # Relatório financeiro mensal
├── Events/
│   └── OrderPaid.php                 # Evento de pagamento confirmado
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php        # Login/registro multi-tenant (admin, garçom, cliente)
│   │   ├── SubscriptionController.php # Ativação/cancelamento de planos do tenant
│   │   ├── Api/DeliveryController.php # API de entregadores
│   │   ├── Superadmin/               # Planos, tenants, financeiro, fidelidade — API only
│   │   ├── Tenant/                   # Pagamentos, credenciais EfiBank, financeiro do tenant
│   │   └── Webhook/                  # Webhooks SaaS + Tenant
│   ├── Middleware/
│   │   ├── CheckAdminRole.php / CheckRole.php / CheckStaffRole.php / EnsureStaffAccess.php
│   │   ├── CheckSubscription.php / CheckTenantSubscription.php
│   │   ├── ResolveTenant.php
│   │   ├── SecurityHeaders.php
│   │   ├── TenantScopeMiddleware.php
│   │   └── ValidateWebhookSignature.php
├── Jobs/
│   ├── ProcessEfiBankWebhook.php     # Processamento de webhook via fila
│   ├── CreateTenantSubscription.php  # Criar assinatura async
│   ├── SuspendTenantAccess.php       # Suspender tenant inadimplente
│   ├── RenewTenantSubscription.php   # Renovar cobrança mensal
│   └── UpdateOrderPaymentStatus.php  # Atualizar status de pagamento
├── Livewire/
│   ├── Admin/     # Painel do restaurante: Dashboard, TablesPage/TableGrid, MenuManager,
│   │               # UserManager, CouponManager, LoyaltyManager, DeliveryPeopleManager,
│   │               # EfiCredentialsManager, SmtpSettings, Settings, SupportManager,
│   │               # SubscriptionCheckout, SidebarCounts
│   ├── Client/    # ClientDashboard, ClientSidebarCounts, SupportPage
│   ├── Waiter/    # WaiterDashboard, WaiterSidebarCounts, WaiterSupport
│   ├── Public/    # Menu (cardápio público), Cart (carrinho)
│   └── Concerns/HasCart.php
├── Models/
│   ├── Traits/BelongsToTenant.php
│   ├── Tenant.php, User.php, UserAddress.php
│   ├── SaasPlan.php, SaasSubscription.php, SaasPaymentHistory.php
│   ├── TenantEfiCredentials.php, TenantBillingConfig.php, TenantInvoice.php
│   ├── Order.php, OrderItem.php, OrderPayment.php, Payment.php
│   ├── Table.php, Category.php, Product.php, ProductAttribute.php, ProductAttributeOption.php
│   ├── Ingredient.php, StockMovement.php
│   ├── Coupon.php, LoyaltyConfig.php, CustomerPoint.php, PointsTransaction.php
│   ├── DeliveryPerson.php, SupportTicket.php, SupportTicketMessage.php
│   └── WebhookLog.php
├── Observers/SaasSubscriptionObserver.php   # Suspensão/reativação automática
├── Policies/SaasSubscriptionPolicy.php, OrderPaymentPolicy.php
├── Scopes/TenantScope.php
└── Services/
    ├── EncryptedCredentialService.php   # AES-256-GCM
    ├── TenantResolverService.php        # Cache + subdomínio
    ├── SubscriptionService.php          # Ciclo de vida da assinatura
    ├── PointsService.php                # Regras de fidelidade
    ├── StockService.php                 # Baixa/ajuste de estoque
    ├── EfiPixService.php
    └── EfiBank/
        ├── EfiBankClient.php            # Factory por contexto (SaaS/Tenant)
        ├── SaasEfiBankService.php       # Operações camada 1
        ├── TenantEfiBankService.php     # Operações camada 2
        ├── TenantEfiCredentialsService.php
        └── WebhookValidatorService.php  # HMAC + validação

config/
├── efibank.php / efi.php   # Configurações EfiBank
└── tenancy.php             # Configurações de multi-tenancy

routes/
├── web.php      # Institucional, dashboard do tenant, cardápio, garçom, cliente, webhooks
├── api.php      # Auth, delivery, superadmin, tenant
├── webhook.php
└── console.php  # Agendamentos (check-subscriptions, financial-report)

database/
├── migrations/  # 43 migrations
├── factories/
└── seeders/     # TenantSeeder, SaasPlanSeeder, CategorySeeder, ProductSeeder,
                 # ProductAttributeSeeder, CouponSeeder, TableSeeder
```

## Pendências / Roadmap

Levantamento feito em análise do código-fonte atual (não é lista de features planejadas oficialmente — é o que falta pra fechar o que já existe):

1. **Painel web do superadmin** — hoje é só API (`/api/superadmin/*`). Falta a interface (Livewire/Blade) para o dono da plataforma ver e gerenciar as empresas sem chamar a API na mão: listagem de tenants, detalhe, suspender/reativar, trocar plano, dashboard financeiro (MRR, gráfico de receita, extrato).
2. **Paginação e busca em `GET /api/superadmin/tenants`** — hoje retorna todos os tenants de uma vez, sem paginação, filtro ou busca por nome/status/plano.
3. **Testes do módulo superadmin** — `PlansController`, `TenantsController`, `FinancialController` e `LoyaltyController` não têm cobertura de teste, apesar de mexerem com suspensão e cobrança de tenants.
4. **Auditoria de ações do superadmin** — não há log de quem suspendeu/reativou/trocou o plano de um tenant e quando.
5. **Rate limiting nas rotas de superadmin** — não têm `throttle` dedicado como as rotas de autenticação têm.
6. **CRUD completo de tenant pelo superadmin** — hoje só existe `index`/`show`; não há edição direta dos dados de uma empresa pelo painel do dono da plataforma (só via cadastro público em `/register`).
