# CARDÁPIO — SISTEMA DE CATEGORIAS, PRODUTOS, ATRIBUTOS E OPÇÕES

## 20. SISTEMA DE CARDÁPIO

### 20.1 Hierarquia

```
Tenant
 └── Category (posição ordenável)
      └── Product (status active/inactive)
           └── ProductAttribute (type: single | multiple, is_required)
                └── ProductAttributeOption (price_additional)
```

### 20.2 Categorias

| Operação | Handler |
|----------|---------|
| Listar | MenuManager (computado: `categories`) |
| Criar | MenuManager@openCreateCategory / saveCategory |
| Editar | MenuManager@editCategory / saveCategory |
| Excluir | MenuManager@deleteCategory (cascade: products + attributes + options) |
| Reordenar | MenuManager@moveCategoryUp / moveCategoryDown |

### 20.3 Produtos

| Operação | Handler |
|----------|---------|
| Listar | MenuManager (computado: `products`) |
| Criar | MenuManager@openCreateProduct / saveProduct |
| Editar | MenuManager@editProduct / saveProduct |
| Excluir | MenuManager@deleteProduct (cascade: attributes + options) |
| Ativar/Desativar | MenuManager@toggleProductStatus |
| Upload de imagem | Livewire WithFileUploads → storage/app/public/products/ |

### 20.4 Atributos e Opções

| Atributo | Tipo single | Tipo multiple |
|----------|-------------|---------------|
| Seleção | Radio button (1 opção) | Checkbox (N opções) |
| Obrigatório | Pode ser | Pode ser |
| Acréscimo | Por opção | Por opção |

### 20.5 Exibição Pública

**Menu.php** carrega no mount:
```php
Category::where('tenant_id', $tenant->id)
    ->with(['products' => fn($q) => $q->active()->with('attributes.options')])
    ->orderBy('position')
    ->get();
```

**Produto Selecionado** é aberto no modal bottom sheet com:
- Nome, descrição, imagem, preço
- Atributos renderizados como radio (single) ou checkbox (multiple)
- Opções com acréscimo de preço exibido

### 20.6 ImageUrl Helper

```php
public function imageUrl(): string
{
    if (!$this->image_url) {
        return 'https://images.unsplash.com/...';  // fallback hamburger
    }
    if (str_starts_with($this->image_url, 'products/')) {
        return Storage::url($this->image_url);  // uploaded file
    }
    return $this->image_url;  // full URL
}
```
