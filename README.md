<p align="center">
  <img src="public/image/imagem.png" alt="SaaS Mesa Logo" width="500">
</p>

# SaaS Mesa

Sistema multi-tenant de gestão de mesas e pedidos para restaurantes, com cardápio digital, painel de garçom, painel/API de entregador, fidelidade/cupons e **3 camadas de pagamento via EfiBank**.

> ⚠️ **Status**: o backend (API, regras de negócio, multi-tenancy, pagamentos, delivery) está maduro. O **painel do dono da plataforma (superadmin)** existe apenas como API JSON — ainda não tem interface web.

---

## Sumário

- [Visão geral](#visão-geral)
- [Stack](#stack)
- [Arquitetura multi-tenant](#arquitetura-multi-tenant)
- [Papéis de usuário](#papéis-de-usuário)
- [Planos da plataforma](#planos-da-plataforma)
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
- Painel do garçom para atender mesas presencialmente (plano Premium)
- Área do cliente final para acompanhar pedidos e pontos de fidelidade
- Painel web (`/entregador`) e API mobile (`/api/delivery`) de entregadores, com convite por token
- Rastreio público de pedido, sem necessidade de login
- Cobrança do cliente final via Pix/Boleto (EfiBank), e cobrança do próprio restaurante pela assinatura da plataforma

Quem administra a plataforma como um todo (o dono do SaaS) gerencia planos, assinaturas dos tenants e financeiro consolidado — hoje só via API.

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Livewire 4, Alpine.js 3, Tailwind CSS 4 (Vite) |
| Banco de dados | MySQL 8+ (SQLite em testes) |
| Cache / Fila / Sessão | Redis |
| Autenticação de API | Laravel Sanctum (guard `delivery`) |
| Pagamentos | EfiBank (ex-Gerencianet) — Pix, Boleto |
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

Cada `Tenant` tem: `plan` (`free` | `paid`), `max_tables`, `status`, período de trial, além de configurações próprias (horário de funcionamento, WhatsApp, logo, SMTP, cupons habilitados, custo de entrega). O `TenantObserver` reage a mudanças de plano: quando um tenant deixa de ser `paid`, desativa automaticamente o módulo de fidelidade (`PointsService::disableForTenant`).

## Papéis de usuário

| Papel | Onde atua | Acesso |
|---|---|---|
| **superadmin** | Dono da plataforma | API `/api/superadmin/*` — gestão de planos, tenants e financeiro consolidado |
| **admin** | Dono/gerente do restaurante (tenant) | `/dashboard/*` — painel completo do estabelecimento |
| **atendente / garçom** | Staff do restaurante | `/painel/{slug}` — atendimento de mesas (requer plano Premium) |
| **entregador** | Delivery | `/entregador/*` (painel web, guard `delivery-web`) e `/api/delivery/*` (app mobile, guard `delivery` via Sanctum) |
| **cliente** | Cliente final do restaurante | `/cardapio/{slug}` (público) e `/conta/{slug}` (logado) |

## Planos da plataforma

O sistema tem **apenas 2 planos** definidos hoje (constantes em `App\Models\Tenant` e seed em `SaasPlanSeeder`):

| Plano | Preço | Mesas | Produtos | Usuários | Boleto | Relatórios | Delivery | Suporte prioritário |
|---|---|---|---|---|---|---|---|---|
| **Gratuito** (`free`) | R$ 0 | 2 | 20 | 2 | Não | Não | Não | Não |
| **Premium** (`paid`) | R$ 97,90/mês | 50 | 999 | 20 | Sim | Sim | Sim | Sim |

> Não há um plano "Enterprise" no código atual — se isso vier a existir, precisa ser criado em `SaasPlanSeeder` e nas constantes `Tenant::PLAN_*`.

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
- **Contadores da sidebar** (`SidebarCounts`) — atualizados por polling (`wire:poll` do Livewire, não websocket)

### Cardápio público e pedidos (`/cardapio/{slug}`)
- Cardápio público (`Livewire\Public\Menu`) e carrinho (`Livewire\Public\Cart`, trait `HasCart`)
- Login/cadastro/recuperação de senha de garçom e cliente por tenant
- Rastreio público de pedido, sem autenticação: `/pedido/{id}/rastreio` (web) e `/api/pedido/{id}/status` (API)

### Painel do garçom (`/painel/{slug}`, requer plano Premium)
- `WaiterDashboard` — atendimento de mesas e pedidos, com contadores via polling (`WaiterSidebarCounts`)
- `WaiterSupport` — suporte

### Área do cliente final (`/conta/{slug}`)
- `ClientDashboard` — histórico de pedidos, pontos de fidelidade, contadores via polling (`ClientSidebarCounts`)
- `SupportPage` — suporte
- Favoritos de produto (model `UserFavorite`, relação `User` ↔ `Product`)

### Painel e API de entregadores
- **Painel web** (`/entregador/*`, guard `delivery-web`, sessão): login, recuperação de senha, dashboard (`Livewire\Delivery\DeliverySidebarCounts` para contadores), aceitar/coletar/entregar pedido, alternar disponibilidade, configurações, notificações e logout — controller `Web\DeliveryWebController`
- **Convite de entregador por token**: `Web\DeliveryInviteController` (fluxo web) e `Api\DeliveryInvitationController` (fluxo mobile), permitindo que o entregador crie a própria conta a partir de um link enviado pelo restaurante
- **API mobile** (`/api/delivery/*`, guard `delivery` via Sanctum, com compatibilidade explícita com token legado): login, aceitar/recusar/coletar pedido, atualizar status, listar pedidos disponíveis e "meus pedidos", perfil — controller `Api\DeliveryController`
- **Regras de negócio de entrega** centralizadas em `App\Services\DeliveryService`
- **Notificações de entregador** (`DeliveryNotificationService` + model `Notification`, relação polimórfica): notifica entregadores ativos quando surge um novo pedido de entrega disponível, e notifica o tenant quando o pedido é aceito/coletado/entregue. As notificações são consultadas via polling (`GET /entregador/notificacoes`), não por push/websocket.
- **Regras de autorização** descritas em `App\Policies\DeliveryPersonPolicy` (quem pode ver/aceitar/recusar/atualizar um pedido de entrega, todas amarradas ao `tenant_id`)

### Painel do superadmin (dono do SaaS) — **somente API, sem UI**
- Planos (`SaasPlan`): CRUD via `apiResource` — hoje só existem os planos Gratuito e Premium (ver [Planos da plataforma](#planos-da-plataforma))
- Gestão de tenants: listar, ver detalhes, suspender, reativar, trocar plano, forçar cobrança
- Financeiro consolidado (`Superadmin\FinancialController::overview`): tenants ativos/suspensos/trial, MRR, total arrecadado, renovações pendentes nos próximos 7 dias, webhooks inválidos nas últimas 24h, receita mês a mês dos últimos 12 meses
- Extrato de pagamentos com filtros por `status`, `tenant_id`, `date_from`, `date_to` e `method`

## 3 camadas de pagamento

### Camada 1 — Assinatura do dono do SaaS

| Entidade | Descrição |
|---|---|
| `SaasPlan` | Planos da plataforma (Gratuito, Premium) |
| `SaasSubscription` | Assinatura por tenant (`trial` → `active` → `past_due` → `suspended` → `cancelled`) |
| `SaasPaymentHistory` | Histórico de pagamentos da assinatura |

**Fluxo**: tenant se registra → `SaasSubscription` em trial → admin escolhe plano → `active` → vencimento sem pagamento → `past_due` → após `EFI_SUSPENSION_AFTER_DAYS` (padrão 5) → `suspended` (job automático) → webhook confirma pagamento → `active` novamente e tenant reativado.

### Camada 2 — Pagamentos internos (cliente paga a comanda)

| Entidade | Descrição |
|---|---|
| `TenantEfiCredentials` | Credenciais EfiBank do tenant, criptografadas (AES-256-GCM) |
| `OrderPayment` | Pagamento do pedido (Pix/Boleto), com chave de idempotência |
| `Order.payment_status` | `pending` \| `paid` |

**Fluxo**: cliente solicita pagamento → `OrderPayment` criado → `EfiBankClient::forTenant()` gera cobrança Pix com as credenciais do tenant → webhook EfiBank → job `ProcessEfiBankWebhook` marca como `paid` → evento `App\Events\OrderPaid` é disparado. O evento implementa `ShouldBroadcast` no canal `tenant.{tenant_id}.orders` (evento `order.paid`) — **por padrão o `.env.example` traz `BROADCAST_CONNECTION=log`**, então esse broadcast só chega ao navegador se um driver real (Reverb/Pusher/Ably) for configurado em produção. O listener `NotifyOrderPaid` reage a esse evento concedendo os pontos de fidelidade do pedido via `PointsService::grantPointsForOrder`.

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
| 3 | Webhook sem validação | HMAC obrigatório (`ValidateWebhookSignature`) + fila + idempotência; CSRF é explicitamente desabilitado só para `webhook/efi/*` |
| 4 | Força bruta no login | `throttle` (5 ou 10 requisições/minuto conforme a rota) nas rotas de auth web, entregador e API |
| 5 | Timeout EfiBank | Operações sensíveis via Job na fila |
| 6 | Cobrança duplicada | `idempotency_key` único na criação de pagamentos |
| 7 | Tenant suspenso acessando o sistema | `CheckTenantSubscription` / `CheckSubscription` bloqueiam o acesso |
| 8 | Acesso indevido entre papéis | Middlewares dedicados: `CheckAdminRole`, `CheckRole`, `CheckStaffRole`, `EnsureStaffAccess` |
| 9 | Headers HTTP | `SecurityHeaders` aplica em toda rota web e API: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Content-Security-Policy` (própria política, ver abaixo) e `Permissions-Policy: camera=(), microphone=(), geolocation=()`. `Strict-Transport-Security` só é enviado quando `APP_ENV=production` |
| 10 | Garçom/cliente/entregador acessando dados de outro tenant | Verificação explícita `tenant_id` nas rotas de painel/conta, além do scope global |

```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.saasmesa.com.br; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self' https://*.efipay.com.br https://*.saasmesa.com.br; frame-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
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
# Não existe comando artisan dedicado no código atual — gerar manualmente:
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
# Colar o resultado em TENANT_CREDENTIAL_ENCRYPTION_KEY no .env

# 6. Criar o banco de dados
mysql -u root -p -e "CREATE DATABASE saas_mesa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. Rodar as migrations (35 arquivos)
php artisan migrate --force

# 8. (Opcional) Popular com dados de teste
php artisan db:seed --force

# 9. Instalar dependências Node e buildar assets
npm install
npm run build

# 10. Criar link simbólico do storage
php artisan storage:link

# 11. Configurar Supervisor (queue worker) — ver seção Deploy

# 12. Configurar o crontab para o agendador
# * * * * * cd /caminho/para/saas-mesa && php artisan schedule:run >> /dev/null 2>&1

# 13. Criar o superadmin (dono da plataforma)
php artisan saas:create-superadmin

# 14. Rodar os testes
php artisan test
```

O `composer.json` também define um script `composer setup` (install → copiar `.env` → `key:generate` → `migrate --force` → `npm install --ignore-scripts` → `npm run build`), útil para provisionar rápido — mas ele **não** gera a `TENANT_CREDENTIAL_ENCRYPTION_KEY` nem cria o superadmin; esses dois passos continuam manuais.

## Ambiente de desenvolvimento

```bash
composer run dev
# Roda em paralelo (via concurrently):
#   - php artisan serve
#   - php artisan queue:listen --tries=1 --timeout=0
#   - php artisan pail --timeout=0
#   - npm run dev
```

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

O `.env.example` do projeto é extenso (sessão, broadcasting, fila, cache, e-mail etc., com a maioria comentada/opcional). Os blocos específicos do domínio do SaaS Mesa que precisam ser preenchidos são:

```env
# App
APP_NAME="SaaS Mesa"
APP_URL=https://app.saasmesa.com.br
APP_LOCALE=pt_BR

# EfiBank — credenciais do SaaS (camada 1)
# Webhook a cadastrar no EfiBank: https://app.saasmesa.com.br/webhook/efi/saas
EFI_CLIENT_ID=
EFI_CLIENT_SECRET=
EFI_PIX_KEY=
EFI_SANDBOX=false
EFI_CERTIFICATE_PATH=/var/www/certs/efi-producao.p12
EFI_CERT_PASSWORD=
EFI_WEBHOOK_SECRET=
EFI_SUSPENSION_AFTER_DAYS=5

# Criptografia das credenciais EfiBank de cada tenant (camada 2)
TENANT_CREDENTIAL_ENCRYPTION_KEY=

# Multi-tenancy
SAAS_MAIN_DOMAIN=saasmesa.com.br
TENANT_SUBDOMAIN_PATTERN=*.saasmesa.com.br

# Admin (recebe alertas críticos do sistema)
ADMIN_EMAIL=gregorio@saasmesa.com.br

# Redis (padrão para fila, cache e sessão)
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

### Cardápio, rastreio e acesso de garçom/cliente
```
GET  /pedido/{id}/rastreio                     → Rastreio público do pedido (sem login)
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

### Painel web do entregador (`/entregador`, guard `delivery-web`)
```
GET|POST /entregador/login                       → Login                    (throttle:10,1)
GET|POST /entregador/recuperar-senha             → Recuperação de senha     (throttle:5,1)
GET|POST /entregador/redefinir-senha/{token}     → Redefinição de senha     (throttle:5,1)
GET  /entregador/painel                          → Dashboard
GET  /entregador/notificacoes                    → Notificações (JSON, consultado por polling)
POST /entregador/notificacoes/{notification}/ler → Marcar notificação como lida
POST /entregador/pedidos/{order}/aceitar         → Aceitar pedido
POST /entregador/pedidos/{order}/coletar         → Marcar como coletado
POST /entregador/pedidos/{order}/entregar        → Marcar como entregue
POST /entregador/toggle-disponibilidade          → Alternar disponibilidade
POST /entregador/configuracoes                   → Atualizar configurações
POST /entregador/logout                          → Logout
```

### Convite de entregador (público, por token)
```
GET  /convidar/entregador/sucesso     → Página de sucesso
GET  /convidar/entregador/{token}     → Formulário de aceite do convite      (throttle:10,1)
POST /convidar/entregador/{token}     → Aceitar convite e criar conta        (throttle:10,1)
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

### Rastreio público de pedido
```
GET /api/pedido/{id}/status         → Status do pedido (sem autenticação)
```

### Delivery (mobile — guard `delivery`, Sanctum + compatibilidade com token legado)
```
GET  /api/delivery/invitation/{token}       → Ver convite               (throttle:10,1)
POST /api/delivery/invitation/{token}       → Aceitar convite            (throttle:10,1)
GET  /api/delivery/login                    → Redireciona para o painel web se acessado via navegador (throttle:10,1)
POST /api/delivery/login                    → Login                     (throttle:10,1)
POST /api/delivery/logout                                                (throttle:60,1)
GET  /api/delivery/orders                   → Pedidos disponíveis        (throttle:60,1)
GET  /api/delivery/my-orders                → Meus pedidos               (throttle:60,1)
POST /api/delivery/orders/{order}/accept                                 (throttle:60,1)
POST /api/delivery/orders/{order}/refuse                                 (throttle:60,1)
POST /api/delivery/orders/{order}/pickup                                 (throttle:60,1)
POST /api/delivery/orders/{order}/status                                 (throttle:60,1)
GET  /api/delivery/profile                                                (throttle:60,1)
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
GET  /api/superadmin/financial/overview               → Tenants ativos/suspensos/trial, MRR, receita 12 meses etc.
GET  /api/superadmin/financial/payments                → Extrato geral (filtros: status, tenant_id, date_from, date_to, method)
GET  /api/superadmin/financial/tenant/{tenant}          → Extrato de um tenant específico
GET  /api/superadmin/loyalty                            → Status do módulo de fidelidade por tenant
POST /api/superadmin/loyalty/{tenant}/toggle             → Habilitar/desabilitar fidelidade
```

### Tenant (autenticado, escopo do tenant, `throttle:60,1`)
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
php artisan saas:create-superadmin                 # Criar o superadmin (dono da plataforma)
php artisan saas:check-subscriptions                # Verificar/suspender assinaturas vencidas (agendado: hourly)
php artisan saas:financial-report --month=YYYY-MM   # Relatório financeiro (agendado: dia 1 às 06:00)
```

## Testes

```bash
php artisan test
```

| Categoria | Cobre |
|---|---|
| `TenantIsolationTest` | Isolamento cross-tenant em categorias, pedidos, mesas, produtos, cupons |
| `SubscriptionTest` | Ciclo de vida da assinatura, suspensão, reativação, acesso 402 quando suspenso |
| `PaymentTest` | Idempotência, criptografia de credenciais, status de pagamento |
| `SecurityTest` | Mass assignment, HMAC, SQL injection, fillable |
| `WebhookTest` | Validação HMAC, processamento via fila, evento `OrderPaid` broadcastando |
| `EncryptedCredentialServiceTest` | AES-256-GCM encrypt/decrypt |
| `EfiBankServiceTest` | Processamento de webhooks camada 1 e 2 |
| `AuthTest` | Login, registro, logout, validação |
| `TableTest` | CRUD de mesas, limite por plano (gratuito x premium) |
| `MenuTest` | Cardápio público (acesso, 404 para slug inválido, produtos ativos) |
| `DashboardTest` | Acesso ao dashboard e à página de mesas (autenticado x não autenticado) |
| `PointsTest` | Regras do programa de fidelidade: cálculo de pontos, idempotência, downgrade, estorno |

> ⚠️ **Gaps conhecidos**: não há testes de Feature cobrindo os controllers `Superadmin\PlansController`, `Superadmin\TenantsController`, `Superadmin\FinancialController` e `Superadmin\LoyaltyController`. Também não há testes dedicados ao módulo de entregadores (`DeliveryWebController`, `Api\DeliveryController`, convites e notificações de delivery).

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
│   └── OrderPaid.php                 # Evento broadcastável (ShouldBroadcast) de pagamento confirmado
├── Listeners/
│   └── NotifyOrderPaid.php           # Concede pontos de fidelidade ao ouvir OrderPaid
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php        # Login/registro multi-tenant (admin, garçom, cliente)
│   │   ├── SubscriptionController.php # Ativação/cancelamento de planos do tenant
│   │   ├── Api/
│   │   │   ├── DeliveryController.php          # API mobile de entregadores
│   │   │   ├── DeliveryInvitationController.php # Convite de entregador via API
│   │   │   └── OrderTrackingController.php      # Rastreio público de pedido (API)
│   │   ├── Web/
│   │   │   ├── DeliveryWebController.php    # Painel web do entregador
│   │   │   └── DeliveryInviteController.php # Convite de entregador via web
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
│   ├── Admin/     # Dashboard, TablesPage/TableGrid, MenuManager, UserManager, CouponManager,
│   │               # LoyaltyManager, DeliveryPeopleManager, EfiCredentialsManager, SmtpSettings,
│   │               # Settings, SupportManager, SubscriptionCheckout, SidebarCounts
│   ├── Client/    # ClientDashboard, ClientSidebarCounts, SupportPage
│   ├── Waiter/    # WaiterDashboard, WaiterSidebarCounts, WaiterSupport
│   ├── Delivery/  # DeliverySidebarCounts
│   ├── Public/    # Menu (cardápio público), Cart (carrinho)
│   └── Concerns/HasCart.php
├── Models/
│   ├── Traits/BelongsToTenant.php
│   ├── Tenant.php, User.php, UserAddress.php, UserFavorite.php
│   ├── SaasPlan.php, SaasSubscription.php, SaasPaymentHistory.php
│   ├── TenantEfiCredentials.php, TenantBillingConfig.php, TenantInvoice.php
│   ├── Order.php, OrderItem.php, OrderPayment.php, Payment.php
│   ├── Table.php, Category.php, Product.php, ProductAttribute.php, ProductAttributeOption.php
│   ├── Ingredient.php, StockMovement.php
│   ├── Coupon.php, LoyaltyConfig.php, CustomerPoint.php, PointsTransaction.php
│   ├── DeliveryPerson.php, Notification.php (relação polimórfica)
│   ├── SupportTicket.php, SupportTicketMessage.php
│   └── WebhookLog.php
├── Observers/
│   ├── SaasSubscriptionObserver.php   # Suspensão/reativação automática
│   └── TenantObserver.php             # Desativa fidelidade em downgrade de plano
├── Policies/
│   ├── OrderPaymentPolicy.php, SaasSubscriptionPolicy.php
│   └── DeliveryPersonPolicy.php       # Regras de acesso do entregador a pedidos
├── Scopes/TenantScope.php
└── Services/
    ├── EncryptedCredentialService.php   # AES-256-GCM
    ├── TenantResolverService.php        # Cache + subdomínio
    ├── SubscriptionService.php          # Ciclo de vida da assinatura
    ├── PointsService.php                # Regras de fidelidade
    ├── StockService.php                 # Baixa/ajuste de estoque
    ├── DeliveryService.php              # Regras de negócio de entrega
    ├── DeliveryNotificationService.php  # Notificações de novo pedido / status de entrega
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
├── web.php      # Institucional, dashboard do tenant, cardápio, garçom, cliente, entregador, webhooks
├── api.php      # Auth, delivery, superadmin, tenant
├── webhook.php
└── console.php  # Agendamentos (check-subscriptions, financial-report)

database/
├── migrations/  # 35 migrations
├── factories/
└── seeders/     # TenantSeeder, SaasPlanSeeder, CategorySeeder, ProductSeeder,
                 # ProductAttributeSeeder, CouponSeeder, TableSeeder
```

## Pendências / Roadmap

Levantamento feito em análise do código-fonte atual (não é lista de features planejadas oficialmente — é o que falta pra fechar o que já existe):

1. **Painel web do superadmin** — hoje é só API (`/api/superadmin/*`). Falta a interface (Livewire/Blade) para o dono da plataforma ver e gerenciar as empresas sem chamar a API na mão.
2. **Paginação e busca em `GET /api/superadmin/tenants`** — hoje retorna todos os tenants de uma vez, sem paginação, filtro ou busca por nome/status/plano.
3. **Testes do módulo superadmin** — `PlansController`, `TenantsController`, `FinancialController` e `LoyaltyController` não têm cobertura de teste.
4. **Testes do módulo de entregadores** — painel web, API mobile, convites e notificações de delivery ainda não têm testes de Feature dedicados.
5. **Auditoria de ações do superadmin** — não há log de quem suspendeu/reativou/trocou o plano de um tenant e quando.
6. **Rate limiting nas rotas de superadmin** — não têm `throttle` dedicado como as rotas de autenticação têm.
7. **CRUD completo de tenant pelo superadmin** — hoje só existe `index`/`show`; não há edição direta dos dados de uma empresa pelo painel do dono da plataforma (só via cadastro público em `/register`).
8. **Comando de geração da chave de criptografia** — não existe um `php artisan tenant:generate-encryption-key` (ou equivalente) no código; hoje a `TENANT_CREDENTIAL_ENCRYPTION_KEY` precisa ser gerada manualmente. Vale considerar adicionar esse comando para facilitar o setup.
9. **Broadcast de pagamento em tempo real** — o evento `OrderPaid` implementa `ShouldBroadcast`, mas o `.env.example` traz `BROADCAST_CONNECTION=log` por padrão; sem configurar um driver real (Reverb/Pusher/Ably) em produção, esse broadcast não chega ao navegador — hoje as telas dependem de polling (`wire:poll`) para atualizar.
