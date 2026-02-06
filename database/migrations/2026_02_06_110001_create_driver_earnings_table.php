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
        Schema::create('driver_earnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('driver_id')->index()->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUuid('order_id')->index()->references('id')->on('orders')->cascadeOnDelete();
            $table->foreignUuid('payout_id')->nullable()->index()->references('id')->on('driver_payouts')->nullOnDelete();
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('commission_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->string('currency')->nullable();
            $table->string('status')->default('PENDING')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_earnings');
    }
};
