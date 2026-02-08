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
  phone?: string | null;
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
  driver?: {
    id?: string | null;
    is_verified?: boolean | null;
  } | null;
};

type DriverDocument = {
  id: string;
  document_type: string;
  file_url: string;
  status: string;
  expires_at?: string | null;
  verified_at?: string | null;
  rejected_at?: string | null;
  rejection_reason?: string | null;
};

type TabType = "customer" | "vendor" | "driver";
type FormUserType = "customer" | "vendor";

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
  const [showDriverDocs, setShowDriverDocs] = useState(false);
  const [driverDocs, setDriverDocs] = useState<DriverDocument[]>([]);
  const [docsLoading, setDocsLoading] = useState(false);
  const [showDriverEdit, setShowDriverEdit] = useState(false);

  const [filters, setFilters] = useState({
    status: "All",
    dateRange: { from: "", to: "" },
  });

  const [formData, setFormData] = useState({
    id: "" as string | undefined, // Added ID for pre-filling edits
    userType: (activeTab === "vendor" ? "vendor" : "customer") as FormUserType,
    fullName: "",
    email: "",
    phone: "",
    address: "",
    status: "",
  });

  const [driverForm, setDriverForm] = useState({
    id: "" as string | undefined,
    name: "",
    email: "",
    phone: "",
    status: "ACTIVE",
  });

  // 2. Data Fetching Logic
  const fetchUsers = useCallback(async () => {
    setLoading(true);
    try {
      const token = localStorage.getItem("token");

      const statusParam =
        filters.status !== "All" ? `&status=${filters.status}` : "";
      const searchParam = searchTerm ? `&search=${searchTerm}` : "";
      const dateFrom = filters.dateRange.from
        ? `&start_date=${filters.dateRange.from}`
        : "";
      const dateTo = filters.dateRange.to
        ? `&end_date=${filters.dateRange.to}`
        : "";

      if (activeTab === "driver") {
        const response = await fetch(
          apiUrl(
            `/api/v1/admin/drivers?page=${currentPage}${statusParam}${searchParam}${dateFrom}${dateTo}`
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
          const drivers = result.data?.data || [];
          const normalized = drivers.map((driver: any) => ({
            id: driver.id,
            name: driver.name ?? null,
            email: driver.email,
            phone: driver.phone ?? "",
            username: null,
            status: driver.status,
            avatar: driver.avatar ?? null,
            country: "",
            user_type: "driver",
            kyc_status: null,
            email_verified_at: null,
            wallet_balance: 0,
            created_at: driver.created_at,
            updated_at: driver.created_at,
            driver: driver.driver ?? null,
          }));
          setUserData(normalized);
          setTotalResults(result.data.total);
          setLastPage(result.data.last_page);
          setPerPage(result.data.per_page);
          if (perPage) {
            return;
          }
        }
      } else {
        const excludeDrivers =
          activeTab === "customer" ? "&exclude_drivers=1" : "";
        const response = await fetch(
          apiUrl(
            `/api/v1/admin/user-management?page=${currentPage}&user_type=${activeTab}${excludeDrivers}${statusParam}${searchParam}`
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
          const list = result.data.data || [];
          const filtered = list.filter(
            (user: User) => user.user_type === activeTab
          );
          setUserData(filtered);
          setTotalResults(result.data.total);
          setLastPage(result.data.last_page);
          setPerPage(result.data.per_page);
          if (perPage) {
            return;
          }
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
      userType: (user.user_type === "vendor" ? "vendor" : "customer") as FormUserType,
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

  const handleDriverEdit = (user: User) => {
    setSelectedUser(user);
    setDriverForm({
      id: user.id,
      name: user.name || "",
      email: user.email,
      phone: user.phone || "",
      status: user.status,
    });
    setShowDriverEdit(true);
  };

  const saveDriverDetails = async () => {
    if (!driverForm.id) return;
    try {
      const token = localStorage.getItem("token");
      const queryParams = new URLSearchParams();
      if (driverForm.name) queryParams.append("name", driverForm.name);
      if (driverForm.email) queryParams.append("email", driverForm.email);
      if (driverForm.phone) queryParams.append("phone", driverForm.phone);
      if (driverForm.status) queryParams.append("status", driverForm.status);
      const response = await fetch(
        apiUrl(`/api/v1/admin/user-management/${driverForm.id}?${queryParams}`),
        {
          method: "PUT",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();
      if (result.success) {
        setShowDriverEdit(false);
        fetchUsers();
      } else {
        alert(result.message || "Failed to update driver.");
      }
    } catch (error) {
      console.error("Driver update failed:", error);
      alert("Network error.");
    }
  };

  const handleDriverDocs = async (user: User) => {
    setShowDriverDocs(true);
    setDocsLoading(true);
    setSelectedUser(user);
    try {
      const token = localStorage.getItem("token");
      const response = await fetch(
        apiUrl(`/api/v1/admin/drivers/${user.id}/documents`),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();
      if (result.success) {
        setDriverDocs(result.data || []);
      } else {
        setDriverDocs([]);
      }
    } catch (error) {
      console.error("Failed to fetch driver documents:", error);
      setDriverDocs([]);
    } finally {
      setDocsLoading(false);
    }
  };

  const handleApproveDocument = async (documentId: string) => {
    try {
      const token = localStorage.getItem("token");
      const response = await fetch(
        apiUrl(`/api/v1/admin/drivers/documents/${documentId}/approve`),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();
      if (result.success && selectedUser) {
        handleDriverDocs(selectedUser);
      }
    } catch (error) {
      console.error("Failed to approve document:", error);
    }
  };

  const handleRejectDocument = async (documentId: string) => {
    const reason = window.prompt("Reason for rejection?");
    if (reason === null) return;
    try {
      const token = localStorage.getItem("token");
      const response = await fetch(
        apiUrl(`/api/v1/admin/drivers/documents/${documentId}/reject`),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ reason }),
        }
      );
      const result = await response.json();
      if (result.success && selectedUser) {
        handleDriverDocs(selectedUser);
      }
    } catch (error) {
      console.error("Failed to reject document:", error);
    }
  };

  const handleDriverBan = async (user: User) => {
    const confirmed = window.confirm(
      `Ban ${user.name ?? user.email}? This will block the driver.`
    );
    if (!confirmed) return;
    try {
      const token = localStorage.getItem("token");
      await fetch(apiUrl(`/api/v1/admin/drivers/${user.id}/block`), {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      fetchUsers();
    } catch (error) {
      console.error("Failed to ban driver:", error);
    }
  };

  const handleDriverActivate = async (user: User) => {
    const confirmed = window.confirm(
      `Activate ${user.name ?? user.email}? This will unblock the driver.`
    );
    if (!confirmed) return;
    try {
      const token = localStorage.getItem("token");
      await fetch(apiUrl(`/api/v1/admin/drivers/${user.id}/unblock`), {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      fetchUsers();
    } catch (error) {
      console.error("Failed to activate driver:", error);
    }
  };

  const handleDriverVerify = async (user: User, approved: boolean) => {
    const actionLabel = approved ? "Verify" : "Reject verification";
    const confirmed = window.confirm(
      `${actionLabel} ${user.name ?? user.email}?`
    );
    if (!confirmed) return;
    const reasonText = approved ? "" : window.prompt("Reason for rejection?") ?? "";
    if (!approved && reasonText.trim().length === 0) {
      alert("Rejection reason is required.");
      return;
    }
    try {
      const token = localStorage.getItem("token");
      const response = await fetch(apiUrl(`/api/v1/admin/drivers/${user.id}/verify`), {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          approved,
          reason: approved ? null : reasonText.trim(),
        }),
      });
      const result = await response.json();
      if (result.success) {
        setUserData((prev) =>
          prev.map((item) =>
            item.id === user.id
              ? {
                  ...item,
                  driver: {
                    ...(item.driver ?? {}),
                    is_verified: approved,
                  },
                }
              : item
          )
        );
        fetchUsers();
      } else {
        alert(result.message || "Failed to update verification.");
      }
    } catch (error) {
      console.error("Failed to verify driver:", error);
      alert("Network error.");
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
          <p className="text-gray-500">
            Manage your platform {activeTab === "driver" ? "drivers" : `${activeTab}s`}
          </p>
        </div>
        <button
          onClick={() => {
            if (activeTab === "driver") return;
            setIsEditing(false);
            setFormData({
              id: undefined,
              userType: activeTab === "vendor" ? "vendor" : "customer",
              fullName: "",
              email: "",
              phone: "",
              address: "",
              status: "ACTIVE",
            });
            setShowModal(true);
          }}
          className={`px-6 py-2 gap-2 rounded-full text-white text-sm flex justify-between items-center font-bold transition ${
            activeTab === "driver"
              ? "bg-gray-300 cursor-not-allowed"
              : "bg-[#1F6728] hover:bg-[#185321]"
          }`}
          disabled={activeTab === "driver"}
        >
          <CgProfile className="text-lg" />
          Add New{" "}
          {activeTab === "customer"
            ? "Customer"
            : activeTab === "vendor"
            ? "Vendor"
            : "Driver"}
        </button>
      </div>

      {/* Tabs */}
      <div className="flex space-x-6 border-b border-gray-200 mb-6">
        {(["customer", "vendor", "driver"] as const).map((tab) => (
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
                        {activeTab !== "driver" && (
                          <>
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
                          </>
                        )}
                        {activeTab === "driver" && (
                          <>
                            <button
                              onClick={() => handleDriverEdit(user)}
                              className="hover:text-[#1F6728] transition"
                              title="Edit driver"
                            >
                              <FaEdit />
                            </button>
                            <button
                              onClick={() => handleDriverDocs(user)}
                              className="hover:text-[#1F6728] transition text-xs font-semibold"
                              title="Review documents"
                            >
                              Docs
                            </button>
                            {user.driver?.is_verified ? (
                              <>
                                <span
                                  className="text-xs text-green-700 font-semibold"
                                  title="Driver verified"
                                >
                                  Verified
                                </span>
                                <button
                                  onClick={() => handleDriverVerify(user, false)}
                                  className="hover:text-red-600 transition text-xs font-semibold"
                                  title="Unverify driver"
                                >
                                  Unverify
                                </button>
                              </>
                            ) : (
                              <>
                                <button
                                  onClick={() => handleDriverVerify(user, true)}
                                  className="hover:text-[#1F6728] transition text-xs font-semibold"
                                  title="Verify driver"
                                >
                                  Verify
                                </button>
                                <button
                                  onClick={() => handleDriverVerify(user, false)}
                                  className="hover:text-red-600 transition text-xs font-semibold"
                                  title="Reject verification"
                                >
                                  Reject
                                </button>
                              </>
                            )}
                            {user.status === "BLOCKED" ? (
                              <button
                                onClick={() => handleDriverActivate(user)}
                                className="hover:text-green-600 transition text-xs font-semibold"
                                title="Activate driver"
                              >
                                Activate
                              </button>
                            ) : (
                              <button
                                onClick={() => handleDriverBan(user)}
                                className="hover:text-red-600 transition text-xs font-semibold"
                                title="Ban driver"
                              >
                                Ban
                              </button>
                            )}
                          </>
                        )}
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

      {showDriverDocs && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] px-4">
          <div className="bg-white w-full max-w-3xl rounded-2xl shadow-xl p-6 relative">
            <button
              className="absolute top-4 right-4 text-gray-400"
              onClick={() => setShowDriverDocs(false)}
            >
              ✕
            </button>
            <h3 className="text-xl font-bold mb-4">Driver Documents</h3>
            {docsLoading ? (
              <div className="py-10 text-center text-gray-500">
                Loading documents...
              </div>
            ) : driverDocs.length === 0 ? (
              <div className="py-10 text-center text-gray-400">
                No documents found.
              </div>
            ) : (
              <div className="space-y-4 max-h-[60vh] overflow-y-auto">
                {driverDocs.map((doc) => (
                  <div
                    key={doc.id}
                    className="border border-gray-200 rounded-lg p-4 flex items-center justify-between gap-4"
                  >
                    <div className="flex items-center gap-4">
                      <div className="h-16 w-16 rounded-md border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
                        <img
                          src={doc.file_url}
                          alt={doc.document_type}
                          className="h-full w-full object-cover"
                        />
                      </div>
                      <div>
                      <p className="font-semibold">{doc.document_type}</p>
                      <p className="text-xs text-gray-500">
                        Status: {doc.status}
                      </p>
                      {doc.rejection_reason && (
                        <p className="text-xs text-red-600">
                          Reason: {doc.rejection_reason}
                        </p>
                      )}
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <a
                        href={doc.file_url}
                        target="_blank"
                        rel="noreferrer"
                        className="text-xs text-[#1F6728] font-semibold"
                      >
                        View
                      </a>
                      <button
                        onClick={() => handleApproveDocument(doc.id)}
                        className="text-xs bg-green-100 text-green-700 px-2 py-1 rounded"
                      >
                        Approve
                      </button>
                      <button
                        onClick={() => handleRejectDocument(doc.id)}
                        className="text-xs bg-red-100 text-red-700 px-2 py-1 rounded"
                      >
                        Reject
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {showDriverEdit && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] px-4">
          <div className="bg-white w-full max-w-xl rounded-2xl shadow-xl p-6 relative">
            <button
              className="absolute top-4 right-4 text-gray-400"
              onClick={() => setShowDriverEdit(false)}
            >
              ✕
            </button>
            <h3 className="text-xl font-bold mb-4">Update Driver</h3>
            <div className="space-y-3">
              <input
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                placeholder="Full name"
                value={driverForm.name}
                onChange={(e) =>
                  setDriverForm((prev) => ({ ...prev, name: e.target.value }))
                }
              />
              <input
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                placeholder="Email"
                value={driverForm.email}
                onChange={(e) =>
                  setDriverForm((prev) => ({ ...prev, email: e.target.value }))
                }
              />
              <input
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                placeholder="Phone"
                value={driverForm.phone}
                onChange={(e) =>
                  setDriverForm((prev) => ({ ...prev, phone: e.target.value }))
                }
              />
              <select
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                value={driverForm.status}
                onChange={(e) =>
                  setDriverForm((prev) => ({ ...prev, status: e.target.value }))
                }
              >
                <option value="ACTIVE">ACTIVE</option>
                <option value="SUSPENDED">SUSPENDED</option>
                <option value="BLOCKED">BLOCKED</option>
              </select>
              <button
                onClick={saveDriverDetails}
                className="w-full bg-[#1F6728] text-white py-2 rounded-lg font-semibold"
              >
                Save
              </button>
              {selectedUser && (
                <div className="border-t border-gray-100 pt-3 flex items-center justify-between">
                  <span className="text-xs text-gray-500">
                    Verification:{" "}
                    {selectedUser.driver?.is_verified ? "Verified" : "Pending"}
                  </span>
                  <div className="flex items-center gap-2">
                    {selectedUser.driver?.is_verified ? (
                      <button
                        onClick={() => handleDriverVerify(selectedUser, false)}
                        className="text-xs bg-red-100 text-red-700 px-3 py-1.5 rounded"
                      >
                        Unverify
                      </button>
                    ) : (
                      <>
                        <button
                          onClick={() => handleDriverVerify(selectedUser, true)}
                          className="text-xs bg-green-100 text-green-700 px-3 py-1.5 rounded"
                        >
                          Verify
                        </button>
                        <button
                          onClick={() => handleDriverVerify(selectedUser, false)}
                          className="text-xs bg-red-100 text-red-700 px-3 py-1.5 rounded"
                        >
                          Reject
                        </button>
                      </>
                    )}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Users;
