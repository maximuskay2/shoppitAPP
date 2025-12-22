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
        Schema::table('cart_vendors', function (Blueprint $table) {
            $table->foreignUuid('coupon_id')->nullable()->after('vendor_id')->references('id')->on('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->unsignedBigInteger('coupon_discount')->default(0)->after('coupon_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_vendors', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'coupon_code', 'coupon_discount']);
        });
    }
};
