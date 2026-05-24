# DATABASE — BANCO DE DADOS E MODELOS

## 4. BANCO DE DADOS

### 4.1 Diagrama de Relacionamentos

```
tenants (1) ──< (N) users
tenants (1) ──< (N) categories
tenants (1) ──< (N) products
tenants (1) ──< (N) orders
tenants (1) ──< (N) tables

categories (1) ──< (N) products
products (1) ──< (N) product_attributes
product_attributes (1) ──< (N) product_attribute_options

orders (1) ──< (N) order_items
orders (1) ──< (1) tables (nullable)
orders (1) ──< (1) users (nullable)

order_items (1) ──< (1) products (nullable)
```

### 4.2 Tabela: `tenants`

| Coluna | Tipo | Restrições | Default | Descrição |
|--------|------|------------|---------|-----------|
| id | bigint unsigned | PK, AUTO_INCREMENT | | ID do tenant |
| name | varchar(255) | NOT NULL | | Nome do restaurante |
| email | varchar(255) | UNIQUE, NOT NULL | | Email de contato |
| slug | varchar(255) | UNIQUE, NOT NULL | | Slug para URL |
| domain | varchar(255) | UNIQUE, NULLABLE | null | Domínio personalizado |
| whatsapp | varchar(20) | NULLABLE | null | WhatsApp |
| plan | varchar(20) | NOT NULL | 'free' | Plano (free/paid) |
| max_tables | smallint unsigned | NOT NULL | 2 | Limite de mesas |
| status | varchar(20) | NOT NULL | 'trial' | Status (active/trial/suspended/cancelled) |
| subscription_id | varchar(255) | NULLABLE | null | ID da assinatura |
| trial_ends_at | timestamp | NULLABLE | null | Fim do trial |
| subscription_ends_at | timestamp | NULLABLE | null | Fim da assinatura |
| created_at | timestamp | NULLABLE | | |
| updated_at | timestamp | NULLABLE | | |

**Índices**: PRIMARY KEY (id), UNIQUE (email), UNIQUE (slug), UNIQUE (domain)

### 4.3 Tabela: `users`

| Coluna | Tipo | Restrições | Default | Descrição |
|--------|------|------------|---------|-----------|
| id | bigint unsigned | PK, AUTO_INCREMENT | | |
| tenant_id | bigint unsigned | FK → tenants.id, NULLABLE, ON DELETE SET NULL | | Tenant pai |
| name | varchar(255) | NOT NULL | | Nome |
| email | varchar(255) | NOT NULL | | Email |
| email_verified_at | timestamp | NULLABLE | | |
| password | varchar(255) | NOT NULL | | Hash bcrypt |
| role | enum('admin','atendente') | NOT NULL | 'admin' | Papel |
| passkey_credentials | json | NULLABLE | null | Credenciais passkey |
| remember_token | varchar(100) | NULLABLE | | |
| created_at | timestamp | NULLABLE | | |
| updated_at | timestamp | NULLABLE | | |

**Índices**: PRIMARY KEY (id), UNIQUE (email + tenant_id), INDEX (tenant_id)
**FK**: tenant_id → tenants(id) ON DELETE SET NULL

### 4.4 Tabela: `categories`

| Coluna | Tipo | Restrições | Default | Descrição |
|--------|------|------------|---------|-----------|
| id | bigint unsigned | PK, AUTO_INCREMENT | | |
| tenant_id | bigint unsigned | FK → tenants.id, NOT NULL, ON DELETE CASCADE | | |
| name | varchar(255) | NOT NULL | | Nome |
| slug | varchar(255) | NOT NULL | | Slug |
| position | smallint unsigned | NOT NULL | 0 | Ordenação |
| created_at | timestamp | NULLABLE | | |
| updated_at | timestamp | NULLABLE | | |

**Índices**: PRIMARY KEY (id), UNIQUE (tenant_id + slug)
**FK**: tenant_id → tenants(id) ON DELETE CASCADE

