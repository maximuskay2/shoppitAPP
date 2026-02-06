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
        Schema::create('delivery_radii', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index(); // e.g., 'default', 'premium'
            $table->decimal('radius_km', 8, 2); // Radius in kilometers
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Insert default global radius
        DB::table('delivery_radii')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'default',
            'radius_km' => 300,
            'description' => 'Default delivery radius in kilometers',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_radii');
    }
};
