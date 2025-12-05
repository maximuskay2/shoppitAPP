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
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->enum('discount_type', ['percent', 'flat']);
            $table->decimal('discount_amount', 10, 2)->nullable(); // For flat discounts
            $table->unsignedTinyInteger('percent')->nullable(); // For percentage discounts (0-100)
            $table->decimal('minimum_order_value', 10, 2)->default(0);
            $table->decimal('maximum_discount', 10, 2)->nullable(); // Cap for percentage discounts
            $table->unsignedInteger('usage_per_customer')->default(1);
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['vendor_id', 'is_active', 'is_visible']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
