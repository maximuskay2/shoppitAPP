<?php

namespace App\Http\Controllers\Api\V1\User\Commerce;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\ProductResource;
use App\Http\Resources\Commerce\VendorResource;
use App\Modules\User\Services\FavouriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FavouriteController extends Controller
{
    public function __construct(private readonly FavouriteService $favouriteService) {}

    public function favouriteVendors (): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $vendors = $this->favouriteService->getFavouriteVendors($user);
            return ShopittPlus::response(true, 'Favourite vendors retrieved successfully', 200, VendorResource::collection($vendors));
        } catch (InvalidArgumentException $e) {
            Log::error('GET FAVOURITE VENDORS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET FAVOURITE VENDORS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve favourite vendors', 500);
        }
    }

    public function addFavouriteVendor (string $vendorId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $this->favouriteService->addFavouriteVendor($user, $vendorId);
            return ShopittPlus::response(true, 'Favourite vendor added successfully', 201);
        } catch (InvalidArgumentException $e) {
            Log::error('ADD FAVOURITE VENDOR: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('ADD FAVOURITE VENDOR: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to add favourite vendor', 500);
        }
    }

    public function removeFavouriteVendor (string $vendorId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $this->favouriteService->removeFavouriteVendor($user, $vendorId);
            return ShopittPlus::response(true, 'Favourite vendor removed successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('REMOVE FAVOURITE VENDOR: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('REMOVE FAVOURITE VENDOR: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to remove favourite vendor', 500);
        }
    }

    public function favouriteProducts (): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $products = $this->favouriteService->getFavouriteProducts($user);
            return ShopittPlus::response(true, 'Favourite products retrieved successfully', 200, ProductResource::collection($products));
        } catch (InvalidArgumentException $e) {
            Log::error('GET FAVOURITE PRODUCTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('GET FAVOURITE PRODUCTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve favourite products', 500);
        }
    }

    public function addFavouriteProduct (string $productId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $this->favouriteService->addFavouriteProduct($user, $productId);
            return ShopittPlus::response(true, 'Favourite product added successfully', 201);
        } catch (InvalidArgumentException $e) {
            Log::error('ADD FAVOURITE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('ADD FAVOURITE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to add favourite product', 500);
        }
    }

    public function removeFavouriteProduct (string $productId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $this->favouriteService->removeFavouriteProduct($user, $productId);
            return ShopittPlus::response(true, 'Favourite product removed successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('REMOVE FAVOURITE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('REMOVE FAVOURITE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to remove favourite product', 500);
        }
    }
}