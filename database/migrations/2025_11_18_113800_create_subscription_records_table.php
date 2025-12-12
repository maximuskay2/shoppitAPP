<?php

use App\Modules\Transaction\Enums\SubscriptionRecordStatusEnum;
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
        Schema::create('subscription_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->foreignUuid('subscription_plan_id')->references('id')->on('subscription_plans')->cascadeOnDelete();
            $table->enum('status', SubscriptionRecordStatusEnum::toArray())->nullable()->default('PENDING');
            $table->unsignedBigInteger('amount');
            $table->string('currency')->default('NGN');
            $table->string('reference');
            $table->string('payment_processor')->nullable();
            $table->string('processor_transaction_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_records');
    }
};
