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
        Schema::table('reviews', function (Blueprint $table) {
            // Drop the existing morphs columns
            $table->dropMorphs('reviewable');

            // Recreate with UUID for reviewable_id
            $table->string('reviewable_type');
            $table->uuid('reviewable_id');
            $table->index(['reviewable_type', 'reviewable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Drop the custom morphs columns
            $table->dropIndex(['reviewable_type', 'reviewable_id']);
            $table->dropColumn(['reviewable_type', 'reviewable_id']);

            // Recreate with default morphs (unsignedBigInteger)
            $table->morphs('reviewable');
        });
    }
};
