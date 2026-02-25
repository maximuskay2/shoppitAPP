import { useState, useEffect, useCallback } from "react";
import { apiUrl } from "../../lib/api";

interface SubscriptionPlan {
  id: string;
  uuid: string;
  name: string;
  description: string;
  price_monthly: number;
  price_annual: number;
  features: string[];
  max_products: number;
  is_active: boolean;
  created_at: string;
}

const SubscriptionPlans = () => {
  const [plans, setPlans] = useState<SubscriptionPlan[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingPlan, setEditingPlan] = useState<SubscriptionPlan | null>(null);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    price_monthly: 0,
    price_annual: 0,
    features: "",
    max_products: 100,
    is_active: true,
  });

  const fetchPlans = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(apiUrl("/api/v1/admin/subscription-management"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      const result = await response.json();
      if (result.success) {
        setPlans(result.data.data || []);
      }
    } catch (err) {
      console.error("Failed to fetch plans:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchPlans();
  }, [fetchPlans]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const token = localStorage.getItem("token");
    const url = editingPlan
      ? apiUrl(`/api/v1/admin/subscription-management/${editingPlan.uuid}`)
      : apiUrl("/api/v1/admin/subscription-management");
    const method = editingPlan ? "PUT" : "POST";

    try {
      const response = await fetch(url, {
        method,
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          ...formData,
          features: formData.features.split("\n").filter((f) => f.trim()),
        }),
      });
      const result = await response.json();
      if (result.success) {
        fetchPlans();
        setShowModal(false);
        resetForm();
      }
    } catch (err) {
      console.error("Failed to save plan:", err);
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!confirm("Are you sure you want to delete this plan?")) return;
    const token = localStorage.getItem("token");
    try {
      await fetch(apiUrl(`/api/v1/admin/subscription-management/${uuid}`), {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      fetchPlans();
    } catch (err) {
      console.error("Failed to delete plan:", err);
    }
  };

  const resetForm = () => {
    setFormData({
      name: "",
      description: "",
      price_monthly: 0,
      price_annual: 0,
      features: "",
      max_products: 100,
      is_active: true,
    });
    setEditingPlan(null);
  };

  const openEditModal = (plan: SubscriptionPlan) => {
    setEditingPlan(plan);
    setFormData({
      name: plan.name,
      description: plan.description || "",
      price_monthly: plan.price_monthly,
      price_annual: plan.price_annual,
      features: (plan.features || []).join("\n"),
      max_products: plan.max_products,
      is_active: plan.is_active,
    });
    setShowModal(true);
  };

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl font-bold text-gray-800">Subscription Plans</h2>
        <button
          onClick={() => {
            resetForm();
            setShowModal(true);
          }}
          className="bg-[#1F6728] text-white px-4 py-2 rounded-lg hover:bg-green-700"
        >
          + Add Plan
        </button>
      </div>

      {loading ? (
        <div className="flex justify-center py-10">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1F6728]"></div>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {plans.map((plan) => (
            <div
              key={plan.uuid}
              className={`border rounded-lg p-6 ${
                plan.is_active ? "border-green-200 bg-white" : "border-gray-200 bg-gray-50"
              }`}
            >
              <div className="flex justify-between items-start mb-4">
                <h3 className="text-xl font-semibold">{plan.name}</h3>
                <span
                  className={`px-2 py-1 rounded-full text-xs ${
                    plan.is_active ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-500"
                  }`}
                >
                  {plan.is_active ? "Active" : "Inactive"}
                </span>
              </div>
              <p className="text-gray-600 text-sm mb-4">{plan.description}</p>
              <div className="mb-4">
                <div className="text-2xl font-bold text-[#1F6728]">
                  ₦{plan.price_monthly?.toLocaleString()}<span className="text-sm font-normal">/mo</span>
                </div>
                <div className="text-sm text-gray-500">
                  ₦{plan.price_annual?.toLocaleString()}/year
                </div>
              </div>
              <div className="text-sm text-gray-600 mb-4">
                Max Products: {plan.max_products}
              </div>
              <div className="flex gap-2">
                <button
                  onClick={() => openEditModal(plan)}
                  className="flex-1 border border-[#1F6728] text-[#1F6728] py-2 rounded hover:bg-green-50"
                >
                  Edit
                </button>
                <button
                  onClick={() => handleDelete(plan.uuid)}
                  className="flex-1 border border-red-500 text-red-500 py-2 rounded hover:bg-red-50"
                >
                  Delete
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
            <h3 className="text-xl font-bold mb-4">
              {editingPlan ? "Edit Plan" : "Create Plan"}
            </h3>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium mb-1">Name</label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Description</label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    rows={3}
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">Monthly Price (₦)</label>
                    <input
                      type="number"
                      value={formData.price_monthly}
                      onChange={(e) => setFormData({ ...formData, price_monthly: Number(e.target.value) })}
                      className="w-full border rounded px-3 py-2"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">Annual Price (₦)</label>
                    <input
                      type="number"
                      value={formData.price_annual}
                      onChange={(e) => setFormData({ ...formData, price_annual: Number(e.target.value) })}
                      className="w-full border rounded px-3 py-2"
                      required
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Max Products</label>
                  <input
                    type="number"
                    value={formData.max_products}
                    onChange={(e) => setFormData({ ...formData, max_products: Number(e.target.value) })}
                    className="w-full border rounded px-3 py-2"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Features (one per line)</label>
                  <textarea
                    value={formData.features}
                    onChange={(e) => setFormData({ ...formData, features: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    rows={4}
                    placeholder="Feature 1&#10;Feature 2&#10;Feature 3"
                  />
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
                  {editingPlan ? "Update" : "Create"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default SubscriptionPlans;
