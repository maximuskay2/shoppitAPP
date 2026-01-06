<?php

namespace App\Modules\Commerce\Services\Admin;

use App\Modules\Commerce\Models\Blog;
use App\Modules\Commerce\Models\BlogCategory;
use App\Modules\User\Services\CloudinaryService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BlogManagementService
{
    public function __construct(private readonly CloudinaryService $cloudinaryService) {}
    /**
     * Get blog statistics for admin
     */
    public function getStats(string $adminId): array
    {
        try {
            $totalPosts = Blog::where('author_id', $adminId)->count();
            $published = Blog::where('author_id', $adminId)->where('is_published', true)->count();
            $drafts = Blog::where('author_id', $adminId)->where('is_published', false)->count();
            $totalViews = Blog::where('author_id', $adminId)->sum('views');

            return [
                'total_posts' => $totalPosts,
                'published' => $published,
                'drafts' => $drafts,
                'total_views' => $totalViews,
            ];
        } catch (Exception $e) {
            Log::error('BLOG MANAGEMENT SERVICE - GET STATS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List blogs with advanced filtering
     */
    public function listBlogs(array $filters = []): mixed
    {
        try {
            $query = Blog::with(['author', 'category'])->orderBy('created_at', 'desc');

            // Search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%')
                        ->orWhere('content', 'LIKE', '%' . $search . '%');
                });
            }

            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                if ($filters['status'] === 'published') {
                    $query->where('is_published', true);
                } elseif ($filters['status'] === 'draft') {
                    $query->where('is_published', false);
                }
            }

            // Category filter
            if (isset($filters['category_id']) && !empty($filters['category_id'])) {
                $query->where('blog_category_id', $filters['category_id']);
            }

            // Author filter
            if (isset($filters['author_id']) && !empty($filters['author_id'])) {
                $query->where('author_id', $filters['author_id']);
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            return $query->paginate($perPage);
        } catch (Exception $e) {
            Log::error('BLOG MANAGEMENT SERVICE - LIST BLOGS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single blog
     */
    public function getBlog(string $id): Blog
    {
        try {
            $blog = Blog::with(['author', 'category'])->find($id);

            if (!$blog) {
                throw new InvalidArgumentException('Blog not found');
            }

            return $blog;
        } catch (Exception $e) {
            Log::error('BLOG MANAGEMENT SERVICE - GET BLOG: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new blog
     */
    public function createBlog(array $data, string $authorId): Blog
    {
        try {
            DB::beginTransaction();

            // Upload featured image to Cloudinary if provided
            $featuredImageUrl = null;
            if (isset($data['featured_image'])) {
                $featuredImageUrl = $this->cloudinaryService->uploadBlogImage($data['featured_image'], 'blog-images');
            }

            $blog = Blog::create([
                'author_id' => $authorId,
                'blog_category_id' => $data['blog_category_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'content' => $data['content'],
                'featured_image' => $featuredImageUrl,
                'is_published' => $data['status'] === 'published',
                'published_at' => $data['status'] === 'published' ? now() : null,
            ]);

            DB::commit();

            return $blog->fresh(['author', 'category']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('BLOG MANAGEMENT SERVICE - CREATE BLOG: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a blog
     */
    public function updateBlog(string $id, array $data): Blog
    {
        try {
            DB::beginTransaction();

            $blog = Blog::find($id);

            if (!$blog) {
                throw new InvalidArgumentException('Blog not found');
            }

            $updateData = [
                'title' => $data['title'] ?? $blog->title,
                'description' => $data['description'] ?? $blog->description,
                'content' => $data['content'] ?? $blog->content,
                'blog_category_id' => $data['category_id'] ?? $blog->blog_category_id,
            ];

            // Handle status change
            if (isset($data['status'])) {
                $isPublished = $data['status'] === 'published';
                $updateData['is_published'] = $isPublished;

                // Set published_at only if publishing for the first time
                if ($isPublished && !$blog->is_published) {
                    $updateData['published_at'] = now();
                } elseif (!$isPublished) {
                    $updateData['published_at'] = null;
                }
            }

            // Upload new featured image if provided
            if (isset($data['featured_image'])) {
                $updateData['featured_image'] = $this->cloudinaryService->uploadBlogImage($data['featured_image'], 'blog-images');
            }

            $blog->update($updateData);

            DB::commit();

            return $blog->fresh(['author', 'category']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('BLOG MANAGEMENT SERVICE - UPDATE BLOG: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a blog
     */
    public function deleteBlog(string $id): void
    {
        try {
            $blog = Blog::find($id);

            if (!$blog) {
                throw new InvalidArgumentException('Blog not found');
            }

            $blog->delete();
        } catch (Exception $e) {
            Log::error('BLOG MANAGEMENT SERVICE - DELETE BLOG: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List blog categories
     */
    public function listCategories(array $filters = []): mixed
    {
        try {
            $query = BlogCategory::withCount('blogs')->orderBy('name', 'asc');

            // Search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $query->where('name', 'LIKE', '%' . $filters['search'] . '%');
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            return $query->paginate($perPage);
        } catch (Exception $e) {
            Log::error('BLOG MANAGEMENT SERVICE - LIST CATEGORIES: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single blog category
     */
    public function getCategory(string $id): BlogCategory
    {
        try {
            $category = BlogCategory::withCount('blogs')->find($id);

            if (!$category) {
                throw new InvalidArgumentException('Blog category not found');
            }

            return $category;
        } catch (Exception $e) {
            Log::error('BLOG MANAGEMENT SERVICE - GET CATEGORY: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a blog category
     */
    public function createCategory(array $data): BlogCategory
    {
        try {
            // Check if category already exists
            if (BlogCategory::where('name', $data['name'])->exists()) {
                throw new InvalidArgumentException('A category with this name already exists');
            }

            $category = BlogCategory::create([
                'name' => $data['name'],
            ]);

            return $category->fresh();
        } catch (Exception $e) {
            Log::error('BLOG MANAGEMENT SERVICE - CREATE CATEGORY: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a blog category
     */
    public function updateCategory(string $id, array $data): BlogCategory
    {
        try {
            $category = BlogCategory::find($id);

            if (!$category) {
                throw new InvalidArgumentException('Blog category not found');
            }

            // Check if another category with this name exists
            if (BlogCategory::where('name', $data['name'])->where('id', '!=', $id)->exists()) {
                throw new InvalidArgumentException('A category with this name already exists');
            }

            $category->update(['name' => $data['name']]);

            return $category->fresh();
        } catch (Exception $e) {
            Log::error('BLOG MANAGEMENT SERVICE - UPDATE CATEGORY: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a blog category
     */
    public function deleteCategory(string $id): void
    {
        try {
            $category = BlogCategory::find($id);

            if (!$category) {
                throw new InvalidArgumentException('Blog category not found');
            }

            // Check if category has blogs
            if ($category->blogs()->count() > 0) {
                throw new InvalidArgumentException('Cannot delete category with existing blogs');
            }

            $category->delete();
        } catch (Exception $e) {
            Log::error('BLOG MANAGEMENT SERVICE - DELETE CATEGORY: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
