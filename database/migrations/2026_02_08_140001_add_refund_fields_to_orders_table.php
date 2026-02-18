<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'refund_status')) {
                $table->string('refund_status')->nullable()->index();
            }
            if (!Schema::hasColumn('orders', 'refund_reason')) {
                $table->text('refund_reason')->nullable();
            }
            if (!Schema::hasColumn('orders', 'refund_requested_at')) {
                $table->timestamp('refund_requested_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'refund_processed_at')) {
                $table->timestamp('refund_processed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'refund_status')) {
                $table->dropColumn('refund_status');
            }
            if (Schema::hasColumn('orders', 'refund_reason')) {
                $table->dropColumn('refund_reason');
            }
            if (Schema::hasColumn('orders', 'refund_requested_at')) {
                $table->dropColumn('refund_requested_at');
            }
            if (Schema::hasColumn('orders', 'refund_processed_at')) {
                $table->dropColumn('refund_processed_at');
            }
        });
    }
};
