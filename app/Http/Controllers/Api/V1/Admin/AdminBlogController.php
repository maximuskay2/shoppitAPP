<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Commerce\CreateBlogCategoryRequest;
use App\Http\Requests\Api\Admin\Commerce\CreateBlogRequest;
use App\Http\Requests\Api\Admin\Commerce\UpdateBlogCategoryRequest;
use App\Http\Requests\Api\Admin\Commerce\UpdateBlogRequest;
use App\Http\Resources\Commerce\BlogCategoryResource;
use App\Http\Resources\Commerce\BlogResource;
use App\Modules\Commerce\Services\Admin\BlogManagementService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AdminBlogController extends Controller
{
    public function __construct(private readonly BlogManagementService $blogManagementService) {}

    /**
     * Get blog statistics for the authenticated admin
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $adminId = $request->user('admin-api')->id;
            $stats = $this->blogManagementService->getStats($adminId);

            return ShopittPlus::response(true, 'Blog statistics retrieved successfully', 200, $stats);
        } catch (Exception $e) {
            Log::error('GET BLOG STATS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve blog statistics', 500);
        }
    }

    /**
     * List all blogs with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'category_id' => $request->query('category_id'),
                'author_id' => $request->query('author_id'),
                'per_page' => $request->query('per_page', 15),
            ];

            $blogs = $this->blogManagementService->listBlogs($filters);

            return ShopittPlus::response(true, 'Blogs retrieved successfully', 200, BlogResource::collection($blogs)->response()->getData());
        } catch (InvalidArgumentException $e) {
            Log::error('LIST BLOGS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('LIST BLOGS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve blogs', 500);
        }
    }

    /**
     * Get a single blog
     */
    public function show(string $id): JsonResponse
    {
        try {
            $blog = $this->blogManagementService->getBlog($id);

            return ShopittPlus::response(true, 'Blog retrieved successfully', 200, new BlogResource($blog));
        } catch (InvalidArgumentException $e) {
            Log::error('GET BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 404);
        } catch (Exception $e) {
            Log::error('GET BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve blog', 500);
        }
    }

    /**
     * Create a new blog
     */
    public function store(CreateBlogRequest $request): JsonResponse
    {
        try {
            $adminId = $request->user('admin-api')->id;
            $data = $request->validated();
            
            if ($request->hasFile('featured_image')) {
                $data['featured_image'] = $request->file('featured_image');
            }

            $blog = $this->blogManagementService->createBlog($data, $adminId);

            return ShopittPlus::response(true, 'Blog created successfully', 201, new BlogResource($blog));
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CREATE BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create blog', 500);
        }
    }

    /**
     * Update a blog
     */
    public function update(UpdateBlogRequest $request, string $id): JsonResponse
    {
        try {
            $data = $request->validated();
            
            if ($request->hasFile('featured_image')) {
                $data['featured_image'] = $request->file('featured_image');
            }

            $blog = $this->blogManagementService->updateBlog($id, $data);

            return ShopittPlus::response(true, 'Blog updated successfully', 200, new BlogResource($blog));
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update blog', 500);
        }
    }

    /**
     * Delete a blog
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->blogManagementService->deleteBlog($id);

            return ShopittPlus::response(true, 'Blog deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 404);
        } catch (Exception $e) {
            Log::error('DELETE BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete blog', 500);
        }
    }

    /**
     * List all categories with filters
     */
    public function indexCategories(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->query('search'),
                'per_page' => $request->query('per_page', 15),
            ];

            $categories = $this->blogManagementService->listCategories($filters);

            return ShopittPlus::response(true, 'Categories retrieved successfully', 200, BlogCategoryResource::collection($categories)->response()->getData());
        } catch (Exception $e) {
            Log::error('LIST BLOG CATEGORIES: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve categories', 500);
        }
    }

    /**
     * Get a single category
     */
    public function showCategory(string $id): JsonResponse
    {
        try {
            $category = $this->blogManagementService->getCategory($id);

            return ShopittPlus::response(true, 'Category retrieved successfully', 200, new BlogCategoryResource($category));
        } catch (InvalidArgumentException $e) {
            Log::error('GET BLOG CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 404);
        } catch (Exception $e) {
            Log::error('GET BLOG CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve category', 500);
        }
    }

    /**
     * Create a new category
     */
    public function storeCategory(CreateBlogCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->blogManagementService->createCategory($request->validated());

            return ShopittPlus::response(true, 'Category created successfully', 201, new BlogCategoryResource($category));
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE BLOG CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CREATE BLOG CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create category', 500);
        }
    }

    /**
     * Update a category
     */
    public function updateCategory(UpdateBlogCategoryRequest $request, string $id): JsonResponse
    {
        try {
            $category = $this->blogManagementService->updateCategory($id, $request->validated());

            return ShopittPlus::response(true, 'Category updated successfully', 200, new BlogCategoryResource($category));
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE BLOG CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE BLOG CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update category', 500);
        }
    }

    /**
     * Delete a category
     */
    public function destroyCategory(string $id): JsonResponse
    {
        try {
            $this->blogManagementService->deleteCategory($id);

            return ShopittPlus::response(true, 'Category deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE BLOG CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('DELETE BLOG CATEGORY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete category', 500);
        }
    }
}
