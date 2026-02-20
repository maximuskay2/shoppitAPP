import { useEffect, useState, useRef, useCallback } from "react";
import {
  BiArrowToLeft,
  BiArrowToRight,
  BiLogOut,
  BiUserCircle,
  BiChevronDown,
  BiUser,
  BiCloudUpload,
  BiX,
  BiLoaderAlt,
  BiShieldQuarter,
  BiShow,
  BiHide,
} from "react-icons/bi";
// Lucide Icons for the Sidebar
import {
  LuLayoutGrid,
  LuUser,
  LuPackage,
  LuWallet,
  LuMegaphone,
  LuFileText,
  LuChartPie,
  LuSettings,
  LuTruck,
} from "react-icons/lu";

import Dashboard from "./dashboard";
import Users from "./users/users";
import Orders from "./orders";
import Transactions from "./transactions/transactions";
import Refunds from "./refunds";
import Promotions from "./promotions/promotions";
import Coupons from "./promotions/coupons";
import Settings from "./settings/settings";
import Reports from "./reports";
import Blog from "./blog/blog";
import Delivery from "./delivery";
import NotificationBell from "./notification";
import AuditLogs from "./auditLogs";
import HealthMonitor from "./healthMonitor";
import SupportTickets from "./supportTickets";
import NotificationBroadcast from "./notificationBroadcast";
import { useNavigate } from "react-router-dom";
import logo from "../assets/Shopittplus-logo.png";
import { apiUrl } from "../lib/api";

interface AdminProfile {
  name: string;
  avatar: string | null;
  email: string;
}

