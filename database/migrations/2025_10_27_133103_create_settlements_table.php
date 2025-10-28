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
        Schema::create('settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('vendor_amount', 10, 2);
            $table->decimal('platform_fee', 10, 2);
            $table->string('payment_gateway')->index();
            $table->string('currency')->index();
            $table->string('status')->index();
            $table->dateTime('settled_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
