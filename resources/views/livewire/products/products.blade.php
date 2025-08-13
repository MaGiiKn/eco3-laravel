<?php

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    #[
        Validate(
            'required|string|max:100|unique:products,name',
            message: [
                'name.required' => 'El nombre es obligatorio.',
                'name.string' => 'El nombre debe ser texto válido.',
                'name.max' => 'Máximo 100 caracteres permitidos.',
                'name.unique' => 'Ya existe un producto con este nombre.',
            ],
        ),
    ]
    public string $name = '';

    #[
        Validate(
            'required|exists:categories,id',
            message: [
                'category_id.required' => 'La categoría es obligatoria.',
                'category_id.exists' => 'La categoría seleccionada no es válida.',
            ],
        ),
    ]
    public $category_id = null;
    public $price = null;

    public ?Product $selectedProduct = null;

    public Collection $products;

    public Collection $categories;

    public function createProduct(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:100|unique:products,name',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        $slug = $this->generateUniqueSlug($validated['name']);
        $sku = $this->generateUniqueSku($validated['name']);

        Product::create([
            'name' => $validated['name'],
            'sku' => $sku,
            'price' => $validated['price'],
            'slug' => $slug,
            'category_id' => $validated['category_id'],
        ]);

        $this->reset(['name', 'price', 'category_id']);
        $this->products = Product::with('category')->get();
        $this->dispatch('modal-close', 'create-product');
    }

    public function delete(): void
    {
        if ($this->selectedProduct) {
            $this->selectedProduct->delete();
            $this->products = Product::with('category')->get();
            $this->dispatch('modal-close', 'delete-product');
        }
    }

    public function update(): void
    {
        $this->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('products', 'name')->ignore($this->selectedProduct?->id),
            ],
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        $slug = $this->selectedProduct && $this->selectedProduct->name !== $this->name
            ? $this->generateUniqueSlug($this->name, $this->selectedProduct->id)
            : $this->selectedProduct->slug;

        $sku = $this->selectedProduct && $this->selectedProduct->name !== $this->name
            ? $this->generateUniqueSku($this->name, $this->selectedProduct->id)
            : $this->selectedProduct->sku;

        $this->selectedProduct->update([
            'name' => $this->name,
            'sku' => $sku,
            'price' => $this->price,
            'slug' => $slug,
            'category_id' => $this->category_id,
        ]);

        $this->reset(['name', 'price', 'category_id']);
        $this->products = Product::with('category')->get();
        $this->dispatch('modal-close', 'edit-product');
    }

    public function updatedName($value)
    {
        $this->name = ucfirst(strtolower($value));
        $this->validateOnly('name');
    }

    public function selectProduct(Product $product): void
    {
        $this->selectedProduct = $product;
        $this->name = $product->name;
        $this->price = (int) round($product->price);
        $this->category_id = $product->category_id;
    }

    public function mount(): void
    {
        $this->products = Product::with('category')->get();
        $this->categories = Category::all();
        $this->dispatch('$refresh');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function generateUniqueSku(string $name, ?int $ignoreId = null): string
    {
        $base = strtoupper(Str::slug($name, ''));
        $base = preg_replace('/[^A-Z0-9]/', '', $base) ?: 'PRODUCTO';
        $base = substr($base, 0, 8);

        $sku = $base;
        $i = 1;
        while (Product::where('sku', $sku)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $suffix = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $sku = $base . $suffix;
            $i++;
        }

        return $sku;
    }
}; ?>

