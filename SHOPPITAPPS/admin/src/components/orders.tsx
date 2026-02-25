import { useState, useEffect, useCallback } from "react";
import { BiDownload } from "react-icons/bi";
import { FaEye } from "react-icons/fa";
import { apiUrl } from "../lib/api";

type TabType =
  | "all"
  | "pending"
  | "paid"
  | "dispatched"
  | "cancelled"
  | "completed";

type DriverOption = {
  id: string;
  name: string;
  email: string;
  phone: string;
  isOnline: boolean;
  isVerified: boolean;
};

const Orders = () => {
  const [activeTab, setActiveTab] = useState<TabType>("all");
  const [searchTerm, setSearchTerm] = useState("");
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const [totalOrders, setTotalOrders] = useState(0);
  const [lastPage, setLastPage] = useState(1);

  const [selectedOrder, setSelectedOrder] = useState<any | null>(null);
  const [drivers, setDrivers] = useState<DriverOption[]>([]);
  const [driversLoading, setDriversLoading] = useState(false);
  const [reassignDriverId, setReassignDriverId] = useState("");
  const [reassignReason, setReassignReason] = useState("");
  const [reassigning, setReassigning] = useState(false);
  const [flaggedOrders, setFlaggedOrders] = useState<any[]>([]);
  const [flaggedLoading, setFlaggedLoading] = useState(false);

  const fetchOrders = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");

    // Construct URL with query parameters per API doc
    const params = new URLSearchParams({
      page: currentPage.toString(),
    });

    if (activeTab !== "all") params.append("status", activeTab.toUpperCase());
    if (searchTerm) params.append("search", searchTerm);

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/order-management?${params.toString()}`),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();

      if (result.success) {
        setOrders(result.data.data);
        setTotalOrders(result.data.total);
        setLastPage(result.data.last_page);
      }
    } catch (err) {
      console.error("Fetch orders error:", err);
    } finally {
      setLoading(false);
    }
  }, [currentPage, activeTab, searchTerm]);

  useEffect(() => {
    fetchOrders();
  }, [fetchOrders]);

  useEffect(() => {
    if (!selectedOrder) {
      setReassignDriverId("");
      setReassignReason("");
      return;
    }

    const currentDriverId =
      selectedOrder.driver_id || selectedOrder.driver?.id || "";
    setReassignDriverId(currentDriverId);
    setReassignReason("");
    fetchDrivers();
  }, [selectedOrder]);

  useEffect(() => {
    fetchFlaggedOrders();
  }, []);

  // Fetch single order details when eye icon is clicked
  const handleViewOrder = async (orderId: string) => {
    const token = localStorage.getItem("token");
    try {
      const res = await fetch(
        apiUrl(`/api/v1/admin/order-management/${orderId}`),
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      const result = await res.json();
      if (result.success) setSelectedOrder(result.data);
    } catch (err) {
      console.error("View order error:", err);
    }
  };

  const updateStatus = async (id: string, newStatus: string) => {
    const token = localStorage.getItem("token");
    const formData = new FormData();
    formData.append("status", newStatus);

    try {
      const res = await fetch(
        apiUrl(`/api/v1/admin/order-management/${id}/update-status`),
        {
          method: "POST",
          headers: { Authorization: `Bearer ${token}` },
          body: formData,
        }
      );
      if (res.ok) {
        fetchOrders();
        setSelectedOrder(null);
      }
    } catch (err) {
      console.error("Update status error:", err);
    }
  };

  const fetchDrivers = async () => {
    const token = localStorage.getItem("token");
    setDriversLoading(true);

    try {
      const response = await fetch(apiUrl("/api/v1/admin/drivers?per_page=100"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      const result = await response.json();
      if (result.success) {
        const items = result.data?.data || [];
        setDrivers(
          items.map((driver: any) => ({
            id: driver.id,
            name: driver.name,
            email: driver.email,
            phone: driver.phone,
            isOnline: Boolean(driver.driver?.is_online),
            isVerified: Boolean(driver.driver?.is_verified),
          }))
        );
      }
    } catch (err) {
      console.error("Fetch drivers error:", err);
    } finally {
      setDriversLoading(false);
    }
  };

  const handleReassignDriver = async () => {
    if (!selectedOrder || !reassignDriverId) return;

    const token = localStorage.getItem("token");
    setReassigning(true);

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/order-management/${selectedOrder.id}/reassign`),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            driver_id: reassignDriverId,
            reason: reassignReason || null,
          }),
        }
      );

      const result = await response.json();
      if (result.success) {
        await fetchOrders();
        setSelectedOrder(null);
      } else {
        alert(result.message || "Failed to reassign driver");
      }
    } catch (err) {
      console.error("Reassign driver error:", err);
      alert("Network error. Please try again.");
    } finally {
      setReassigning(false);
    }
  };

  const fetchFlaggedOrders = async () => {
    const token = localStorage.getItem("token");
    setFlaggedLoading(true);

    const params = new URLSearchParams({
      status: "READY_FOR_PICKUP",
      per_page: "50",
    });

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/order-management?${params.toString()}`),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();

      if (result.success) {
        const items = result.data?.data || [];
        const fifteenMinutesAgo = Date.now() - 15 * 60 * 1000;
        setFlaggedOrders(
          items.filter((order: any) => {
            const createdAt = new Date(order.created_at).getTime();
            return createdAt < fifteenMinutesAgo;
          })
        );
      }
    } catch (err) {
      console.error("Fetch flagged orders error:", err);
    } finally {
      setFlaggedLoading(false);
    }
  };

  const handleExportCSV = () => {
    if (orders.length === 0) return alert("No orders to export");

    // 1. Define CSV Headers
    const headers = [
      "Tracking ID",
      "Customer Name",
      "Customer Email",
      "Vendor",
      "Amount (NGN)",
      "Status",
      "Date",
      "Payment Ref",
    ];

    // 2. Map orders to CSV Rows
    const rows = orders.map((order) => [
      order.tracking_id,
      order.receiver_name,
      order.email,
      order.vendor?.business_name || "N/A",
      order.net_total_amount,
      order.status,
      new Date(order.created_at).toLocaleDateString(),
      order.payment_reference,
    ]);

    // 3. Combine into a single string
    const csvContent = [
      headers.join(","),
      ...rows.map((row) => row.join(",")),
    ].join("\n");

    // 4. Create a Blob and trigger download
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute(
      "download",
      `shopittplus_orders_${new Date().toISOString().split("T")[0]}.csv`
    );
    link.style.visibility = "hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div>
      {/* Header */}
      <div className="flex justify-between items-start px-4 mb-4">
        <div>
          <p className="text-2xl font-bold text-gray-800">Order Management</p>
          <p className="text-gray-500">
            Track and manage all orders from your marketplace.
          </p>
        </div>
        <button
          onClick={handleExportCSV}
          className="bg-[#2C9139] flex gap-2 items-center justify-between text-white px-5 py-2 rounded-full font-bold text-sm shadow-lg shadow-green-900/20 hover:bg-[#185321] transition active:scale-95"
        >
          <BiDownload className="text-lg" />
          Export CSV
        </button>
      </div>

      {/* Tabs */}
      <div className="flex items-center border-b border-gray-200 px-4 mb-4 overflow-x-auto">
        {(
          [
            "all",
            "pending",
            "paid",
            "dispatched",
            "completed",
            "cancelled",
          ] as TabType[]
        ).map((tab) => (
          <button
            key={tab}
            onClick={() => {
              setActiveTab(tab);
              setCurrentPage(1);
            }}
            className={`px-4 py-2 font-semibold capitalize whitespace-nowrap ${
              activeTab === tab
                ? "border-b-2 border-[#1F6728] text-[#1F6728]"
                : "text-gray-400 hover:text-gray-700"
            }`}
          >
            {tab}
          </button>
        ))}
      </div>

      {/* Flagged Orders */}
      <div className="border border-red-100 bg-red-50 p-4 rounded-xl mx-4 mb-4">
        <div className="flex items-center justify-between mb-2">
          <div>
            <p className="text-sm font-bold text-red-700">Flagged Orders</p>
            <p className="text-xs text-red-600">
              Orders in READY_FOR_PICKUP for more than 15 minutes
            </p>
          </div>
          <button
            onClick={fetchFlaggedOrders}
            className="px-3 py-2 rounded-full bg-red-600 text-white text-xs"
            disabled={flaggedLoading}
          >
            {flaggedLoading ? "Refreshing..." : "Refresh"}
          </button>
        </div>
        {flaggedOrders.length === 0 && !flaggedLoading && (
          <p className="text-xs text-red-500">No flagged orders right now.</p>
        )}
        {flaggedOrders.length > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
            {flaggedOrders.slice(0, 6).map((order) => (
              <div
                key={order.id}
                className="bg-white border border-red-100 rounded-lg px-3 py-2 flex items-center justify-between"
              >
                <div>
                  <p className="text-xs font-semibold text-gray-700">
                    {order.tracking_id}
                  </p>
                  <p className="text-[11px] text-gray-500">
                    {order.vendor?.business_name || "Unknown vendor"}
                  </p>
                </div>
                <button
                  onClick={() => handleViewOrder(order.id)}
                  className="text-red-600 text-xs font-semibold"
                >
                  View
                </button>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Search */}
      <div className="px-4 mb-4">
        <input
          type="text"
          placeholder="Search by Tracking ID or email..."
          className="border border-gray-300 rounded-full px-6 py-2 text-sm w-1/2 focus:ring-2 focus:ring-[#1F6728] outline-none"
          onChange={(e) => {
            setSearchTerm(e.target.value);
            setCurrentPage(1);
          }}
        />
      </div>

      {/* Table */}
      <div className="border border-gray-200 p-4 rounded-xl bg-white relative">
        {loading && (
          <div className="relative w-full h-[60vh] flex flex-col items-center justify-center">
            <div className="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-[#1F6728]"></div>
          </div>
        )}
        ;
        <table className="w-full text-left text-sm text-gray-600">
          <thead>
            <tr className="text-gray-400 text-[10px] font-bold uppercase border-b border-gray-100">
              <th className="py-3 px-2">Order ID</th>
              <th className="py-3">Customer</th>
              <th className="py-3">Vendor</th>
              <th className="py-3">Total</th>
              <th className="py-3">Status</th>
              <th className="py-3">Date</th>
              <th className="py-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            {orders.map((order) => (
              <tr
                key={order.id}
                className="hover:bg-gray-50 border-b border-gray-50"
              >
                <td className="py-4 px-2 font-mono text-xs">
                  {order.tracking_id}
                </td>
                <td className="py-4 font-bold text-gray-800">
                  {order.receiver_name}
                </td>
                <td className="py-4 text-gray-500">
                  {order.vendor?.business_name}
                </td>
                <td className="py-4 font-bold text-gray-800">
                  ₦{order.net_total_amount.toLocaleString()}
                </td>
                <td className="py-4 text-xs font-bold">
                  <span
                    className={`px-3 py-1 rounded-full ${
                      order.status === "COMPLETED"
                        ? "bg-green-100 text-green-700"
                        : "bg-yellow-100 text-yellow-700"
                    }`}
                  >
                    {order.status}
                  </span>
                </td>
                <td className="py-4 text-gray-400">
                  {new Date(order.created_at).toLocaleDateString()}
                </td>
                <td className="py-4 text-center">
                  <button
                    onClick={() => handleViewOrder(order.id)}
                    className="text-[#1F6728] hover:scale-110 transition"
                  >
                    <FaEye />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {/* Pagination */}
        <div className="flex justify-between items-center mt-6">
          <p className="text-gray-400">
            Showing {orders.length} of {totalOrders} orders
          </p>
          <div className="flex gap-2">
            <button
              disabled={currentPage === 1}
              onClick={() => setCurrentPage((p) => p - 1)}
              className="px-4 py-1 border rounded-full disabled:opacity-30"
            >
              Prev
            </button>
            <span className="px-4 py-1 bg-[#1F6728] text-white rounded-full font-bold">
              {currentPage}
            </span>
            <button
              disabled={currentPage === lastPage}
              onClick={() => setCurrentPage((p) => p + 1)}
              className="px-4 py-1 border rounded-full disabled:opacity-30"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      {/* Modal */}
      {selectedOrder && (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-[60] px-4 backdrop-blur-sm">
          <div className="bg-white w-full max-w-lg rounded-2xl shadow-xl p-8 relative overflow-y-auto max-h-[90vh]">
            <button
              className="absolute top-6 right-6 text-gray-400"
              onClick={() => setSelectedOrder(null)}
            >
              ✕
            </button>
            <h3 className="text-2xl font-black mb-6">Order Details</h3>

            <div className="space-y-6">
              <section className="bg-gray-50 p-4 rounded-xl">
                <p className="text-[10px] font-bold text-gray-400 uppercase mb-2">
                  Vendor Information
                </p>
                <p className="font-bold">
                  {selectedOrder.vendor?.business_name}
                </p>
                <p className="text-xs text-gray-500">
                  {selectedOrder.vendor?.user?.phone}
                </p>
              </section>

              <section>
                <p className="text-[10px] font-bold text-gray-400 uppercase mb-3">
                  Line Items
                </p>
                {selectedOrder.line_items?.map((item: any) => (
                  <div
                    key={item.id}
                    className="flex justify-between items-center py-2 border-b border-gray-50"
                  >
                    <div className="flex items-center gap-3">
                      <img
                        src={item.product?.avatar?.[0]}
                        className="w-10 h-10 rounded-lg"
                      />
                      <div>
                        <p className="text-sm font-bold">
                          {item.product?.name}
                        </p>
                        <p className="text-xs text-gray-400">
                          Qty: {item.quantity}
                        </p>
                      </div>
                    </div>
                    <p className="font-bold">
                      ₦{item.subtotal.toLocaleString()}
                    </p>
                  </div>
                ))}
              </section>

              <section className="border-t pt-4 space-y-2">
                <div className="flex justify-between text-sm">
                  <span>Delivery Fee</span>
                  <span>₦{selectedOrder.delivery_fee}</span>
                </div>
                <div className="flex justify-between text-sm text-red-500">
                  <span>Coupon Discount</span>
                  <span>-₦{selectedOrder.coupon_discount}</span>
                </div>
                <div className="flex justify-between text-lg font-black pt-2">
                  <span>Total Amount</span>
                  <span>
                    ₦{selectedOrder.net_total_amount.toLocaleString()}
                  </span>
                </div>
              </section>

              <div className="flex gap-3 pt-4">
                <button
                  onClick={() => updateStatus(selectedOrder.id, "DISPATCHED")}
                  className="flex-1 bg-blue-600 text-white py-3 rounded-full font-bold text-sm"
                >
                  Mark Dispatched
                </button>
                <button
                  onClick={() => updateStatus(selectedOrder.id, "COMPLETED")}
                  className="flex-1 bg-[#1F6728] text-white py-3 rounded-full font-bold text-sm"
                >
                  Complete Order
                </button>
              </div>

              <section className="bg-gray-50 p-4 rounded-xl mt-6">
                <p className="text-[10px] font-bold text-gray-400 uppercase mb-2">
                  Reassign Driver
                </p>
                {driversLoading ? (
                  <p className="text-xs text-gray-500">Loading drivers...</p>
                ) : (
                  <select
                    className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                    value={reassignDriverId}
                    onChange={(e) => setReassignDriverId(e.target.value)}
                  >
                    <option value="">Select driver</option>
                    {drivers.map((driver) => (
                      <option key={driver.id} value={driver.id}>
                        {driver.name} {driver.isOnline ? "(Online)" : "(Offline)"}
                        {driver.isVerified ? "" : " - Unverified"}
                      </option>
                    ))}
                  </select>
                )}
                <input
                  type="text"
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mt-2"
                  placeholder="Reason (optional)"
                  value={reassignReason}
                  onChange={(e) => setReassignReason(e.target.value)}
                />
                <button
                  className="mt-3 w-full bg-red-600 text-white py-2 rounded-full text-sm font-bold disabled:opacity-60"
                  onClick={handleReassignDriver}
                  disabled={reassigning || !reassignDriverId}
                >
                  {reassigning ? "Reassigning..." : "Reassign Driver"}
                </button>
              </section>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Orders;
