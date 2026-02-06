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
        Schema::create('driver_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('driver_id')->index()->references('id')->on('users')->cascadeOnDelete();
            $table->bigInteger('amount')->default(0);
            $table->string('currency')->nullable();
            $table->string('status')->default('PENDING')->index();
            $table->string('reference')->nullable()->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_payouts');
    }
};
