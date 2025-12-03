<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\Cart\AddToCartRequest;
use App\Http\Requests\Api\V1\User\Cart\ProcessCartRequest;
use App\Http\Requests\Api\V1\User\Cart\UpdateCartItemRequest;
use App\Http\Resources\Commerce\CartResource;
use App\Modules\Commerce\Models\Cart;
use App\Modules\Commerce\Models\CartItem;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\OrderLineItems;
use App\Modules\Commerce\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $cart = $user->cart;

            if (!$cart) {
                $cart = Cart::create(['user_id' => $user->id]);
            }

            return ShopittPlus::response(true, 'Cart retrieved successfully', 200, new CartResource($cart));
        } catch (InvalidArgumentException $e) {
            Log::error('GET CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve cart', 500);
        }
    }

    public function addItem(AddToCartRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $user = Auth::user();
            $cart = $user->cart;

            if (!$cart) {
                $cart = Cart::create(['user_id' => $user->id]);
            }

            $product = Product::findOrFail($validatedData['product_id']);

            if (!$product->is_available) {
                throw new InvalidArgumentException('Product is not available');
            }

            $existingItem = $cart->items()->where('product_id', $product->id)->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $validatedData['quantity'],
                    'subtotal' => ($existingItem->quantity + $validatedData['quantity']) * $product->price,
                ]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $validatedData['quantity'],
                    'price' => $product->price,
                    'subtotal' => $validatedData['quantity'] * $product->price,
                ]);
            }

            DB::commit();
            return ShopittPlus::response(true, 'Item added to cart successfully', 200, new CartResource($cart->fresh()));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('ADD TO CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ADD TO CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to add item to cart', 500);
        }
    }

    public function updateItem(UpdateCartItemRequest $request, $itemId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $user = Auth::user();
            $cart = $user->cart;

            if (!$cart) {
                throw new InvalidArgumentException('Cart not found');
            }

            $item = $cart->items()->findOrFail($itemId);

            if ($validatedData['quantity'] <= 0) {
                $item->delete();
            } else {
                $item->update([
                    'quantity' => $validatedData['quantity'],
                    'subtotal' => $validatedData['quantity'] * $item->price,
                ]);
            }

            DB::commit();
            return ShopittPlus::response(true, 'Cart item updated successfully', 200, new CartResource($cart->fresh()));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('UPDATE CART ITEM: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UPDATE CART ITEM: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update cart item', 500);
        }
    }

    public function removeItem(Request $request, $itemId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $cart = $user->cart;

            if (!$cart) {
                throw new InvalidArgumentException('Cart not found');
            }

            $item = $cart->items()->findOrFail($itemId);
            $item->delete();

            DB::commit();
            return ShopittPlus::response(true, 'Item removed from cart successfully', 200, new CartResource($cart->fresh()));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('REMOVE CART ITEM: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('REMOVE CART ITEM: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to remove item from cart', 500);
        }
    }

    public function clearCart(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $cart = $user->cart;

            if (!$cart) {
                throw new InvalidArgumentException('Cart not found');
            }

            $cart->items()->delete();

            DB::commit();
            return ShopittPlus::response(true, 'Cart cleared successfully', 200, new CartResource($cart->fresh()));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('CLEAR CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CLEAR CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to clear cart', 500);
        }
    }

    public function processCart(ProcessCartRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $user = Auth::user();
            $cart = $user->cart;

            if (!$cart || $cart->items->isEmpty()) {
                throw new InvalidArgumentException('Cart is empty');
            }

            foreach ($cart->items as $item) {
                if (!$item->product->is_available) {
                    throw new InvalidArgumentException("Product {$item->product->name} is no longer available");
                }
            }

            $grossTotal = $cart->items->sum('subtotal');
            $netTotal = $grossTotal;

            $trackingId = strtoupper(uniqid()) . '-' . time();
            $paymentReference = 'TRX-' . strtoupper(uniqid()) . '-' . time();

            $order = Order::create([
                'user_id' => $user->id,
                'vendor_id' => $cart->items->first()->product->vendor_id,
                'payment_reference' => $paymentReference,
                'processor_transaction_id' => "null",
                'status' => 'pending',
                'email' => $user->email,
                'tracking_id' => 'ORD-' . $trackingId,
                'order_notes' => $validatedData['order_notes'] ?? null,
                'is_gift' => $validatedData['is_gift'] ?? false,
                'receiver_delivery_address' => $validatedData['receiver_delivery_address'],
                'receiver_name' => $validatedData['receiver_name'] ?? $user->name,
                'receiver_email' => $validatedData['receiver_email'] ?? $user->email,
                'receiver_phone' => $validatedData['receiver_phone'] ?? null,
                'currency' => 'NGN',
                'delivery_fee' => 0.00,
                'gross_total_amount' => $grossTotal,
                'net_total_amount' => $netTotal,
            ]);

            foreach ($cart->items as $cartItem) {
                OrderLineItems::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'subtotal' => $cartItem->subtotal,
                    'type' => 'product',
                ]);
            }

            // $cart->items()->delete();

            DB::commit();

            return ShopittPlus::response(true, 'Order created successfully', 201, [
                'order_reference' => $order->tracking_id,
                'payment_reference' => $order->payment_reference,
                'order_id' => $order->id,
                'amount' => $netTotal
            ]);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('PROCESS CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PROCESS CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to process cart', 500);
        }
    }
}