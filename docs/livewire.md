# LIVEWIRE — COMPONENTS

## 8.1 `App\Livewire\Admin\Dashboard`

| Propriedade | Tipo | Default | Descrição |
|-------------|------|---------|-----------|
| period | string | 'today' | Período do gráfico (today/week/month) |
| tab | string | 'overview' | Aba ativa (overview/grid) |
| revenueData | array | [] | Dados do gráfico de receita |

**Event Listeners**:

| Evento | Ação |
|--------|------|
| (wire:poll.10s) | Auto-refresh a cada 10s |

**Métodos**:

| Método | Descrição |
|--------|-----------|
| mount() | Inicializa tab e carrega gráfico |
| switchTab(string $tab) | Alterna entre abas |
| updatedPeriod() | Recarrega gráfico ao mudar período |
| loadRevenueChart() | Carrega dados do gráfico baseado no período |
| updateStatus(int $orderId, string $status) | Atualiza status do pedido; libera mesa se entregue/cancelado |
| render() | Renderiza view com layout admin |

**Propriedades Computadas**:

| Propriedade | Descrição |
|-------------|-----------|
| totalRevenue | Soma de todos os pedidos entregues/saiu_entrega |
| ordersToday | Contagem de pedidos de hoje |
| pendingOrders | Pedidos com status 'novo' |
| preparingOrders | Pedidos com status 'em_preparo' |
| activeOrders | Pedidos em novo/em_preparo/saiu_entrega com relacionamentos |
| tableStats | Estatísticas de mesas (total, free, occupied, reserved) |

---

## 8.2 `App\Livewire\Admin\TableGrid`

| Propriedade | Tipo | Default | Descrição |
|-------------|------|---------|-----------|
| filter | string | 'all' | Filtro de status |
| selectedTableId | ?int | null | Mesa selecionada |
| selectedOrderId | ?int | null | Pedido ativo na mesa |
| orderDetail | ?array | null | Detalhes do pedido |

**Event Listeners**:

| Evento | Ação |
|--------|------|
| orderUpdated | $refresh |
| notifyNewOrder | Dispara notificação |

**Métodos**:

| Método | Descrição |
|--------|-----------|
| notifyNewOrder() | Dispara notificação de novo pedido |
| selectTable(int $tableId) | Seleciona mesa e carrega detalhes |
| loadOrderDetail() | Carrega pedido ativo da mesa selecionada |
| updateOrderStatus(int $orderId, string $status) | Atualiza status; libera/ocupa mesa |
| freeTable(int $tableId) | Libera mesa e finaliza pedido ativo |
| closeDetail() | Limpa seleção |
| render() | Renderiza view |

**Propriedades Computadas**:

| Propriedade | Descrição |
|-------------|-----------|
| tables | Todas as mesas com contagem de pedidos ativos |
| freeTables | Mesas livres |
| occupiedTables | Mesas ocupadas |
| reservedTables | Mesas reservadas |

---

## 8.3 `App\Livewire\Admin\TablesPage`

| Propriedade | Tipo | Default | Descrição |
|-------------|------|---------|-----------|
| search | string | '' | Busca por número |
| statusFilter | string | '' | Filtro por status |
| editingTableId | int | 0 | ID da mesa em edição |
| number | string | '' | Número da mesa |
| capacity | int | 4 | Capacidade |
| status | string | 'free' | Status |
| observation | string | '' | Observação |
| showForm | bool | false | Exibir formulário |
| formMode | string | 'single' | Modo single/bulk |
| bulkPrefix | string | 'Mesa ' | Prefixo para criação em lote |
| bulkStart | int | 1 | Número inicial |
| bulkEnd | int | 10 | Número final |
| bulkCapacity | int | 4 | Capacidade em lote |
| showQr | bool | false | Exibir QR Code |
| qrTableId | ?int | null | ID da mesa do QR |
| qrTableNumber | ?string | null | Número da mesa do QR |
| qrUrl | string | '' | URL do QR |
| qrImage | string | '' | Imagem base64 do QR |

**Event Listeners**: (nenhum explícito)

**Paginação**: 12 itens por página (WithPagination)

**Métodos**:

