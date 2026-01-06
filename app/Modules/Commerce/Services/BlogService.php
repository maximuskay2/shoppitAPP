<?php

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Models\Blog;
use App\Modules\Commerce\Models\BlogCategory;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BlogService
{
    /**
     * List blog categories
     */
    public function listCategories(): mixed
    {
        try {
            return BlogCategory::withCount('blogs')->orderBy('name', 'asc')->get();
        } catch (Exception $e) {
            Log::error('BLOG SERVICE - LIST CATEGORIES: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List published blogs with advanced filtering
     */
    public function listBlogs(array $filters = []): mixed
    {
        try {
            $query = Blog::with(['author', 'category'])
                ->where('is_published', true)
                ->orderBy('published_at', 'desc');

            // Search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            }

            // Category filter
            if (isset($filters['category_id']) && !empty($filters['category_id'])) {
                $query->where('blog_category_id', $filters['category_id']);
            }

            // Author filter (admin name)
            if (isset($filters['author']) && !empty($filters['author'])) {
                $query->whereHas('author', function ($q) use ($filters) {
                    $q->where('name', 'LIKE', '%' . $filters['author'] . '%');
                });
            }

            // Category name filter
            if (isset($filters['category_name']) && !empty($filters['category_name'])) {
                $query->whereHas('category', function ($q) use ($filters) {
                    $q->where('name', 'LIKE', '%' . $filters['category_name'] . '%');
                });
            }

            // Published date filter
            if (isset($filters['published_at']) && !empty($filters['published_at'])) {
                $query->whereDate('published_at', $filters['published_at']);
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            return $query->paginate($perPage);
        } catch (Exception $e) {
            Log::error('BLOG SERVICE - LIST BLOGS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single published blog and increment views
     */
    public function getBlog(string $id): Blog
    {
        try {
            $blog = Blog::with(['author', 'category'])
                ->where('is_published', true)
                ->find($id);

            if (!$blog) {
                throw new InvalidArgumentException('Blog not found');
            }

            // Increment views
            $blog->incrementViews();

            return $blog;
        } catch (Exception $e) {
            Log::error('BLOG SERVICE - GET BLOG: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