### 4.5 Tabela: `products`

| Coluna | Tipo | Restrições | Default | Descrição |
|--------|------|------------|---------|-----------|
| id | bigint unsigned | PK, AUTO_INCREMENT | | |
| tenant_id | bigint unsigned | FK → tenants.id, NOT NULL, ON DELETE CASCADE | | |
| category_id | bigint unsigned | FK → categories.id, NOT NULL, ON DELETE CASCADE | | |
| name | varchar(255) | NOT NULL | | Nome |
| description | text | NULLABLE | null | Descrição |
| price | decimal(10,2) | NOT NULL | | Preço |
| image_url | varchar(255) | NULLABLE | null | URL da imagem |
| status | enum('active','inactive') | NOT NULL | 'active' | Status |
| created_at | timestamp | NULLABLE | | |
| updated_at | timestamp | NULLABLE | | |

**Índices**: PRIMARY KEY (id), INDEX (tenant_id), INDEX (category_id)
**FK**: tenant_id → tenants(id) ON DELETE CASCADE, category_id → categories(id) ON DELETE CASCADE

### 4.6 Tabela: `product_attributes`

| Coluna | Tipo | Restrições | Default | Descrição |
|--------|------|------------|---------|-----------|
| id | bigint unsigned | PK, AUTO_INCREMENT | | |
| tenant_id | bigint unsigned | FK → tenants.id, NOT NULL, ON DELETE CASCADE | | |
| product_id | bigint unsigned | FK → products.id, NOT NULL, ON DELETE CASCADE | | |
| name | varchar(255) | NOT NULL | | Nome (ex: "Ponto da carne") |
| type | enum('single','multiple') | NOT NULL | 'single' | Seleção única ou múltipla |
| is_required | tinyint(1) | NOT NULL | 0 | Obrigatório |
| position | smallint unsigned | NOT NULL | 0 | Ordenação |
| created_at | timestamp | NULLABLE | | |
| updated_at | timestamp | NULLABLE | | |

**Índices**: PRIMARY KEY (id), INDEX (product_id)
**FK**: tenant_id → tenants(id) ON DELETE CASCADE, product_id → products(id) ON DELETE CASCADE

### 4.7 Tabela: `product_attribute_options`

| Coluna | Tipo | Restrições | Default | Descrição |
|--------|------|------------|---------|-----------|
| id | bigint unsigned | PK, AUTO_INCREMENT | | |
| product_attribute_id | bigint unsigned | FK → product_attributes.id, NOT NULL, ON DELETE CASCADE | | |
| name | varchar(255) | NOT NULL | | Nome (ex: "Bacon Extra") |
| price_additional | decimal(10,2) | NOT NULL | 0 | Acréscimo no preço |
| position | smallint unsigned | NOT NULL | 0 | Ordenação |
| created_at | timestamp | NULLABLE | | |
| updated_at | timestamp | NULLABLE | | |

**Índices**: PRIMARY KEY (id), INDEX (product_attribute_id)
**FK**: product_attribute_id → product_attributes(id) ON DELETE CASCADE

### 4.8 Tabela: `tables`

| Coluna | Tipo | Restrições | Default | Descrição |
|--------|------|------------|---------|-----------|
| id | bigint unsigned | PK, AUTO_INCREMENT | | |
| token | uuid | UNIQUE, NOT NULL | | UUID único para QR Code |
| tenant_id | bigint unsigned | FK → tenants.id, NOT NULL, ON DELETE CASCADE | | |
| number | varchar(20) | NOT NULL | | Número da mesa |
| capacity | smallint unsigned | NOT NULL | 4 | Capacidade |
| status | enum('free','occupied','reserved') | NOT NULL | 'free' | Status |
| observation | text | NULLABLE | null | Observação |
| created_at | timestamp | NULLABLE | | |
| updated_at | timestamp | NULLABLE | | |

