import { useState, useEffect, useCallback } from "react";
import { apiUrl } from "../../lib/api";

interface Coupon {
  id: string;
  uuid: string;
  code: string;
  description: string;
  discount_type: "percentage" | "fixed";
  discount_value: number;
  min_order_amount: number;
  max_discount: number;
  usage_limit: number;
  used_count: number;
  start_date: string;
  end_date: string;
  is_active: boolean;
  vendor_id?: string;
  created_at: string;
}

const Coupons = () => {
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingCoupon, setEditingCoupon] = useState<Coupon | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [formData, setFormData] = useState({
    code: "",
    description: "",
    discount_type: "percentage" as "percentage" | "fixed",
    discount_value: 0,
    min_order_amount: 0,
    max_discount: 0,
    usage_limit: 0,
    start_date: "",
    end_date: "",
    is_active: true,
  });

  const fetchCoupons = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(apiUrl("/api/v1/admin/coupons"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      const result = await response.json();
      if (result.success) {
        setCoupons(result.data.data || []);
      }
    } catch (err) {
      console.error("Failed to fetch coupons:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchCoupons();
  }, [fetchCoupons]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const token = localStorage.getItem("token");
    const url = editingCoupon
      ? apiUrl(`/api/v1/admin/coupons/${editingCoupon.uuid}`)
      : apiUrl("/api/v1/admin/coupons");
    const method = editingCoupon ? "PUT" : "POST";

    try {
      const response = await fetch(url, {
        method,
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(formData),
      });
      const result = await response.json();
      if (result.success) {
        fetchCoupons();
        setShowModal(false);
        resetForm();
      } else {
        alert(result.message || "Failed to save coupon");
      }
    } catch (err) {
      console.error("Failed to save coupon:", err);
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!confirm("Are you sure you want to delete this coupon?")) return;
    const token = localStorage.getItem("token");
    try {
      await fetch(apiUrl(`/api/v1/admin/coupons/${uuid}`), {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      fetchCoupons();
    } catch (err) {
      console.error("Failed to delete coupon:", err);
    }
  };

  const toggleStatus = async (coupon: Coupon) => {
    const token = localStorage.getItem("token");
    try {
      await fetch(apiUrl(`/api/v1/admin/coupons/${coupon.uuid}`), {
        method: "PUT",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ ...coupon, is_active: !coupon.is_active }),
      });
      fetchCoupons();
    } catch (err) {
      console.error("Failed to toggle status:", err);
    }
  };

  const resetForm = () => {
    setFormData({
      code: "",
      description: "",
      discount_type: "percentage",
      discount_value: 0,
      min_order_amount: 0,
      max_discount: 0,
      usage_limit: 0,
      start_date: "",
      end_date: "",
      is_active: true,
    });
    setEditingCoupon(null);
  };

  const openEditModal = (coupon: Coupon) => {
    setEditingCoupon(coupon);
    setFormData({
      code: coupon.code,
      description: coupon.description || "",
      discount_type: coupon.discount_type,
      discount_value: coupon.discount_value,
      min_order_amount: coupon.min_order_amount || 0,
      max_discount: coupon.max_discount || 0,
      usage_limit: coupon.usage_limit || 0,
      start_date: coupon.start_date?.split("T")[0] || "",
      end_date: coupon.end_date?.split("T")[0] || "",
      is_active: coupon.is_active,
    });
    setShowModal(true);
  };

  const generateCode = () => {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    let code = "";
    for (let i = 0; i < 8; i++) {
      code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    setFormData({ ...formData, code });
  };

  const filteredCoupons = coupons.filter(
    (c) =>
      c.code.toLowerCase().includes(searchQuery.toLowerCase()) ||
      c.description?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const isExpired = (endDate: string) => new Date(endDate) < new Date();

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl font-bold text-gray-800">Coupon Management</h2>
        <button
          onClick={() => {
            resetForm();
            setShowModal(true);
          }}
          className="bg-[#1F6728] text-white px-4 py-2 rounded-lg hover:bg-green-700"
        >
          + Create Coupon
        </button>
      </div>

      {/* Search */}
      <div className="mb-6">
        <input
          type="text"
          placeholder="Search by code or description..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="w-full max-w-md border rounded-lg px-4 py-2"
        />
      </div>

      {loading ? (
        <div className="flex justify-center py-10">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1F6728]"></div>
        </div>
      ) : (
        <div className="bg-white rounded-lg border overflow-hidden">
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-sm font-semibold text-gray-600">Code</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-gray-600">Discount</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-gray-600">Usage</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-gray-600">Validity</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
                <th className="px-4 py-3 text-right text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {filteredCoupons.map((coupon) => (
                <tr key={coupon.uuid} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <div className="font-mono font-semibold text-[#1F6728]">{coupon.code}</div>
                    <div className="text-sm text-gray-500">{coupon.description}</div>
                  </td>
                  <td className="px-4 py-3">
                    {coupon.discount_type === "percentage" ? (
                      <span>{coupon.discount_value}% OFF</span>
                    ) : (
                      <span>₦{coupon.discount_value.toLocaleString()} OFF</span>
                    )}
                    {coupon.max_discount > 0 && (
                      <div className="text-xs text-gray-500">
                        Max: ₦{coupon.max_discount.toLocaleString()}
                      </div>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-sm">
                      {coupon.used_count} / {coupon.usage_limit || "∞"}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="text-sm">
                      {coupon.start_date && (
                        <div>From: {new Date(coupon.start_date).toLocaleDateString()}</div>
                      )}
                      {coupon.end_date && (
                        <div className={isExpired(coupon.end_date) ? "text-red-500" : ""}>
                          To: {new Date(coupon.end_date).toLocaleDateString()}
                        </div>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <button
                      onClick={() => toggleStatus(coupon)}
                      className={`px-2 py-1 rounded-full text-xs ${
                        coupon.is_active && !isExpired(coupon.end_date)
                          ? "bg-green-100 text-green-700"
                          : "bg-gray-100 text-gray-500"
                      }`}
                    >
                      {isExpired(coupon.end_date) ? "Expired" : coupon.is_active ? "Active" : "Inactive"}
                    </button>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button
                      onClick={() => openEditModal(coupon)}
                      className="text-blue-600 hover:text-blue-800 mr-3"
                    >
                      Edit
                    </button>
                    <button
                      onClick={() => handleDelete(coupon.uuid)}
                      className="text-red-600 hover:text-red-800"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))}
              {filteredCoupons.length === 0 && (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                    No coupons found
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {/* Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <h3 className="text-xl font-bold mb-4">
              {editingCoupon ? "Edit Coupon" : "Create Coupon"}
            </h3>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium mb-1">Coupon Code</label>
                  <div className="flex gap-2">
                    <input
                      type="text"
                      value={formData.code}
                      onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                      className="flex-1 border rounded px-3 py-2 font-mono"
                      placeholder="SAVE20"
                      required
                    />
                    <button
                      type="button"
                      onClick={generateCode}
                      className="border border-gray-300 px-3 py-2 rounded hover:bg-gray-50"
                    >
                      Generate
                    </button>
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Description</label>
                  <input
                    type="text"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    placeholder="20% off on all orders"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">Discount Type</label>
                    <select
                      value={formData.discount_type}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          discount_type: e.target.value as "percentage" | "fixed",
                        })
                      }
                      className="w-full border rounded px-3 py-2"
                    >
                      <option value="percentage">Percentage (%)</option>
                      <option value="fixed">Fixed Amount (₦)</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">
                      Discount Value {formData.discount_type === "percentage" ? "(%)" : "(₦)"}
                    </label>
                    <input
                      type="number"
                      value={formData.discount_value}
                      onChange={(e) => setFormData({ ...formData, discount_value: Number(e.target.value) })}
                      className="w-full border rounded px-3 py-2"
                      required
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">Min Order Amount (₦)</label>
                    <input
                      type="number"
                      value={formData.min_order_amount}
                      onChange={(e) => setFormData({ ...formData, min_order_amount: Number(e.target.value) })}
                      className="w-full border rounded px-3 py-2"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">Max Discount (₦)</label>
                    <input
                      type="number"
                      value={formData.max_discount}
                      onChange={(e) => setFormData({ ...formData, max_discount: Number(e.target.value) })}
                      className="w-full border rounded px-3 py-2"
                      placeholder="0 = unlimited"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Usage Limit (0 = unlimited)</label>
                  <input
                    type="number"
                    value={formData.usage_limit}
                    onChange={(e) => setFormData({ ...formData, usage_limit: Number(e.target.value) })}
                    className="w-full border rounded px-3 py-2"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">Start Date</label>
                    <input
                      type="date"
                      value={formData.start_date}
                      onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                      className="w-full border rounded px-3 py-2"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">End Date</label>
                    <input
                      type="date"
                      value={formData.end_date}
                      onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                      className="w-full border rounded px-3 py-2"
                    />
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="is_active"
                    checked={formData.is_active}
                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                  />
                  <label htmlFor="is_active" className="text-sm">Active</label>
                </div>
              </div>
              <div className="flex gap-2 mt-6">
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="flex-1 border border-gray-300 py-2 rounded hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 bg-[#1F6728] text-white py-2 rounded hover:bg-green-700"
                >
                  {editingCoupon ? "Update" : "Create"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default Coupons;
