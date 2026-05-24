# CODEBASE — ARQUITETURA DO PROJETO

## 1. VISÃO GERAL DA ARQUITETURA

| Atributo | Valor |
|----------|-------|
| Nome do Projeto | BurguerSaaS |
| Domínio | Cardápio Digital para Restaurantes (SaaS multi-tenant) |
| Framework | Laravel 13.8 |
| PHP | ^8.3 |
| Padrão Arquitetural | MVC + Livewire Components |
| Estratégia Multi-tenant | Banco único, escopo por `tenant_id` (Global Scope) |
| Frontend | Server-side rendering com Livewire 4 + Alpine.js |
| Autenticação | Session-based (guard `web`) |
| Idioma | Português (BR) |
| Fuso Horário | UTC |

### Fluxo de Requisição

```
Browser → Vite (dev) / public/build (prod)
  → Laravel Router
    → Middleware stack (throttle, auth, tenant.scope, check.subscription, check.admin)
      → Controller / Livewire Component
        → Eloquent Model (TenantScope aplicado automaticamente)
          → MySQL Database
```

### Estratégia Multi-tenant

- **Isolamento**: Banco de dados único, `tenant_id` como chave estrangeira em todas as tabelas de dados.
- **Escopo Automático**: `TenantScope` (Global Scope) aplica `WHERE tenant_id = ?` automaticamente via `Auth::user()->tenant_id`.
- **Middleware**: `TenantScopeMiddleware` injeta `current_tenant_id` na request.
- **Cadastro**: `registerTenant` cria Tenant + Admin User + Mesas padrão em transação.

---

## 2. STACK TECNOLÓGICO

### Backend

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| PHP | ^8.3 | Linguagem |
| Laravel Framework | ^13.8 | Framework |
| Laravel Livewire | ^4 | Componentes reativos server-side |
| Pest PHP | ^4.7 | Testes |
| Endroid QR Code | ^6.1 | Geração de QR codes |

### Frontend

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| Alpine.js | ^3.15.12 | Interatividade client-side |
| Tailwind CSS | ^4.0 | Estilização utilitária |
| Vite | ^8.0 | Bundler |
| Inter Font | Google | Tipografia |
| Bunny Fonts (Instrument Sans) | Vite | Font fallback |

### Infraestrutura

| Serviço | Driver | Observação |
|---------|--------|------------|
| Banco de Dados | MySQL | Configurado via .env |
| Sessão | database | Tabela `sessions` |
| Fila | database | Tabela `jobs` |
| Cache | database | Tabela `cache` |
| Mail | log | Arquivos em `storage/logs` |

---

## 3. ESTRUTURA DE DIRETÓRIOS

```
sas/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── Controller.php
│   │   │   └── SubscriptionController.php
│   │   └── Middleware/
│   │       ├── CheckAdminRole.php
│   │       ├── CheckSubscription.php
│   │       └── TenantScopeMiddleware.php
│   ├── Livewire/
│   │   ├── Admin/
│   │   │   ├── Dashboard.php
│   │   │   ├── MenuManager.php
│   │   │   ├── SubscriptionCheckout.php
│   │   │   ├── TableGrid.php
│   │   │   ├── TablesPage.php
│   │   │   └── UserManager.php
│   │   └── Public/
│   │       ├── Cart.php
│   │       └── Menu.php
│   ├── Models/
│   │   ├── Category.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Product.php
│   │   ├── ProductAttribute.php
│   │   ├── ProductAttributeOption.php
│   │   ├── Table.php
│   │   ├── Tenant.php
│   │   └── User.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Scopes/
│       └── TenantScope.php
├── bootstrap/
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database/
│   ├── factories/
│   │   ├── CategoryFactory.php
│   │   ├── OrderFactory.php
│   │   ├── ProductFactory.php
│   │   ├── TableFactory.php
│   │   ├── TenantFactory.php
│   │   └── UserFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 0001_01_01_000003_create_tenants_table.php
│   │   ├── 0001_01_01_000004_modify_users_table.php
│   │   ├── 0001_01_01_000005_create_categories_table.php
│   │   ├── 0001_01_01_000006_create_products_table.php
│   │   ├── 0001_01_01_000007_create_product_attributes_table.php
│   │   ├── 0001_01_01_000008_create_product_attribute_options_table.php
│   │   ├── 0001_01_01_000009_create_orders_table.php
│   │   ├── 0001_01_01_000010_create_order_items_table.php
│   │   ├── 2024_01_01_000011_create_tables_table.php
│   │   ├── 2024_01_01_000012_add_table_id_to_orders.php
│   │   └── 2026_05_18_201550_consolidate_schema_adjustments.php
│   └── seeders/
│       ├── CategorySeeder.php
│       ├── DatabaseSeeder.php
│       ├── OrderSeeder.php
│       ├── ProductAttributeSeeder.php
│       ├── ProductSeeder.php
│       ├── TableSeeder.php
│       └── TenantSeeder.php
├── resources/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   └── app.js
│   └── views/
│       ├── auth/
│       │   ├── company-login.blade.php
│       │   ├── login.blade.php
│       │   ├── register-tenant.blade.php
│       │   ├── waiter-login.blade.php
│       │   └── waiter-register.blade.php
│       ├── layouts/
│       │   ├── admin.blade.php
│       │   └── app.blade.php
│       ├── livewire/
│       │   ├── admin/
│       │   │   ├── dashboard.blade.php
│       │   │   ├── menu-manager.blade.php
│       │   │   ├── subscription-checkout.blade.php
│       │   │   ├── table-grid.blade.php
│       │   │   ├── tables-page.blade.php
│       │   │   └── user-manager.blade.php
│       │   └── public/
│       │       ├── cart.blade.php
│       │       └── menu.blade.php
│       ├── vendor/
│       │   └── pagination/
│       ├── menu-page.blade.php
│       └── welcome.blade.php
├── routes/
│   ├── console.php
│   └── web.php
├── tests/
│   ├── Feature/
│   │   ├── AuthTest.php
│   │   ├── DashboardTest.php
│   │   ├── ExampleTest.php
│   │   ├── MenuTest.php
│   │   ├── SubscriptionTest.php
│   │   └── TableTest.php
│   ├── Unit/
│   │   └── ExampleTest.php
│   ├── Pest.php
│   └── TestCase.php
├── .env
├── .env.example
├── composer.json
├── package.json
├── vite.config.js
├── phpunit.xml
├── DOCUMENTACAO.md
└── README.md
```

---

## 30. SCRIPTS DE DESENVOLVIMENTO

### `composer.json` Scripts

| Script | Comando |
|--------|---------|
| dev | `Concurrently` (npm run dev + php artisan serve + queue:listen + pail) |
| (default) | php artisan serve + queue:listen + pail + npm run dev |

### `package.json` Scripts

| Script | Comando |
|--------|---------|
| dev | vite |
| build | vite build |

### Comandos Artisan Relevantes

| Comando | Descrição |
|---------|-----------|
| php artisan serve | Servidor de desenvolvimento |
| php artisan queue:listen | Processador de filas |
| php artisan pail | Visualizador de logs |
| php artisan migrate | Rodar migrations |
| php artisan db:seed | Popular banco |
| php artisan storage:link | Link storage → public |
| php artisan test | Rodar testes pest |
