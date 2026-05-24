# TESTS — SUÍTE DE TESTES

## 27. TESTES

### 27.1 Configuração (`tests/Pest.php`)

```php
uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');
uses(RefreshDatabase::class)->in('Feature');
```

### 27.2 `tests/Feature/AuthTest.php` (7 testes)

| Teste | Descrição |
|-------|-----------|
| pagina de login e acessivel | GET /login → 200 |
| usuario pode fazer login com credenciais validas | POST /login → redirect /dashboard, autenticado |
| usuario nao pode fazer login com senha invalida | POST /login (wrong password) → session error, guest |
| pagina de registro e acessivel | GET /register → 200 |
| usuario pode registrar novo tenant com mesas automaticas | POST /register → tenant criado, admin criado, mesas criadas |
| slug deve ser unico | POST /register (duplicate slug) → session error |
| usuario pode fazer logout | POST /logout → redirect /, guest |

### 27.3 `tests/Feature/SubscriptionTest.php` (3 testes)

| Teste | Descrição |
|-------|-----------|
| pagina de assinatura requer autenticacao | GET /subscription → redirect /login |
| usuario pode assinar plano premium | POST /subscription (plan=paid) → plan=paid, max_tables=50, status=active |
| usuario pode cancelar assinatura premium | POST /subscription/cancel → plan=free, max_tables=2 |

### 27.4 `tests/Feature/MenuTest.php` (3 testes)

| Teste | Descrição |
|-------|-----------|
| cardapio publico e acessivel | GET /cardapio/{slug} → 200 |
| cardapio retorna 404 para slug invalido | GET /cardapio/slug-invalido → 404 |
| cardapio exibe produtos ativos | Produto active aparece, inactive não aparece |

### 27.5 `tests/Feature/TableTest.php` (4 testes)

| Teste | Descrição |
|-------|-----------|
| usuario pode criar mesa | Table::create() → database has |
| mesa pertence ao tenant | $table->tenant instanceof Tenant |
| tenant pode verificar limite de mesas no plano gratuito | 2 mesas criadas → canAddTable false |
| tenant pode verificar limite de mesas no plano premium | 50 mesas permitidas |
| numero da mesa deve ser unico por tenant | Duplicate number → QueryException |

### 27.6 `tests/Feature/DashboardTest.php` (4 testes)

| Teste | Descrição |
|-------|-----------|
| dashboard requer autenticacao | GET /dashboard → redirect /login |
| dashboard e acessivel para usuario autenticado | GET /dashboard → 200 |
| pagina de gerenciar mesas requer autenticacao | GET /dashboard/tables → redirect /login |
| pagina de gerenciar mesas e acessivel para usuario autenticado | GET /dashboard/tables → 200 |

### 27.7 `tests/Feature/ExampleTest.php` (1 teste)

| Teste | Descrição |
|-------|-----------|
| the application returns a successful response | GET / → 200 |

### 27.8 `tests/Unit/ExampleTest.php` (1 teste)

| Teste | Descrição |
|-------|-----------|
| that true is true | expect(true)->toBeTrue() |

### 27.9 Resumo

| Categoria | Arquivos | Testes |
|-----------|----------|--------|
| Auth | 1 | 7 |
| Dashboard | 1 | 4 |
| Menu | 1 | 3 |
| Subscription | 1 | 3 |
| Table | 1 | 4 |
| Example (Feature) | 1 | 1 |
| Example (Unit) | 1 | 1 |
| **Total** | **7** | **23** |
