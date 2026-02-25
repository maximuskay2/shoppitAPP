/* eslint-disable @typescript-eslint/no-explicit-any */
import React, { useState, useEffect } from "react";
import {
  BiX,
  BiCheck,
  BiUndo,
  BiErrorCircle,
  BiLoaderAlt,
} from "react-icons/bi";
import { apiUrl } from "../../lib/api";

interface BlogModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSave: () => void;
  editingPost: any;
}

const BlogModal: React.FC<BlogModalProps> = ({
  isOpen,
  onClose,
  onSave,
  editingPost,
}) => {
  const [loading, setLoading] = useState(false);
  const [catLoading, setCatLoading] = useState(false);
  const [isAddingCategory, setIsAddingCategory] = useState(false);
  const [newCategoryName, setNewCategoryName] = useState("");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // Form State
  const [title, setTitle] = useState("");
  const [content, setContent] = useState("");
  const [description, setDescription] = useState("");
  const [featuredImage, setFeaturedImage] = useState<File | null>(null);
  const [categoryId, setCategoryId] = useState("");
  const [status, setStatus] = useState("published");
  const [categories, setCategories] = useState<{ id: string; name: string }[]>(
    []
  );

  const fetchCats = async () => {
    const token = localStorage.getItem("token");
    try {
      const res = await fetch(
        apiUrl("/api/v1/admin/blog-management/categories/management"),
        { headers: { Authorization: `Bearer ${token}` } }
      );
      const result = await res.json();
      if (result.success) setCategories(result.data.data);
    } catch (err) {
      console.error("Error fetching categories", err);
    }
  };

  useEffect(() => {
    if (isOpen) {
      fetchCats();
      setErrorMessage(null);
    }
  }, [isOpen]);

  const handleAddCategory = async () => {
    if (!newCategoryName.trim()) return;
    setCatLoading(true);
    setErrorMessage(null);
    const token = localStorage.getItem("token");
    const data = new FormData();
    data.append("name", newCategoryName);

    try {
      const res = await fetch(
        apiUrl("/api/v1/admin/blog-management/categories/management"),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
          body: data,
        }
      );
      const result = await res.json();
      if (result.success) {
        await fetchCats();
        setCategoryId(result.data.id);
        setIsAddingCategory(false);
        setNewCategoryName("");
      } else {
        setErrorMessage(result.message);
      }
    } catch (err) {
      setErrorMessage("Failed to add category");
    } finally {
      setCatLoading(false);
    }
  };

  useEffect(() => {
    if (editingPost) {
      setTitle(editingPost.title || "");
      setContent(editingPost.content || "");
      setDescription(editingPost.description || "");
      setCategoryId(editingPost.category?.id || "");
      setStatus(editingPost.is_published ? "published" : "draft");
    } else {
      handleReset();
    }
  }, [editingPost, isOpen]);

  const handleReset = () => {
    setTitle("");
    setContent("");
    setDescription("");
    setFeaturedImage(null);
    setCategoryId("");
    setStatus("published");
    setIsAddingCategory(false);
  };

  const handleSubmit = async () => {
    if (!title || !content || !description) {
      setErrorMessage("Please fill in all required fields.");
      return;
    }
    if (!categoryId) {
      setErrorMessage("Please select or add a category.");
      return;
    }

    setLoading(true);
    setErrorMessage(null);
    const token = localStorage.getItem("token");
    const data = new FormData();
    data.append("title", title);
    data.append("content", content);
    data.append("description", description);
    data.append("blog_category_id", categoryId);
    data.append("status", status);
    if (featuredImage) data.append("featured_image", featuredImage);

    try {
      const url = editingPost
        ? apiUrl(`/api/v1/admin/blog-management/${editingPost.id}`)
        : apiUrl("/api/v1/admin/blog-management");

      const response = await fetch(url, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: data,
      });

      const result = await response.json();
      if (result.success) {
        onSave();
        onClose();
      } else {
        setErrorMessage(result.message || "Something went wrong");
      }
    } catch (error) {
      setErrorMessage("Network error. Please try again later.");
    } finally {
      setLoading(false);
    }
  };

  const inputStyle =
    "border-2 border-gray-100 bg-gray-50 px-4 py-2 w-full rounded-full focus:bg-white focus:border-[#1F6728] outline-none font-medium transition-all text-sm";
  const labelStyle =
    "block mb-1 text-[10px] font-bold text-gray-400 uppercase tracking-widest";

  if (!isOpen) return null;

  return (
    <div
      className="fixed inset-0 bg-black/40 backdrop-blur-sm flex justify-center items-center z-[100] p-4"
      onClick={onClose}
    >
      <div
        className="bg-white w-full max-w-lg p-8 rounded-3xl shadow-2xl relative animate-in zoom-in duration-200"
        onClick={(e) => e.stopPropagation()}
      >
        <button
          className="absolute top-6 right-6 text-gray-400 hover:text-black p-1 hover:bg-gray-100 rounded-full transition-colors"
          onClick={onClose}
        >
          <BiX className="text-2xl" />
        </button>

        <p className="text-2xl font-black mb-6 text-gray-800 tracking-tight">
          {editingPost ? "Edit Blog Post" : "Create Blog Post"}
        </p>

        {/* ERROR MESSAGE UI */}
        {errorMessage && (
          <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl flex items-center gap-3 animate-in slide-in-from-top-2">
            <BiErrorCircle className="text-red-500 text-xl flex-shrink-0" />
            <p className="text-red-700 text-xs font-bold uppercase tracking-tight">
              {errorMessage}
            </p>
          </div>
        )}

        <div className="space-y-4 max-h-[60vh] overflow-y-auto px-1 scrollbar-hide">
          <div>
            <label className={labelStyle}>Title</label>
            <input
              className={inputStyle}
              placeholder="Main blog title"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
            />
          </div>

          <div>
            <label className={labelStyle}>Brief Summary</label>
            <input
              className={inputStyle}
              placeholder="Short description for the blog card"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
            />
          </div>

          <div>
            <label className={labelStyle}>Full Content</label>
            <textarea
              className={`${inputStyle} rounded-md h-32 resize-none py-3`}
              placeholder="Write your blog post content here..."
              value={content}
              onChange={(e) => setContent(e.target.value)}
            />
          </div>

          <div>
            <label className={labelStyle}>Featured Image</label>
            <div className="bg-gray-50 border-2 border-dashed border-gray-200 p-4 rounded-2xl text-center">
              <input
                type="file"
                accept="image/*"
                className="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-green-100 file:text-[#1F6728] hover:file:bg-green-200 cursor-pointer"
                onChange={(e) =>
                  setFeaturedImage(e.target.files ? e.target.files[0] : null)
                }
              />
            </div>
          </div>

          <div className="flex gap-4 items-end">
            <div className="flex-1">
              <label className={labelStyle}>Category</label>
              {isAddingCategory ? (
                <div className="flex items-center gap-2 animate-in slide-in-from-top-1">
                  <input
                    autoFocus
                    className="border-2 border-[#1F6728] bg-white px-4 py-2 flex-1 rounded-full outline-none text-sm font-medium"
                    placeholder="Category name..."
                    value={newCategoryName}
                    onChange={(e) => setNewCategoryName(e.target.value)}
                  />
                  <button
                    onClick={handleAddCategory}
                    disabled={catLoading}
                    className="bg-[#1F6728] p-2 rounded-full text-white hover:bg-[#185321] transition-colors"
                  >
                    {catLoading ? (
                      <BiLoaderAlt className="animate-spin text-xl" />
                    ) : (
                      <BiCheck className="text-xl" />
                    )}
                  </button>
                  <button
                    onClick={() => setIsAddingCategory(false)}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <BiUndo className="text-xl" />
                  </button>
                </div>
              ) : (
                <select
                  className={inputStyle}
                  value={categoryId}
                  onChange={(e) =>
                    e.target.value === "ADD_NEW"
                      ? setIsAddingCategory(true)
                      : setCategoryId(e.target.value)
                  }
                >
                  <option value="">Select Category</option>
                  <option value="ADD_NEW" className="text-[#1F6728] font-bold">
                    + Add New Category
                  </option>
                  {categories.map((cat) => (
                    <option key={cat.id} value={cat.id}>
                      {cat.name}
                    </option>
                  ))}
                </select>
              )}
            </div>

            <div className="w-1/3">
              <label className={labelStyle}>Status</label>
              <select
                className={inputStyle}
                value={status}
                onChange={(e) => setStatus(e.target.value)}
              >
                <option value="published">Published</option>
                <option value="draft">Draft</option>
              </select>
            </div>
          </div>
        </div>

        <button
          disabled={loading}
          className={`w-full mt-8 px-4 py-4 rounded-full text-white font-bold transition-all shadow-lg active:scale-95 flex justify-center items-center gap-2 ${
            loading
              ? "bg-gray-300 cursor-not-allowed"
              : "bg-[#1F6728] hover:bg-[#185321] shadow-green-900/10"
          }`}
          onClick={handleSubmit}
        >
          {loading && <BiLoaderAlt className="animate-spin text-xl" />}
          {loading
            ? "Publishing..."
            : editingPost
            ? "Update Post"
            : "Publish Post"}
        </button>
      </div>
    </div>
  );
};

export default BlogModal;
