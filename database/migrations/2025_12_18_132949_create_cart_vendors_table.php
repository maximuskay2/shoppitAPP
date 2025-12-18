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
        Schema::create('cart_vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->references('id')->on('carts')->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cart_id', 'vendor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_vendors');
    }
};
