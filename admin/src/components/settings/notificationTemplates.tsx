import { useState, useEffect, useCallback } from "react";
import { apiUrl } from "../../lib/api";

interface NotificationTemplate {
  id: string;
  uuid: string;
  name: string;
  type: "push" | "email" | "sms";
  subject: string;
  body: string;
  variables: string[];
  is_active: boolean;
  created_at: string;
}

const NotificationTemplates = () => {
  const [templates, setTemplates] = useState<NotificationTemplate[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingTemplate, setEditingTemplate] = useState<NotificationTemplate | null>(null);
  const [filterType, setFilterType] = useState<string>("all");
  const [formData, setFormData] = useState({
    name: "",
    type: "push" as "push" | "email" | "sms",
    subject: "",
    body: "",
    variables: "",
    is_active: true,
  });

  const fetchTemplates = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(apiUrl("/api/v1/admin/notification-templates"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      const result = await response.json();
      if (result.success) {
        setTemplates(result.data.data || result.data || []);
      }
    } catch (err) {
      console.error("Failed to fetch templates:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchTemplates();
  }, [fetchTemplates]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const token = localStorage.getItem("token");
    const url = editingTemplate
      ? apiUrl(`/api/v1/admin/notification-templates/${editingTemplate.uuid}`)
      : apiUrl("/api/v1/admin/notification-templates");
    const method = editingTemplate ? "PUT" : "POST";

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
          variables: formData.variables
            .split(",")
            .map((v) => v.trim())
            .filter((v) => v),
        }),
      });
      const result = await response.json();
      if (result.success) {
        fetchTemplates();
        setShowModal(false);
        resetForm();
      } else {
        alert(result.message || "Failed to save template");
      }
    } catch (err) {
      console.error("Failed to save template:", err);
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!confirm("Are you sure you want to delete this template?")) return;
    const token = localStorage.getItem("token");
    try {
      await fetch(apiUrl(`/api/v1/admin/notification-templates/${uuid}`), {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      fetchTemplates();
    } catch (err) {
      console.error("Failed to delete template:", err);
    }
  };

  const toggleStatus = async (template: NotificationTemplate) => {
    const token = localStorage.getItem("token");
    try {
      await fetch(apiUrl(`/api/v1/admin/notification-templates/${template.uuid}`), {
        method: "PUT",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ ...template, is_active: !template.is_active }),
      });
      fetchTemplates();
    } catch (err) {
      console.error("Failed to toggle status:", err);
    }
  };

  const resetForm = () => {
    setFormData({
      name: "",
      type: "push",
      subject: "",
      body: "",
      variables: "",
      is_active: true,
    });
    setEditingTemplate(null);
  };

  const openEditModal = (template: NotificationTemplate) => {
    setEditingTemplate(template);
    setFormData({
      name: template.name,
      type: template.type,
      subject: template.subject || "",
      body: template.body,
      variables: (template.variables || []).join(", "),
      is_active: template.is_active,
    });
    setShowModal(true);
  };

  const getTypeIcon = (type: string) => {
    switch (type) {
      case "push":
        return "📱";
      case "email":
        return "📧";
      case "sms":
        return "💬";
      default:
        return "📝";
    }
  };

  const getTypeBadgeColor = (type: string) => {
    switch (type) {
      case "push":
        return "bg-purple-100 text-purple-700";
      case "email":
        return "bg-blue-100 text-blue-700";
      case "sms":
        return "bg-yellow-100 text-yellow-700";
      default:
        return "bg-gray-100 text-gray-700";
    }
  };

  const filteredTemplates = templates.filter(
    (t) => filterType === "all" || t.type === filterType
  );

  const commonVariables = [
    "{{user_name}}",
    "{{order_id}}",
    "{{order_total}}",
    "{{store_name}}",
    "{{delivery_time}}",
    "{{tracking_url}}",
  ];

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-800">Notification Templates</h2>
          <p className="text-gray-500 text-sm">Manage push, email, and SMS notification templates</p>
        </div>
        <button
          onClick={() => {
            resetForm();
            setShowModal(true);
          }}
          className="bg-[#1F6728] text-white px-4 py-2 rounded-lg hover:bg-green-700"
        >
          + Create Template
        </button>
      </div>

      {/* Filter Tabs */}
      <div className="flex gap-2 mb-6">
        {["all", "push", "email", "sms"].map((type) => (
          <button
            key={type}
            onClick={() => setFilterType(type)}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              filterType === type
                ? "bg-[#1F6728] text-white"
                : "bg-gray-100 text-gray-600 hover:bg-gray-200"
            }`}
          >
            {type === "all" ? "All" : type.charAt(0).toUpperCase() + type.slice(1)}
            {type !== "all" && (
              <span className="ml-1 text-xs">
                ({templates.filter((t) => t.type === type).length})
              </span>
            )}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="flex justify-center py-10">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1F6728]"></div>
        </div>
      ) : filteredTemplates.length === 0 ? (
        <div className="text-center py-10 bg-gray-50 rounded-lg">
          <div className="text-gray-400 text-5xl mb-4">📝</div>
          <h3 className="text-lg font-semibold text-gray-600 mb-2">No templates found</h3>
          <p className="text-gray-500 mb-4">Create your first notification template</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {filteredTemplates.map((template) => (
            <div
              key={template.uuid}
              className={`border rounded-lg p-4 ${
                template.is_active ? "bg-white" : "bg-gray-50 opacity-70"
              }`}
            >
              <div className="flex justify-between items-start mb-3">
                <div className="flex items-center gap-2">
                  <span className="text-xl">{getTypeIcon(template.type)}</span>
                  <div>
                    <h3 className="font-semibold">{template.name}</h3>
                    <span className={`text-xs px-2 py-0.5 rounded ${getTypeBadgeColor(template.type)}`}>
                      {template.type.toUpperCase()}
                    </span>
                  </div>
                </div>
                <button
                  onClick={() => toggleStatus(template)}
                  className={`px-2 py-1 rounded-full text-xs ${
                    template.is_active
                      ? "bg-green-100 text-green-700"
                      : "bg-gray-100 text-gray-500"
                  }`}
                >
                  {template.is_active ? "Active" : "Inactive"}
                </button>
              </div>

              {template.subject && (
                <div className="mb-2">
                  <div className="text-xs text-gray-500">Subject:</div>
                  <div className="text-sm font-medium truncate">{template.subject}</div>
                </div>
              )}

              <div className="mb-3">
                <div className="text-xs text-gray-500">Body:</div>
                <div className="text-sm text-gray-600 line-clamp-2">{template.body}</div>
              </div>

              {template.variables && template.variables.length > 0 && (
                <div className="mb-3">
                  <div className="text-xs text-gray-500 mb-1">Variables:</div>
                  <div className="flex flex-wrap gap-1">
                    {template.variables.map((v, idx) => (
                      <code
                        key={idx}
                        className="bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded text-xs"
                      >
                        {`{{${v}}}`}
                      </code>
                    ))}
                  </div>
                </div>
              )}

              <div className="flex gap-2 pt-3 border-t">
                <button
                  onClick={() => openEditModal(template)}
                  className="flex-1 border border-[#1F6728] text-[#1F6728] py-1.5 rounded text-sm hover:bg-green-50"
                >
                  Edit
                </button>
                <button
                  onClick={() => handleDelete(template.uuid)}
                  className="flex-1 border border-red-500 text-red-500 py-1.5 rounded text-sm hover:bg-red-50"
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
          <div className="bg-white rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <h3 className="text-xl font-bold mb-4">
              {editingTemplate ? "Edit Template" : "Create Template"}
            </h3>
            <form onSubmit={handleSubmit}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium mb-1">Template Name</label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    placeholder="Order Confirmation"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Type</label>
                  <select
                    value={formData.type}
                    onChange={(e) =>
                      setFormData({ ...formData, type: e.target.value as "push" | "email" | "sms" })
                    }
                    className="w-full border rounded px-3 py-2"
                  >
                    <option value="push">Push Notification</option>
                    <option value="email">Email</option>
                    <option value="sms">SMS</option>
                  </select>
                </div>
                {formData.type === "email" && (
                  <div>
                    <label className="block text-sm font-medium mb-1">Subject</label>
                    <input
                      type="text"
                      value={formData.subject}
                      onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                      className="w-full border rounded px-3 py-2"
                      placeholder="Your order #{{order_id}} has been confirmed"
                    />
                  </div>
                )}
                <div>
                  <label className="block text-sm font-medium mb-1">Body</label>
                  <textarea
                    value={formData.body}
                    onChange={(e) => setFormData({ ...formData, body: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    rows={5}
                    placeholder="Hi {{user_name}}, your order has been confirmed..."
                    required
                  />
                  <div className="mt-2">
                    <div className="text-xs text-gray-500 mb-1">Common variables:</div>
                    <div className="flex flex-wrap gap-1">
                      {commonVariables.map((v) => (
                        <button
                          key={v}
                          type="button"
                          onClick={() =>
                            setFormData({ ...formData, body: formData.body + v })
                          }
                          className="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs hover:bg-gray-200"
                        >
                          {v}
                        </button>
                      ))}
                    </div>
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">
                    Custom Variables (comma-separated)
                  </label>
                  <input
                    type="text"
                    value={formData.variables}
                    onChange={(e) => setFormData({ ...formData, variables: e.target.value })}
                    className="w-full border rounded px-3 py-2"
                    placeholder="user_name, order_id, order_total"
                  />
                  <div className="text-xs text-gray-500 mt-1">
                    List variables used in this template for documentation
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
                  {editingTemplate ? "Update" : "Create"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default NotificationTemplates;