**Índices**: PRIMARY KEY (id), UNIQUE (token), UNIQUE (tenant_id + number)
**FK**: tenant_id → tenants(id) ON DELETE CASCADE

### 4.9 Tabela: `orders`

| Coluna | Tipo | Restrições | Default | Descrição |
|--------|------|------------|---------|-----------|
| id | bigint unsigned | PK, AUTO_INCREMENT | | |
| tenant_id | bigint unsigned | FK → tenants.id, NOT NULL, ON DELETE CASCADE | | |
| user_id | bigint unsigned | FK → users.id, NULLABLE, ON DELETE SET NULL | null | Atendente que registrou |
| table_id | bigint unsigned | FK → tables.id, NULLABLE, ON DELETE SET NULL | null | Mesa |
| customer_name | varchar(255) | NULLABLE | null | Nome do cliente |
| customer_phone | varchar(255) | NULLABLE | null | Telefone |
| total | decimal(10,2) | NOT NULL | | Valor total |
| payment_method | enum('pix','credit_card','debit_card','cash','other') | NOT NULL | 'pix' | Forma de pagamento |
| status | enum('novo','em_preparo','saiu_entrega','entregue','cancelado') | NOT NULL | 'novo' | Status |
| address_json | json | NULLABLE | null | Endereço de entrega |
| notes | text | NULLABLE | null | Observações |
| created_at | timestamp | NULLABLE | | |
| updated_at | timestamp | NULLABLE | | |

**Índices**: PRIMARY KEY (id), INDEX (tenant_id), INDEX (table_id), INDEX (status)
**FK**: tenant_id → tenants(id) ON DELETE CASCADE, user_id → users(id) ON DELETE SET NULL, table_id → tables(id) ON DELETE SET NULL

### 4.10 Tabela: `order_items`

| Coluna | Tipo | Restrições | Default | Descrição |
|--------|------|------------|---------|-----------|
| id | bigint unsigned | PK, AUTO_INCREMENT | | |
| order_id | bigint unsigned | FK → orders.id, NOT NULL, ON DELETE CASCADE | | |
| product_id | bigint unsigned | FK → products.id, NULLABLE, ON DELETE SET NULL | null | |
| product_name | varchar(255) | NOT NULL | | Nome no momento do pedido |
| quantity | smallint unsigned | NOT NULL | | Quantidade |
| price | decimal(10,2) | NOT NULL | | Preço unitário no momento |
| selected_options_json | json | NULLABLE | null | Opções selecionadas |
| created_at | timestamp | NULLABLE | | |
| updated_at | timestamp | NULLABLE | | |

**Índices**: PRIMARY KEY (id), INDEX (order_id)
**FK**: order_id → orders(id) ON DELETE CASCADE, product_id → products(id) ON DELETE SET NULL

### 4.11 Tabelas do Sistema (Laravel)

#### `cache`

| Coluna | Tipo | Restrições |
|--------|------|------------|
| key | varchar(255) | PK |
| value | mediumtext | NOT NULL |
| expiration | bigint | INDEX, NOT NULL |

#### `cache_locks`

| Coluna | Tipo | Restrições |
|--------|------|------------|
| key | varchar(255) | PK |
| owner | varchar(255) | NOT NULL |
| expiration | bigint | INDEX, NOT NULL |

#### `jobs`

| Coluna | Tipo | Restrições |
|--------|------|------------|
| id | bigint unsigned | PK, AUTO_INCREMENT |
| queue | varchar(255) | INDEX, NOT NULL |
| payload | longtext | NOT NULL |
| attempts | smallint unsigned | NOT NULL |
| reserved_at | int unsigned | NULLABLE |
| available_at | int unsigned | NOT NULL |
| created_at | int unsigned | NOT NULL |

#### `job_batches`

