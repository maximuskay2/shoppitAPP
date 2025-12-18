<?php

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Models\Cart;
use App\Modules\Commerce\Models\CartVendor;
use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\Settings;
use App\Modules\User\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CartService
{
    public $currency;

    public function __construct()
    {
        $this->currency = Settings::where('name', 'currency')->first()->value;
    }

    public function getCart(User $user)
    {
        $cart = Cart::with(['vendors.vendor.user', 'vendors.items.product'])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart) {
            $cart = $user->cart()->create();
        }

        return $cart;
    }

    public function addItem(User $user, array $data)
    {
        $product = Product::findOrFail($data['product_id']);
        $quantity = $data['quantity'] ?? 1;

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1');
        }

        DB::beginTransaction();
        try {
            // Get or create cart
            $cart = $this->getCart($user);

            // Get or create cart vendor
            $cartVendor = CartVendor::firstOrCreate([
                'cart_id' => $cart->id,
                'vendor_id' => $product->vendor_id,
            ]);

            // Check if product already exists in cart vendor
            $existingItem = $cartVendor->items()->where('product_id', $product->id)->first();

            if ($existingItem) {
                // Update quantity and subtotal
                $newQuantity = $existingItem->quantity + $quantity;
                $existingItem->update([
                    'quantity' => $newQuantity,
                    'subtotal' => Money::of($product->price->getAmount()->toFloat() * $newQuantity, $this->currency),
                ]);
                $cartItem = $existingItem;
            } else {
                // Create new cart item
                $cartItem = $cartVendor->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'subtotal' => Money::of($product->price->getAmount()->toFloat() * $quantity, $this->currency),
                ]);
            }

            DB::commit();
            return $cart->fresh(['vendors.vendor.user', 'vendors.items.product']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateItemQuantity(User $user, string $itemId, int $quantity)
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1');
        }

        $cart = $this->getCart($user);
        
        // Query cart item directly to avoid ambiguous column issue
        $cartItem = DB::table('cart_items')
            ->join('cart_vendors', 'cart_items.cart_vendor_id', '=', 'cart_vendors.id')
            ->where('cart_vendors.cart_id', $cart->id)
            ->where('cart_items.id', $itemId)
            ->select('cart_items.*')
            ->first();

        if (!$cartItem) {
            throw new InvalidArgumentException('Cart item not found');
        }

        DB::table('cart_items')
            ->where('id', $itemId)
            ->update([
                'quantity' => $quantity,
                'subtotal' => $cartItem->price * $quantity,
                'updated_at' => now(),
            ]);

        return $cart->fresh(['vendors.vendor.user', 'vendors.items.product']);
    }

    public function removeItem(User $user, string $itemId)
    {
        $cart = $this->getCart($user);
        
        // Query cart item directly to avoid ambiguous column issue
        $cartItem = DB::table('cart_items')
            ->join('cart_vendors', 'cart_items.cart_vendor_id', '=', 'cart_vendors.id')
            ->where('cart_vendors.cart_id', $cart->id)
            ->where('cart_items.id', $itemId)
            ->select('cart_items.*', 'cart_items.cart_vendor_id')
            ->first();

        if (!$cartItem) {
            throw new InvalidArgumentException('Cart item not found');
        }

        $cartVendorId = $cartItem->cart_vendor_id;
        
        // Delete the cart item
        DB::table('cart_items')->where('id', $itemId)->delete();

        // If cart vendor has no more items, delete the cart vendor
        $remainingItems = DB::table('cart_items')->where('cart_vendor_id', $cartVendorId)->count();
        if ($remainingItems === 0) {
            DB::table('cart_vendors')->where('id', $cartVendorId)->delete();
        }

        return $cart->fresh(['vendors.vendor.user', 'vendors.items.product']);
    }

    public function clearCart(User $user)
    {
        $cart = $this->getCart($user);
        
        // Delete all cart vendors (cascade will delete items)
        $cart->vendors()->delete();

        return true;
    }
}
