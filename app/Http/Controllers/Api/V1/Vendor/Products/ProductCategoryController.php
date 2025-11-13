<?php 

namespace App\Http\Controllers\Api\V1\Vendor\Products;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\Products\StoreProductCategoryRequest;
use App\Http\Requests\Api\V1\Vendor\Products\UpdateProductCategoryRequest;
use App\Http\Resources\Commerce\ProductCategoryResource;
use App\Modules\Commerce\Services\Products\ProductCategoryService;
use App\Modules\User\Services\CloudinaryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProductCategoryController extends Controller
{
    public function __construct(
        private readonly ProductCategoryService $productCategoryService,
        private readonly CloudinaryService $cloudinaryService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $vendor = $user->vendor;

            $categories = $this->productCategoryService->getVendorCategories($vendor);

            return ShopittPlus::response(
                true, 
                'Product categories retrieved successfully', 
                200, 
                (object) ["categories" => ProductCategoryResource::collection($categories)]
            );
        } catch (Exception $e) {
            Log::error('GET PRODUCT CATEGORIES: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve product categories', 500);
        }
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $user = Auth::user();
            $vendor = $user->vendor;

            $categoryData = [
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'is_active' => $validatedData['is_active'] ?? true,
            ];

            // Handle avatar upload if provided
            if (isset($validatedData['avatar'])) {
                $uploadResult = $this->cloudinaryService->uploadProductCategoryAvatar(
                    $validatedData['avatar'],
                    $vendor->id
                );

                if (!$uploadResult['success']) {
                    DB::rollBack();
                    throw new Exception($uploadResult['error'] ?? 'Failed to upload category avatar');
                }

                $categoryData['avatar'] = $uploadResult['data']['secure_url'];
            }

            $category = $this->productCategoryService->createProductCategory($vendor, $categoryData);

            DB::commit();
            return ShopittPlus::response(
                true, 
                'Product category created successfully', 
                201, 
                (object) ["category" => new ProductCategoryResource($category)]
            );
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('CREATE PRODUCT CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CREATE PRODUCT CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create product category', 500);
        }
    }

    public function update(UpdateProductCategoryRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $user = Auth::user();
            $vendor = $user->vendor;

            $category = $this->productCategoryService->findCategoryById($id, $vendor);

            if (!$category) {
                throw new InvalidArgumentException('Product category not found');
            }

            $updateData = [
                'name' => isset($validatedData['name']) ? $validatedData['name'] : $category->name,
                'description' => isset($validatedData['description']) ? $validatedData['description'] : $category->description,
                'is_active' => isset($validatedData['is_active']) ? $validatedData['is_active'] : $category->is_active,
            ];

            // Handle avatar upload if provided
            if (isset($validatedData['avatar'])) {
                $uploadResult = $this->cloudinaryService->uploadUserAvatar(
                    $validatedData['avatar'],
                    'category_' . $vendor->id . '_' . time()
                );

                if (!$uploadResult['success']) {
                    DB::rollBack();
                    throw new Exception($uploadResult['error'] ?? 'Failed to upload category avatar');
                }

                $updateData['avatar'] = $uploadResult['data']['secure_url'];
            } else {
                $updateData['avatar'] = $category->avatar;
            }

            $category = $this->productCategoryService->updateProductCategory($category, $updateData);

            DB::commit();
            return ShopittPlus::response(
                true, 
                'Product category updated successfully', 
                200, 
                (object) ["category" => new ProductCategoryResource($category)]
            );
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('UPDATE PRODUCT CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('UPDATE PRODUCT CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update product category', 500);
        }
    }

    public function delete(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $vendor = $user->vendor;

            $category = $this->productCategoryService->findCategoryById($id, $vendor);

            if (!$category) {
                throw new InvalidArgumentException('Product category not found');
            }

            $this->productCategoryService->deleteProductCategory($category);

            DB::commit();
            return ShopittPlus::response(true, 'Product category deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('DELETE PRODUCT CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('DELETE PRODUCT CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete product category', 500);
        }
    }
}