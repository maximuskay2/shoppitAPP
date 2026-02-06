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
        Schema::table('orders', function (Blueprint $table) {
            // Delivery address coordinates (drop-off location)
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('receiver_phone');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            $table->index(['delivery_latitude', 'delivery_longitude']);
            
            // OTP was already added; ensure it exists
            if (!Schema::hasColumn('orders', 'otp_code')) {
                $table->string('otp_code')->nullable()->index();
            }
            
            // Cancellation reason for tracking
            if (!Schema::hasColumn('orders', 'cancelled_at')) {
                $table->dateTime('cancelled_at')->nullable()->index();
            }
            if (!Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->string('cancellation_reason')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivery_latitude', 'delivery_longitude']);
            $table->dropColumn(['delivery_latitude', 'delivery_longitude']);
            
            if (Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
        });
    }
};
