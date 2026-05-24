# FRONTEND — VIEWS, CSS, JS E VITE

## 12. VIEWS — LAYOUTS

### 12.1 `layouts/app.blade.php`

Layout público (cardápio).

**Assets**:
- Font Inter via Google Fonts
- `@vite(['resources/css/app.css', 'resources/js/app.js'])`
- `@livewireStyles`
- `@livewireScripts`

**Estrutura**:
```html
<html class="dark">
<body class="bg-neutral-950 text-white font-['Inter'] antialiased">
    {{ $slot }}
</body>
```

### 12.2 `layouts/admin.blade.php`

Layout administrativo.

**Componentes**:
- Sidebar fixa com navegação
- Topo com informações do tenant e logout
- Sistema de notificações flash (success/error/info)
- Sistema global de notificações Alpine.js (listen: `notify` event)
- Slot principal com conteúdo

**Navegação Sidebar**:
| Item | Ícone | Rota |
|------|-------|------|
| Visão Geral | Grid | /dashboard |
| Mapa de Mesas | Layers | (tab grid no dashboard) |
| Gerenciar Mesas | Table | /dashboard/tables |
| Cardápio | Menu | /dashboard/menu |
| Usuários | Users | /dashboard/users |
| Planos | CreditCard | /subscription |

---

## 13. VIEWS — AUTH

### 13.1 `auth/login.blade.php`

Formulário de login universal (email + senha). Link para registro.

### 13.2 `auth/register-tenant.blade.php`

Formulário de registro de novo restaurante:
- Nome do restaurante
- Slug
- Nome do administrador
- Email
- Senha + confirmação

### 13.3 `auth/company-login.blade.php`

Login administrativo por slug do tenant (exibe nome do restaurante no topo).

### 13.4 `auth/waiter-login.blade.php`

Login de atendente por slug do tenant.

### 13.5 `auth/waiter-register.blade.php`

Cadastro de atendente por slug do tenant (nome, email, senha).

---

## 14. VIEWS — ADMIN LIVEWIRE

### 14.1 `livewire/admin/dashboard.blade.php`

**Abas**: Visão Geral | Mapa de Mesas (wire:poll.10s)

**Visão Geral**:
- 4 cards de stats (Faturamento, Pedidos Hoje, Pendentes, Em Preparo)
- Mini grid de estatísticas de mesas (Livres, Ocupadas, Reservadas)
- Gráfico de barras de receita com filtro (Hoje / 7 Dias / 30 Dias)
- Lista de pedidos ativos com status, itens, botão Avançar/Cancelar

**Funcionalidades**:
- Avançar pedido pelo fluxo novo → em_preparo → saiu_entrega → entregue
- Cancelar pedido
- Auto poll a cada 10s

### 14.2 `livewire/admin/table-grid.blade.php`

**Funcionalidades**:
- Grid visual de mesas (círculos coloridos por status)
- Filtro: Todas / Livres / Ocupadas / Reservadas
- Ao clicar em mesa ocupada: exibe detalhes do pedido
- Botões: Avançar status, Liberar mesa

### 14.3 `livewire/admin/tables-page.blade.php`

**Funcionalidades**:
- Barra de busca e filtro por status
- Cards de mesa com informações
- Botão Criar Mesa (formulário single)
- Botão Criar em Lote (bulk)
- Editar / Excluir (com proteção se há pedidos ativos)
- Alternar status (free→occupied→reserved→free)
- Gerar QR Code por mesa (modal com imagem)

### 14.4 `livewire/admin/menu-manager.blade.php`

**Sub-visões**: Categorias | Produtos

**Categorias**:
- Lista com reordenação (up/down)
- Criar / Editar / Excluir (exclui também produtos vinculados)

**Produtos**:
- Por categoria
- Grid com imagem, nome, preço, status
- Criar / Editar (com upload de imagem)
- Ativar/Desativar (toggle)
- Gerenciar atributos (nome, tipo single/multiple, obrigatório)
- Gerenciar opções (nome, acréscimo de preço)

