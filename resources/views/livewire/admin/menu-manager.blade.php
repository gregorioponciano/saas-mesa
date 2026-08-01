<div class="p-4 lg:p-8 space-y-6"
     x-data="{
         showCategoryForm: @entangle('showCategoryForm'),
         showProductForm: @entangle('showProductForm'),
         showAttributeForm: @entangle('showAttributeForm'),
         init() {
             this.$watch('showCategoryForm', val => { document.body.style.overflow = val ? 'hidden' : '' });
             this.$watch('showProductForm', val => { document.body.style.overflow = val ? 'hidden' : '' });
             this.$watch('showAttributeForm', val => { document.body.style.overflow = val ? 'hidden' : '' });
         }
     }"
     @keydown.window.escape="
         if (showCategoryForm) $wire.resetForm();
         if (showProductForm) $wire.resetProductForm();
         if (showAttributeForm) $wire.resetAttributeForm();
     ">

    {{-- Header --}}
    @if (!$tenant)
        <div class="max-w-md mx-auto mt-16 p-8 rounded-2xl bg-neutral-900 border border-neutral-800 text-center space-y-3">
            <p class="text-5xl">🏪</p>
            <h2 class="text-lg font-bold text-white">Nenhuma empresa vinculada</h2>
            <p class="text-sm text-neutral-400">Sua conta não está vinculada a nenhuma empresa. Fale com o suporte ou entre com a conta de uma empresa para gerenciar o cardápio.</p>
        </div>
        @php return; @endphp
    @endif
    <x-admin.page-header title="Gerenciar Cardápio" subtitle="{{ $categories->count() }} categorias &bull; {{ $products->count() }} produtos">
        <x-slot:action>
            <x-admin.button variant="secondary" wire:click="openCreateCategory"
                icon='<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>'>
                Nova Categoria
            </x-admin.button>
            <x-admin.button variant="primary" wire:click="openCreateProduct"
                icon='<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>'>
                Novo Produto
            </x-admin.button>
        </x-slot:action>
    </x-admin.page-header>

    {{-- View Switcher --}}
    <div class="flex gap-1 p-1 rounded-2xl bg-neutral-900 border border-neutral-800 w-fit flex-wrap">
        <button wire:click="switchView('categories')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $view === 'categories' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Categorias
        </button>
        <button wire:click="switchView('products')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $view === 'products' ? 'bg-amber-500 text-neutral-950 shadow-lg shadow-amber-500/20' : 'text-neutral-400 hover:text-white' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Produtos
        </button>
        @if (auth()->user()->isAdmin() && $tenant->isPaid())
            <button wire:click="switchView('pontos')"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ $view === 'pontos' ? 'bg-emerald-500 text-neutral-950 shadow-lg shadow-emerald-500/20' : 'text-neutral-400 hover:text-white' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Pontos
            </button>
        @endif
    </div>

    {{-- ========== CATEGORIES VIEW ========== --}}
    @if ($view === 'categories')
        @forelse ($categories as $category)
            <x-admin.card :padding="false" class="overflow-hidden">
                {{-- Category Header --}}
                <div class="flex items-center justify-between px-6 py-4 bg-neutral-900 border-b border-neutral-800">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 flex items-center justify-center rounded-lg bg-neutral-800 text-xs font-bold text-neutral-400">{{ $category->position }}</span>
                        <div>
                            <h3 class="font-semibold text-white">{{ $category->name }}</h3>
                            <p class="text-xs text-neutral-500">{{ $category->products_count }} produto(s)</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button wire:click="moveCategoryUp({{ $category->id }})"
                                class="p-2 rounded-lg text-neutral-500 hover:text-white hover:bg-neutral-800 transition-all"
                                title="Mover para cima">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>
                        <button wire:click="moveCategoryDown({{ $category->id }})"
                                class="p-2 rounded-lg text-neutral-500 hover:text-white hover:bg-neutral-800 transition-all"
                                title="Mover para baixo">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <button wire:click="editCategory({{ $category->id }})"
                                class="p-2 rounded-lg text-neutral-500 hover:text-amber-400 hover:bg-neutral-800 transition-all"
                                title="Editar">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button wire:click="confirmDeleteCategory({{ $category->id }})"
                                class="p-2 rounded-lg text-neutral-500 hover:text-red-400 hover:bg-neutral-800 transition-all"
                                title="Excluir">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Confirm Delete Category --}}
                @if ($confirmDeleteCategoryId === $category->id)
                    <div class="flex items-center gap-2 px-6 py-3 bg-red-500/10 border-b border-red-500/20">
                        <span class="text-sm text-red-400">Excluir &quot;{{ $category->name }}&quot; e todos os seus produtos?</span>
                        <button wire:click="deleteCategory({{ $category->id }})" wire:loading.attr="disabled"
                                                                 class="px-3 py-1.5 text-xs font-bold bg-red-500 text-white rounded-lg hover:bg-red-400 disabled:opacity-50">Sim</button>
                        <button wire:click="$set('confirmDeleteCategoryId', null)"
                                class="px-3 py-1.5 text-xs font-bold bg-neutral-800 text-neutral-400 rounded-lg hover:text-white">Não</button>
                    </div>
                @endif

                {{-- Products inside Category --}}
                <div class="divide-y divide-neutral-800">
                    @forelse ($products->where('category_id', $category->id) as $product)
                        <div class="px-6 py-4 hover:bg-neutral-800/30 transition-colors" wire:key="product-{{ $product->id }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="w-10 h-10 rounded-xl bg-neutral-800 overflow-hidden shrink-0">
                                        @if ($product->image_url)
                                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-neutral-600">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-medium text-sm truncate">{{ $product->name }}</h4>
                                        <p class="text-xs text-neutral-500">
                                            R$ {{ number_format($product->price, 2, ',', '.') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 ml-2">
                                        @if ($product->stock > 10)
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">{{ $product->stock }} em estoque</span>
                                        @elseif ($product->stock > 0)
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400">{{ $product->stock }} em estoque</span>
                                        @else
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-500/10 text-red-400">Sem estoque</span>
                                        @endif
                                        <button wire:click="openStockModal({{ $product->id }})"
                                                class="p-1.5 rounded-lg text-neutral-500 hover:text-emerald-400 hover:bg-neutral-800 transition-all"
                                                title="Ajustar Estoque">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </button>
                                        <button wire:click="viewStockMovements({{ $product->id }})"
                                                class="p-1.5 rounded-lg text-neutral-500 hover:text-blue-400 hover:bg-neutral-800 transition-all"
                                                title="Histórico de Estoque">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($product->status === 'active')
                                        <x-admin.badge variant="success">Ativo</x-admin.badge>
                                    @else
                                        <span class="px-2.5 py-1 text-[10px] font-medium rounded-full bg-neutral-800 text-neutral-500 border border-neutral-700">Inativo</span>
                                    @endif
                                    <button wire:click="toggleProductStatus({{ $product->id }})"
                                            class="p-1.5 rounded-lg text-neutral-500 hover:text-amber-400 hover:bg-neutral-800 transition-all"
                                            title="Ativar/Desativar">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="editProduct({{ $product->id }})"
                                            class="p-1.5 rounded-lg text-neutral-500 hover:text-amber-400 hover:bg-neutral-800 transition-all"
                                            title="Editar">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDeleteProduct({{ $product->id }})"
                                            class="p-1.5 rounded-lg text-neutral-500 hover:text-red-400 hover:bg-neutral-800 transition-all"
                                            title="Excluir">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Confirm Delete Product --}}
                            @if ($confirmDeleteProductId === $product->id)
                                <div class="flex items-center gap-2 mt-3 p-3 rounded-xl bg-red-500/10 border border-red-500/20">
                                    <span class="text-sm text-red-400">Excluir &quot;{{ $product->name }}&quot; e seus atributos?</span>
                                    <button wire:click="deleteProduct({{ $product->id }})" wire:loading.attr="disabled"
                                                                     class="px-3 py-1.5 text-xs font-bold bg-red-500 text-white rounded-lg hover:bg-red-400 disabled:opacity-50">Sim</button>
                                                            <button wire:click="$set('confirmDeleteProductId', null)"
                                                                     class="px-3 py-1.5 text-xs font-bold bg-neutral-800 text-neutral-400 rounded-lg hover:text-white">Não</button>
                                </div>
                            @endif

                            {{-- Attributes Section --}}
                            <div class="mt-3 ml-12">
                                @forelse ($product->attributes as $attr)
                                    <div class="mb-2 p-3 rounded-xl bg-neutral-800/30 border border-neutral-800" wire:key="attr-{{ $attr->id }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-neutral-200">{{ $attr->name }}</span>
                                                @if ($attr->price > 0)
                                                    <x-admin.badge variant="warning">R$ {{ number_format($attr->price, 2, ',', '.') }}</x-admin.badge>
                                                @endif
                                                @if ($attr->type === 'single')
                                                    <x-admin.badge variant="info">Única</x-admin.badge>
                                                @else
                                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-400">Múltipla</span>
                                                @endif
                                                @if ($attr->is_required)
                                                    <x-admin.badge variant="warning">Obrigatório</x-admin.badge>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <button wire:click="editAttribute({{ $attr->id }})"
                                                        class="p-1 rounded text-neutral-500 hover:text-amber-400 hover:bg-neutral-800 transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <button wire:click="confirmDeleteAttribute({{ $attr->id }})"
                                                        class="p-1 rounded text-neutral-500 hover:text-red-400 hover:bg-neutral-800 transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Confirm Delete Attribute --}}
                                        @if ($confirmDeleteAttributeId === $attr->id)
                                            <div class="flex items-center gap-2 p-2 mb-2 rounded-lg bg-red-500/10 border border-red-500/20">
                                                <span class="text-xs text-red-400">Excluir &quot;{{ $attr->name }}&quot; e suas opções?</span>
                                                <button wire:click="deleteAttribute({{ $attr->id }})" wire:loading.attr="disabled"
                                                         class="px-2 py-1 text-[10px] font-bold bg-red-500 text-white rounded-md hover:bg-red-400 disabled:opacity-50">Sim</button>
                                                <button wire:click="$set('confirmDeleteAttributeId', null)"
                                                        class="px-2 py-1 text-[10px] font-bold bg-neutral-800 text-neutral-400 rounded-md hover:text-white">Não</button>
                                            </div>
                                        @endif

                                        {{-- Options --}}
                                        <div class="ml-4 space-y-1">
                                            @forelse ($attr->options as $opt)
                                                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-neutral-800/30">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="text-sm text-neutral-300 truncate">{{ $opt->name }}</span>
                                                        @if ($opt->ingredient)
                                                            <x-admin.badge variant="success" title="Ingrediente: {{ $opt->ingredient->name }}">
                                                                {{ $opt->ingredient->name }}
                                                            </x-admin.badge>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-3 shrink-0">
                                                        @if ($opt->price_additional > 0)
                                                            <span class="text-xs text-amber-400">+R$ {{ number_format($opt->price_additional, 2, ',', '.') }}</span>
                                                        @endif
                                                        <div class="flex gap-1">
                                                            <button wire:click="editOption({{ $opt->id }})"
                                                                    class="p-1 rounded bg-neutral-800 text-neutral-500 hover:text-white transition-all">
                                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                            </button>
                                                            <button wire:click="deleteOption({{ $opt->id }})"
                                                                    class="p-1 rounded bg-neutral-800 text-neutral-500 hover:text-red-400 transition-all">
                                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-xs text-neutral-500 py-1">Nenhuma opção</p>
                                            @endforelse
                                            <button wire:click="openCreateOption({{ $attr->id }})"
                                                    class="text-xs text-amber-400 hover:text-amber-300 transition-colors mt-1 inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                Adicionar opção
                                            </button>
                                        </div>

                                        {{-- Option Form --}}
                                        @if ($showOptionForm && $optionAttributeId === $attr->id)
                                            <div class="mt-3 p-3 rounded-lg bg-neutral-800/50 border border-neutral-700">
                                                <form wire:submit="saveOption" class="space-y-3">
                                                    <div class="flex items-end gap-3">
                                                        <div class="flex-1">
                                                            <label class="block text-xs text-neutral-400 mb-1">Nome da opção</label>
                                                            <input wire:model="optionName" placeholder="Ex: Médio"
                                                                   class="w-full px-3 py-1.5 rounded-lg bg-neutral-950 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                                        </div>
                                                        <div class="w-28">
                                                            <label class="block text-xs text-neutral-400 mb-1">Adicional R$</label>
                                                            <input wire:model="optionPrice" type="number" step="0.01" min="0" placeholder="0,00"
                                                                   class="w-full px-3 py-1.5 rounded-lg bg-neutral-950 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2 pt-1">
                                                        <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                                                            {{ $editingOptionId ? 'Atualizar' : 'Criar' }}
                                                        </x-admin.button>
                                                        <x-admin.button type="button" variant="ghost" wire:click="$set('showOptionForm', false)">
                                                            Cancelar
                                                        </x-admin.button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="flex items-center gap-2 py-2">
                                        <p class="text-xs text-neutral-500">Nenhum atributo cadastrado para este produto</p>
                                    </div>
                                @endforelse

                                <button wire:click="openCreateAttribute({{ $product->id }})"
                                        class="text-xs text-amber-400 hover:text-amber-300 transition-colors inline-flex items-center gap-1 mt-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Adicionar atributo
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-neutral-500">
                            <p>Nenhum produto nesta categoria</p>
                            <button wire:click="openCreateProduct"
                                    class="mt-2 text-amber-400 hover:text-amber-300 transition-colors inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Adicionar produto
                            </button>
                        </div>
                    @endforelse
                </div>
            </x-admin.card>
        @empty
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto mb-4 text-neutral-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <h3 class="text-lg font-semibold text-neutral-400 mb-2">Nenhuma categoria</h3>
                <p class="text-sm text-neutral-600 mb-4">Comece criando sua primeira categoria de produtos</p>
                <button wire:click="openCreateCategory"
                        class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02]">
                    Criar Categoria
                </button>
            </div>
        @endforelse

    {{-- ========== PRODUCTS VIEW ========== --}}
    @elseif ($view === 'products')
        @forelse ($products as $product)
            <x-admin.card :padding="false" class="overflow-hidden" wire:key="all-product-{{ $product->id }}">
                <div class="px-6 py-4 hover:bg-neutral-800/20 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-10 h-10 rounded-xl bg-neutral-800 overflow-hidden shrink-0">
                                @if ($product->image_url)
                                    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-neutral-600">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-medium text-sm truncate">{{ $product->name }}</h4>
                                <p class="text-xs text-neutral-500">
                                    {{ $product->category->name ?? 'Sem categoria' }} &bull; R$ {{ number_format($product->price, 2, ',', '.') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 ml-2">
                                @if ($product->stock > 10)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">{{ $product->stock }} em estoque</span>
                                @elseif ($product->stock > 0)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400">{{ $product->stock }} em estoque</span>
                                @else
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-500/10 text-red-400">Sem estoque</span>
                                @endif
                                <button wire:click="openStockModal({{ $product->id }})"
                                        class="p-1.5 rounded-lg text-neutral-500 hover:text-emerald-400 hover:bg-neutral-800 transition-all"
                                        title="Ajustar Estoque">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </button>
                                <button wire:click="viewStockMovements({{ $product->id }})"
                                        class="p-1.5 rounded-lg text-neutral-500 hover:text-blue-400 hover:bg-neutral-800 transition-all"
                                        title="Histórico de Estoque">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($product->status === 'active')
                                <x-admin.badge variant="success">Ativo</x-admin.badge>
                            @else
                                <span class="px-2.5 py-1 text-[10px] font-medium rounded-full bg-neutral-800 text-neutral-500 border border-neutral-700">Inativo</span>
                            @endif
                            <button wire:click="toggleProductStatus({{ $product->id }})"
                                    class="p-1.5 rounded-lg text-neutral-500 hover:text-amber-400 hover:bg-neutral-800 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </button>
                            <button wire:click="editProduct({{ $product->id }})"
                                    class="p-1.5 rounded-lg text-neutral-500 hover:text-amber-400 hover:bg-neutral-800 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="confirmDeleteProduct({{ $product->id }})"
                                    class="p-1.5 rounded-lg text-neutral-500 hover:text-red-400 hover:bg-neutral-800 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Confirm Delete Product --}}
                    @if ($confirmDeleteProductId === $product->id)
                        <div class="flex items-center gap-2 mt-3 p-3 rounded-xl bg-red-500/10 border border-red-500/20">
                            <span class="text-sm text-red-400">Excluir &quot;{{ $product->name }}&quot; e seus atributos?</span>
                            <button wire:click="deleteProduct({{ $product->id }})"
                                    class="px-3 py-1.5 text-xs font-bold bg-red-500 text-white rounded-lg hover:bg-red-400">Sim</button>
                            <button wire:click="$set('confirmDeleteProductId', null)"
                                    class="px-3 py-1.5 text-xs font-bold bg-neutral-800 text-neutral-400 rounded-lg hover:text-white">Não</button>
                        </div>
                    @endif

                    {{-- Attributes Section --}}
                    <div class="mt-3 ml-12">
                        @forelse ($product->attributes as $attr)
                            <div class="mb-2 p-3 rounded-xl bg-neutral-800/30 border border-neutral-800" wire:key="all-attr-{{ $attr->id }}">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-neutral-200">{{ $attr->name }}</span>
                                        @if ($attr->price > 0)
                                            <x-admin.badge variant="warning">R$ {{ number_format($attr->price, 2, ',', '.') }}</x-admin.badge>
                                        @endif
                                        @if ($attr->type === 'single')
                                            <x-admin.badge variant="info">Única</x-admin.badge>
                                        @else
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-400">Múltipla</span>
                                        @endif
                                        @if ($attr->is_required)
                                            <x-admin.badge variant="warning">Obrigatório</x-admin.badge>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button wire:click="editAttribute({{ $attr->id }})"
                                                class="p-1 rounded text-neutral-500 hover:text-amber-400 hover:bg-neutral-800 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button wire:click="confirmDeleteAttribute({{ $attr->id }})"
                                                class="p-1 rounded text-neutral-500 hover:text-red-400 hover:bg-neutral-800 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                                @if ($confirmDeleteAttributeId === $attr->id)
                                    <div class="flex items-center gap-2 p-2 mb-2 rounded-lg bg-red-500/10 border border-red-500/20">
                                        <span class="text-xs text-red-400">Excluir &quot;{{ $attr->name }}&quot; e suas opções?</span>
                                        <button wire:click="deleteAttribute({{ $attr->id }})" wire:loading.attr="disabled"
                                                 class="px-2 py-1 text-[10px] font-bold bg-red-500 text-white rounded-md hover:bg-red-400 disabled:opacity-50">Sim</button>
                                        <button wire:click="$set('confirmDeleteAttributeId', null)"
                                                class="px-2 py-1 text-[10px] font-bold bg-neutral-800 text-neutral-400 rounded-md hover:text-white">Não</button>
                                    </div>
                                @endif
                                <div class="ml-4 space-y-1">
                                    @forelse ($attr->options as $opt)
                                        <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-neutral-800/30">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="text-sm text-neutral-300 truncate">{{ $opt->name }}</span>
                                                @if ($opt->ingredient)
                                                    <x-admin.badge variant="success">{{ $opt->ingredient->name }}</x-admin.badge>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3 shrink-0">
                                                @if ($opt->price_additional > 0)
                                                    <span class="text-xs text-amber-400">+R$ {{ number_format($opt->price_additional, 2, ',', '.') }}</span>
                                                @endif
                                                <div class="flex gap-1">
                                                    <button wire:click="editOption({{ $opt->id }})"
                                                            class="p-1 rounded bg-neutral-800 text-neutral-500 hover:text-white transition-all">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    </button>
                                                    <button wire:click="deleteOption({{ $opt->id }})"
                                                            class="p-1 rounded bg-neutral-800 text-neutral-500 hover:text-red-400 transition-all">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-xs text-neutral-500 py-1">Nenhuma opção</p>
                                    @endforelse
                                    <button wire:click="openCreateOption({{ $attr->id }})"
                                            class="text-xs text-amber-400 hover:text-amber-300 transition-colors mt-1 inline-flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        Adicionar opção
                                    </button>
                                </div>
                                @if ($showOptionForm && $optionAttributeId === $attr->id)
                                    <div class="mt-3 p-3 rounded-lg bg-neutral-800/50 border border-neutral-700">
                                        <form wire:submit="saveOption" class="space-y-3">
                                            <div class="flex items-end gap-3">
                                                <div class="flex-1">
                                                    <label class="block text-xs text-neutral-400 mb-1">Nome da opção</label>
                                                    <input wire:model="optionName" placeholder="Ex: Médio"
                                                           class="w-full px-3 py-1.5 rounded-lg bg-neutral-950 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                                </div>
                                                <div class="w-28">
                                                    <label class="block text-xs text-neutral-400 mb-1">Adicional R$</label>
                                                    <input wire:model="optionPrice" type="number" step="0.01" min="0" placeholder="0,00"
                                                           class="w-full px-3 py-1.5 rounded-lg bg-neutral-950 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 pt-1">
                                                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                                                    {{ $editingOptionId ? 'Atualizar' : 'Criar' }}
                                                </x-admin.button>
                                                <x-admin.button type="button" variant="ghost" wire:click="$set('showOptionForm', false)">
                                                    Cancelar
                                                </x-admin.button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="flex items-center gap-2 py-2">
                                <p class="text-xs text-neutral-500">Nenhum atributo cadastrado para este produto</p>
                            </div>
                        @endforelse

                        <button wire:click="openCreateAttribute({{ $product->id }})"
                                class="text-xs text-amber-400 hover:text-amber-300 transition-colors inline-flex items-center gap-1 mt-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Adicionar atributo
                        </button>
                    </div>
                </div>
            </x-admin.card>
        @empty
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto mb-4 text-neutral-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-semibold text-neutral-400 mb-2">Nenhum produto</h3>
                <p class="text-sm text-neutral-600 mb-4">Comece criando seu primeiro produto</p>
                <button wire:click="openCreateProduct"
                        class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-neutral-950 font-semibold rounded-xl transition-all duration-200 hover:scale-[1.02]">
                    Criar Produto
                </button>
            </div>
        @endforelse
    {{-- ========== PONTOS VIEW ========== --}}
    @elseif ($view === 'pontos' && auth()->user()->isAdmin() && $tenant->isPaid())
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-sm text-neutral-400">Selecione os produtos que podem ser trocados por pontos e defina o custo em pontos.</p>
            </div>
            @php
                $pointsProducts = \App\Models\Product::where('tenant_id', auth()->user()->tenant_id)
                    ->where('status', 'active')
                    ->with('category')
                    ->orderBy('name')
                    ->get();
            @endphp
            @forelse ($pointsProducts as $product)
                <div class="flex items-center gap-4 p-4 rounded-2xl bg-neutral-900/50 border border-neutral-800 hover:border-emerald-500/20 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-neutral-800 overflow-hidden shrink-0">
                        @if ($product->image_url)
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-neutral-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm">{{ $product->name }}</p>
                        <p class="text-xs text-neutral-500">{{ $product->category?->name }} &bull; R$ {{ number_format($product->price, 2, ',', '.') }}</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500 text-xs">pts</span>
                            <input wire:model="pointsPrices.{{ $product->id }}" type="number" step="1" min="0" placeholder="---"
                                   class="w-28 pl-8 pr-3 py-2 rounded-xl bg-neutral-950 border border-neutral-800 text-white text-sm placeholder-neutral-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                        </div>
                        <button wire:click="savePointsProduct({{ $product->id }})"
                                class="px-3 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-neutral-950 text-xs font-semibold transition-all hover:scale-[1.02] disabled:opacity-50"
                                wire:loading.attr="disabled">
                            Salvar
                        </button>
                        @if ($product->points_price)
                            <button wire:click="clearPointsProduct({{ $product->id }})"
                                    wire:confirm="Remover {{ $product->name }} da troca por pontos?"
                                    class="p-2 rounded-xl text-neutral-500 hover:text-red-400 hover:bg-red-500/10 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-16 text-neutral-500">
                    <p class="text-lg font-medium text-neutral-300 mb-1">Nenhum produto ativo</p>
                    <p class="text-sm">Crie produtos no cardapio primeiro</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ========== CATEGORY FORM MODAL ========== --}}
    <x-admin.modal show="showCategoryForm" maxWidth="max-w-lg" title="{{ $editingCategoryId ? 'Editar' : 'Nova' }} Categoria">
        <form wire:submit="saveCategory" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Nome *</label>
                <input wire:model.live="categoryName" type="text" placeholder="Ex: Hambúrgueres"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('categoryName') border-red-500 @enderror">
                @error('categoryName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Slug *</label>
                <input wire:model="categorySlug" type="text" placeholder="hamburgueres"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('categorySlug') border-red-500 @enderror">
                @error('categorySlug') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Posição</label>
                <input wire:model="categoryPosition" type="number" min="0"
                       class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
            </div>
            <div class="md:col-span-3 flex items-center gap-3 pt-2">
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled" class="flex items-center gap-2">
                    <span wire:loading.remove>{{ $editingCategoryId ? 'Atualizar' : 'Criar' }} Categoria</span>
                    <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                </x-admin.button>
                <x-admin.button type="button" variant="ghost" wire:click="resetForm">
                    Cancelar
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ========== PRODUCT FORM MODAL ========== --}}
    <x-admin.modal show="showProductForm" maxWidth="max-w-xl" title="{{ $editingProductId ? 'Editar' : 'Novo' }} Produto">
        <form wire:submit="saveProduct" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Nome *</label>
                    <input wire:model="productName" type="text" placeholder="Ex: Smash Burger Duplo"
                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('productName') border-red-500 @enderror">
                    @error('productName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Descrição</label>
                    <textarea wire:model="productDescription" rows="2" placeholder="Descrição do produto..."
                              class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Preço *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500 text-sm">R$</span>
                        <input wire:model="productPrice" type="number" step="0.01" min="0" placeholder="0,00"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('productPrice') border-red-500 @enderror">
                    </div>
                    @error('productPrice') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                @if ($tenant->isPaid())
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Preço em Pontos <span class="text-xs text-neutral-500">(opcional)</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500 text-sm">pts</span>
                        <input wire:model="productPointsPrice" type="number" step="1" min="0" placeholder="Deixe vazio para desabilitar"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                    </div>
                    <p class="mt-1 text-[10px] text-neutral-500">Produtos com preço em pontos aparecerão na aba "Pontos" do cliente para troca.</p>
                </div>
                @else
                <div>
                    <div class="p-3 rounded-lg bg-amber-500/5 border border-amber-500/10">
                        <p class="text-xs text-amber-400 flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Programa de pontos exclusivo do plano Premium.
                            <a href="{{ route('subscription.checkout') }}" class="text-amber-400 hover:text-amber-300 underline font-medium">Fazer Upgrade</a>
                        </p>
                    </div>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Categoria *</label>
                    <select wire:model="productCategoryId"
                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('productCategoryId') border-red-500 @enderror">
                        <option value="">Selecione...</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('productCategoryId') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Estoque</label>
                    <input wire:model="productStock" type="number" step="1" min="0" placeholder="0"
                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('productStock') border-red-500 @enderror">
                    <p class="mt-1 text-[10px] text-neutral-500">Quantidade disponível em estoque. 0 = produto indisponível para venda.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Status</label>
                    <select wire:model="productStatus"
                            class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Imagem (URL)</label>
                    <input wire:model="productImageUrl" type="url" placeholder="https://..."
                           class="w-full px-4 py-2.5 rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-300 mb-2">Ou enviar imagem</label>
                    <input wire:model="productImage" type="file" accept="image/jpeg,image/png,image/webp"
                           class="w-full text-sm text-neutral-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-500 file:text-neutral-950 hover:file:bg-amber-400 file:cursor-pointer file:transition-colors">
                </div>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled" class="flex items-center gap-2">
                    <span wire:loading.remove>{{ $editingProductId ? 'Atualizar' : 'Criar' }} Produto</span>
                    <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                </x-admin.button>
                <x-admin.button type="button" variant="ghost" wire:click="resetProductForm">
                    Cancelar
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ========== ATTRIBUTE FORM MODAL ========== --}}
    <x-admin.modal show="showAttributeForm" maxWidth="max-w-lg" title="{{ $editingAttributeId ? 'Editar' : 'Novo' }} Atributo">
        <form wire:submit="saveAttribute" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-neutral-400 mb-1">Nome do atributo</label>
                    <input wire:model="attributeName" placeholder="Ex: Ponto da carne"
                           class="w-full px-3 py-1.5 rounded-lg bg-neutral-950 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    @error('attributeName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs text-neutral-400 mb-1">Preço</label>
                    <input wire:model="attributePrice" type="number" step="0.01" min="0" placeholder="0,00"
                           class="w-full px-3 py-1.5 rounded-lg bg-neutral-950 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    @error('attributePrice') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs text-neutral-400 mb-1">Tipo</label>
                    <select wire:model="attributeType"
                            class="w-full px-3 py-1.5 rounded-lg bg-neutral-950 border border-neutral-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="single">Única escolha</option>
                        <option value="multiple">Múltipla escolha</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-neutral-300 cursor-pointer">
                    <input type="checkbox" wire:model="attributeRequired" class="rounded bg-neutral-800 border-neutral-600 text-amber-500 focus:ring-amber-500">
                    Obrigatório
                </label>
            </div>
            <div class="flex items-center gap-2 pt-1">
                <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled">
                    {{ $editingAttributeId ? 'Atualizar' : 'Criar' }} Atributo
                </x-admin.button>
                <x-admin.button type="button" variant="ghost" wire:click="resetAttributeForm">
                    Cancelar
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ========== STOCK ADJUSTMENT MODAL ========== --}}
    <x-admin.modal show="showStockModal" maxWidth="max-w-md" title="Ajustar Estoque">
        <form wire:submit="adjustStock" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-neutral-300 mb-2">Nova quantidade em estoque</label>
                <input wire:model="stockAdjustmentValue" type="number" step="1" min="0" placeholder="0"
                       class="w-full px-4 py-3 text-2xl font-bold text-center rounded-xl bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                <p class="mt-2 text-xs text-neutral-500">Defina a quantidade total disponível para venda. 0 = produto indisponível.</p>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <x-admin.button type="submit" variant="primary" class="flex items-center gap-2">
                    <span wire:loading.remove>Salvar Estoque</span>
                    <span wire:loading><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                </x-admin.button>
                <x-admin.button type="button" variant="ghost" wire:click="closeStockModal">
                    Cancelar
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ========== STOCK MOVEMENTS MODAL ========== --}}
    <x-admin.modal show="showStockMovementsModal" maxWidth="max-w-2xl" title="Histórico de Estoque: {{ $stockMovementsProductName }}">
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @forelse ($stockMovementsData as $movement)
                <div class="flex items-center justify-between p-3 rounded-xl bg-neutral-900/50 border border-neutral-800">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="shrink-0">
                            @if ($movement['quantity'] > 0)
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-500/10 text-red-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                </span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-neutral-200">
                                {{ $movement['quantity'] > 0 ? '+' : '' }}{{ $movement['quantity'] }} unidades
                                <span class="text-xs text-neutral-500 ml-1">({{ $movement['stock_before'] }} → {{ $movement['stock_after'] }})</span>
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ \App\Models\StockMovement::TYPES[$movement['type']] ?? $movement['type'] }}
                                @if ($movement['description'])
                                    &bull; {{ $movement['description'] }}
                                @endif
                            </p>
                            @if ($movement['user'])
                                <p class="text-[10px] text-neutral-600 mt-0.5">por {{ $movement['user']['name'] ?? 'Sistema' }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="text-[10px] text-neutral-600 shrink-0">{{ \Carbon\Carbon::parse($movement['created_at'])->format('d/m H:i') }}</span>
                </div>
            @empty
                <div class="text-center py-8 text-neutral-500">
                    <p class="text-sm">Nenhuma movimentação registrada.</p>
                </div>
            @endforelse
        </div>
        <div class="flex justify-end pt-3">
            <x-admin.button type="button" variant="ghost" wire:click="closeStockMovements">
                Fechar
            </x-admin.button>
        </div>
    </x-admin.modal>
</div>
