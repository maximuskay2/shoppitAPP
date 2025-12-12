<?php

use App\Modules\Transaction\Enums\SubscriptionStatusEnum;
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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('key')->unique();
            $table->string('name');
            $table->enum('status', SubscriptionStatusEnum::toArray())->index()->nullable()->default('ACTIVE');
            $table->unsignedBigInteger('amount');
            $table->string('currency')->default('NGN');
            $table->string('interval')->default('monthly')->nullable();
            $table->string('paystack_plan_id')->nullable();
            $table->string('provider')->default('paystack');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
