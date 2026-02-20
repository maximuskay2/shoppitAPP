import { useState, useEffect } from "react";
import { BiX, BiErrorCircle, BiShow, BiHide } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

interface UserFormData {
  id?: string;
  fullName: string;
  email: string;
  phone: string;
  userType: "customer" | "vendor";
  status: string;
}

type UserModalProps = {
  show: boolean;
  onClose: () => void;
  isEditing: boolean;
  formData: UserFormData;
  setFormData: (data: any) => void;
  onSuccess: () => void;
};

const UserModal = ({
  show,
  onClose,
  isEditing,
  formData,
  setFormData,
  onSuccess,
}: UserModalProps) => {
  const [loading, setLoading] = useState(false);
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    setErrorMessage(null);
  }, [show, formData.fullName, formData.email, formData.phone]);

  if (!show) return null;

  const handleSubmit = async () => {
    // Validation
    if (isEditing) {
      if (!formData.fullName) {
        setErrorMessage("Name is required for updates.");
        return;
      }
    } else {
      if (
        !formData.fullName ||
        !formData.email ||
        !formData.phone ||
        !password
      ) {
        setErrorMessage("Please fill in Name, Email, Phone, and Password.");
        return;
      }
    }

    setLoading(true);
    setErrorMessage(null);
    const token = localStorage.getItem("token");

    try {
      let url = apiUrl("/api/v1/admin/user-management");
      let method = "";
      let body: any = null;

      if (isEditing) {
        method = "PUT";
        const queryParams = new URLSearchParams();
        queryParams.append("name", formData.fullName);
        queryParams.append("status", formData.status || "ACTIVE");
        if (formData.phone) queryParams.append("phone", formData.phone);
        url = `${url}/${formData.id}?${queryParams.toString()}`;
      } else {
        method = "POST";
        const data = new FormData();
        data.append("name", formData.fullName);
        data.append("email", formData.email);
        data.append("password", password); // Added password to FormData
        data.append("status", formData.status || "ACTIVE");
        data.append("user_type", formData.userType);
        data.append("phone", formData.phone);
        body = data;
      }

      const res = await fetch(url, {
        method: method,
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: body,
      });

      const result = await res.json();

      if (res.ok && result.success) {
        onSuccess();
        onClose();
      } else {
        setErrorMessage(result.message || "Something went wrong.");
      }
    } catch (error) {
      setErrorMessage("A network error occurred.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
      <div className="bg-white p-8 rounded-3xl w-full max-w-[450px] shadow-2xl relative animate-in zoom-in duration-200">
        <button
          onClick={onClose}
          disabled={loading}
          className="absolute top-6 right-6 text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-full"
        >
          <BiX className="text-2xl" />
        </button>

        <h2 className="text-2xl font-black text-gray-800 mb-6 tracking-tight">
          {isEditing ? "Update User" : `Add New ${formData.userType}`}
        </h2>

        {errorMessage && (
          <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl flex items-center gap-3 animate-in slide-in-from-top-2">
            <BiErrorCircle className="text-red-500 text-xl flex-shrink-0" />
            <p className="text-red-700 text-xs font-bold uppercase tracking-tight">
              {errorMessage}
            </p>
          </div>
        )}

        <div className="space-y-4">
          <div>
            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
              Full Name *
            </label>
            <input
              type="text"
              value={formData.fullName}
              onChange={(e) =>
                setFormData({ ...formData, fullName: e.target.value })
              }
              className="w-full border-2 border-gray-100 bg-gray-50 rounded-2xl px-5 py-3 focus:bg-white focus:border-[#1F6728] outline-none font-medium text-gray-700 transition-all"
              placeholder="Full Name"
            />
          </div>

          <div>
            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
              Email Address *
            </label>
            <input
              type="email"
              disabled={isEditing}
              value={formData.email}
              onChange={(e) =>
                setFormData({ ...formData, email: e.target.value })
              }
              className={`w-full border-2 border-gray-100 rounded-2xl px-5 py-3 outline-none font-medium transition-all ${
                isEditing
                  ? "bg-gray-100 text-gray-400 cursor-not-allowed"
                  : "bg-gray-50 focus:bg-white focus:border-[#1F6728] text-gray-700"
              }`}
              placeholder="Email Address"
            />
          </div>

          {!isEditing && (
            <div className="relative">
              <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
                Password *
              </label>
              <input
                type={showPassword ? "text" : "password"}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full border-2 border-gray-100 bg-gray-50 rounded-2xl px-5 py-3 focus:bg-white focus:border-[#1F6728] outline-none font-medium text-gray-700 transition-all pr-12"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-4 bottom-3.5 text-gray-400 hover:text-[#1F6728]"
              >
                {showPassword ? (
                  <BiHide className="text-xl" />
                ) : (
                  <BiShow className="text-xl" />
                )}
              </button>
            </div>
          )}

          <div>
            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
              Phone Number *
            </label>
            <input
              type="text"
              value={formData.phone}
              onChange={(e) =>
                setFormData({ ...formData, phone: e.target.value })
              }
              className="w-full border-2 border-gray-100 bg-gray-50 rounded-2xl px-5 py-3 focus:bg-white focus:border-[#1F6728] outline-none font-medium text-gray-700 transition-all"
              placeholder="+234..."
            />
          </div>

          <div>
            <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
              Status
            </label>
            <select
              value={formData.status}
              onChange={(e) =>
                setFormData({ ...formData, status: e.target.value })
              }
              className="w-full border-2 border-gray-100 bg-gray-50 rounded-2xl px-5 py-3 focus:bg-white focus:border-[#1F6728] outline-none font-medium text-gray-700 transition-all"
            >
              <option value="ACTIVE">ACTIVE</option>
              <option value="PENDING">PENDING</option>
              <option value="SUSPENDED">SUSPENDED</option>
            </select>
          </div>
        </div>

        <button
          onClick={handleSubmit}
          disabled={loading}
          className={`w-full mt-8 py-4 rounded-2xl text-white font-bold text-lg transition-all shadow-lg active:scale-95 flex justify-center items-center gap-2 ${
            loading
              ? "bg-gray-300 cursor-not-allowed"
              : "bg-[#1F6728] hover:bg-[#185321] shadow-green-900/10"
          }`}
        >
          {loading && <BiLoaderAlt className="animate-spin text-xl" />}
          {loading
            ? "Processing..."
            : isEditing
            ? "Save Changes"
            : "Create User"}
        </button>
      </div>
    </div>
  );
};

const BiLoaderAlt = ({ className }: { className: string }) => (
  <svg
    className={className}
    stroke="currentColor"
    fill="currentColor"
    strokeWidth="0"
    viewBox="0 0 24 24"
    height="1em"
    width="1em"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path d="M12 22c5.421 0 10-4.579 10-10h-2c0 4.411-3.589 8-8 8s-8-3.589-8-8 3.589-8 8-8V2c-5.421 0-10 4.579-10 10s4.579 10 10 10Z"></path>
  </svg>
);

export default UserModal;
