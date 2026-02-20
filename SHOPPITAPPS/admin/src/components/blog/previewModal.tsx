/* eslint-disable @typescript-eslint/no-explicit-any */
import React from "react";
import { BiCalendar, BiUser, BiX } from "react-icons/bi";
import { BsEye } from "react-icons/bs";

interface PreviewModalProps {
  isOpen: boolean;
  onClose: () => void;
  post: any | null;
}

const PreviewModal: React.FC<PreviewModalProps> = ({
  isOpen,
  onClose,
  post,
}) => {
  if (!isOpen || !post) return null;

  return (
    <div
      className="fixed inset-0 bg-black/40 flex justify-center items-center z-[110] p-4"
      onClick={onClose}
    >
      <div
        className="bg-white w-full max-w-2xl rounded-2xl shadow-2xl relative overflow-y-auto max-h-[90vh] animate-in fade-in zoom-in duration-200"
        onClick={(e) => e.stopPropagation()}
      >
        {/* HEADER IMAGE */}
        <div className="relative h-64 w-full">
          <img
            src={post.featured_image}
            alt={post.title}
            className="w-full h-full object-cover"
          />
          <button
            className="absolute top-4 right-4 bg-white/20 backdrop-blur-md text-white hover:bg-white hover:text-black transition-all p-2 rounded-full"
            onClick={onClose}
          >
            <BiX className="text-2xl" />
          </button>
        </div>

        <div className="p-8">
          {/* BADGES */}
          <div className="flex gap-3 mb-4">
            <span
              className={`px-3 py-1 text-[10px] font-bold uppercase rounded-full ${
                post.is_published
                  ? "bg-green-100 text-green-700"
                  : "bg-yellow-100 text-yellow-700"
              }`}
            >
              {post.is_published ? "Published" : "Draft"}
            </span>
            <span className="text-blue-700 rounded-full bg-blue-50 px-3 py-1 text-[10px] font-bold uppercase">
              {post.category?.name}
            </span>
          </div>

          {/* TITLE & DESCRIPTION */}
          <h2 className="text-3xl font-black text-gray-900 leading-tight">
            {post.title}
          </h2>
          <p className="text-gray-500 mt-2 font-medium italic">
            "{post.description}"
          </p>

          {/* METADATA */}
          <div className="flex flex-wrap gap-6 items-center text-xs my-6 py-4 border-y border-gray-100 text-gray-400 font-bold uppercase tracking-widest">
            <p className="flex gap-2 items-center">
              <BiUser className="text-lg text-[#1F6728]" />
              {post.author?.name}
            </p>
            <p className="flex gap-2 items-center">
              <BiCalendar className="text-lg text-[#1F6728]" />
              {new Date(post.created_at).toLocaleDateString()}
            </p>
            <p className="flex gap-2 items-center">
              <BsEye className="text-lg text-[#1F6728]" />
              {post.views} Views
            </p>
          </div>

          {/* MAIN CONTENT */}
          <div className="prose prose-green max-w-none">
            <div className="text-gray-700 leading-relaxed whitespace-pre-line text-lg">
              {post.content}
            </div>
          </div>
        </div>

        {/* FOOTER ACTION */}
        <div className="p-6 bg-gray-50 border-t flex justify-end">
          <button
            onClick={onClose}
            className="px-8 py-2 bg-gray-800 text-white rounded-full font-bold hover:bg-black transition-all"
          >
            Close Preview
          </button>
        </div>
      </div>
    </div>
  );
};

export default PreviewModal;
