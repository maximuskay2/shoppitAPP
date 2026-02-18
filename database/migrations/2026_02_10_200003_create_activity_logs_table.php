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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100)->index();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_type', 50)->nullable();
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Composite index for subject lookups
            $table->index(['subject_type', 'subject_id']);
            
            // Index for time-based queries
            $table->index('created_at');
        });

        // Add SOS alerts table for driver emergencies
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('status', 20)->default('pending'); // pending, responding, resolved
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('driver_id');
            $table->index('status');
            $table->index('created_at');
        });

        // Add signature captures table for delivery proof
        Schema::create('signature_captures', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('order_id');
            $table->string('receiver_name');
            $table->text('signature_data'); // Base64 encoded signature image
            $table->string('signature_path')->nullable(); // Path to stored image file
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->unsignedBigInteger('captured_by'); // Driver ID
            $table->timestamps();

            $table->index('order_id');
            $table->index('captured_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_captures');
        Schema::dropIfExists('sos_alerts');
        Schema::dropIfExists('activity_logs');
    }
};
