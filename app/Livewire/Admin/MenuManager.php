<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class MenuManager extends Component
{
    use WithFileUploads;

    public string $view = 'categories';

    public bool $showCategoryForm = false;
    public ?int $editingCategoryId = null;
    public string $categoryName = '';
    public string $categorySlug = '';
    public int $categoryPosition = 0;

    public bool $showProductForm = false;
    public ?int $editingProductId = null;
    public string $productName = '';
    public ?string $productDescription = null;
    public string $productPrice = '';
    public ?string $productImageUrl = null;
    public $productImage = null;
    public string $productStatus = 'active';
    public ?int $productCategoryId = null;

    public ?int $confirmDeleteCategoryId = null;
    public ?int $confirmDeleteProductId = null;
    public ?int $confirmDeleteAttributeId = null;

    public ?int $editingAttributeId = null;
    public bool $showAttributeForm = false;
    public string $attributeName = '';
    public string $attributeType = 'single';
    public bool $attributeRequired = false;
    public ?int $attributeProductId = null;

    public ?int $editingOptionId = null;
    public bool $showOptionForm = false;
    public string $optionName = '';
    public string $optionPrice = '0';
    public ?int $optionAttributeId = null;

    protected function rules(): array
    {
        if ($this->showCategoryForm) {
            return [
                'categoryName' => 'required|string|max:100',
                'categorySlug' => 'required|string|max:100|alpha_dash',
                'categoryPosition' => 'required|integer|min:0|max:999',
            ];
        }
        if ($this->showAttributeForm) {
            return [
                'attributeName' => 'required|string|max:100',
                'attributeType' => 'required|in:single,multiple',
                'attributeRequired' => 'boolean',
            ];
        }
        if ($this->showOptionForm) {
            return [
                'optionName' => 'required|string|max:100',
                'optionPrice' => 'required|numeric|min:0|max:999',
            ];
        }
        return [
            'productName' => 'required|string|max:255',
            'productDescription' => 'nullable|string|max:1000',
            'productPrice' => 'required|numeric|min:0|max:99999',
            'productImageUrl' => 'nullable|url|max:500',
            'productImage' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'productStatus' => 'required|in:active,inactive',
            'productCategoryId' => 'required|integer|exists:categories,id',
        ];
    }

    protected $messages = [
        'categoryName.required' => 'O nome da categoria é obrigatório.',
        'categorySlug.required' => 'O slug é obrigatório.',
        'categorySlug.alpha_dash' => 'O slug deve conter apenas letras, números, traços e underscores.',
        'productName.required' => 'O nome do produto é obrigatório.',
        'productPrice.required' => 'O preço é obrigatório.',
        'productPrice.numeric' => 'O preço deve ser um número.',
        'productCategoryId.required' => 'Selecione uma categoria.',
        'productImageUrl.url' => 'A URL da imagem deve ser válida.',
        'attributeName.required' => 'O nome do atributo é obrigatório.',
        'optionName.required' => 'O nome da opção é obrigatório.',
    ];

    public function switchView(string $view): void
    {
        $this->view = $view;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'showCategoryForm', 'editingCategoryId', 'categoryName', 'categorySlug', 'categoryPosition',
            'showProductForm', 'editingProductId', 'productName', 'productDescription', 'productPrice',
            'productImageUrl', 'productImage', 'productStatus', 'productCategoryId',
            'showAttributeForm', 'editingAttributeId', 'attributeName', 'attributeType', 'attributeRequired',
            'showOptionForm', 'editingOptionId', 'optionName', 'optionPrice',
            'confirmDeleteCategoryId', 'confirmDeleteProductId', 'confirmDeleteAttributeId',
        ]);
        $this->resetValidation();
    }

    public function resetProductForm(): void
    {
        $this->showProductForm = false;
        $this->editingProductId = null;
        $this->reset([
            'productName', 'productDescription', 'productPrice',
            'productImageUrl', 'productImage', 'productStatus', 'productCategoryId',
        ]);
        $this->resetValidation();
    }

    public function resetAttributeForm(): void
    {
        $this->showAttributeForm = false;
        $this->editingAttributeId = null;
        $this->reset([
            'attributeName', 'attributeType', 'attributeRequired',
            'showOptionForm', 'editingOptionId', 'optionName', 'optionPrice',
        ]);
        $this->resetValidation();
    }

    public function openCreateCategory(): void
    {
        $this->resetForm();
        $this->showCategoryForm = true;
        $this->categoryPosition = Category::count();
    }

    public function editCategory(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categorySlug = $category->slug;
        $this->categoryPosition = $category->position;
        $this->showCategoryForm = true;
    }

    public function saveCategory(): void
    {
        $this->validateOnly('categoryName');
        $this->validateOnly('categorySlug');
        $this->validateOnly('categoryPosition');

        $tenant = auth()->user()->tenant;
        $data = [
            'name' => $this->categoryName,
            'slug' => $this->categorySlug,
            'position' => $this->categoryPosition,
        ];

        if ($this->editingCategoryId) {
            $category = Category::findOrFail($this->editingCategoryId);
            $category->update($data);
            $this->dispatch('notify', message: 'Categoria "' . $category->name . '" atualizada!');
        } else {
            $data['tenant_id'] = $tenant->id;
            Category::create($data);
            $this->dispatch('notify', message: 'Categoria "' . $this->categoryName . '" criada!');
        }

        $this->showCategoryForm = false;
        $this->editingCategoryId = null;
    }

    public function confirmDeleteCategory(int $id): void
    {
        $this->confirmDeleteCategoryId = $id;
    }

    public function deleteCategory(int $id): void
    {
        $category = Category::findOrFail($id);
        $name = $category->name;
        $category->products()->delete();
        $category->delete();
        $this->confirmDeleteCategoryId = null;
        $this->dispatch('notify', message: 'Categoria "' . $name . '" e seus produtos foram excluídos!');
    }

    public function moveCategoryUp(int $id): void
    {
        $category = Category::findOrFail($id);
        $prev = Category::where('position', '<', $category->position)
            ->orderBy('position', 'desc')->first();
        if ($prev) {
            $tmp = $category->position;
            $category->update(['position' => $prev->position]);
            $prev->update(['position' => $tmp]);
        }
    }

    public function moveCategoryDown(int $id): void
    {
        $category = Category::findOrFail($id);
        $next = Category::where('position', '>', $category->position)
            ->orderBy('position')->first();
        if ($next) {
            $tmp = $category->position;
            $category->update(['position' => $next->position]);
            $next->update(['position' => $tmp]);
        }
    }

    public function openCreateProduct(): void
    {
        $this->resetForm();
        $this->showProductForm = true;
    }

    public function editProduct(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->editingProductId = $product->id;
        $this->productName = $product->name;
        $this->productDescription = $product->description;
        $this->productPrice = (string) $product->price;
        $this->productImageUrl = $product->image_url;
        $this->productStatus = $product->status;
        $this->productCategoryId = $product->category_id;
        $this->productImage = null;
        $this->showProductForm = true;
    }

    public function updatedProductImage(): void
    {
        $this->validateOnly('productImage');
    }

    public function removeUploadedImage(): void
    {
        $this->productImage = null;
    }

    public function saveProduct(): void
    {
        $this->validate();

        $tenant = auth()->user()->tenant;

        if ($this->productImage) {
            $this->productImageUrl = $this->productImage->store('products', 'public');
        }

        $data = [
            'name' => $this->productName,
            'description' => $this->productDescription ?: null,
            'price' => $this->productPrice,
            'image_url' => $this->productImageUrl ?: null,
            'status' => $this->productStatus,
            'category_id' => $this->productCategoryId,
        ];

        if ($this->editingProductId) {
            $product = Product::findOrFail($this->editingProductId);
            $product->update($data);
            $this->dispatch('notify', message: 'Produto "' . $product->name . '" atualizado!');
        } else {
            $data['tenant_id'] = $tenant->id;
            Product::create($data);
            $this->dispatch('notify', message: 'Produto "' . $this->productName . '" criado!');
        }

        $this->showProductForm = false;
        $this->editingProductId = null;
    }

    public function confirmDeleteProduct(int $id): void
    {
        $this->confirmDeleteProductId = $id;
    }

    public function deleteProduct(int $id): void
    {
        $product = Product::findOrFail($id);
        $name = $product->name;
        $product->attributes()->each(fn ($attr) => $attr->options()->delete());
        $product->attributes()->delete();
        $product->delete();
        $this->confirmDeleteProductId = null;
        $this->dispatch('notify', message: 'Produto "' . $name . '" excluído!');
    }

    public function toggleProductStatus(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['status' => $product->status === 'active' ? 'inactive' : 'active']);
        $this->dispatch('notify', message: $product->name . ' ' . ($product->status === 'active' ? 'ativado' : 'desativado') . '!');
    }

    public function openCreateAttribute(int $productId): void
    {
        $this->reset([
            'showAttributeForm', 'showOptionForm', 'editingAttributeId', 'editingOptionId',
            'attributeName', 'attributeType', 'attributeRequired',
            'optionName', 'optionPrice',
        ]);
        $this->resetValidation();
        $this->attributeProductId = $productId;
        $this->showAttributeForm = true;
    }

    public function editAttribute(int $id): void
    {
        $attr = ProductAttribute::findOrFail($id);
        $this->editingAttributeId = $attr->id;
        $this->attributeName = $attr->name;
        $this->attributeType = $attr->type;
        $this->attributeRequired = $attr->is_required;
        $this->attributeProductId = $attr->product_id;
        $this->showAttributeForm = true;
    }

    public function saveAttribute(): void
    {
        $this->validateOnly('attributeName');
        $this->validateOnly('attributeType');

        $tenant = auth()->user()->tenant;
        $data = [
            'name' => $this->attributeName,
            'type' => $this->attributeType,
            'is_required' => $this->attributeRequired,
        ];

        if ($this->editingAttributeId) {
            $attr = ProductAttribute::findOrFail($this->editingAttributeId);
            $attr->update($data);
            $this->dispatch('notify', message: 'Atributo "' . $attr->name . '" atualizado!');
        } else {
            $data['tenant_id'] = $tenant->id;
            $data['product_id'] = $this->attributeProductId;
            $data['position'] = ProductAttribute::where('product_id', $this->attributeProductId)->count();
            ProductAttribute::create($data);
            $this->dispatch('notify', message: 'Atributo "' . $this->attributeName . '" criado!');
        }

        $this->showAttributeForm = false;
        $this->editingAttributeId = null;
    }

    public function confirmDeleteAttribute(int $id): void
    {
        $this->confirmDeleteAttributeId = $id;
    }

    public function deleteAttribute(int $id): void
    {
        $attr = ProductAttribute::findOrFail($id);
        $name = $attr->name;
        $attr->options()->delete();
        $attr->delete();
        $this->confirmDeleteAttributeId = null;
        $this->dispatch('notify', message: 'Atributo "' . $name . '" removido!');
    }

    public function openCreateOption(int $attributeId): void
    {
        $this->reset([
            'showOptionForm', 'editingOptionId', 'optionName', 'optionPrice',
        ]);
        $this->resetValidation();
        $this->optionAttributeId = $attributeId;
        $this->optionPrice = '0';
        $this->showOptionForm = true;
    }

    public function editOption(int $id): void
    {
        $option = ProductAttributeOption::findOrFail($id);
        $this->editingOptionId = $option->id;
        $this->optionName = $option->name;
        $this->optionPrice = (string) $option->price_additional;
        $this->optionAttributeId = $option->product_attribute_id;
        $this->showOptionForm = true;
    }

    public function saveOption(): void
    {
        $this->validateOnly('optionName');
        $this->validateOnly('optionPrice');

        $data = [
            'name' => $this->optionName,
            'price_additional' => $this->optionPrice,
        ];

        if ($this->editingOptionId) {
            $option = ProductAttributeOption::findOrFail($this->editingOptionId);
            $option->update($data);
            $this->dispatch('notify', message: 'Opção "' . $option->name . '" atualizada!');
        } else {
            $data['product_attribute_id'] = $this->optionAttributeId;
            $data['position'] = ProductAttributeOption::where('product_attribute_id', $this->optionAttributeId)->count();
            ProductAttributeOption::create($data);
            $this->dispatch('notify', message: 'Opção "' . $this->optionName . '" criada!');
        }

        $this->showOptionForm = false;
        $this->editingOptionId = null;
    }

    public function deleteOption(int $id): void
    {
        $option = ProductAttributeOption::findOrFail($id);
        $option->delete();
        $this->dispatch('notify', message: 'Opção removida!');
    }

    public function getCategoriesProperty()
    {
        return Category::withCount('products')->orderBy('position')->get();
    }

    public function getProductsProperty()
    {
        $query = Product::with(['category', 'attributes.options']);
        if ($this->view === 'categories') {
            $query->whereIn('category_id', $this->categories->pluck('id'));
        }
        return $query->orderBy('category_id')->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.admin.menu-manager', [
            'categories' => $this->categories,
            'products' => $this->products,
        ])->layout('layouts.admin');
    }
}
