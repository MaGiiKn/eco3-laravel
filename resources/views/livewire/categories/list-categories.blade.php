<?php

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    #[
        Validate(
            'required|string|max:25|unique:categories,name',
            message: [
                'name.required' => 'El nombre es obligatorio.',
                'name.string' => 'El nombre debe ser texto válido.',
                'name.max' => 'Máximo 25 caracteres permitidos.',
                'name.unique' => 'Ya existe una categoría con este nombre.',
            ],
        ),
    ]
    public string $name = '';

    public ?Category $selectedCategory = null;

    public Collection $categories;

    public function createCategory(): void
    {
        $this->validate();

        Category::create([
            'name' => $this->name,
        ]);

        $this->name = '';
        $this->categories = Category::all();
        $this->dispatch('modal-close', 'create-category');
    }

    public function delete(): void
    {
        if ($this->selectedCategory) {
            $this->selectedCategory->delete();
            $this->categories = Category::all();
            $this->dispatch('modal-close', 'delete-category');
        }
    }

    public function update(): void
    {
        $this->validate();

        $this->selectedCategory->update([
            'name' => $this->name,
        ]);

        $this->name = '';

        $this->categories = Category::all();
        $this->dispatch('modal-close', 'edit-categorie');
    }

    public function updatedName($value)
    {
        $this->name = ucfirst(strtolower($value));
        $this->validateOnly('name');
    }

    public function selectCategory(Category $category): void
    {
        $this->selectedCategory = $category;
    }

    public function mount(): void
    {
        $this->categories = Category::all();
        $this->dispatch('$refresh');
    }
}; ?>

<div>
    <header class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Lista de categorias</h1>
        <flux:modal.trigger name="create-category">
            <flux:button variant="primary">Crear categoria</flux:button>
        </flux:modal.trigger>
    </header>

    {{-- Modal crear categoria --}}

    <flux:modal name="create-category" class="md:w-96" x-on:close="$wire.name = ''">
        <form wire:submit="createCategory" class="space-y-6">
            <div>
                <flux:heading size="lg">Crear categoria</flux:heading>
                <flux:text class="mt-2">Crea una categoria para productos</flux:text>
            </div>

            <flux:input wire:model.live="name" label="Nombre categoria" placeholder="Nombre categoria" autofocus />

            <div class="flex">
                <flux:spacer />

                <flux:button type="submit" variant="primary">Crear</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal eliminar categoria --}}

    <flux:modal name="delete-category" class="min-w-[22rem]">
        <form wire:submit="delete">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Eliminar categoria?</flux:heading>
                    <flux:text class="mt-2">
                        <p>Estas a punto de eliminar <b>"{{ $selectedCategory?->name }}"</b>.</p>
                        <p>Esta accion no puede ser revertida.</p>
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

    {{-- Modal editar categoria --}}

    <flux:modal name="edit-categorie" class="md:w-96" x-on:close="$wire.name = ''">
        <form wire:submit="update">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Editar categoria</flux:heading>
                    <flux:text class="mt-2">Edita la categoria <b>{{ $selectedCategory?->name }}</b>.</flux:text>
                </div>

                <flux:input wire:model.live="name" label="Nombre categoria" placeholder="Nombre categoria" autofocus
                    value="{{ $selectedCategory?->name }}" />

                <div class="flex">
                    <flux:spacer />

                    <flux:button type="submit" variant="primary">Editar</flux:button>
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
                    @forelse ($categories as $category)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            <td
                                class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-200 flex items-center gap-2 justify-between">
                                {{ $category->name }}

                                {{-- Modal pantallas grandes --}}
                                <div>
                                    <flux:dropdown>
                                        <flux:button icon:trailing="ellipsis-vertical" variant="outline"></flux:button>

                                        <flux:menu class="">
                                            <flux:modal.trigger name="edit-categorie">
                                                <flux:button wire:click="selectCategory({{ $category->id }})"
                                                    icon="pencil-square" kbd="⌘S">Editar</flux:button>
                                            </flux:modal.trigger>
                                            <flux:modal.trigger name="delete-category">
                                                <flux:button wire:click="selectCategory({{ $category->id }})"
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
                                No hay categorias
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</div>
