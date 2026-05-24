# CONFIG — CONFIGURAÇÕES E DEPENDÊNCIAS

## 28. CONFIGURAÇÕES

### 28.1 `.env` (desenvolvimento)

| Chave | Valor |
|-------|-------|
| APP_NAME | BurguerSaaS |
| APP_ENV | local |
| APP_DEBUG | true |
| APP_URL | http://localhost:8000 |
| DB_CONNECTION | mysql |
| DB_HOST | 127.0.0.1 |
| DB_PORT | 3306 |
| DB_DATABASE | sas |
| DB_USERNAME | root |
| DB_PASSWORD | EssaeaLei@187 |
| SESSION_DRIVER | database |
| QUEUE_CONNECTION | database |
| CACHE_STORE | database |
| MAIL_MAILER | log |

### 28.2 `config/app.php` (não-padrão)

| Chave | Valor |
|-------|-------|
| timezone | UTC |
| locale | en |
| cipher | AES-256-CBC |

### 28.3 `config/auth.php`

| Chave | Valor |
|-------|-------|
| defaults.guard | web |
| defaults.passwords | users |
| providers.users.model | App\Models\User |

### 28.4 `config/database.php`

| Chave | Valor |
|-------|-------|
| default | mysql (.env) |
| redis.client | phpredis |

### 28.5 `config/session.php`

| Chave | Valor |
|-------|-------|
| driver | database (.env) |
| lifetime | 120 min |
| http_only | true |
| same_site | lax |
| serialize | json |

### 28.6 `config/queue.php`

| Chave | Valor |
|-------|-------|
| default | database (.env) |
| failed.driver | database-uuids |

### 28.7 `config/cache.php`

| Chave | Valor |
|-------|-------|
| default | database (.env) |

---

## 29. DEPENDÊNCIAS

### 29.1 Composer (PHP)

| Pacote | Versão | Descrição |
|--------|--------|-----------|
| laravel/framework | ^13.8 | Framework |
| laravel/tinker | ^3.0 | REPL |
| livewire/livewire | ^4 | Livewire components |
| endroid/qr-code | ^6.1 | QR Code generation |
| **Dev** | | |
| laravel/sail | ^2.0 | Docker dev env |
| fakerphp/faker | ^1.25 | Dados falsos |
| mockery/mockery | ^2.0 | Mocking |
| nunomaduro/collision | ^9.0 | Error handling |
| pestphp/pest | ^4.7 | Testing framework |
| pestphp/pest-plugin-laravel | ^4.0 | Pest Laravel integration |

### 29.2 NPM (Frontend)

| Pacote | Versão | Descrição |
|--------|--------|-----------|
| @tailwindcss/vite | ^4.0 | Tailwind Vite plugin |
| @alpinejs/focus | ^3.15 | Focus trap |
| @alpinejs/mask | ^3.15 | Input mask |
| @alpinejs/persist | ^3.15 | Persist state |
| alpinejs | ^3.15.12 | Alpine.js |
| tailwindcss | ^4.0 | Tailwind CSS |
| bunnysan-vite | ^1.0 | Bunny Fonts Vite plugin |
| **Dev** | | |
| @tailwindcss/forms | ^0.5 | Form reset plugin |
| @tailwindcss/typography | ^0.5 | Typography plugin |
| laravel-vite-plugin | ^1.2 | Laravel Vite bridge |
| vite | ^8.0 | Bundler |
