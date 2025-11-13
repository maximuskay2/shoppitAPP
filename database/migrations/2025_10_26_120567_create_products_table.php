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
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreignUuid('product_category_id')->references('id')->on('product_categories')->cascadeOnDelete();
            $table->string('name')->index();
            $table->json('avatar')->nullable(); //Max 5 images
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2)->default(0.00);
            $table->decimal('discount_price', 8, 2)->default(0.00);
            $table->unsignedInteger('approximate_delivery_time')->index()->default(0);
            $table->boolean('is_available')->default(true);
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