const MainLayout = () => {
  // 1. Menu Configuration
  const menuItems = [
    { name: "Dashboard", icon: <LuLayoutGrid />, component: <Dashboard /> },
    { name: "Users", icon: <LuUser />, component: <Users /> },
    { name: "Orders", icon: <LuPackage />, component: <Orders /> },
    { name: "Delivery", icon: <LuTruck />, component: <Delivery /> },
    { name: "Transactions", icon: <LuWallet />, component: <Transactions /> },
    { name: "Refunds", icon: <LuWallet />, component: <Refunds /> },
    { name: "Notifications", icon: <LuMegaphone />, component: <NotificationBroadcast /> },
    { name: "Audit Logs", icon: <LuFileText />, component: <AuditLogs /> },
    { name: "Health Monitor", icon: <LuChartPie />, component: <HealthMonitor /> },
    { name: "Support Tickets", icon: <LuFileText />, component: <SupportTickets /> },
    { name: "Promotions", icon: <LuMegaphone />, component: <Promotions /> },
    { name: "Coupons", icon: <LuMegaphone />, component: <Coupons /> },
    { name: "Blog", icon: <LuFileText />, component: <Blog /> },
    { name: "Reports", icon: <LuChartPie />, component: <Reports /> },
    { name: "Settings", icon: <LuSettings />, component: <Settings /> },
  ];

  type SectionKey = (typeof menuItems)[number]["name"];

  // UI States
  const [activeSection, setActiveSection] = useState<SectionKey>("Dashboard");
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalTab, setModalTab] = useState<"profile" | "security">("profile");
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Data States
  const [admin, setAdmin] = useState<AdminProfile | null>(null);
  const [newName, setNewName] = useState("");
  const [avatarFile, setAvatarFile] = useState<File | null>(null);

  // Password States
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);
  //const [showConfirm, setShowConfirm] = useState(false);

  const [updating, setUpdating] = useState(false);
  const navigate = useNavigate();

  const toggleSidebar = () => setSidebarOpen((prev) => !prev);

  const fetchProfile = useCallback(async () => {
    const token = localStorage.getItem("token");
    if (!token) return navigate("/");
    try {
      const response = await fetch(
        apiUrl("/api/v1/admin/profile"),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();
      if (result.success) {
        setAdmin(result.data);
        setNewName(result.data.name);
      }
    } catch (err) {
      console.error("Profile fetch error:", err);
    }
  }, [navigate]);

  useEffect(() => {
    fetchProfile();
  }, [fetchProfile]);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(event.target as Node)
      ) {
        setDropdownOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const logOut = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("isAuthenticated");
    navigate("/");
  };

  const handleUpdateProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setUpdating(true);
    const token = localStorage.getItem("token");
    try {
      const nameResponse = await fetch(
        apiUrl(
          `/api/v1/admin/profile?name=${encodeURIComponent(
          newName
          )}`
        ),
        {
          method: "PUT",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const nameResult = await nameResponse.json();
      if (!nameResult.success)
        throw new Error(nameResult.message || "Failed to update name");

      if (avatarFile) {
        const formData = new FormData();
        formData.append("avatar", avatarFile);
        const avatarResponse = await fetch(
          apiUrl("/api/v1/admin/profile/update-avatar"),
          {
            method: "POST",
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
            body: formData,
          }
        );
        const avatarResult = await avatarResponse.json();
        if (!avatarResult.success)
          throw new Error(avatarResult.message || "Failed to update avatar");
      }

      await fetchProfile();
      setIsModalOpen(false);
      setAvatarFile(null);
      alert("Profile updated successfully!");
    } catch (err: any) {
      alert(err.message || "An unexpected error occurred.");
    } finally {
      setUpdating(false);
    }
  };

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    if (newPassword !== confirmPassword) return alert("Passwords do not match");
    setUpdating(true);
    const token = localStorage.getItem("token");
    const formData = new FormData();
    formData.append("current_password", currentPassword);
    formData.append("new_password", newPassword);
    formData.append("new_password_confirmation", confirmPassword);

    try {
      const res = await fetch(
        apiUrl("/api/v1/admin/profile/change-password"),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
          body: formData,
        }
      );
      const result = await res.json();
      if (result.success) {
        alert("Password changed successfully");
        setIsModalOpen(false);
        setCurrentPassword("");
        setNewPassword("");
        setConfirmPassword("");
      } else {
        alert(result.message);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setUpdating(false);
    }
  };

  const inputStyle =
    "w-full border-2 border-gray-100 bg-gray-50 rounded-full px-5 py-3 focus:bg-white focus:border-[#1F6728] outline-none transition-all font-medium text-sm pr-12";

  return (
    <div className="flex transition-all duration-500 ease-in-out">
      {/* SIDEBAR */}
      <div
        className={`bg-[#1F6728] min-h-screen transition-all duration-500 ${
          sidebarOpen ? "w-64" : "w-20"
        } relative flex flex-col`}
      >
        {/* Logo & Toggle */}
        <div
          className={`flex items-center p-6 ${
            sidebarOpen ? "justify-between" : "justify-center"
          }`}
        >
          {sidebarOpen && (
            <img src={logo} alt="Logo" className="w-32 object-contain" />
          )}
          <div
            className="cursor-pointer z-10 p-1 hover:bg-white/10 rounded-md transition-colors"
            onClick={toggleSidebar}
          >
            {sidebarOpen ? (
              <BiArrowToLeft className="text-2xl text-white" />
            ) : (
              <BiArrowToRight className="text-2xl text-white" />
            )}
          </div>
        </div>

        {/* Navigation Content */}
        <div className="flex flex-col h-full px-3">
          <nav className="flex flex-col gap-1 mt-4">
            {sidebarOpen && (
              <p className="px-4 text-[10px] font-bold text-green-200/60 uppercase mb-4 tracking-widest">
                Menu
              </p>
            )}
            {menuItems.map((item) => (
              <button
                key={item.name}
                onClick={() => setActiveSection(item.name)}
                className={`flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all duration-300 ${
                  activeSection === item.name
                    ? "bg-white text-[#1F6728] font-bold shadow-lg"
                    : "text-white hover:bg-white/10"
                }`}
              >
                <span className="text-xl">{item.icon}</span>
                {sidebarOpen && <span className="text-sm">{item.name}</span>}
              </button>
            ))}
          </nav>

          <div className="mt-auto mb-10 px-3">
            <button
              onClick={logOut}
              className="flex items-center gap-4 w-full py-3 px-4 rounded-xl text-white font-bold text-sm hover:bg-red-500/20 transition-all"
            >
              <BiLogOut className="text-xl" />
              {sidebarOpen && <span>Logout</span>}
            </button>
          </div>
        </div>
      </div>

      {/* CONTENT AREA */}
      <div className="flex-1 min-h-screen bg-white transition-all duration-500 overflow-y-auto">
        <div className="flex justify-between items-center mb-8 px-8 py-4 bg-white sticky top-0 z-40 border-b border-gray-50">
          <p className="text-gray-400 text-xs font-bold uppercase tracking-widest">
            Admin / <span className="text-[#1F6728]">{activeSection}</span>
          </p>
          <div className="flex items-center gap-4">
            <NotificationBell />
            <div className="h-8 w-[1px] bg-gray-100 mx-1"></div>
            <div className="relative" ref={dropdownRef}>
              <button
                onClick={() => setDropdownOpen(!dropdownOpen)}
                className="flex items-center gap-3 hover:bg-gray-50 p-1.5 rounded-xl transition-all"
              >
                <div className="text-right hidden sm:block">
                  <p className="text-sm font-bold text-gray-800">
                    {admin?.name || "Loading..."}
                  </p>
                  <p className="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">
                    Administrator
                  </p>
                </div>
                {admin?.avatar ? (
                  <img
                    src={admin.avatar}
                    className="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm"
                    alt="Admin"
                  />
                ) : (
                  <div className="p-1 rounded-full bg-green-50 text-[#1F6728]">
                    <BiUserCircle className="text-3xl" />
                  </div>
                )}
                <BiChevronDown
                  className={`text-gray-400 transition-transform ${
                    dropdownOpen ? "rotate-180" : ""
                  }`}
                />
              </button>

              {dropdownOpen && (
                <div className="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-50 py-2 z-50 overflow-hidden">
                  <button
                    onClick={() => {
                      setModalTab("profile");
                      setIsModalOpen(true);
                      setDropdownOpen(false);
                    }}
                    className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-[#1F6728] transition-colors"
                  >
                    <BiUser className="text-lg" /> Profile Settings
                  </button>
                  <button
                    onClick={() => {
                      setModalTab("security");
                      setIsModalOpen(true);
                      setDropdownOpen(false);
                    }}
                    className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-[#1F6728] transition-colors"
                  >
                    <BiShieldQuarter className="text-lg" /> Security
                  </button>
                  <div className="border-t border-gray-50 my-1"></div>
                  <button
                    onClick={logOut}
                    className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                  >
                    <BiLogOut className="text-lg" /> Logout
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="px-8 pb-10">
          {menuItems.find((item) => item.name === activeSection)?.component}
        </div>
      </div>

      {/* MODAL (Profile/Security) */}
      {isModalOpen && (
        <div className="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
          <div className="bg-white w-full max-w-md rounded-3xl overflow-hidden shadow-2xl">
            <div className="flex justify-between items-center p-6 border-b border-gray-50">
              <div className="flex gap-4">
                <button
                  onClick={() => setModalTab("profile")}
                  className={`text-sm font-bold uppercase tracking-widest transition-all ${
                    modalTab === "profile"
                      ? "text-[#1F6728] border-b-2 border-[#1F6728]"
                      : "text-gray-400"
                  }`}
                >
                  Profile
                </button>
                <button
                  onClick={() => setModalTab("security")}
                  className={`text-sm font-bold uppercase tracking-widest transition-all ${
                    modalTab === "security"
                      ? "text-[#1F6728] border-b-2 border-[#1F6728]"
                      : "text-gray-400"
                  }`}
                >
                  Security
                </button>
              </div>
              <button
                onClick={() => setIsModalOpen(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <BiX className="text-2xl" />
              </button>
            </div>

            <div className="p-8">
              {modalTab === "profile" ? (
                <form onSubmit={handleUpdateProfile} className="space-y-6">
                  <div className="flex flex-col items-center gap-3">
                    <div className="relative group">
                      <img
                        src={
                          avatarFile
                            ? URL.createObjectURL(avatarFile)
                            : admin?.avatar || ""
                        }
                        className="w-24 h-24 rounded-full object-cover border-4 border-green-50"
                        alt="Avatar"
                      />
                      <label className="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full opacity-0 group-hover:opacity-100 cursor-pointer transition-all">
                        <BiCloudUpload className="text-white text-3xl" />
                        <input
                          type="file"
                          className="hidden"
                          accept="image/*"
                          onChange={(e) =>
                            setAvatarFile(e.target.files?.[0] || null)
                          }
                        />
                      </label>
                    </div>
                  </div>
                  <div>
                    <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                      Full Name
                    </label>
                    <input
                      type="text"
                      value={newName}
                      onChange={(e) => setNewName(e.target.value)}
                      className={inputStyle}
                    />
                  </div>
                  <button
                    disabled={updating}
                    className="w-full bg-[#1F6728] text-white py-4 rounded-full font-bold shadow-lg disabled:bg-gray-300"
                  >
                    {updating ? (
                      <BiLoaderAlt className="animate-spin mx-auto" />
                    ) : (
                      "Save Profile"
                    )}
                  </button>
                </form>
              ) : (
                <form onSubmit={handleChangePassword} className="space-y-5">
                  <div className="relative">
                    <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                      Current Password
                    </label>
                    <input
                      type={showCurrent ? "text" : "password"}
                      value={currentPassword}
                      onChange={(e) => setCurrentPassword(e.target.value)}
                      className={inputStyle}
                      required
                    />
                    <button
                      type="button"
                      onClick={() => setShowCurrent(!showCurrent)}
                      className="absolute right-4 bottom-3 text-gray-400"
                    >
                      {showCurrent ? (
                        <BiHide className="text-xl" />
                      ) : (
                        <BiShow className="text-xl" />
                      )}
                    </button>
                  </div>
                  <div className="relative">
                    <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                      New Password
                    </label>
                    <input
                      type={showNew ? "text" : "password"}
                      value={newPassword}
                      onChange={(e) => setNewPassword(e.target.value)}
                      className={inputStyle}
                      required
                    />
                    <button
                      type="button"
                      onClick={() => setShowNew(!showNew)}
                      className="absolute right-4 bottom-3 text-gray-400"
                    >
                      {showNew ? (
                        <BiHide className="text-xl" />
                      ) : (
                        <BiShow className="text-xl" />
                      )}
                    </button>
                  </div>
                  <button
                    disabled={updating}
                    className="w-full bg-[#1F6728] text-white py-4 rounded-full font-bold shadow-lg mt-4"
                  >
                    {updating ? (
                      <BiLoaderAlt className="animate-spin mx-auto" />
                    ) : (
                      "Update Password"
                    )}
                  </button>
                </form>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default MainLayout;
