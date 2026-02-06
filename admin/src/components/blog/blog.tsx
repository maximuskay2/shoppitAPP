import { useCallback, useEffect, useState } from "react";
import { BiPencil, BiPlus, BiTrash, BiUser } from "react-icons/bi";
import BlogModal from "./blogModal";
import { BsEye } from "react-icons/bs";
import { CgCalendar } from "react-icons/cg";
import PreviewModal from "./previewModal";
import { apiUrl } from "../../lib/api";

// Updated Interface to match API response
interface Post {
  id: string;
  title: string;
  description: string;
  featured_image: string;
  content: string;
  views: number;
  is_published: boolean;
  published_at: string;
  author: {
    name: string;
  };
  category: {
    id: string;
    name: string;
  };
  created_at: string;
}

const Blog = () => {
  const [modalOpen, setModalOpen] = useState(false);
  const [editingPost, setEditingPost] = useState<Post | null>(null);
  const [previewPost, setPreviewPost] = useState<Post | null>(null);

  // API States
  const [posts, setPosts] = useState<Post[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [inputValue, setInputValue] = useState(""); // UI typing state
  const [statusFilter, setStatusFilter] = useState("All");
  const [categoryFilter, setCategoryFilter] = useState("All");
  const [blogStats, setBlogStats] = useState({
    total_posts: 0,
    published: 0,
    drafts: 0,
    total_views: 0,
  });

  // 1. Fetch Blog Statistics
  const fetchStats = useCallback(async () => {
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(
        apiUrl("/api/v1/admin/blog-management/stats"),
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      const result = await response.json();
      if (result.success) setBlogStats(result.data);
    } catch (err) {
      console.error("Stats fetch error:", err);
    }
  }, []);

  // 2. Fetch Blog Posts with Filters
  const fetchPosts = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const params = new URLSearchParams();
      if (searchTerm) params.append("search", searchTerm);
      if (statusFilter !== "All")
        params.append("status", statusFilter.toLowerCase());
      if (categoryFilter !== "All")
        params.append("category_id", categoryFilter);

      const response = await fetch(
        apiUrl(`/api/v1/admin/blog-management?${params.toString()}`),
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      const result = await response.json();
      if (result.success) setPosts(result.data.data);
    } catch (err) {
      console.error("Fetch error:", err);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, statusFilter, categoryFilter]);

  // Debounce Search
  useEffect(() => {
    const delayDebounceFn = setTimeout(() => {
      setSearchTerm(inputValue);
    }, 500);
    return () => clearTimeout(delayDebounceFn);
  }, [inputValue]);

  useEffect(() => {
    fetchStats();
    fetchPosts();
  }, [fetchStats, fetchPosts]);

  const openNewModal = () => {
    setEditingPost(null);
    setModalOpen(true);
  };

  const openEditModal = (post: Post) => {
    setEditingPost(post);
    setModalOpen(true);
  };

  // 3. Delete Post
  const handleDelete = async (id: string) => {
    if (!window.confirm("Permanent delete this article?")) return;
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/blog-management/${id}`),
        {
          method: "DELETE",
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      if (response.ok) {
        fetchPosts();
        fetchStats();
      }
    } catch (err) {
      console.error("Delete error:", err);
    }
  };

  return (
    <div>
      {/* HEADER */}
      <div className="mb-4 flex justify-between items-center">
        <div>
          <p className="text-2xl font-bold">Blog Management</p>
          <p className="text-gray-500">Create, edit and manage blog posts</p>
        </div>
        <button
          onClick={openNewModal}
          className="bg-[#2C9139] px-5 py-2 rounded-full text-sm flex gap-2 items-center text-white font-bold hover:bg-[#185321]"
        >
          <BiPlus className="text-[18px]" />
          Create New Post
        </button>
      </div>

      {/* STATS Mapping */}
      <div className="grid grid-cols-4 gap-6 mt-6">
        <div className="border border-gray-200 shadow-sm rounded-md p-6">
          <p className="text-sm text-gray-500">Total Posts</p>
          <p className="font-medium text-xl">{blogStats.total_posts}</p>
        </div>
        <div className="border border-gray-200 shadow-sm rounded-md p-6">
          <p className="text-sm text-gray-500">Published</p>
          <p className="font-medium text-xl text-green-600">
            {blogStats.published}
          </p>
        </div>
        <div className="border border-gray-200 shadow-sm rounded-md p-6">
          <p className="text-sm text-gray-500">Drafts</p>
          <p className="font-medium text-xl text-yellow-600">
            {blogStats.drafts}
          </p>
        </div>
        <div className="border border-gray-200 shadow-sm rounded-md p-6">
          <p className="text-sm text-gray-500">Total Views</p>
          <p className="font-medium text-xl text-blue-600">
            {blogStats.total_views.toLocaleString()}
          </p>
        </div>
      </div>

      {/* SEARCH & FILTERS */}
      <div className="flex flex-col md:flex-row gap-4 mt-8 items-center w-full">
        <input
          type="text"
          placeholder="Search articles..."
          className="border border-gray-300 px-4 py-3 rounded-full w-full md:flex-1 outline-none focus:border-[#1F6728]"
          value={inputValue}
          onChange={(e) => setInputValue(e.target.value)}
        />

        <div className="flex gap-4 w-full md:w-auto">
          <select
            className="border border-gray-300 px-4 py-3 rounded-full w-1/2 md:w-40"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="All">All Status</option>
            <option value="Published">Published</option>
            <option value="Draft">Draft</option>
          </select>

          {/* This should ideally be populated from the Categories Index endpoint */}
          <select
            className="border border-gray-300 px-4 py-3 rounded-full w-1/2 md:w-40"
            value={categoryFilter}
            onChange={(e) => setCategoryFilter(e.target.value)}
          >
            <option value="All">All Categories</option>
            <option value="958fd1d4-2665-4c2a-9ec0-915131c69aac">
              Security
            </option>
          </select>
        </div>
      </div>

      {/* GRID */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-6 relative">
        {loading && (
          <div className="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
            <div className="animate-spin rounded-full h-10 w-10 border-t-2 border-[#1F6728]"></div>
          </div>
        )}

        {posts.map((post) => (
          <div
            key={post.id}
            className="rounded-xl overflow-hidden shadow-md border border-gray-100 bg-white transition-hover hover:shadow-lg"
          >
            {/* IMAGE */}
            <div className="relative h-48 bg-gray-100">
              <img
                src={post.featured_image}
                alt=""
                className="h-full w-full object-cover"
              />
              <span
                className={`absolute top-3 right-3 px-3 py-1 text-[10px] font-bold uppercase rounded-full ${
                  post.is_published
                    ? "bg-green-600 text-white"
                    : "bg-yellow-500 text-white"
                }`}
              >
                {post.is_published ? "Published" : "Draft"}
              </span>
            </div>

            {/* CONTENT Mapping */}
            <div className="p-5">
              <div className="flex justify-between items-center text-xs mb-3">
                <p className="bg-green-50 text-[#1F6728] px-3 py-1 rounded-full font-semibold">
                  {post.category?.name}
                </p>
                <p className="text-gray-400 font-medium">{post.views} views</p>
              </div>
              <p className="font-bold text-lg leading-tight line-clamp-2 mb-2">
                {post.title}
              </p>
              <p className="text-gray-500 text-sm line-clamp-2 mb-4">
                {post.description}
              </p>

              <div className="text-[11px] text-gray-400 flex items-center gap-4 mb-6 border-t pt-4">
                <span className="flex gap-2 items-center">
                  <BiUser className="text-gray-300" />
                  {post.author?.name}
                </span>
                <span className="flex gap-2 items-center">
                  <CgCalendar className="text-gray-300" />
                  {new Date(post.created_at).toLocaleDateString()}
                </span>
              </div>

              {/* BUTTONS */}
              <div className="flex justify-between items-center gap-2">
                <button
                  className="flex-1 border border-gray-200 py-2.5 flex justify-center items-center rounded-full text-xs font-bold text-gray-600 hover:bg-gray-50 transition"
                  onClick={() => setPreviewPost(post)}
                >
                  <BsEye className="mr-2" /> Preview
                </button>
                <button
                  onClick={() => openEditModal(post)}
                  className="p-2.5 bg-[#1F6728] text-white rounded-full hover:bg-[#185321] transition"
                >
                  <BiPencil />
                </button>
                <button
                  onClick={() => handleDelete(post.id)}
                  className="p-2.5 bg-red-50 text-red-600 rounded-full hover:bg-red-100 transition"
                >
                  <BiTrash />
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* MODAL */}
      <BlogModal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        onSave={() => {
          fetchPosts();
          fetchStats();
        }}
        editingPost={editingPost}
      />
      <PreviewModal
        isOpen={!!previewPost}
        onClose={() => setPreviewPost(null)}
        post={previewPost}
      />
    </div>
  );
};

export default Blog;
