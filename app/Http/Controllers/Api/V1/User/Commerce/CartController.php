<?php

namespace App\Http\Controllers\Api\V1\User\Commerce;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\Cart\AddToCartRequest;
use App\Http\Requests\Api\V1\User\Cart\ProcessCartRequest;
use App\Http\Requests\Api\V1\User\Cart\UpdateCartItemRequest;
use App\Http\Resources\Commerce\CartResource;
use App\Http\Resources\Commerce\CartVendorResource;
use App\Modules\Commerce\Services\CartService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function vendorCart(string $vendorId): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $cart = $this->cartService->vendorCart($user, $vendorId);

            return ShopittPlus::response(true, 'Vendor cart retrieved successfully', 200, new CartVendorResource($cart));
        } catch (InvalidArgumentException $e) {
            Log::error('GET VENDOR CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET VENDOR CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve vendor cart', 500);
        }
    }

    public function clearVendorCart(string $vendorId): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $cart = $this->cartService->clearVendorCart($user, $vendorId);

            return ShopittPlus::response(true, 'Vendor cart cleared successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('CLEAR VENDOR CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('CLEAR VENDOR CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to clear vendor cart', 500);
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
            $validatedData = $request->validated();
            $user = Auth::user();
            
            $responseData = $this->cartService->processCart($user, $validatedData);
            return ShopittPlus::response(true, 'Order created successfully', 201, $responseData);
        } catch (InvalidArgumentException $e) {
            Log::error('PROCESS CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('PROCESS CART: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to process cart', 500);
        }
    }

    public function applyCoupon(Request $request, $vendorId): JsonResponse
    {
        try {
            $request->validate([
                'coupon_code' => 'required|string',
            ]);

            $user = Auth::user();
            $cart = $this->cartService->applyCoupon($user, $vendorId, $request->coupon_code);

            return ShopittPlus::response(true, 'Coupon applied successfully', 200, new CartResource($cart));
        } catch (InvalidArgumentException $e) {
            Log::error('APPLY COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('APPLY COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to apply coupon', 500);
        }
    }

    public function removeCoupon(Request $request, $vendorId): JsonResponse
    {
        try {
            $user = Auth::user();
            $cart = $this->cartService->removeCoupon($user, $vendorId);

            return ShopittPlus::response(true, 'Coupon removed successfully', 200, new CartResource($cart));
        } catch (InvalidArgumentException $e) {
            Log::error('REMOVE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('REMOVE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to remove coupon', 500);
        }
    }

    public function validateCoupon(Request $request, $vendorId): JsonResponse
    {
        try {
            $request->validate([
                'coupon_code' => 'required|string',
            ]);
            $user = Auth::user();
            
            $result = $this->cartService->validateCoupon($user, $vendorId, $request->coupon_code);
            return ShopittPlus::response(true, 'Coupon is valid', 200, $result);
        } catch (InvalidArgumentException $e) {
            Log::error('VALIDATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('VALIDATE COUPON: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to validate coupon', 500);
        }
    }
}