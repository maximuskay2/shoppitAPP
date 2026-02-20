import { useEffect, useState } from "react";
import { BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

const CommissionSettings = () => {
  const [commissionRate, setCommissionRate] = useState("");
  const [deliveryFee, setDeliveryFee] = useState("");
  const [minimumWithdrawal, setMinimumWithdrawal] = useState("");
  const [deliveryRadiusKm, setDeliveryRadiusKm] = useState("");
  const [radiusActive, setRadiusActive] = useState(true);
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);

  useEffect(() => {
    const fetchSettings = async () => {
      const token = localStorage.getItem("token");
      try {
        const response = await fetch(apiUrl("/api/v1/admin/settings/commission"), {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        });
        const result = await response.json();
        if (result.success) {
          setCommissionRate(
            result.data.driver_commission_rate !== null
              ? String(result.data.driver_commission_rate)
              : ""
          );
          setDeliveryFee(
            result.data.delivery_fee_commission !== null
              ? String(result.data.delivery_fee_commission)
              : ""
          );
          setMinimumWithdrawal(
            result.data.minimum_withdrawal !== null
              ? String(result.data.minimum_withdrawal)
              : ""
          );
          setDeliveryRadiusKm(
            result.data.driver_match_radius_km !== null
              ? String(result.data.driver_match_radius_km)
              : ""
          );
          setRadiusActive(
            result.data.driver_match_radius_active !== null
              ? Boolean(result.data.driver_match_radius_active)
              : true
          );
        }
      } catch (err) {
        console.error("Failed to fetch commission settings:", err);
      } finally {
        setFetching(false);
      }
    };

    fetchSettings();
  }, []);

  const handleSave = async () => {
    setLoading(true);
    const token = localStorage.getItem("token");

    const payload = {
      driver_commission_rate: commissionRate ? Number(commissionRate) : null,
      delivery_fee_commission: deliveryFee ? Number(deliveryFee) : null,
      minimum_withdrawal: minimumWithdrawal ? Number(minimumWithdrawal) : null,
      driver_match_radius_km: deliveryRadiusKm ? Number(deliveryRadiusKm) : null,
      driver_match_radius_active: radiusActive,
    };

    try {
      const response = await fetch(apiUrl("/api/v1/admin/settings/commission"), {
        method: "PUT",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(payload),
      });

      const result = await response.json();
      if (result.success) {
        alert("Commission settings updated successfully!");
      } else {
        alert(result.message || "Failed to update commission settings");
      }
    } catch (err) {
      console.error("Update Error:", err);
      alert("Network error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  if (fetching) {
    return (
      <div className="flex flex-col items-center justify-center py-20 text-gray-400">
        <BiLoaderAlt className="animate-spin text-4xl mb-2" />
        <p className="font-bold text-xs uppercase tracking-widest">
          Fetching Configuration...
        </p>
      </div>
    );
  }

  return (
    <div className="animate-in fade-in duration-500 flex flex-col min-h-full">
      <p className="text-xl font-semibold mb-4">Commission Settings</p>

      <div>
        <div className="mb-5">
          <label className="block mb-1 font-medium">
            Default Commission Rate
          </label>
          <input
            type="number"
            min="0"
            max="100"
            step="1"
            value={commissionRate}
            onChange={(e) => setCommissionRate(e.target.value)}
            className="px-3 py-2 rounded-full w-full border border-gray-300"
          />
          <p className="text-xs">Platform commission on each transaction</p>
        </div>

        <div className="mb-5">
          <label className="block mb-1 font-medium">
            Delivery Fee Commission
          </label>
          <input
            type="number"
            min="0"
            max="100"
            step="1"
            value={deliveryFee}
            onChange={(e) => setDeliveryFee(e.target.value)}
            className="px-3 py-2 rounded-full w-full border border-gray-300"
          />
        </div>

        <div className="mb-5">
          <label className="block mb-1 font-medium">
            Minimum Withdrawal Amount
          </label>
          <input
            type="number"
            min="0"
            max="100"
            step="1000"
            value={minimumWithdrawal}
            onChange={(e) => setMinimumWithdrawal(e.target.value)}
            className="px-3 py-2 rounded-full w-full border border-gray-300"
          />
        </div>

        <div className="mb-5">
          <label className="block mb-1 font-medium">
            Driver Match Radius (km)
          </label>
          <input
            type="number"
            min="1"
            step="1"
            value={deliveryRadiusKm}
            onChange={(e) => setDeliveryRadiusKm(e.target.value)}
            className="px-3 py-2 rounded-full w-full border border-gray-300"
          />
          <p className="text-xs">Max distance to show available orders</p>
        </div>

        <div className="mb-5 flex items-center justify-between rounded-full border border-gray-300 px-4 py-3">
          <div>
            <p className="font-medium">Radius Filtering</p>
            <p className="text-xs text-gray-500">Enable/disable distance filtering</p>
          </div>
          <button
            type="button"
            onClick={() => setRadiusActive((prev) => !prev)}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
              radiusActive ? "bg-[#1F6728]" : "bg-gray-300"
            }`}
            aria-pressed={radiusActive}
          >
            <span
              className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                radiusActive ? "translate-x-6" : "translate-x-1"
              }`}
            />
          </button>
        </div>
      </div>

      <div className="mt-6 flex justify-end">
        <button
          onClick={handleSave}
          disabled={loading}
          className="bg-[#1F6728] text-white px-10 py-3 rounded-full font-bold shadow-lg hover:bg-[#185321] transition-all disabled:bg-gray-300 flex items-center gap-2"
        >
          {loading && <BiLoaderAlt className="animate-spin" />}
          {loading ? "Saving..." : "Save Settings"}
        </button>
      </div>
    </div>
  );
};

export default CommissionSettings;