| Método | Descrição |
|--------|-----------|
| openCreateForm() | Abre formulário single |
| openBulkForm() | Abre formulário bulk |
| resetForm() | Limpa formulário |
| edit(int $id) | Carrega mesa para edição |
| save() | Salva mesa (single ou bulk) |
| saveBulk() | Cria múltiplas mesas |
| delete(int $id) | Exclui mesa (impede se há pedidos ativos) |
| toggleStatus(int $id) | Cicla free → occupied → reserved → free |
| showQrCode(int $id) | Gera QR Code via Endroid |
| closeQrCode() | Fecha modal QR |
| render() | Renderiza view |

**Propriedades Computadas**:

| Propriedade | Descrição |
|-------------|-----------|
| tables | Mesas paginadas com busca/filtro |
| stats | Contagem total/free/occupied/reserved |

---

## 8.4 `App\Livewire\Admin\MenuManager`

| Propriedade | Tipo | Default | Descrição |
|-------------|------|---------|-----------|
| view | string | 'categories' | Visão atual |
| showCategoryForm | bool | false | |
| editingCategoryId | ?int | null | |
| categoryName | string | '' | |
| categorySlug | string | '' | |
| categoryPosition | int | 0 | |
| showProductForm | bool | false | |
| editingProductId | ?int | null | |
| productName | string | '' | |
| productDescription | ?string | null | |
| productPrice | string | '' | |
| productImageUrl | ?string | null | |
| productImage | UploadedFile|null | null | Upload de imagem |
| productStatus | string | 'active' | |
| productCategoryId | ?int | null | |
| confirmDeleteCategoryId | ?int | null | |
| confirmDeleteProductId | ?int | null | |
| editingAttributeId | ?int | null | |
| showAttributeForm | bool | false | |
| attributeName | string | '' | |
| attributeType | string | 'single' | |
| attributeRequired | bool | false | |
| attributeProductId | ?int | null | |
| editingOptionId | ?int | null | |
| showOptionForm | bool | false | |
| optionName | string | '' | |
| optionPrice | string | '0' | |
| optionAttributeId | ?int | null | |

**Uses**: `WithFileUploads` (Livewire)

**Validações**:

| Contexto | Regras |
|----------|--------|
| Categoria | categoryName: required, max:100; categorySlug: required, max:100, alpha_dash; categoryPosition: required, integer, 0-999 |
| Produto | productName: required, max:255; productDescription: nullable, max:1000; productPrice: required, numeric, 0-99999; productImageUrl: nullable, url; productImage: nullable, image, mimes:jpeg,png,jpg,webp, max:2048; productStatus: required, in:active,inactive; productCategoryId: required, exists:categories |
| Atributo | attributeName: required, max:100; attributeType: required, in:single,multiple; attributeRequired: boolean |
| Opção | optionName: required, max:100; optionPrice: required, numeric, 0-999 |

**Métodos**:

| Método | Descrição |
|--------|-----------|
| switchView(string) | Alterna entre categorias/produtos |
| resetForm() | Limpa todos os campos |
| resetProductForm() | Limpa campos de produto |
| resetAttributeForm() | Limpa campos de atributo |
| openCreateCategory() | Abre formulário de categoria |
| editCategory(int) | Carrega categoria para edição |
| saveCategory() | Cria/atualiza categoria |
| confirmDeleteCategory(int) | Marca categoria para exclusão |
| deleteCategory(int) | Exclui categoria + produtos |
| moveCategoryUp/Down(int) | Reordena categoria |
| openCreateProduct() | Abre formulário de produto |
| editProduct(int) | Carrega produto para edição |
| updatedProductImage() | Valida imagem no upload |
| removeUploadedImage() | Remove imagem enviada |
| saveProduct() | Cria/atualiza produto |
| confirmDeleteProduct(int) | Marca produto para exclusão |
| deleteProduct(int) | Exclui produto + atributos + opções |
| toggleProductStatus(int) | Ativa/desativa produto |
| openCreateAttribute(int) | Abre formulário de atributo |
| editAttribute(int) | Carrega atributo |
| saveAttribute() | Cria/atualiza atributo |
| deleteAttribute(int) | Exclui atributo + opções |
| openCreateOption(int) | Abre formulário de opção |
| editOption(int) | Carrega opção |
| saveOption() | Cria/atualiza opção |
| deleteOption(int) | Exclui opção |
| render() | Renderiza view |

