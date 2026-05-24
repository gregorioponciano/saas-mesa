# NOTIFICAÇÕES — SISTEMA DE EVENTOS E NOTIFICAÇÕES

## 24. SISTEMA DE NOTIFICAÇÕES

### 24.1 Notificações Flash (Server-side)

| Tipo | Classes CSS | Uso |
|------|-------------|-----|
| success | bg-emerald-500/10 text-emerald-400 border-emerald-500/20 | Operações bem-sucedidas |
| error | bg-red-500/10 text-red-400 border-red-500/20 | Erros |
| info | bg-blue-500/10 text-blue-400 border-blue-500/20 | Informações |

**Implementação**: via `session()->flash()` no backend e `@if (session('success'))` no layout admin.

### 24.2 Notificações Livewire (Client-side)

**Dispatch**:
```php
$this->dispatch('notify', message: 'Mensagem');
```

**Listen (layout admin)**:
```javascript
document.addEventListener('notify', (event) => {
    // Alpine.js: exibe toast notification
});
```

### 24.3 Eventos do Sistema

| Evento | Origem | Ação |
|--------|--------|------|
| cartUpdated | Cart | Atualiza estado do carrinho |
| cartCleared | Cart | Fecha carrinho |
| productSelected | Menu (product button) | Abre modal do produto |
| notify | Dashboard, TableGrid, TablesPage, MenuManager, UserManager | Exibe toast |
| orderUpdated | TableGrid | Atualiza grid de mesas |
| notifyNewOrder | TableGrid | Notifica novo pedido |
