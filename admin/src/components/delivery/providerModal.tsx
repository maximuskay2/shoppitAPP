import React, { useState, useEffect } from "react";
import { BiX } from "react-icons/bi";

export type ProviderFormData = {
  name: string;
  status: string;
  location: string;
  webhookURL: string;
  APIKey: string;
};

interface ProviderModalProps {
  mode: "add" | "edit";
  initialData?: ProviderFormData;
  onClose: () => void;
  onSubmit: (data: ProviderFormData) => void;
}

const ProviderModal: React.FC<ProviderModalProps> = ({
  mode,
  initialData,
  onClose,
  onSubmit,
}) => {
  const [form, setForm] = useState<ProviderFormData>({
    name: "",
    status: "Active",
    location: "",
    webhookURL: "",
    APIKey: "",
  });

  const [errors, setErrors] = useState({
    name: false,
    location: false,
    webhookURL: false,
    APIKey: false,
  });

  // Pre-fill form when editing
  useEffect(() => {
    if (mode === "edit" && initialData) {
      setForm(initialData);
    }
  }, [mode, initialData]);

  const handleChange = (field: keyof ProviderFormData, value: string) => {
    setForm({ ...form, [field]: value });
    setErrors({ ...errors, [field]: false });
  };

  const handleSubmit = () => {
    const newErrors = {
      name: form.name.trim() === "",
      location: form.location.trim() === "",
      webhookURL: form.webhookURL.trim() === "",
      APIKey: form.APIKey.trim() === "",
    };

    setErrors(newErrors);

    if (Object.values(newErrors).includes(true)) return;

    onSubmit(form);
    onClose();
  };

  return (
    <div
      className="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      onClick={onClose}
    >
      <div
        className="bg-white p-8 rounded-lg shadow-lg w-[500px] relative"
        onClick={(e) => e.stopPropagation()}
      >
        <button
          onClick={onClose}
          className="absolute top-5 right-6 text-gray-500 hover:text-black text-xl"
        >
          <BiX />
        </button>

        <h2 className="text-xl font-semibold mb-4">
          {mode === "add" ? "Add Provider" : "Edit Provider"}
        </h2>

        {/* Provider Name */}
        <div className="mb-4">
          <label className="block mb-1">Provider Name</label>
          <input
            type="text"
            value={form.name}
            onChange={(e) => handleChange("name", e.target.value)}
            className={`border rounded-full px-3 py-2 w-full ${
              errors.name ? "border-red-500" : "border-gray-300"
            }`}
          />
        </div>

        {/* Status */}
        <div className="mb-4">
          <label className="block mb-1">Status</label>
          <select
            value={form.status}
            onChange={(e) => handleChange("status", e.target.value)}
            className="border rounded-full px-3 py-2 w-full border-gray-300"
          >
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>

        {/* Region */}
        <div className="mb-4">
          <label className="block mb-1">Region</label>
          <input
            type="text"
            value={form.location}
            onChange={(e) => handleChange("location", e.target.value)}
            className={`border rounded-full px-3 py-2 w-full ${
              errors.location ? "border-red-500" : "border-gray-300"
            }`}
          />
        </div>

        {/* Webhook URL */}
        <div className="mb-4">
          <label className="block mb-1">Webhook URL</label>
          <input
            type="text"
            value={form.webhookURL}
            onChange={(e) => handleChange("webhookURL", e.target.value)}
            className={`border rounded-full px-3 py-2 w-full ${
              errors.webhookURL ? "border-red-500" : "border-gray-300"
            }`}
          />
        </div>

        {/* API Key */}
        <div className="mb-4">
          <label className="block mb-1">API Key</label>
          <input
            type="text"
            value={form.APIKey}
            onChange={(e) => handleChange("APIKey", e.target.value)}
            className={`border rounded-full px-3 py-2 w-full ${
              errors.APIKey ? "border-red-500" : "border-gray-300"
            }`}
          />
        </div>

        {/* Buttons */}
        <div className="flex gap-3 mt-5">
          <button
            onClick={onClose}
            className="px-4 py-2 w-1/2 rounded-full bg-gray-200 text-gray-700"
          >
            Cancel
          </button>

          <button
            onClick={handleSubmit}
            className="px-4 py-2 w-1/2 rounded-full bg-[#1F6728] text-white"
          >
            {mode === "add" ? "Add Provider" : "Save Changes"}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ProviderModal;
