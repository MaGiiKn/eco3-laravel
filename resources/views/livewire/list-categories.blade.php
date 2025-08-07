<?php

use App\Models\Category;
use Livewire\Volt\Component;

new class extends Component {

    public $categories;

    public function mount()
    {
        $this->categories = Category::all();
    }
    
}; ?>

<div>
    @forelse ($categories as $category)
        {{ $category->name }}
    @empty
        <p>No categories found</p>
    @endforelse
</div>