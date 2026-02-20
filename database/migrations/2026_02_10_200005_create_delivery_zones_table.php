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
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('areas')->nullable(); // Array of area names, e.g. ["Victoria Island", "Ikoyi"]
            $table->decimal('base_fee', 12, 2)->default(0);
            $table->decimal('per_km_fee', 12, 2)->default(0);
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('estimated_time_min')->default(30);
            $table->unsignedSmallInteger('estimated_time_max')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
