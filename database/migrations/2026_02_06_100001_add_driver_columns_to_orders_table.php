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
            $table->foreignUuid('driver_id')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable()->index();
            $table->dateTime('picked_up_at')->nullable()->index();
            $table->dateTime('delivered_at')->nullable()->index();
            $table->string('otp_code')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['driver_id', 'assigned_at', 'picked_up_at', 'delivered_at', 'otp_code']);
        });
    }
};
