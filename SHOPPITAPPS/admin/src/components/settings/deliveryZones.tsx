import { useState, useEffect, useCallback } from "react";
import { apiUrl } from "../../lib/api";

interface DeliveryZone {
  id: string;
  uuid: string;
  name: string;
  description: string;
  areas: string[];
  center_latitude?: number | null;
  center_longitude?: number | null;
  radius_km?: number | null;
  base_fee: number;
  per_km_fee: number;
  min_order_amount: number;
  estimated_time_min: number;
  estimated_time_max: number;
  is_active: boolean;
  created_at: string;
}

const DeliveryZones = () => {
  const [zones, setZones] = useState<DeliveryZone[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingZone, setEditingZone] = useState<DeliveryZone | null>(null);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    areas: "",
    center_latitude: null as number | null,
    center_longitude: null as number | null,
    radius_km: null as number | null,
    base_fee: 0,
    per_km_fee: 0,
    min_order_amount: 0,
    estimated_time_min: 30,
    estimated_time_max: 60,
    is_active: true,
  });

  const fetchZones = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(apiUrl("/api/v1/admin/delivery-zones"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      const result = await response.json();
      if (result.success) {
        setZones(result.data.data || result.data || []);
      }
    } catch (err) {
      console.error("Failed to fetch zones:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchZones();
  }, [fetchZones]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const token = localStorage.getItem("token");
    const url = editingZone
      ? apiUrl(`/api/v1/admin/delivery-zones/${editingZone.uuid}`)
      : apiUrl("/api/v1/admin/delivery-zones");
    const method = editingZone ? "PUT" : "POST";

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
          areas: formData.areas.split("\n").filter((a) => a.trim()),
          center_latitude: formData.center_latitude ?? undefined,
          center_longitude: formData.center_longitude ?? undefined,
          radius_km: formData.radius_km ?? undefined,
        }),
      });
      const result = await response.json();
      if (result.success) {
        fetchZones();
        setShowModal(false);
        resetForm();
      } else {
        alert(result.message || "Failed to save zone");
      }
    } catch (err) {
      console.error("Failed to save zone:", err);
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!confirm("Are you sure you want to delete this delivery zone?")) return;
    const token = localStorage.getItem("token");
    try {
      await fetch(apiUrl(`/api/v1/admin/delivery-zones/${uuid}`), {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      fetchZones();
    } catch (err) {
      console.error("Failed to delete zone:", err);
    }
  };

  const toggleStatus = async (zone: DeliveryZone) => {
    const token = localStorage.getItem("token");
    try {
      await fetch(apiUrl(`/api/v1/admin/delivery-zones/${zone.uuid}`), {
        method: "PUT",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          ...zone,
          is_active: !zone.is_active,
        }),
      });
      fetchZones();
    } catch (err) {
      console.error("Failed to toggle status:", err);
    }
  };

  const resetForm = () => {
    setFormData({
      name: "",
      description: "",
      areas: "",
      center_latitude: null,
      center_longitude: null,
      radius_km: null,
      base_fee: 0,
      per_km_fee: 0,
      min_order_amount: 0,
      estimated_time_min: 30,
      estimated_time_max: 60,
      is_active: true,
    });
    setEditingZone(null);
  };

  const useCurrentLocation = () => {
    if (!navigator.geolocation) {
      alert("Geolocation is not supported by your browser.");
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setFormData((f) => ({
          ...f,
          center_latitude: Math.round(pos.coords.latitude * 1000000) / 1000000,
          center_longitude: Math.round(pos.coords.longitude * 1000000) / 1000000,
          radius_km: f.radius_km ?? 10,
        }));
      },
      () => alert("Could not get your location.")
    );
  };

  const openEditModal = (zone: DeliveryZone) => {
    setEditingZone(zone);
    setFormData({
      name: zone.name,
      description: zone.description || "",
      areas: (zone.areas || []).join("\n"),
      center_latitude: zone.center_latitude ?? null,
      center_longitude: zone.center_longitude ?? null,
      radius_km: zone.radius_km ?? null,
      base_fee: zone.base_fee,
      per_km_fee: zone.per_km_fee,
      min_order_amount: zone.min_order_amount || 0,
      estimated_time_min: zone.estimated_time_min || 30,
      estimated_time_max: zone.estimated_time_max || 60,
      is_active: zone.is_active,
    });
    setShowModal(true);
  };

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-800">Delivery Zones</h2>
          <p className="text-gray-500 text-sm">Configure delivery areas and pricing</p>
        </div>
        <button
          onClick={() => {
            resetForm();
            setShowModal(true);
          }}
          className="bg-[#1F6728] text-white px-4 py-2 rounded-lg hover:bg-green-700"
        >
          + Add Zone
        </button>
      </div>

      {loading ? (
        <div className="flex justify-center py-10">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1F6728]"></div>
        </div>
      ) : zones.length === 0 ? (
        <div className="text-center py-10 bg-gray-50 rounded-lg">
          <div className="text-gray-400 text-5xl mb-4">🗺️</div>
          <h3 className="text-lg font-semibold text-gray-600 mb-2">No delivery zones configured</h3>
          <p className="text-gray-500 mb-4">Add your first delivery zone to start accepting orders</p>
          <button
            onClick={() => {
              resetForm();
              setShowModal(true);
            }}
            className="bg-[#1F6728] text-white px-4 py-2 rounded-lg hover:bg-green-700"
          >
            Add Zone
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {zones.map((zone) => (
            <div
              key={zone.uuid}
              className={`border rounded-lg overflow-hidden ${
                zone.is_active ? "border-green-200" : "border-gray-200 opacity-60"
              }`}
            >
              <div className="p-4 bg-white">
                <div className="flex justify-between items-start mb-3">
                  <h3 className="text-lg font-semibold">{zone.name}</h3>
                  <button
                    onClick={() => toggleStatus(zone)}
                    className={`px-2 py-1 rounded-full text-xs ${
                      zone.is_active
                        ? "bg-green-100 text-green-700"
                        : "bg-gray-100 text-gray-500"
                    }`}
                  >
                    {zone.is_active ? "Active" : "Inactive"}
                  </button>
                </div>
                {zone.description && (
                  <p className="text-gray-600 text-sm mb-3">{zone.description}</p>
                )}

                {(zone.center_latitude != null && zone.center_longitude != null && zone.radius_km != null) && (
                  <div className="text-xs text-gray-500 mb-3">
                    📍 {zone.center_latitude?.toFixed(4)}, {zone.center_longitude?.toFixed(4)} · {zone.radius_km} km radius
                  </div>
                )}
                <div className="space-y-2 mb-4">
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Base Fee:</span>
                    <span className="font-semibold">₦{zone.base_fee?.toLocaleString()}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Per KM:</span>
                    <span className="font-semibold">₦{zone.per_km_fee?.toLocaleString()}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Min Order:</span>
                    <span className="font-semibold">₦{zone.min_order_amount?.toLocaleString()}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-500">Est. Time:</span>
                    <span className="font-semibold">
                      {zone.estimated_time_min}-{zone.estimated_time_max} mins
                    </span>
                  </div>
                </div>

                {zone.areas && zone.areas.length > 0 && (
                  <div className="mb-4">
                    <div className="text-xs text-gray-500 mb-1">Areas covered:</div>
                    <div className="flex flex-wrap gap-1">
                      {zone.areas.slice(0, 5).map((area, idx) => (
                        <span
                          key={idx}
                          className="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs"
                        >
                          {area}
                        </span>
                      ))}
                      {zone.areas.length > 5 && (
                        <span className="text-gray-400 text-xs">
                          +{zone.areas.length - 5} more
                        </span>
                      )}
                    </div>
                  </div>
                )}

                <div className="flex gap-2 pt-3 border-t">
                  <button
                    onClick={() => openEditModal(zone)}
                    className="flex-1 border border-[#1F6728] text-[#1F6728] py-2 rounded text-sm hover:bg-green-50"
                  >
                    Edit
                  </button>
                  <button
                    onClick={() => handleDelete(zone.uuid)}
                    className="flex-1 border border-red-500 text-red-500 py-2 rounded text-sm hover:bg-red-50"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <h3 className="text-xl font-bold mb-4">
              {editingZone ? "Edit Delivery Zone" : "Add Delivery Zone"}
            </h3>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium mb-1">Zone Name</label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    placeholder="e.g., Lagos Island"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Description</label>
                  <input
                    type="text"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    placeholder="Brief description of this zone"
                  />
                </div>
                <div className="border border-gray-200 rounded-lg p-4 bg-gray-50">
                  <h4 className="text-sm font-semibold text-gray-700 mb-2">Geo mapping (for registration validation)</h4>
                  <p className="text-xs text-gray-500 mb-3">
                    Define center and radius. Users, vendors, and riders in this area can register.
                  </p>
                  <div className="grid grid-cols-3 gap-4">
                    <div>
                      <label className="block text-xs font-medium mb-1">Center Lat</label>
                      <input
                        type="number"
                        step="any"
                        value={formData.center_latitude ?? ""}
                        onChange={(e) =>
                          setFormData({
                            ...formData,
                            center_latitude: e.target.value ? parseFloat(e.target.value) : null,
                          })
                        }
                        className="w-full border rounded px-3 py-2 text-sm"
                        placeholder="e.g. 6.5244"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium mb-1">Center Lng</label>
                      <input
                        type="number"
                        step="any"
                        value={formData.center_longitude ?? ""}
                        onChange={(e) =>
                          setFormData({
                            ...formData,
                            center_longitude: e.target.value ? parseFloat(e.target.value) : null,
                          })
                        }
                        className="w-full border rounded px-3 py-2 text-sm"
                        placeholder="e.g. 3.3792"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium mb-1">Radius (km)</label>
                      <input
                        type="number"
                        step="0.1"
                        min="0.1"
                        value={formData.radius_km ?? ""}
                        onChange={(e) =>
                          setFormData({
                            ...formData,
                            radius_km: e.target.value ? parseFloat(e.target.value) : null,
                          })
                        }
                        className="w-full border rounded px-3 py-2 text-sm"
                        placeholder="e.g. 15"
                      />
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={useCurrentLocation}
                    className="mt-3 text-xs text-[#1F6728] font-medium hover:underline"
                  >
                    📍 Use my current location
                  </button>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">Base Fee (₦)</label>
                    <input
                      type="number"
                      value={formData.base_fee}
                      onChange={(e) => setFormData({ ...formData, base_fee: Number(e.target.value) })}
                      className="w-full border rounded px-3 py-2"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">Per KM Fee (₦)</label>
                    <input
                      type="number"
                      value={formData.per_km_fee}
                      onChange={(e) => setFormData({ ...formData, per_km_fee: Number(e.target.value) })}
                      className="w-full border rounded px-3 py-2"
                      required
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Minimum Order Amount (₦)</label>
                  <input
                    type="number"
                    value={formData.min_order_amount}
                    onChange={(e) => setFormData({ ...formData, min_order_amount: Number(e.target.value) })}
                    className="w-full border rounded px-3 py-2"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">Est. Min Time (mins)</label>
                    <input
                      type="number"
                      value={formData.estimated_time_min}
                      onChange={(e) => setFormData({ ...formData, estimated_time_min: Number(e.target.value) })}
                      className="w-full border rounded px-3 py-2"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">Est. Max Time (mins)</label>
                    <input
                      type="number"
                      value={formData.estimated_time_max}
                      onChange={(e) => setFormData({ ...formData, estimated_time_max: Number(e.target.value) })}
                      className="w-full border rounded px-3 py-2"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">
                    Areas Covered (one per line)
                  </label>
                  <textarea
                    value={formData.areas}
                    onChange={(e) => setFormData({ ...formData, areas: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    rows={4}
                    placeholder="Victoria Island&#10;Ikoyi&#10;Lekki Phase 1"
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
                  {editingZone ? "Update" : "Create"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default DeliveryZones;
