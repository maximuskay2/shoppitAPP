<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes to frequently queried columns (skip if already exists)
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'status')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('status', 'users_status_idx');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index(['status', 'created_at'], 'orders_status_created_idx');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        if (Schema::hasTable('products')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->index(['vendor_id', 'is_active'], 'products_vendor_active_idx');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Create feature flags table
        if (!Schema::hasTable('feature_flags')) {
            Schema::create('feature_flags', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('key')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_enabled')->default(false);
                $table->json('conditions')->nullable(); // For percentage rollouts, user targeting, etc.
                $table->string('environment')->default('all'); // all, production, staging, development
                $table->timestamps();
            });
        }

        // Create system settings table for maintenance mode, etc.
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string'); // string, boolean, json, integer
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Remove indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['driver_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['product_category_id']);
            $table->dropIndex(['vendor_id', 'is_active']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_id']);
            $table->dropIndex(['read_at']);
            $table->dropIndex(['created_at']);
        });

        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('system_settings');
    }
};
