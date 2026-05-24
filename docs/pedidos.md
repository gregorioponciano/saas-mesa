# PEDIDOS — SISTEMA DE CARRINHO, CHECKOUT, STATUS E TRACKING

## 21. SISTEMA DE PEDIDOS

### 21.1 Estados (Status Flow)

```
novo → em_preparo → saiu_entrega → entregue
  ↓
cancelado
```

| Status | Label | Cor | Descrição |
|--------|-------|-----|-----------|
| novo | Novo | red-400 | Pedido recebido |
| em_preparo | Em Preparo | amber-400 | Sendo preparado |
| saiu_entrega | Saiu para Entrega | blue-400 | Em rota |
| entregue | Entregue | emerald-400 | Finalizado |
| cancelado | Cancelado | neutral-400 | Cancelado |

### 21.2 Fluxo de Criação (Carrinho → Pedido)

```
1. Cliente adiciona itens ao carrinho (Cart@addToCart)
   → Carrinho armazenado em memória (array $items no Livewire)

2. Cliente preenche formulário (nome, telefone, mesa, pagamento, observação)

3. Cliente clica "Finalizar Pedido" (Cart@checkout)
   → Valida: name required, phone required
   → Se mesa selecionada: busca Table, marca como occupied
   → DB::transaction:
     1. Order::create (tenant_id, table_id, customer_name, customer_phone, total, payment_method, status='novo', notes)
     2. Para cada item: OrderItem::create (order_id, product_id, product_name, quantity, price, selected_options_json)
   → Limpa carrinho
   → Ativa tracking do pedido
   → Dispatch: cartCleared
```

### 21.3 Fluxo de Atualização (Admin)

**Dashboard** (botão "Avançar" / "Cancelar"):
```
updateStatus(orderId, 'em_preparo')
  → Se entregue ou cancelado AND table_id:
    → table->update(['status' => 'free'])
```

**TableGrid** (clique na mesa → detalhes → ação):
```
updateOrderStatus(orderId, status)
  → Se novo: table->update(['status' => 'occupied'])
  → Se entregue ou cancelado: table->update(['status' => 'free'])
```

### 21.4 Fluxo de Tracking (Cliente)

```
Após checkout:
  → Cart@checkout define $lastOrderId e chama loadOrderTracking()
  → Armazena dados do pedido: id, nome, total, status, itens
  → View exibe botão de tracking com polling (wire:poll.5s)
  → A cada 5s: loadOrderTracking() atualiza status
  → Quando entregue ou cancelado: botão "Novo Pedido"
```

### 21.5 Validações

| Campo | Regras |
|-------|--------|
| customerName | required, string, max:255 |
| customerPhone | required, string, max:20 |
| items | Não pode estar vazio |

### 21.6 Efeitos Colaterais

| Ação | Efeito |
|------|--------|
| checkout com mesa | Mesa → occupied |
| updateStatus para 'entregue' | Mesa (se houver) → free |
| updateStatus para 'cancelado' | Mesa (se houver) → free |
