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
        Schema::create('driver_payment_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('driver_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('bank_code')->index();
            $table->string('bank_name')->index();
            $table->string('account_number')->index();
            $table->string('account_name')->index();
            $table->string('paystack_recipient_code')->index()->nullable();
            $table->json('recipient_meta')->nullable();
            $table->timestamps();

            $table->unique('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_payment_details');
    }
};
