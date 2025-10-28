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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->string('status')->index();
            $table->string('email')->index();
            $table->string('tracking_id')->index();
            $table->text('order_notes')->nullable();
            $table->boolean('is_gift')->default(false);
            $table->text('receiver_delivery_address')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_email')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('currency')->nullable();
            $table->string('payment_reference')->index();
            $table->string('processor_transaction_id')->index();
            $table->decimal('delivery_fee', 10, 2)->default(0.00);
            $table->decimal('gross_total_amount', 10, 2)->default(0.00);
            $table->decimal('net_total_amount', 10, 2)->default(0.00);
            $table->dateTime('paid_at')->nullable()->index();
            $table->dateTime('dispatched_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable()->index();
            $table->dateTime('settled_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
