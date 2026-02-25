import { useState, useEffect, useCallback } from "react";
import PromotionModal from "./promotionModal";
import { BiPencil, BiPlus, BiTrash } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

type TabType = "all" | "active" | "scheduled" | "expired";
type SecondTab = "activePromotions" | "endingRequests";

interface Promotion {
  id: string;
  title: string;
  description: string;
  start_date: string;
  end_date: string;
  status: string;
  banner_image: string;
  discount_value: string;
  discount_type: string;
  is_currently_active: boolean;
  is_scheduled: boolean;
  is_expired: boolean;
  vendor?: { name: string; email: string };
  created_at: string;
}

const Promotions = () => {
  const [activeTab, setActiveTab] = useState<TabType>("all");
  const [secondTab, setSecondTab] = useState<SecondTab>("activePromotions");
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingPromo, setEditingPromo] = useState<any>(null);

  const [promotions, setPromotions] = useState<Promotion[]>([]);
  const [stats, setStats] = useState({
    total_promotions: 0,
    active_promotions: 0,
    scheduled_promotions: 0,
    expired_promotions: 0,
  });

  // 1. FETCH DATA FROM API
  const fetchData = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      // Fetch Stats
      const statsRes = await fetch(
        apiUrl("/api/v1/admin/promotion-management/stats"),
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      const statsJson = await statsRes.json();
      if (statsJson.success) setStats(statsJson.data);

      // Fetch All Promotions
      const listRes = await fetch(
        apiUrl("/api/v1/admin/promotion-management"),
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      const listJson = await listRes.json();
      if (listJson.success) setPromotions(listJson.data.data);
    } catch (err) {
      console.error("Fetch error:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // 2. FILTER LOGIC
  const filteredPromotions = promotions.filter((p) => {
    if (secondTab === "activePromotions") {
      // Exclude pending ones from the grid view
      if (p.status === "pending") return false;

      if (activeTab === "active") return p.is_currently_active;
      if (activeTab === "scheduled") return p.is_scheduled;
      if (activeTab === "expired") return p.is_expired;
      return true;
    }
    // "Ending Requests" tab shows only pending
    return p.status === "pending";
  });

  // 3. ACTION HANDLERS
  const handleStatusUpdate = async (
    id: string,
    action: "approve" | "reject"
  ) => {
    const token = localStorage.getItem("token");
    try {
      const res = await fetch(
        apiUrl(`/api/v1/admin/promotion-management/${id}/${action}`),
        {
          method: "POST",
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      const result = await res.json();
      if (result.success) {
        alert(`Promotion ${action}d successfully`);
        fetchData();
      } else {
        alert(result.message);
      }
    } catch (err) {
      alert("Error updating promotion status");
    }
  };

  const handleDelete = async (id: string) => {
    if (!window.confirm("Delete this promotion?")) return;
    const token = localStorage.getItem("token");
    await fetch(
      apiUrl(`/api/v1/admin/promotion-management/${id}`),
      {
        method: "DELETE",
        headers: { Authorization: `Bearer ${token}` },
      }
    );
    fetchData();
  };

  const openNewModal = () => {
    setEditingPromo(null);
    setModalOpen(true);
  };

  const openEditModal = (promo: Promotion) => {
    setEditingPromo(promo);
    setModalOpen(true);
  };

  return (
    <div>
      {/* PAGE HEADER */}
      <div className="mb-4 flex justify-between items-center">
        <div>
          <p className="text-2xl font-bold text-gray-800">Promotions</p>
          <p className="text-gray-500">
            Manage promotional campaigns and vendor requests.
          </p>
        </div>
        <button
          onClick={openNewModal}
          className="bg-[#2C9139] px-5 py-2 rounded-full text-sm flex gap-2 items-center text-white font-bold hover:bg-[#185321]"
        >
          <BiPlus className="text-[18px]" /> New Promotion
        </button>
      </div>

      {/* STATS FROM API */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {[
          { label: "total", val: stats.total_promotions },
          { label: "active", val: stats.active_promotions },
          { label: "scheduled", val: stats.scheduled_promotions },
          { label: "expired", val: stats.expired_promotions },
        ].map((s) => (
          <div
            key={s.label}
            className="bg-white shadow-sm rounded-md p-6 border border-gray-200"
          >
            <p className="font-semibold text-gray-400 capitalize">{s.label}</p>
            <p className="text-2xl font-bold text-[#1F6728] mt-2">{s.val}</p>
          </div>
        ))}
      </div>

      {/* TABS */}
      <div className="flex flex-col gap-4 mb-6">
        <div className="flex items-center border-b border-gray-200 gap-2 pb-2">
          {(["all", "active", "scheduled", "expired"] as TabType[]).map(
            (tab) => (
              <button
                key={tab}
                onClick={() => setActiveTab(tab)}
                className={`px-4 py-1.5 text-sm font-semibold capitalize rounded-full transition-all ${
                  activeTab === tab
                    ? "bg-[#1F6728] text-white"
                    : "text-gray-500 hover:bg-gray-100"
                }`}
              >
                {tab}
              </button>
            )
          )}
        </div>

        <div className="flex items-center gap-2">
          {(
            [
              { key: "activePromotions", label: "Approved Campaigns" },
              { key: "endingRequests", label: "Pending Requests" },
            ] as const
          ).map((tab) => (
            <button
              key={tab.key}
              onClick={() => setSecondTab(tab.key)}
              className={`px-4 py-2 text-xs font-bold uppercase tracking-wider transition-all ${
                secondTab === tab.key
                  ? "border-b-2 border-[#1F6728] text-[#1F6728]"
                  : "text-gray-400"
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      {loading ? (
        <div className="relative w-full h-[60vh] flex flex-col items-center justify-center">
          <div className="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-[#1F6728]"></div>
        </div>
      ) : secondTab === "activePromotions" ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredPromotions.map((promo) => (
            <div
              key={promo.id}
              className="border border-gray-100 rounded-2xl bg-white overflow-hidden shadow-sm hover:shadow-md transition-all group"
            >
              <div className="relative h-44 w-full bg-gray-100">
                <img
                  src={promo.banner_image}
                  alt={promo.title}
                  className="h-full w-full object-cover"
                />
                <div className="absolute inset-0 bg-black/40 flex flex-col justify-end p-4 opacity-0 group-hover:opacity-100 transition-opacity">
                  <div className="flex gap-2 mb-2">
                    <button
                      onClick={() => openEditModal(promo)}
                      className="bg-white p-2 rounded-full shadow-lg hover:scale-110 transition"
                    >
                      <BiPencil className="text-black" />
                    </button>
                    <button
                      onClick={() => handleDelete(promo.id)}
                      className="bg-white p-2 rounded-full shadow-lg hover:scale-110 transition"
                    >
                      <BiTrash className="text-red-600" />
                    </button>
                  </div>
                </div>
                <div className="absolute top-3 left-3 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-[10px] font-black uppercase text-[#1F6728]">
                  {promo.discount_value}
                  {promo.discount_type === "percentage" ? "%" : " NGN"} OFF
                </div>
              </div>

              <div className="p-4">
                <div className="flex justify-between items-start mb-2">
                  <h3 className="font-bold text-gray-800 line-clamp-1">
                    {promo.title}
                  </h3>
                  <span
                    className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                      promo.is_currently_active
                        ? "bg-green-100 text-green-700"
                        : "bg-gray-100 text-gray-500"
                    }`}
                  >
                    {promo.is_currently_active ? "Active" : "Inactive"}
                  </span>
                </div>
                <p className="text-gray-500 text-xs line-clamp-2 h-8">
                  {promo.description}
                </p>
                <div className="mt-4 pt-4 border-t border-gray-50 text-[10px] text-gray-400 font-bold flex justify-between">
                  <span>
                    START: {new Date(promo.start_date).toLocaleDateString()}
                  </span>
                  <span>
                    END: {new Date(promo.end_date).toLocaleDateString()}
                  </span>
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        /* PENDING REQUESTS TAB */
        <div className="space-y-4">
          {filteredPromotions.map((req) => (
            <div
              key={req.id}
              className="border-2 border-gray-50 rounded-2xl bg-white p-6 flex justify-between items-center shadow-sm"
            >
              <div className="flex gap-6 items-center">
                <img
                  src={req.banner_image}
                  className="w-20 h-20 rounded-xl object-cover"
                />
                <div className="space-y-1">
                  <div className="flex gap-3 items-center">
                    <p className="text-lg font-black text-gray-800">
                      {req.title}
                    </p>
                    <p className="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                      Vendor Campaign
                    </p>
                  </div>
                  <p className="text-sm text-gray-500 font-medium">
                    {req.vendor?.name || "Unknown Vendor"} •{" "}
                    {new Date(req.created_at).toLocaleDateString()}
                  </p>
                  <div className="flex gap-6 mt-3">
                    <div className="flex flex-col">
                      <span className="text-[10px] text-gray-400 font-bold uppercase">
                        Discount
                      </span>
                      <span className="font-bold text-[#1F6728]">
                        {req.discount_value}
                        {req.discount_type === "percentage" ? "%" : " NGN"}
                      </span>
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[10px] text-gray-400 font-bold uppercase">
                        Duration
                      </span>
                      <span className="font-medium text-gray-700 text-xs">
                        {new Date(req.start_date).toLocaleDateString()} -{" "}
                        {new Date(req.end_date).toLocaleDateString()}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex gap-3">
                <button
                  onClick={() => handleStatusUpdate(req.id, "approve")}
                  className="bg-[#1F6728] text-white px-6 py-2 rounded-full font-bold text-sm hover:shadow-lg transition"
                >
                  Approve
                </button>
                <button
                  onClick={() => handleStatusUpdate(req.id, "reject")}
                  className="border-2 border-red-50 text-red-600 px-6 py-2 rounded-full font-bold text-sm hover:bg-red-50 transition"
                >
                  Reject
                </button>
              </div>
            </div>
          ))}
          {filteredPromotions.length === 0 && (
            <div className="text-center py-20 text-gray-400">
              No pending promotion requests.
            </div>
          )}
        </div>
      )}

      <PromotionModal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        editingPromo={editingPromo}
        onSuccess={fetchData}
      />
    </div>
  );
};

export default Promotions;
