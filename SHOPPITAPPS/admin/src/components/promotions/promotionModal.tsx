/* eslint-disable @typescript-eslint/no-explicit-any */
import React, { useState, useEffect } from "react";
import { BiX, BiCloudUpload, BiErrorCircle, BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

interface PromotionModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  editingPromo: any | null;
}

const PromotionModal: React.FC<PromotionModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
  editingPromo,
}) => {
  const [loading, setLoading] = useState(false);
  const [banner, setBanner] = useState<File | null>(null);
  const [preview, setPreview] = useState("");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const [formData, setFormData] = useState({
    title: "",
    description: "",
    discount_type: "percentage",
    discount_value: "",
    start_date: "",
    end_date: "",
    is_active: "1",
  });

  useEffect(() => {
    if (editingPromo) {
      setFormData({
        title: editingPromo.title || "",
        description: editingPromo.description || "",
        discount_type: editingPromo.discount_type || "percentage",
        discount_value: editingPromo.discount_value || "",
        start_date: editingPromo.start_date?.split("T")[0] || "",
        end_date: editingPromo.end_date?.split("T")[0] || "",
        is_active: editingPromo.is_active ? "1" : "0",
      });
      setPreview(editingPromo.banner_image || "");
    } else {
      setFormData({
        title: "",
        description: "",
        discount_type: "percentage",
        discount_value: "",
        start_date: "",
        end_date: "",
        is_active: "1",
      });
      setPreview("");
      setBanner(null);
    }
    setErrorMessage(null);
  }, [editingPromo, isOpen]);

  const formatDateForAPI = (dateStr: string) => {
    if (!dateStr) return "";
    const [year, month, day] = dateStr.split("-");
    return `${month}/${day}/${year}`;
  };

  const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setBanner(file);
      setPreview(URL.createObjectURL(file));
      setErrorMessage(null);
    }
  };

  const handleSubmit = async () => {
    // Basic Validation
    if (
      !formData.title ||
      !formData.discount_value ||
      !formData.start_date ||
      !formData.end_date
    ) {
      setErrorMessage("Please fill in all required fields.");
      return;
    }

    setLoading(true);
    setErrorMessage(null);
    const token = localStorage.getItem("token");
    const data = new FormData();

    data.append("title", formData.title);
    data.append("description", formData.description);
    data.append("discount_type", formData.discount_type);
    data.append("discount_value", formData.discount_value);
    data.append("start_date", formatDateForAPI(formData.start_date));
    data.append("end_date", formatDateForAPI(formData.end_date));
    data.append("is_active", formData.is_active);

    if (banner) {
      data.append("banner_image", banner);
    }

    try {
      const url = editingPromo
        ? apiUrl(`/api/v1/admin/promotion-management/${editingPromo.id}`)
        : apiUrl("/api/v1/admin/promotion-management");

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
        onSuccess();
        onClose();
      } else {
        setErrorMessage(result.message || "Failed to save promotion");
      }
    } catch (err) {
      setErrorMessage("Network error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const inputStyle =
    "w-full border-2 border-gray-100 bg-gray-50 rounded-full px-5 py-2.5 outline-none focus:bg-white focus:border-[#1F6728] transition-all font-medium text-sm text-gray-700";
  const labelStyle =
    "block mb-1 text-[10px] font-bold text-gray-400 uppercase tracking-widest";

  if (!isOpen) return null;

  return (
    <div
      className="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-[100] p-4"
      onClick={onClose}
    >
      <div
        className="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-lg relative max-h-[90vh] overflow-y-auto animate-in zoom-in duration-200"
        onClick={(e) => e.stopPropagation()}
      >
        <button
          onClick={onClose}
          className="absolute top-6 right-6 text-gray-400 hover:text-black transition p-1 hover:bg-gray-100 rounded-full"
        >
          <BiX className="text-2xl" />
        </button>

        <h2 className="text-2xl font-black mb-6 text-gray-800 tracking-tight">
          {editingPromo ? "Edit Promotion" : "Create Promotion"}
        </h2>

        {/* Error UI Banner */}
        {errorMessage && (
          <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl flex items-center gap-3 animate-in slide-in-from-top-2">
            <BiErrorCircle className="text-red-500 text-xl flex-shrink-0" />
            <p className="text-red-700 text-xs font-bold uppercase tracking-tight">
              {errorMessage}
            </p>
          </div>
        )}

        <div className="space-y-5">
          {/* Banner Upload */}
          <div>
            <label className={labelStyle}>Banner Image</label>
            <div className="relative h-44 w-full border-2 border-dashed border-gray-200 rounded-2xl overflow-hidden flex flex-col items-center justify-center bg-gray-50 hover:bg-white hover:border-[#1F6728] transition-all cursor-pointer group">
              {preview ? (
                <img
                  src={preview}
                  alt="Banner Preview"
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="text-center">
                  <BiCloudUpload className="text-5xl text-gray-300 mx-auto group-hover:text-[#1F6728] transition-colors" />
                  <p className="text-[10px] text-gray-400 font-bold uppercase mt-2">
                    Click to Upload Banner
                  </p>
                </div>
              )}
              <input
                type="file"
                className="absolute inset-0 opacity-0 cursor-pointer"
                onChange={handleImageChange}
                accept="image/*"
              />
            </div>
          </div>

          <div>
            <label className={labelStyle}>Promotion Title</label>
            <input
              type="text"
              className={inputStyle}
              placeholder="e.g. Summer Clearance Sale"
              value={formData.title}
              onChange={(e) =>
                setFormData({ ...formData, title: e.target.value })
              }
            />
          </div>

          <div className="flex gap-4">
            <div className="w-1/2">
              <label className={labelStyle}>Discount Type</label>
              <select
                className={inputStyle}
                value={formData.discount_type}
                onChange={(e) =>
                  setFormData({ ...formData, discount_type: e.target.value })
                }
              >
                <option value="percentage">Percentage (%)</option>
                <option value="fixed">Fixed (₦)</option>
              </select>
            </div>
            <div className="w-1/2">
              <label className={labelStyle}>Value</label>
              <input
                type="number"
                className={inputStyle}
                placeholder={
                  formData.discount_type === "percentage" ? "20" : "2000"
                }
                value={formData.discount_value}
                onChange={(e) =>
                  setFormData({ ...formData, discount_value: e.target.value })
                }
              />
            </div>
          </div>

          <div>
            <label className={labelStyle}>Brief Description</label>
            <textarea
              rows={2}
              className={`${inputStyle} rounded-2xl py-3 resize-none`}
              placeholder="Give more details about this campaign..."
              value={formData.description}
              onChange={(e) =>
                setFormData({ ...formData, description: e.target.value })
              }
            ></textarea>
          </div>

          <div className="flex gap-4">
            <div className="w-1/2">
              <label className={labelStyle}>Start Date</label>
              <input
                type="date"
                className={inputStyle}
                value={formData.start_date}
                onChange={(e) =>
                  setFormData({ ...formData, start_date: e.target.value })
                }
              />
            </div>
            <div className="w-1/2">
              <label className={labelStyle}>End Date</label>
              <input
                type="date"
                className={inputStyle}
                value={formData.end_date}
                onChange={(e) =>
                  setFormData({ ...formData, end_date: e.target.value })
                }
              />
            </div>
          </div>
        </div>

        <div className="mt-8 flex gap-3">
          <button
            onClick={handleSubmit}
            disabled={loading}
            className="flex-1 bg-[#1F6728] text-white py-4 rounded-full font-bold shadow-lg shadow-green-900/10 hover:bg-[#185321] transition active:scale-95 disabled:bg-gray-300 flex items-center justify-center gap-2"
          >
            {loading && <BiLoaderAlt className="animate-spin text-xl" />}
            {loading
              ? "Processing..."
              : editingPromo
              ? "Update Campaign"
              : "Launch Campaign"}
          </button>
          <button
            onClick={onClose}
            className="px-8 py-4 bg-gray-100 text-gray-500 rounded-full font-bold hover:bg-gray-200 transition"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  );
};

export default PromotionModal;
