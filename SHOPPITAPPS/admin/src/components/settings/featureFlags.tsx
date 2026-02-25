import { useState, useEffect, useCallback } from "react";
import { apiUrl } from "../../lib/api";

interface FeatureFlag {
  id: string;
  uuid: string;
  key: string;
  name: string;
  description: string;
  is_enabled: boolean;
  metadata: Record<string, unknown>;
  created_at: string;
}

interface SystemStatus {
  maintenance_mode: boolean;
  maintenance_message: string;
}

const FeatureFlags = () => {
  const [flags, setFlags] = useState<FeatureFlag[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingFlag, setEditingFlag] = useState<FeatureFlag | null>(null);
  const [systemStatus, setSystemStatus] = useState<SystemStatus | null>(null);
  const [maintenanceMessage, setMaintenanceMessage] = useState("");
  const [formData, setFormData] = useState({
    key: "",
    name: "",
    description: "",
    is_enabled: false,
    metadata: "{}",
  });

  const fetchFlags = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(apiUrl("/api/v1/admin/feature-flags"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      const result = await response.json();
      if (result.success) {
        setFlags(result.data.data || result.data || []);
      }
    } catch (err) {
      console.error("Failed to fetch flags:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchSystemStatus = useCallback(async () => {
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(apiUrl("/api/v1/admin/system/maintenance-status"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      const result = await response.json();
      if (result.success) {
        setSystemStatus(result.data);
        setMaintenanceMessage(result.data.maintenance_message || "");
      }
    } catch (err) {
      console.error("Failed to fetch system status:", err);
    }
  }, []);

  useEffect(() => {
    fetchFlags();
    fetchSystemStatus();
  }, [fetchFlags, fetchSystemStatus]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const token = localStorage.getItem("token");
    const url = editingFlag
      ? apiUrl(`/api/v1/admin/feature-flags/${editingFlag.uuid}`)
      : apiUrl("/api/v1/admin/feature-flags");
    const method = editingFlag ? "PUT" : "POST";

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
          metadata: JSON.parse(formData.metadata || "{}"),
        }),
      });
      const result = await response.json();
      if (result.success) {
        fetchFlags();
        setShowModal(false);
        resetForm();
      } else {
        alert(result.message || "Failed to save flag");
      }
    } catch (err) {
      console.error("Failed to save flag:", err);
      alert("Invalid JSON in metadata field");
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!confirm("Are you sure you want to delete this feature flag?")) return;
    const token = localStorage.getItem("token");
    try {
      await fetch(apiUrl(`/api/v1/admin/feature-flags/${uuid}`), {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      fetchFlags();
    } catch (err) {
      console.error("Failed to delete flag:", err);
    }
  };

  const toggleFlag = async (flag: FeatureFlag) => {
    const token = localStorage.getItem("token");
    try {
      await fetch(apiUrl(`/api/v1/admin/feature-flags/${flag.uuid}/toggle`), {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      fetchFlags();
    } catch (err) {
      console.error("Failed to toggle flag:", err);
    }
  };

  const toggleMaintenance = async () => {
    const token = localStorage.getItem("token");
    const newState = !systemStatus?.maintenance_mode;
    try {
      await fetch(apiUrl("/api/v1/admin/system/maintenance"), {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          enabled: newState,
          message: maintenanceMessage,
        }),
      });
      fetchSystemStatus();
    } catch (err) {
      console.error("Failed to toggle maintenance:", err);
    }
  };

  const resetForm = () => {
    setFormData({
      key: "",
      name: "",
      description: "",
      is_enabled: false,
      metadata: "{}",
    });
    setEditingFlag(null);
  };

  const openEditModal = (flag: FeatureFlag) => {
    setEditingFlag(flag);
    setFormData({
      key: flag.key,
      name: flag.name,
      description: flag.description || "",
      is_enabled: flag.is_enabled,
      metadata: JSON.stringify(flag.metadata || {}, null, 2),
    });
    setShowModal(true);
  };

  return (
    <div className="p-6">
      {/* Maintenance Mode Section */}
      <div className="bg-white border rounded-lg p-6 mb-6">
        <div className="flex justify-between items-start">
          <div>
            <h3 className="text-lg font-semibold mb-1">Maintenance Mode</h3>
            <p className="text-gray-500 text-sm mb-4">
              Enable to show maintenance page to all users
            </p>
            <input
              type="text"
              value={maintenanceMessage}
              onChange={(e) => setMaintenanceMessage(e.target.value)}
              placeholder="Custom maintenance message (optional)"
              className="border rounded px-3 py-2 w-full max-w-md text-sm"
            />
          </div>
          <button
            onClick={toggleMaintenance}
            className={`px-4 py-2 rounded-lg font-medium transition-colors ${
              systemStatus?.maintenance_mode
                ? "bg-red-500 text-white hover:bg-red-600"
                : "bg-gray-100 text-gray-700 hover:bg-gray-200"
            }`}
          >
            {systemStatus?.maintenance_mode ? "Disable Maintenance" : "Enable Maintenance"}
          </button>
        </div>
        {systemStatus?.maintenance_mode && (
          <div className="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div className="flex items-center gap-2 text-yellow-800">
              <span className="text-lg">⚠️</span>
              <span className="font-medium">Maintenance mode is currently active</span>
            </div>
          </div>
        )}
      </div>

      {/* Feature Flags Section */}
      <div className="flex justify-between items-center mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-800">Feature Flags</h2>
          <p className="text-gray-500 text-sm">Toggle features on/off without deploying</p>
        </div>
        <button
          onClick={() => {
            resetForm();
            setShowModal(true);
          }}
          className="bg-[#1F6728] text-white px-4 py-2 rounded-lg hover:bg-green-700"
        >
          + Add Flag
        </button>
      </div>

      {loading ? (
        <div className="flex justify-center py-10">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1F6728]"></div>
        </div>
      ) : flags.length === 0 ? (
        <div className="text-center py-10 bg-gray-50 rounded-lg">
          <div className="text-gray-400 text-5xl mb-4">🚩</div>
          <h3 className="text-lg font-semibold text-gray-600 mb-2">No feature flags</h3>
          <p className="text-gray-500 mb-4">Create your first feature flag to control app features</p>
        </div>
      ) : (
        <div className="bg-white border rounded-lg overflow-hidden">
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-sm font-semibold text-gray-600">Flag</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-gray-600">Key</th>
                <th className="px-4 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
                <th className="px-4 py-3 text-right text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {flags.map((flag) => (
                <tr key={flag.uuid} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <div className="font-medium">{flag.name}</div>
                    {flag.description && (
                      <div className="text-sm text-gray-500">{flag.description}</div>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <code className="bg-gray-100 px-2 py-1 rounded text-sm">{flag.key}</code>
                  </td>
                  <td className="px-4 py-3">
                    <button
                      onClick={() => toggleFlag(flag)}
                      className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                        flag.is_enabled ? "bg-[#1F6728]" : "bg-gray-300"
                      }`}
                    >
                      <span
                        className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                          flag.is_enabled ? "translate-x-6" : "translate-x-1"
                        }`}
                      />
                    </button>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <button
                      onClick={() => openEditModal(flag)}
                      className="text-blue-600 hover:text-blue-800 mr-3"
                    >
                      Edit
                    </button>
                    <button
                      onClick={() => handleDelete(flag.uuid)}
                      className="text-red-600 hover:text-red-800"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
            <h3 className="text-xl font-bold mb-4">
              {editingFlag ? "Edit Feature Flag" : "Create Feature Flag"}
            </h3>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium mb-1">Key (unique identifier)</label>
                  <input
                    type="text"
                    value={formData.key}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        key: e.target.value.toLowerCase().replace(/\s+/g, "_"),
                      })
                    }
                    className="w-full border rounded px-3 py-2 font-mono"
                    placeholder="enable_new_checkout"
                    required
                    disabled={!!editingFlag}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Display Name</label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    placeholder="Enable New Checkout"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Description</label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    rows={2}
                    placeholder="What this flag controls"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Metadata (JSON)</label>
                  <textarea
                    value={formData.metadata}
                    onChange={(e) => setFormData({ ...formData, metadata: e.target.value })}
                    className="w-full border rounded px-3 py-2 font-mono text-sm"
                    rows={4}
                    placeholder="{}"
                  />
                </div>
                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="is_enabled"
                    checked={formData.is_enabled}
                    onChange={(e) => setFormData({ ...formData, is_enabled: e.target.checked })}
                  />
                  <label htmlFor="is_enabled" className="text-sm">Enabled</label>
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
                  {editingFlag ? "Update" : "Create"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default FeatureFlags;
