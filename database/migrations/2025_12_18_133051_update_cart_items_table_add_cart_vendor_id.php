<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            Schema::table('cart_items', function (Blueprint $table) {
                if (!Schema::hasColumn('cart_items', 'cart_vendor_id')) {
                    $table->uuid('cart_vendor_id')->nullable()->after('id');
                }
            });
            return;
        }

        // First, migrate existing cart items to cart_vendors
        $cartItems = DB::table('cart_items')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->select('cart_items.cart_id', 'products.vendor_id')
            ->distinct()
            ->get();

        $uuidExpression = $driver === 'pgsql' ? 'gen_random_uuid()' : 'UUID()';

        foreach ($cartItems as $item) {
            // Create cart_vendor entry if doesn't exist
            DB::table('cart_vendors')->updateOrInsert(
                ['cart_id' => $item->cart_id, 'vendor_id' => $item->vendor_id],
                [
                    'id' => DB::raw($uuidExpression),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Add cart_vendor_id column as nullable first
        Schema::table('cart_items', function (Blueprint $table) {
            $table->uuid('cart_vendor_id')->nullable()->after('id');
        });

        // Update cart_vendor_id for existing items
        if ($driver === 'pgsql') {
            DB::statement('
                UPDATE cart_items
                SET cart_vendor_id = cart_vendors.id
                FROM cart_vendors
                JOIN products ON cart_vendors.vendor_id = products.vendor_id
                WHERE cart_items.cart_id = cart_vendors.cart_id
                AND cart_items.product_id = products.id
            ');
        } else {
            DB::statement('
                UPDATE cart_items
                JOIN cart_vendors ON cart_items.cart_id = cart_vendors.cart_id
                JOIN products ON cart_items.product_id = products.id
                    AND cart_vendors.vendor_id = products.vendor_id
                SET cart_items.cart_vendor_id = cart_vendors.id
            ');
        }

        // Now make it non-nullable and add foreign key
        Schema::table('cart_items', function (Blueprint $table) {
            $table->uuid('cart_vendor_id')->nullable(false)->change();
            $table->foreign('cart_vendor_id')->references('id')->on('cart_vendors')->cascadeOnDelete();
            $table->dropForeign(['cart_id']);
            $table->dropColumn('cart_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['cart_vendor_id']);
            $table->dropColumn('cart_vendor_id');
            $table->foreignUuid('cart_id')->after('id')->references('id')->on('carts')->cascadeOnDelete();
        });
    }
};