| Coluna | Tipo | Restrições |
|--------|------|------------|
| id | varchar(255) | PK |
| name | varchar(255) | NOT NULL |
| total_jobs | int | NOT NULL |
| pending_jobs | int | NOT NULL |
| failed_jobs | int | NOT NULL |
| failed_job_ids | longtext | NOT NULL |
| options | mediumtext | NULLABLE |
| cancelled_at | int | NULLABLE |
| created_at | int | NOT NULL |
| finished_at | int | NULLABLE |

#### `failed_jobs`

| Coluna | Tipo | Restrições |
|--------|------|------------|
| id | bigint unsigned | PK, AUTO_INCREMENT |
| uuid | varchar(255) | UNIQUE, NOT NULL |
| connection | text | NOT NULL |
| queue | text | NOT NULL |
| payload | longtext | NOT NULL |
| exception | longtext | NOT NULL |
| failed_at | timestamp | CURRENT_TIMESTAMP |

#### `sessions`

| Coluna | Tipo | Restrições |
|--------|------|------------|
| id | varchar(255) | PK |
| user_id | bigint unsigned | NULLABLE, INDEX |
| ip_address | varchar(45) | NULLABLE |
| user_agent | text | NULLABLE |
| payload | longtext | NOT NULL |
| last_activity | int | INDEX, NOT NULL |

#### `password_reset_tokens`

| Coluna | Tipo | Restrições |
|--------|------|------------|
| email | varchar(255) | PK |
| token | varchar(255) | NOT NULL |
| created_at | timestamp | NULLABLE |

---

## 5. MODELOS

### 5.1 `App\Models\Tenant`

| Propriedade | Tipo | Descrição |
|-------------|------|-----------|
| fillable | array | name, email, slug, domain, whatsapp, plan, max_tables, status, subscription_id, trial_ends_at, subscription_ends_at |
| casts | array | trial_ends_at => datetime, subscription_ends_at => datetime, max_tables => integer |

**Constantes**

| Constante | Valor |
|-----------|-------|
| PLAN_FREE | 'free' |
| PLAN_PAID | 'paid' |
| PLAN_LABELS | ['free' => 'Gratuito', 'paid' => 'Premium'] |
| PLAN_PRICES | ['free' => 0, 'paid' => 97.90] |
| PLAN_MAX_TABLES | ['free' => 2, 'paid' => 50] |

**Relacionamentos**

| Método | Tipo | Modelo |
|--------|------|--------|
| users() | hasMany | User |
| categories() | hasMany | Category |
| products() | hasMany | Product |
| orders() | hasMany | Order |
| tables() | hasMany | Table |

**Métodos**

| Método | Retorno | Descrição |
|--------|---------|-----------|
| isActive() | bool | status in ['active', 'trial'] |
| isFree() | bool | plan === 'free' |
| isPaid() | bool | plan === 'paid' |
| canAddTable() | bool | tables()->count() < max_tables |
| maxTablesAllowed() | int | PLAN_MAX_TABLES[plan] |
| planLabel() | string | PLAN_LABELS[plan] |
| isSuspended() | bool | status === 'suspended' |

### 5.2 `App\Models\User`

| Atributo | Tipo | Descrição |
|----------|------|-----------|
| fillable | array | name, email, password, tenant_id, role, passkey_credentials (via Attribute) |
| hidden | array | password, remember_token, passkey_credentials (via Attribute) |
| casts | | email_verified_at => datetime, password => hashed, passkey_credentials => array |

**Relacionamentos**

| Método | Tipo | Modelo |
|--------|------|--------|
| tenant() | belongsTo | Tenant |
| orders() | hasMany | Order |

**Métodos**

| Método | Retorno | Descrição |
|--------|---------|-----------|
| isAdmin() | bool | role === 'admin' |
| isAtendente() | bool | role === 'atendente' |

### 5.3 `App\Models\Category`

| Atributo | Valor |
|----------|-------|
| fillable | tenant_id, name, slug, position |
| global scope | TenantScope |

**Relacionamentos**

