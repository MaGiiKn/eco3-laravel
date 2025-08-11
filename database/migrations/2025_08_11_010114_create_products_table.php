<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->string('sku')->unique();
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('stock')->default(0);
            $table->integer('views')->default(0);
            $table->integer('sales')->default(0);
            $table->integer('rating')->default(0);
            $table->integer('rating_count')->default(0);
            $table->foreignId('category_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
