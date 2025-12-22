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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('provider');
            $table->string('method');
            $table->string('authorization_code')->nullable();
            $table->string('customer_code')->nullable();
            $table->string('access_code')->nullable();
            $table->string('reference')->nullable();
            $table->string('card_type')->nullable();
            $table->string('last_four')->nullable();
            $table->string('expiry_month')->nullable();
            $table->string('expiry_year')->nullable();
            $table->string('account_name')->nullable();
            $table->string('bank')->nullable();
            $table->string('brand')->nullable();
            $table->string('currency')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
