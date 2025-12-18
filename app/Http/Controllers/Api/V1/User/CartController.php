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
use App\Modules\Commerce\Models\Coupon;
use App\Modules\Commerce\Models\CouponUsage;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\OrderLineItems;
use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\Settings;
use App\Modules\Commerce\Services\CartService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService) {
        $this->cartService = $cartService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $cart = $this->cartService->getCart($user);

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
            $user = User::find(Auth::id());
            
            $this->cartService->addItem($user, $request->validated());            
            return ShopittPlus::response(true, 'Item added to cart successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('ADD TO CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('ADD TO CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to add item to cart', 500);
        }
    }

    public function updateItem(UpdateCartItemRequest $request, $itemId): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $user = Auth::user();
            
            $this->cartService->updateItemQuantity(
                $user,
                $itemId,
                $validatedData['quantity']
            );

            return ShopittPlus::response(true, 'Cart item updated successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE CART ITEM: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('UPDATE CART ITEM: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update cart item', 500);
        }
    }

    public function removeItem(Request $request, $itemId): JsonResponse
    {
        try {
            $user = Auth::user();
            $this->cartService->removeItem($user, $itemId);

            return ShopittPlus::response(true, 'Item removed from cart successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('REMOVE CART ITEM: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('REMOVE CART ITEM: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to remove item from cart', 500);
        }
    }

    public function clearCart(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $cart = $this->cartService->clearCart($user);

            return ShopittPlus::response(true, 'Cart cleared successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('CLEAR CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
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
            $cart = $this->cartService->getCart($user->id);

            if (!$cart || $cart->vendors->isEmpty()) {
                throw new InvalidArgumentException('Cart is empty');
            }

            // Validate all products are still available
            foreach ($cart->vendors as $cartVendor) {
                foreach ($cartVendor->items as $item) {
                    if (!$item->product->is_available) {
                        throw new InvalidArgumentException("Product {$item->product->name} is no longer available");
                    }
                }
            }

            // Note: Multi-vendor checkout - this currently creates one order per cart
            // If you want separate orders per vendor, you'll need to loop through vendors
            $firstVendor = $cart->vendors->first();
            $grossTotal = $cart->total();
            $netTotal = $grossTotal;
            $coupon = null;
            $couponDiscount = 0;

            // Handle coupon application
            if (!empty($validatedData['coupon_code'])) {
                $coupon = Coupon::where('code', $validatedData['coupon_code'])
                    ->where('vendor_id', $firstVendor->vendor_id)
                    ->first();

                if ($coupon) {
                    // Validate coupon
                    if (!$coupon->isValidForUser($user->id)) {
                        throw new InvalidArgumentException('Coupon is not valid or has expired');
                    }

                    if (!$coupon->canApplyToOrder($grossTotal)) {
                        throw new InvalidArgumentException("Order total must be at least ₦{$coupon->minimum_order_value} to use this coupon");
                    }

                    // Calculate discount
                    $couponDiscount = $coupon->calculateDiscount($grossTotal);
                    $netTotal = $grossTotal - $couponDiscount;
                } else {
                    throw new InvalidArgumentException('Invalid coupon code');
                }
            }

            $trackingId = strtoupper(uniqid()) . '-' . time();
            $paymentReference = 'TRX-' . strtoupper(uniqid()) . '-' . time();

            $order = Order::create([
                'user_id' => $user->id,
                'vendor_id' => $firstVendor->vendor_id,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'coupon_discount' => $couponDiscount,
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

            // Add all cart items to order (across all vendors)
            foreach ($cart->vendors as $cartVendor) {
                foreach ($cartVendor->items as $cartItem) {
                    OrderLineItems::create([
                        'order_id' => $order->id,
                        'product_id' => $cartItem->product_id,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                        'subtotal' => $cartItem->subtotal,
                        'type' => 'product',
                    ]);
                }
            }

            // Create coupon usage record if coupon was applied
            if ($coupon) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $couponDiscount,
                ]);

                // Increment coupon usage count
                $coupon->increment('usage_count');
            }

            // $cart->vendors()->delete(); // Uncomment to clear cart after order

            DB::commit();

            $responseData = [
                'order_reference' => $order->tracking_id,
                'payment_reference' => $order->payment_reference,
                'order_id' => $order->id,
                'amount' => $netTotal,
                'gross_total' => $grossTotal,
                'coupon_discount' => $couponDiscount,
                'net_total' => $netTotal,
            ];

            if ($coupon) {
                $responseData['coupon'] = [
                    'code' => $coupon->code,
                    'discount_type' => $coupon->discount_type,
                    'discount_amount' => $coupon->discount_amount,
                    'percent' => $coupon->percent,
                ];
            }

            return ShopittPlus::response(true, 'Order created successfully', 201, $responseData);
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