<div>
    <header class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Lista de productos</h1>
        <flux:modal.trigger name="create-product">
            <flux:button variant="primary">Crear producto</flux:button>
        </flux:modal.trigger>
    </header>

    {{-- Modal crear producto --}}

    <flux:modal name="create-product" class="md:w-[28rem]" x-on:close="$wire.name = ''; $wire.price=null; $wire.category_id = null">
        <form wire:submit="createProduct" class="space-y-6">
            <div>
                <flux:heading size="lg">Crear producto</flux:heading>
                <flux:text class="mt-2">Crea un producto y asígnale una categoría</flux:text>
            </div>

            <flux:input wire:model.live="name" label="Nombre del producto" placeholder="Nombre del producto" autofocus />
            <div
                x-data="{
                    priceRaw: @entangle('price'),
                    priceText: '',
                    format(n) {
                        if (n === null || n === undefined || n === '') return '';
                        n = n.toString().replace(/\\D/g, '');
                        if (!n) return '';
                        return n.replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.');
                    }
                }"
                x-init="$watch('priceRaw', v => priceText = format(v)); priceText = format(priceRaw)"
            >
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Precio</label>
                <input
                    type="text"
                    x-model="priceText"
                    inputmode="numeric"
                    pattern="[0-9\.]*"
                    placeholder="0"
                    class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    @input="let v=$event.target.value.replace(/\\D/g,''); priceRaw = v ? parseInt(v,10) : null; priceText = format(v)"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Categoría</label>
                <select wire:model.live="category_id" class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Selecciona una categoría</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Crear</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal eliminar producto --}}

    <flux:modal name="delete-product" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">¿Eliminar producto?</flux:heading>
                    <flux:text class="mt-2">
                        <p>Estas a punto de eliminar <b>"{{ $selectedProduct?->name }}"</b>.</p>
                        <p>Esta acción no puede ser revertida.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger">Eliminar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Modal editar producto --}}

    <flux:modal name="edit-product" class="md:w-[28rem]" x-on:close="$wire.name = ''; $wire.price=null; $wire.category_id = null">
        <form wire:submit="update">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Editar producto</flux:heading>
                    <flux:text class="mt-2">Edita el producto <b>{{ $selectedProduct?->name }}</b>.</flux:text>
                </div>

                <flux:input wire:model.live="name" label="Nombre del producto" placeholder="Nombre del producto" autofocus />

                <div
                    x-data="{
                        priceRaw: @entangle('price'),
                        priceText: '',
                        format(n) {
                            if (n === null || n === undefined || n === '') return '';
                            n = n.toString().replace(/\\D/g, '');
                            if (!n) return '';
                            return n.replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.');
                        }
                    }"
                    x-init="$watch('priceRaw', v => priceText = format(v)); priceText = format(priceRaw)"
                >
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Precio</label>
                    <input
                        type="text"
                        x-model="priceText"
                        inputmode="numeric"
                        pattern="[0-9\.]*"
                        placeholder="0"
                        class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @input="let v=$event.target.value.replace(/\\D/g,''); priceRaw = v ? parseInt(v,10) : null; priceText = format(v)"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1">Categoría</label>
                    <select wire:model.live="category_id" class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecciona una categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <main class="mt-4">
        <div
            class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
            <table class="w-full table-auto">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th scope="col"
                            class="text-left text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase tracking-wider px-4 py-3">
                            Nombre
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($products as $product)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            <td
                                class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-200 flex items-center gap-2 justify-between">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ $product->name }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                        SKU: {{ $product->sku }} · Precio: ${{ $product->price_formatted }} · Categoría: {{ $product->category?->name ?? '—' }}
                                    </span>
                                </div>

                                <div>
                                    <flux:dropdown>
                                        <flux:button icon:trailing="ellipsis-vertical" variant="outline"></flux:button>

                                        <flux:menu class="">
                                            <flux:modal.trigger name="edit-product">
                                                <flux:button wire:click="selectProduct({{ $product->id }})"
                                                    icon="pencil-square" kbd="⌘S">Editar</flux:button>
                                            </flux:modal.trigger>
                                            <flux:modal.trigger name="delete-product">
                                                <flux:button wire:click="selectProduct({{ $product->id }})"
                                                    icon="trash" kbd="⌘⌫" variant="danger">Eliminar
                                                </flux:button>
                                            </flux:modal.trigger>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No hay productos
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</div>
