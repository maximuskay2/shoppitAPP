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
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
            $table->foreignUuid('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->references('id')->on('orders')->nullOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->timestamps();
            $table->unique(['coupon_id', 'user_id', 'order_id']);
            $table->index(['coupon_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
