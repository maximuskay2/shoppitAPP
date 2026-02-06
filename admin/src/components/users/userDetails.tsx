import { useState, useEffect } from "react";
import {
  BiCalendar,
  BiEnvelopeOpen,
  BiLeftArrowAlt,
  BiGlobe,
  BiLoaderAlt,
  BiErrorCircle,
  BiCheckCircle,
} from "react-icons/bi";
import { apiUrl } from "../../lib/api";

type UserDetailsProps = {
  user: {
    id: string;
    name: string | null;
    email: string;
    username: string | null;
    status: string;
    avatar: string | null;
    country: string;
    user_type: string;
    kyc_status: string | null;
    email_verified_at: string | null;
    wallet_balance: number;
    created_at: string;
    updated_at: string;
    referral_code?: string | null;
    last_logged_in_device?: string | null;
    total_transactions?: number;
    total_orders?: number;
    total_spent?: number;
    referrals_count?: number;
  };
  userType: "customer" | "vendor";
  onBack: () => void;
  onUpdate: () => void;
};

const UserDetails = ({
  user,
  userType,
  onBack,
  onUpdate,
}: UserDetailsProps) => {
  const [isUpdating, setIsUpdating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const initial = (user.name || user.email).charAt(0).toUpperCase();

  // Clear messages after 5 seconds
  useEffect(() => {
    if (error || success) {
      const timer = setTimeout(() => {
        setError(null);
        setSuccess(null);
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [error, success]);

  const handleStatusToggle = async () => {
    setIsUpdating(true);
    setError(null); // Reset errors
    setSuccess(null); // Reset success

    const token = localStorage.getItem("token");
    const newStatus = user.status === "ACTIVE" ? "SUSPENDED" : "ACTIVE";

    try {
      const url = apiUrl(
        `/api/v1/admin/user-management/${user.id}?status=${newStatus}`
      );

      const response = await fetch(url, {
        method: "PUT",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      // 1. Check if the response is actually OK (200-299)
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(
          errorData.message || `Server returned ${response.status}`
        );
      }

      const result = await response.json();

      if (result.success) {
        setSuccess(`Account has been ${newStatus.toLowerCase()} successfully.`);
        onUpdate(); // Refresh data
        return; // IMPORTANT: Exit the function here so it doesn't hit the catch block
      } else {
        throw new Error(result.message || "Action failed");
      }
    } catch (err: any) {
      // 2. This only runs if the 'throw' was triggered or a real network failure happened
      console.error("Status update error:", err);
      setError(err.message || "A communication error occurred.");
    } finally {
      setIsUpdating(false);
    }
  };

  return (
    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
      <button
        onClick={onBack}
        className="mb-6 px-2 flex py-2 text-sm font-medium items-center text-gray-500 hover:text-[#1F6728] transition-colors"
      >
        <BiLeftArrowAlt className="text-2xl mr-2" />
        Back to {userType === "customer" ? "Customers" : "Vendors"}
      </button>

      {/* FEEDBACK UI COMPONENT */}
      <div className="mb-6 h-12">
        {error && (
          <div className="p-3 bg-red-50 border-l-4 border-red-500 rounded-lg flex items-center gap-3 animate-in slide-in-from-top-2">
            <BiErrorCircle className="text-red-500 text-xl flex-shrink-0" />
            <p className="text-red-800 text-xs font-bold uppercase tracking-tight">
              {error}
            </p>
          </div>
        )}
        {success && (
          <div className="p-3 bg-green-50 border-l-4 border-green-500 rounded-lg flex items-center gap-3 animate-in slide-in-from-top-2">
            <BiCheckCircle className="text-green-500 text-xl flex-shrink-0" />
            <p className="text-green-800 text-xs font-bold uppercase tracking-tight">
              {success}
            </p>
          </div>
        )}
      </div>

      {/* Header Profile Section */}
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 border-b border-gray-50 pb-8">
        <div className="flex gap-5 items-center">
          {user.avatar ? (
            <img
              src={user.avatar}
              alt="avatar"
              className="w-20 h-20 rounded-2xl object-cover border-2 border-white shadow-md"
            />
          ) : (
            <div className="w-20 h-20 bg-[#1F6728] rounded-2xl flex items-center justify-center shadow-lg">
              <p className="text-white text-3xl font-bold">{initial}</p>
            </div>
          )}

          <div className="flex flex-col">
            <h2 className="text-2xl font-bold text-gray-800">
              {user.name || "Unnamed User"}
            </h2>
            <div className="flex flex-wrap text-sm gap-y-2 gap-x-6 mt-1 text-gray-500">
              <p className="flex gap-2 items-center">
                <BiEnvelopeOpen className="text-[#1F6728]" /> {user.email}
              </p>
              <p className="flex gap-2 items-center">
                <BiGlobe className="text-[#1F6728]" /> {user.country}
              </p>
              <p className="flex gap-2 items-center">
                <BiCalendar className="text-[#1F6728]" /> Joined{" "}
                {new Date(user.created_at).toLocaleDateString()}
              </p>
            </div>
          </div>
        </div>

        <button
          onClick={handleStatusToggle}
          disabled={isUpdating}
          className={`px-8 py-3 rounded-full font-bold text-xs transition-all flex items-center gap-2 ${
            user.status === "ACTIVE"
              ? "bg-red-50 text-red-600 hover:bg-red-600 hover:text-white"
              : "bg-green-50 text-[#1F6728] hover:bg-[#1F6728] hover:text-white"
          } disabled:opacity-50`}
        >
          {isUpdating ? (
            <BiLoaderAlt className="animate-spin text-lg" />
          ) : user.status === "ACTIVE" ? (
            "Suspend Account"
          ) : (
            "Activate Account"
          )}
        </button>
      </div>

      {/* Details Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div className="p-6 border border-gray-100 rounded-2xl bg-gray-50/30">
          <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">
            Identity & Contact
          </p>
          <div className="space-y-4">
            <DetailItem label="Username" value={user.username || "Not set"} />
            <DetailItem
              label="Referral Code"
              value={user.referral_code || "N/A"}
              highlight
            />
            <DetailItem
              label="Last Device"
              value={user.last_logged_in_device || "Unknown"}
            />
          </div>
        </div>

        <div className="p-6 border border-gray-100 rounded-2xl bg-gray-50/30">
          <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">
            Account Health
          </p>
          <div className="space-y-4">
            <div>
              <p className="text-[10px] text-gray-400 font-bold uppercase mb-1">
                Status
              </p>
              <span
                className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase ${
                  user.status === "ACTIVE"
                    ? "bg-green-100 text-green-700"
                    : "bg-red-100 text-red-700"
                }`}
              >
                {user.status}
              </span>
            </div>
            <div>
              <p className="text-[10px] text-gray-400 font-bold uppercase mb-1">
                KYC Status
              </p>
              <span
                className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase ${
                  user.kyc_status === "APPROVED"
                    ? "bg-blue-100 text-blue-700"
                    : "bg-gray-100 text-gray-500"
                }`}
              >
                {user.kyc_status || "Not Verified"}
              </span>
            </div>
            <DetailItem
              label="Verification Date"
              value={
                user.email_verified_at
                  ? new Date(user.email_verified_at).toLocaleDateString()
                  : "Unverified"
              }
            />
          </div>
        </div>

        <div className="p-6 border border-gray-100 rounded-2xl bg-[#1F6728]/5 border-l-4 border-[#1F6728]">
          <p className="text-[10px] font-bold text-[#1F6728] uppercase tracking-widest mb-4">
            Wallet & Sales
          </p>
          <div className="space-y-4 text-gray-800">
            <div>
              <p className="text-[10px] text-gray-400 font-bold uppercase mb-1">
                Available Balance
              </p>
              <p className="text-2xl font-black">
                ₦{(user.wallet_balance || 0).toLocaleString()}
              </p>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <DetailItem label="Orders" value={user.total_orders || 0} />
              <DetailItem
                label="Spent"
                value={`₦${(user.total_spent || 0).toLocaleString()}`}
              />
            </div>
            <DetailItem label="Referrals" value={user.referrals_count || 0} />
          </div>
        </div>
      </div>
    </div>
  );
};

// Helper sub-component for cleaner code
const DetailItem = ({
  label,
  value,
  highlight = false,
}: {
  label: string;
  value: string | number;
  highlight?: boolean;
}) => (
  <div>
    <p className="text-[10px] text-gray-400 font-bold uppercase mb-1">
      {label}
    </p>
    <p
      className={`font-semibold ${
        highlight ? "text-[#1F6728]" : "text-gray-700"
      }`}
    >
      {value}
    </p>
  </div>
);

export default UserDetails;