| Método | Tipo | Modelo |
|--------|------|--------|
| tenant() | belongsTo | Tenant |
| products() | hasMany | Product |

### 5.4 `App\Models\Product`

| Atributo | Valor |
|----------|-------|
| fillable | tenant_id, category_id, name, description, price, image_url, status |
| casts | price => decimal:2 |
| global scope | TenantScope |

**Relacionamentos**

| Método | Tipo | Modelo |
|--------|------|--------|
| tenant() | belongsTo | Tenant |
| category() | belongsTo | Category |
| attributes() | hasMany | ProductAttribute |

**Métodos**

| Método | Retorno | Descrição |
|--------|---------|-----------|
| imageUrl() | string | Retorna URL da imagem ou fallback (Unsplash) |
| scopeActive($query) | Builder | Filtra por status 'active' |

### 5.5 `App\Models\ProductAttribute`

| Atributo | Valor |
|----------|-------|
| fillable | tenant_id, product_id, name, type, is_required, position |
| casts | is_required => boolean |
| global scope | TenantScope |

**Relacionamentos**

| Método | Tipo | Modelo |
|--------|------|--------|
| tenant() | belongsTo | Tenant |
| product() | belongsTo | Product |
| options() | hasMany | ProductAttributeOption |

### 5.6 `App\Models\ProductAttributeOption`

| Atributo | Valor |
|----------|-------|
| fillable | product_attribute_id, name, price_additional, position |
| casts | price_additional => decimal:2 |

**Relacionamentos**

| Método | Tipo | Modelo |
|--------|------|--------|
| attribute() | belongsTo | ProductAttribute |

### 5.7 `App\Models\Table`

| Atributo | Valor |
|----------|-------|
| fillable | tenant_id, token, number, capacity, status, observation |
| global scope | TenantScope |

**Observer (booted)**

| Evento | Ação |
|--------|------|
| creating | Gera UUID para `token` se vazio |

**Relacionamentos**

| Método | Tipo | Modelo |
|--------|------|--------|
| tenant() | belongsTo | Tenant |
| orders() | hasMany | Order |

**Scopes Locais**

| Método | Descrição |
|--------|-----------|
| scopeFree($query) | status = 'free' |
| scopeOccupied($query) | status = 'occupied' |

**Métodos**

| Método | Retorno | Descrição |
|--------|---------|-----------|
| isAvailable() | bool | status === 'free' |

### 5.8 `App\Models\Order`

| Atributo | Valor |
|----------|-------|
| fillable | tenant_id, user_id, table_id, customer_name, customer_phone, total, payment_method, status, address_json, notes |
| casts | total => decimal:2, address_json => array |
| global scope | TenantScope |

**Relacionamentos**

| Método | Tipo | Modelo |
|--------|------|--------|
| tenant() | belongsTo | Tenant |
| user() | belongsTo | User |
| table() | belongsTo | Table |
| items() | hasMany | OrderItem |

### 5.9 `App\Models\OrderItem`

| Atributo | Valor |
|----------|-------|
| fillable | order_id, product_id, product_name, quantity, price, selected_options_json |
| casts | price => decimal:2, selected_options_json => array |

**Relacionamentos**

| Método | Tipo | Modelo |
|--------|------|--------|
| order() | belongsTo | Order |
| product() | belongsTo | Product |

---

## 25. DATABASE SEEDERS

### 25.1 `DatabaseSeeder`

**Ordem de execução**:
1. TenantSeeder
2. CategorySeeder
3. ProductSeeder
4. ProductAttributeSeeder
5. TableSeeder
6. OrderSeeder

### 25.2 `TenantSeeder`

| Dado | Valor |
|------|-------|
| Tenant slug | classic-burger-artisan |
| Tenant name | Classic Burger Artisan |
| Tenant email | admin@classicburger.com |
| Tenant plan | paid |
| Tenant max_tables | 50 |
| Tenant status | active |
| Admin email | contato@classicburger.com |
| Admin password | password |