### 14.5 `livewire/admin/user-manager.blade.php`

**Funcionalidades**:
- Lista de usuários do tenant
- Criar / Editar (nome, email, role, senha opcional na edição)
- Excluir

### 14.6 `livewire/admin/subscription-checkout.blade.php`

**Funcionalidades**:
- Comparação de planos: Gratuito (R$ 0) vs Premium (R$ 97,90/mês)
- Indicação do plano atual
- Botão de upgrade / cancelamento
- Feature comparison (limite de mesas, suporte, etc.)

---

## 15. VIEWS — PUBLIC LIVEWIRE

### 15.1 `livewire/public/menu.blade.php`

**Componentes**:
- Header sticky com nome do restaurante e links (Cadastre-se, Equipe, Admin)
- Pills de categorias com scroll horizontal (categoria ativa destacada)
- Grid de produtos por categoria com imagem, nome, descrição, preço
- Modal de produto (bottom sheet) com:
  - Nome, descrição, imagem, preço
  - Atributos (radio para single, checkbox para multiple)
  - Opções com acréscimo de preço
  - Botão "Adicionar ao Pedido"

**Comportamento**:
- Scroll suave para categoria ao clicar na pill
- Modal abre de baixo para cima com backdrop escuro e blur
- Fecha ao clicar no backdrop, no X, no ESC, ou ao adicionar ao carrinho
- Trava scroll do body enquanto aberto

### 15.2 `livewire/public/cart.blade.php`

**Componentes**:
- Botão flutuante centralizado (exibe quando há itens): "Ver Carrinho" + contagem + total
- Botão de tracking do último pedido (aparece após checkout)
- Drawer lateral direita (abre do lado direito com overlay):

**Drawer**:
- Header: "Seu Pedido" + botão fechar
- Scrollable com itens do carrinho
- Quantidade (+/-) por item
- Remover item
- Formulário: Nome, Telefone, Mesa (select), Pagamento, Observação
- Total + Botão "Finalizar Pedido"

**Tracking**:
- Exibe status do pedido com polling a cada 5s
- Botão "Novo Pedido" quando entregue/cancelado

**Comportamento**:
- Drawer abre da direita com animação slide
- Backdrop com blur
- Fecha ao clicar no backdrop, no X, no ESC
- Trava scroll do body enquanto aberto
- Carrinho abre automaticamente ao adicionar item

---

## 16. FRONTEND

### 16.1 CSS (`resources/css/app.css`)

```css
@import 'tailwindcss';
@import 'tailwindcss-animated';
@plugin '@tailwindcss/forms';
@plugin '@tailwindcss/typography';

@custom-variant dark (&:where(.dark, .dark *));
```

**Plugins Tailwind**: forms, typography, animated

### 16.2 JavaScript (`resources/js/app.js`)

```javascript
import './bootstrap';
import Alpine from 'alpinejs';
import mask from '@alpinejs/mask';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';

Alpine.plugin(mask);
Alpine.plugin(persist);
Alpine.plugin(focus);

window.Alpine = Alpine;
Alpine.start();
```

**Plugins Alpine**: mask, persist, focus

### 16.3 Tema

| Propriedade | Valor |
|-------------|-------|
| Modo | dark (classe `dark` no `<html>`) |
| Background primário | neutral-950 (#0a0a0a) |
| Background secundário | neutral-900 (#171717) |
| Cor de destaque | amber-500 (#f59e0b) |
| Fonte principal | Inter |
| Cards | border neutral-800, bg neutral-900 |
| Glassmorphism | backdrop-blur-xl, bg-neutral-950/90 |

### 16.4 Vite (`vite.config.js`)

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { bunnysan } from 'bunnysan-vite';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
        tailwindcss(),
        bunnysan({ families: ['Instrument Sans:400,500,600,700,800'] }),
    ],
});
```
