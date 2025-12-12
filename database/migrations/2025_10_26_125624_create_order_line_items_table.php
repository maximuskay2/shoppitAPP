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
        Schema::create('order_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreignUuid('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('subtotal');
            $table->string('type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_line_items');
    }
};