**Propriedades Computadas**:

| Propriedade | Descrição |
|-------------|-----------|
| categories | Categorias com contagem de produtos, ordenadas por position |
| products | Produtos com categoria, ordenados |

---

## 8.5 `App\Livewire\Admin\UserManager`

| Propriedade | Tipo | Default | Descrição |
|-------------|------|---------|-----------|
| showForm | bool | false | |
| editingUserId | ?int | null | |
| name | string | '' | |
| email | string | '' | |
| password | string | '' | |
| passwordConfirmation | string | '' | |
| role | string | 'atendente' | |
| confirmDeleteUserId | ?int | null | |

**Validações**:

| Campo | Regras |
|-------|--------|
| name | required, string, max:255 |
| email | required, email, max:255 |
| role | required, in:admin,atendente |
| password (create) | required, string, min:6 |
| passwordConfirmation (create) | required, same:password |
| password (edit) | nullable, string, min:6 |
| passwordConfirmation (edit) | nullable, same:password |

**Métodos**:

| Método | Descrição |
|--------|-----------|
| resetForm() | Limpa formulário |
| openCreate() | Abre formulário de criação |
| edit(int) | Carrega usuário para edição |
| save() | Cria/atualiza usuário |
| confirmDelete(int) | Marca para exclusão |
| delete(int) | Exclui usuário |
| render() | Renderiza view |

**Propriedades Computadas**:

| Propriedade | Descrição |
|-------------|-----------|
| users | Usuários do tenant autenticado |

---

## 8.6 `App\Livewire\Admin\SubscriptionCheckout`

| Propriedade | Tipo | Default |
|-------------|------|---------|
| (nenhuma) | | |

**Métodos**:

| Método | Descrição |
|--------|-----------|
| render() | Renderiza view com layout admin |

---

## 8.7 `App\Livewire\Public\Menu`

| Propriedade | Tipo | Default | Descrição |
|-------------|------|---------|-----------|
| tenant | Tenant | (mount) | Tenant atual |
| categories | Collection | (mount) | Categorias com produtos ativos |
| selectedProduct | ?Product | null | Produto selecionado no modal |

**Event Listeners**:

| Evento | Ação |
|--------|------|
| productSelected | showProduct() |

**Métodos**:

| Método | Descrição |
|--------|-----------|
| mount($tenant) | Carrega categorias com produtos ativos e atributos |
| showProduct($productId) | Encontra e define produto selecionado |
| closeProduct() | Limpa produto selecionado |
| render() | Renderiza view |

---

## 8.8 `App\Livewire\Public\Cart`

| Propriedade | Tipo | Default | Descrição |
|-------------|------|---------|-----------|
| tenant | Tenant | (mount) | Tenant atual |
| items | array | [] | Itens no carrinho |
| customerName | string | '' | |
| customerPhone | string | '' | |
| tableNumber | string | '' | Mesa selecionada |
| paymentMethod | string | 'pix' | |
| notes | string | '' | |
| showCart | bool | false | Estado do drawer |
| lastOrderId | ?int | null | Último pedido |
| orderTracking | ?array | null | Dados de tracking |

**Event Listeners**:

| Evento | Ação |
|--------|------|
| addToCart | addToCart() (PHP via Livewire) |
| cartUpdated | $refresh |

**Métodos**:

| Método | Descrição |
|--------|-----------|
| mount($tenant) | Define tenant |
| addToCart($productId, $productName, $price, $selectedOptions, $quantity) | Adiciona item; abre carrinho |
| removeItem($key) | Remove item; fecha carrinho se vazio |
| updateQuantity($key, $delta) | Ajusta quantidade (mínimo 1) |
| checkout() | Valida formulário, cria pedido + itens em transação, atualiza mesa |
| loadOrderTracking() | Carrega dados do último pedido |
| newOrder() | Reinicia para novo pedido |
| render() | Renderiza view |

**Propriedades Computadas**:

| Propriedade | Descrição |
|-------------|-----------|
| total | Soma dos itens com quantidades |
| itemsCount | Quantidade total de itens |
| freeTables | Mesas livres do tenant |
