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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUuid('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
            $table->bigInteger('amount'); // signed, stored in kobo
            $table->bigInteger('balance_after'); 
            $table->string('type');
            $table->string('reference')->nullable()->unique();
            $table->string('external_reference')->nullable()->unique();
            $table->string('description')->nullable();
            $table->bigInteger('transaction_fee')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
