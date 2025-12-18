<?php 

namespace App\Http\Controllers\Api\V1\Vendor\Products;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\Products\StoreProductRequest;
use App\Http\Requests\Api\V1\Vendor\Products\UpdateProductRequest;
use App\Http\Resources\Commerce\ProductResource;
use App\Modules\Commerce\Services\Products\ProductService;
use App\Modules\User\Services\CloudinaryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CloudinaryService $cloudinaryService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            $products = $this->productService->getVendorProducts($vendor);

            return ShopittPlus::response(
                true, 
                'Products retrieved successfully', 
                200, 
                ProductResource::collection($products)
            );
        } catch (Exception $e) {
            Log::error('GET PRODUCTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve products', 500);
        }
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $user = Auth::user();
            $vendor = $user->vendor;

            $productData = [
                'product_category_id' => $validatedData['product_category_id'],
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'price' => $validatedData['price'],
                'discount_price' => $validatedData['discount_price'] ?? 0.00,
                'approximate_delivery_time' => $validatedData['approximate_delivery_time'] ?? 0,
                'is_available' => $validatedData['is_available'] ?? true,
            ];

            // Handle multiple avatar uploads if provided
            if (isset($validatedData['avatar']) && is_array($validatedData['avatar'])) {
                $uploadResult = $this->cloudinaryService->uploadProductImages(
                    $validatedData['avatar'],
                    $vendor->id
                );

                if (!$uploadResult['success']) {
                    DB::rollBack();
                    throw new Exception($uploadResult['error'] ?? 'Failed to upload product images');
                }

                $productData['avatar'] = $uploadResult['data'];
            }

            $product = $this->productService->createProduct($vendor, $productData);

            DB::commit();
            return ShopittPlus::response(true, 'Product created successfully', 201, new ProductResource($product));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('CREATE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CREATE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create product', 500);
        }
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $user = Auth::user();
            $vendor = $user->vendor;

            $product = $this->productService->findProductById($id, $vendor);

            if (!$product) {
                throw new InvalidArgumentException('Product not found');
            }

            $updateData = [
                'product_category_id' => isset($validatedData['product_category_id']) ? $validatedData['product_category_id'] : $product->product_category_id,
                'name' => isset($validatedData['name']) ? $validatedData['name'] : $product->name,
                'description' => isset($validatedData['description']) ? $validatedData['description'] : $product->description,
                'price' => isset($validatedData['price']) ? $validatedData['price'] : $product->price,
                'discount_price' => isset($validatedData['discount_price']) ? $validatedData['discount_price'] : $product->discount_price,
                'approximate_delivery_time' => isset($validatedData['approximate_delivery_time']) ? $validatedData['approximate_delivery_time'] : $product->approximate_delivery_time,
                'is_available' => isset($validatedData['is_available']) ? $validatedData['is_available'] : $product->is_available,
            ];

            // Handle multiple avatar uploads if provided
            if (isset($validatedData['avatar']) && is_array($validatedData['avatar'])) {
                $uploadResult = $this->cloudinaryService->uploadProductImages(
                    $validatedData['avatar'],
                    $vendor->id
                );

                if (!$uploadResult['success']) {
                    DB::rollBack();
                    throw new Exception($uploadResult['error'] ?? 'Failed to upload product images');
                }

                $updateData['avatar'] = $uploadResult['data'];
            } else {
                $updateData['avatar'] = $product->avatar;
            }

            $product = $this->productService->updateProduct($product, $updateData);

            DB::commit();
            return ShopittPlus::response(true, 'Product updated successfully', 200, new ProductResource($product));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('UPDATE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('UPDATE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update product', 500);
        }
    }

    public function delete(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $vendor = $user->vendor;

            $product = $this->productService->findProductById($id, $vendor);

            if (!$product) {
                throw new InvalidArgumentException('Product not found');
            }

            $this->productService->deleteProduct($product);

            DB::commit();
            return ShopittPlus::response(true, 'Product deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('DELETE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('DELETE PRODUCT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete product', 500);
        }
    }
}