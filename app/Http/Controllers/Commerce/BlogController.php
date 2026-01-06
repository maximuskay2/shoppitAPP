<?php

namespace App\Http\Controllers\Commerce;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\BlogCategoryResource;
use App\Http\Resources\Commerce\BlogResource;
use App\Modules\Commerce\Services\BlogService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BlogController extends Controller
{
    public function __construct(private readonly BlogService $blogService) {}

    /**
     * List blog categories
     */
    public function indexCategories(): JsonResponse
    {
        try {
            $categories = $this->blogService->listCategories();

            return ShopittPlus::response(true, 'Categories retrieved successfully', 200, BlogCategoryResource::collection($categories));
        } catch (Exception $e) {
            Log::error('LIST BLOG CATEGORIES: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve categories', 500);
        }
    }

    /**
     * List published blogs with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->query('search'),
                'category_id' => $request->query('category_id'),
                'author' => $request->query('author'),
                'category_name' => $request->query('category_name'),
                'published_at' => $request->query('published_at'),
                'per_page' => $request->query('per_page', 15),
            ];

            $blogs = $this->blogService->listBlogs($filters);

            return ShopittPlus::response(true, 'Blogs retrieved successfully', 200, BlogResource::collection($blogs)->response()->getData());
        } catch (Exception $e) {
            Log::error('LIST PUBLISHED BLOGS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve blogs', 500);
        }
    }

    /**
     * Get a single published blog
     */
    public function show(string $id): JsonResponse
    {
        try {
            $blog = $this->blogService->getBlog($id);

            return ShopittPlus::response(true, 'Blog retrieved successfully', 200, new BlogResource($blog));
        } catch (InvalidArgumentException $e) {
            Log::error('GET PUBLISHED BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 404);
        } catch (Exception $e) {
            Log::error('GET PUBLISHED BLOG: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve blog', 500);
        }
    }
}
