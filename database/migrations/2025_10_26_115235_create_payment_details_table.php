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
        Schema::create('payment_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->string('bank_code')->index();
            $table->string('bank_name')->index();
            $table->string('account_number')->index();
            $table->string('account_name')->index();
            $table->string('paystack_subaccount_code')->index()->nullable();
            $table->string('paystack_recipient_code')->index()->nullable();
            $table->json('subaccount_codes')->nullable();
            $table->json('recipient_codes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};
