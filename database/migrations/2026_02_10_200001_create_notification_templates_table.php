<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->string('title');
                $table->text('body');
                $table->string('type')->default('push'); // push, email, sms
                $table->string('category')->nullable(); // order, promotion, system, etc.
                $table->json('variables')->nullable(); // e.g., ["order_id", "customer_name"]
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('scheduled_notifications')) {
            Schema::create('scheduled_notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('template_id')->nullable();
                $table->string('title');
                $table->text('body');
                $table->string('type')->default('push');
                $table->string('target_audience')->default('all'); // all, customers, vendors, drivers
                $table->json('target_user_ids')->nullable();
                $table->timestamp('scheduled_at');
                $table->timestamp('sent_at')->nullable();
                $table->string('status')->default('pending'); // pending, sent, failed, cancelled
                $table->integer('recipients_count')->default(0);
                $table->integer('delivered_count')->default(0);
                $table->integer('failed_count')->default(0);
                $table->timestamps();

                if (Schema::hasTable('notification_templates')) {
                    $table->foreign('template_id')->references('id')->on('notification_templates')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
        Schema::dropIfExists('notification_templates');
    }
};