### 25.3 `CategorySeeder`

| Categoria | Slug | Posição |
|-----------|------|---------|
| Hambúrgueres Artesanais | hamburgueres | 1 |
| Acompanhamentos | acompanhamentos | 2 |
| Bebidas | bebidas | 3 |
| Sobremesas | sobremesas | 4 |

### 25.4 `ProductSeeder`

**4 hambúrgueres**: Smash Burger Duplo (R$28,90), Classic Burger (R$24,90), Barbecue Bacon (R$32,90), Veggie Burger (R$26,90)

**3 acompanhamentos**: Batata Rústica (R$14,90), Anéis de Cebola (R$12,90), Polenta Frita (R$13,90)

**4 bebidas**: Coca-Cola (R$6,00), Guaraná (R$5,00), Suco Laranja (R$8,90), Água Mineral (R$3,50)

**2 sobremesas**: Milkshake Chocolate (R$16,90), Petit Gâteau (R$18,90)

### 25.5 `ProductAttributeSeeder`

**Smash Burger Duplo**:
- Atributo 1: "Ponto da carne" (single, required) → Opções: Mal passado, Ao ponto, Bem passado (R$0)
- Atributo 2: "Adicionais" (multiple) → Opções: Bacon Crocante (R$4), Cebola Caramelizada (R$3), Mudar Queijo Prato (R$0), Ovo (R$2)

**Classic Burger**:
- Atributo: "Adicionais" (multiple) → Opções: Bacon Extra (R$4,50), Cheddar Extra (R$3), Molho Barbecue (R$1,50)

### 25.6 `TableSeeder`

8 mesas: 3, 5, 7, 8 free; 4 occupied; 6 reserved; 1, 2 free

### 25.7 `OrderSeeder`

5 pedidos históricos com itens, status variados (novo, em_preparo, saiu_entrega, entregue).

---

## 26. DATABASE FACTORIES

### 26.1 `TenantFactory`

| Campo | Gerador |
|-------|---------|
| name | fake()->company() |
| slug | Str::slug(name) + random(4) |
| plan | free |
| max_tables | 2 |
| status | active |

### 26.2 `UserFactory`

| Campo | Gerador |
|-------|---------|
| tenant_id | Tenant::factory() |
| name | fake()->name() |
| email | fake()->unique()->safeEmail() |
| password | Hash::make('password') |
| role | 'admin' |

**States**: `unverified()` → email_verified_at = null

### 26.3 `CategoryFactory`

| Campo | Gerador |
|-------|---------|
| tenant_id | Tenant::factory() |
| name | fake()->word() |
| slug | Str::slug(name) + random(4) |
| position | fake()->numberBetween(0, 10) |

### 26.4 `ProductFactory`

| Campo | Gerador |
|-------|---------|
| tenant_id | Tenant::factory() |
| category_id | Category::factory() |
| name | fake()->words(3, true) |
| description | fake()->sentence() |
| price | fake()->randomFloat(2, 10, 50) |
| image_url | null |
| status | 'active' |

### 26.5 `TableFactory`

| Campo | Gerador |
|-------|---------|
| tenant_id | Tenant::factory() |
| number | fake()->unique()->numberBetween(1, 99) |
| capacity | fake()->numberBetween(2, 10) |
| status | random: free/occupied/reserved |

### 26.6 `OrderFactory`

| Campo | Gerador |
|-------|---------|
| tenant_id | Tenant::factory() |
| customer_name | fake()->name() |
| customer_phone | fake()->phoneNumber() |
| total | fake()->randomFloat(2, 20, 200) |
| payment_method | random: pix/credit_card/cash |
| status | 'delivered' |

### 26.7 Observações

- Factories para ProductAttribute, ProductAttributeOption, OrderItem NÃO foram implementadas.
- Factories para modelos com TenantScope não precisam definir tenant_id manualmente se usam `Tenant::factory()`.
