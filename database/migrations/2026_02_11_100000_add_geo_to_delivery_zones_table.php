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
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->decimal('center_latitude', 10, 7)->nullable()->after('description');
            $table->decimal('center_longitude', 10, 7)->nullable()->after('center_latitude');
            $table->decimal('radius_km', 8, 2)->nullable()->after('center_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn(['center_latitude', 'center_longitude', 'radius_km']);
        });
    }
};
