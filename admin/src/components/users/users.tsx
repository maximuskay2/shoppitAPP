import { useState, useEffect, useCallback } from "react";
import { FaEye, FaEdit } from "react-icons/fa";
import UserModal from "./usersModal";
import UserDetails from "./userDetails";
import UserFiltersModal from "./userFilterModal";
import { CgProfile } from "react-icons/cg";
import { BiSolidTrash } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

// Interface updated to match your exact API response
type User = {
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
};

type TabType = "customer" | "vendor";

const Users = () => {
  // 1. State Management
  const [activeTab, setActiveTab] = useState<TabType>("customer");
  const [userData, setUserData] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);

  // API Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const [totalResults, setTotalResults] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [perPage, setPerPage] = useState(15);

  const [searchTerm, setSearchTerm] = useState("");
  const [inputValue, setInputValue] = useState(""); // UI typing state

  const [showModal, setShowModal] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [showUserDetails, setShowUserDetails] = useState(false);
  const [showFilters, setShowFilters] = useState(false);

  const [filters, setFilters] = useState({
    status: "All",
    dateRange: { from: "", to: "" },
  });

  const [formData, setFormData] = useState({
    id: "" as string | undefined, // Added ID for pre-filling edits
    userType: activeTab as TabType,
    fullName: "",
    email: "",
    phone: "",
    address: "",
    status: "",
  });

  // 2. Data Fetching Logic
  const fetchUsers = useCallback(async () => {
    setLoading(true);
    try {
      const token = localStorage.getItem("token");

      const statusParam =
        filters.status !== "All" ? `&status=${filters.status}` : "";
      const searchParam = searchTerm ? `&search=${searchTerm}` : "";

      const response = await fetch(
        apiUrl(
          `/api/v1/admin/user-management?page=${currentPage}&user_type=${activeTab}${statusParam}${searchParam}`
        ),
        {
          method: "GET",
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
            Accept: "application/json",
          },
        }
      );

      const result = await response.json();

      if (result.success) {
        setUserData(result.data.data);
        setTotalResults(result.data.total);
        setLastPage(result.data.last_page);
        setPerPage(result.data.per_page);
        if (perPage) {
          return;
        }
      }
    } catch (error) {
      console.error("Failed to fetch users:", error);
    } finally {
      setLoading(false);
    }
  }, [activeTab, currentPage, filters.status, searchTerm]);

  useEffect(() => {
    const delayDebounceFn = setTimeout(() => {
      setSearchTerm(inputValue);
      setCurrentPage(1);
    }, 500);

    return () => clearTimeout(delayDebounceFn);
  }, [inputValue]);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  // 3. Event Handlers
  const handleViewUser = (user: User) => {
    setSelectedUser(user);
    setShowUserDetails(true);
  };

  // REFRESH SPECIFIC USER DATA
  // This helps if the user is suspended/activated while viewing details
  const refreshSingleUser = async () => {
    if (!selectedUser) return;

    try {
      const token = localStorage.getItem("token");
      const response = await fetch(
        apiUrl(`/api/v1/admin/user-management/${selectedUser.id}`),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();
      if (result.success) {
        setSelectedUser(result.data); // Update the detail view data
        fetchUsers(); // Also update the background table list
      }
    } catch (error) {
      console.error("Failed to refresh user details:", error);
    }
  };

  const handleEdit = (user: User) => {
    setSelectedUser(user);
    setFormData({
      id: user.id,
      userType: user.user_type as TabType,
      fullName: user.name || "",
      email: user.email,
      phone: "",
      address: "",
      status: user.status,
    });
    setIsEditing(true);
    setShowModal(true);
  };

  const handleTabChange = (tab: TabType) => {
    setActiveTab(tab);
    setCurrentPage(1);
    setInputValue(""); // Clear search on tab change
    setSearchTerm("");
  };

  const handleSetFormData = (
    data: Partial<typeof formData> | typeof formData
  ) => {
    setFormData((prev) => ({ ...prev, ...data }));
  };

  const handleDelete = async (user: User) => {
    // 1. Ask for confirmation
    const confirmed = window.confirm(
      `Are you sure you want to delete ${
        user.name || user.email
      }? This action cannot be undone.`
    );

    if (!confirmed) return;

    setLoading(true);
    try {
      const token = localStorage.getItem("token");
      const response = await fetch(
        apiUrl(`/api/v1/admin/user-management/${user.id}`),
        {
          method: "DELETE",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );

      const result = await response.json();

      if (response.ok && result.success) {
        fetchUsers(); // Refresh the table list
      } else {
        alert(`Error: ${result.message || "Failed to delete user"}`);
      }
    } catch (error) {
      console.error("Delete error:", error);
      alert("A network error occurred.");
    } finally {
      setLoading(false);
    }
  };

  if (showUserDetails && selectedUser) {
    return (
      <UserDetails
        user={selectedUser}
        userType={activeTab}
        onBack={() => setShowUserDetails(false)}
        onUpdate={refreshSingleUser} // PASSING THE MISSING PROP
      />
    );
  }

  return (
    <div>
      {/* Header */}
      <div className="flex justify-between mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-800">User Management</h2>
          <p className="text-gray-500">Manage your platform {activeTab}s</p>
        </div>
        <button
          onClick={() => {
            setIsEditing(false);
            setFormData({
              id: undefined,
              userType: activeTab,
              fullName: "",
              email: "",
              phone: "",
              address: "",
              status: "ACTIVE",
            });
            setShowModal(true);
          }}
          className="bg-[#1F6728] px-6 py-2 gap-2 rounded-full text-white text-sm flex justify-between items-center font-bold hover:bg-[#185321] transition"
        >
          <CgProfile className="text-lg" />
          Add New {activeTab === "customer" ? "Customer" : "Vendor"}
        </button>
      </div>

      {/* Tabs */}
      <div className="flex space-x-6 border-b border-gray-200 mb-6">
        {(["customer", "vendor"] as const).map((tab) => (
          <button
            key={tab}
            onClick={() => handleTabChange(tab)}
            className={`pb-2 px-2 font-semibold capitalize transition-colors ${
              activeTab === tab
                ? "border-b-2 border-[#1F6728] text-[#1F6728]"
                : "text-gray-400 hover:text-gray-600"
            }`}
          >
            {tab}s
          </button>
        ))}
      </div>

      {/* Search & Filters */}
      <div className="flex justify-between items-center mb-6 gap-4">
        <div className="relative flex-1">
          <input
            type="text"
            placeholder="Search by email or name..."
            className="w-full border border-gray-300 rounded-full px-5 py-2 text-sm focus:ring-2 focus:ring-[#1F6728] outline-none"
            value={inputValue}
            onChange={(e) => setInputValue(e.target.value)}
          />
          {inputValue && (
            <button
              onClick={() => setInputValue("")}
              className="absolute right-4 top-2.5 text-gray-400 hover:text-gray-600 font-bold"
            >
              ×
            </button>
          )}
        </div>
        <button
          onClick={() => setShowFilters(true)}
          className="border border-gray-300 px-6 py-2 rounded-full text-gray-600 hover:bg-gray-50 transition"
        >
          Filters
        </button>
      </div>

      {/* Table Section */}
      <div className="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden relative">
        {loading && (
          <div className="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-20">
            <div className="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-[#1F6728]"></div>
          </div>
        )}

        <table className="w-full text-left text-sm">
          <thead className="bg-gray-50 text-gray-400 text-xs uppercase font-medium">
            <tr>
              <th className="px-6 py-4">User</th>
              <th className="px-6 py-4">Email</th>

              <th className="px-6 py-4">Status</th>
              <th className="px-6 py-4">Join Date</th>
              <th className="px-6 py-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {userData.length > 0
              ? userData.map((user) => (
                  <tr
                    key={user.id}
                    className="hover:bg-gray-50 transition-colors"
                  >
                    <td className="px-6 py-4 flex items-center space-x-3">
                      <img
                        src={
                          user.avatar ||
                          "https://ui-avatars.com/api/?name=" +
                            (user.name || "User")
                        }
                        alt="avatar"
                        className="w-9 h-9 rounded-full object-cover border border-gray-200"
                      />
                      <span className="font-medium text-gray-700">
                        {user.name || "Unnamed User"}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-gray-500">{user.email}</td>
                    <td className="px-6 py-4">
                      <span
                        className={`px-2 py-1 rounded-full text-[10px] font-bold uppercase ${
                          user.status === "ACTIVE"
                            ? "bg-green-100 text-green-700"
                            : "bg-red-100 text-red-700"
                        }`}
                      >
                        {user.status}
                      </span>
                    </td>

                    <td className="px-6 py-4 text-gray-500">
                      {new Date(user.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex justify-center space-x-4 text-gray-400">
                        <button
                          onClick={() => handleViewUser(user)}
                          className="hover:text-[#1F6728] transition"
                        >
                          <FaEye />
                        </button>
                        <button
                          onClick={() => handleEdit(user)}
                          className="hover:text-[#1F6728] transition"
                        >
                          <FaEdit />
                        </button>
                        <button
                          onClick={() => handleDelete(user)}
                          className="hover:text-red-600 transition"
                        >
                          <BiSolidTrash />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              : !loading && (
                  <tr>
                    <td
                      colSpan={6}
                      className="text-center py-20 text-gray-400 italic"
                    >
                      No users found matching your criteria.
                    </td>
                  </tr>
                )}
          </tbody>
        </table>

        {/* Pagination */}
        <div className="px-6 py-4 bg-gray-50 flex justify-between items-center border-t border-gray-100 text-gray-500 text-xs">
          <p>
            Showing{" "}
            <span className="font-semibold text-gray-700">
              {userData.length}
            </span>{" "}
            of{" "}
            <span className="font-semibold text-gray-700">{totalResults}</span>{" "}
            users
          </p>

          <div className="flex items-center space-x-2">
            <button
              disabled={currentPage === 1}
              onClick={() => setCurrentPage((p) => p - 1)}
              className="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <div className="bg-[#1F6728] text-white px-4 py-2 rounded-md font-bold">
              {currentPage}
            </div>
            <button
              disabled={currentPage === lastPage}
              onClick={() => setCurrentPage((p) => p + 1)}
              className="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <UserFiltersModal
        show={showFilters}
        onClose={() => setShowFilters(false)}
        onSave={(status, dateRange) => {
          setFilters({ status, dateRange });
          setCurrentPage(1);
        }}
      />

      <UserModal
        show={showModal}
        onClose={() => setShowModal(false)}
        isEditing={isEditing}
        formData={formData}
        setFormData={handleSetFormData}
        onSuccess={fetchUsers} // Correctly refreshes the table
      />
    </div>
  );
};

export default Users;
