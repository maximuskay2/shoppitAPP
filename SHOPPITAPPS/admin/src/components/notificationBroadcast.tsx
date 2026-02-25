import { useState, useEffect, useCallback } from "react";
import { BiSend, BiHistory, BiSearch, BiUserPlus, BiX, BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../lib/api";

interface UnifiedNotification {
  id: string;
  type: string;
  notifiable_type: string;
  notifiable_id: string;
  title: string;
  body: string;
  data: unknown;
  read_at: string | null;
  created_at: string;
  updated_at: string;
}

interface SearchUser {
  id: string;
  name: string | null;
  email: string;
  role: string;
  user_type: string;
}

const TYPE_LABELS: Record<string, string> = {
  info: "Info",
  order: "Order",
  promo: "Promo",
  alert: "Alert",
  broadcast: "Broadcast",
};

const USER_TYPE_LABELS: Record<string, string> = {
  user: "Customer",
  vendor: "Vendor",
  driver: "Driver",
};

const NotificationBroadcast = () => {
  const [logs, setLogs] = useState<UnifiedNotification[]>([]);
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState<"send" | "history">("send");
  const [form, setForm] = useState({
    title: "",
    body: "",
    type: "info",
    audience: "all" as "all" | "vendor" | "user" | "specific",
  });
  const [selectedUsers, setSelectedUsers] = useState<SearchUser[]>([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [searchResults, setSearchResults] = useState<SearchUser[]>([]);
  const [searching, setSearching] = useState(false);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    fetchLogs();
  }, []);

  const fetchLogs = async () => {
    setLoading(true);
    setError(null);
    try {
      const token = localStorage.getItem("token");
      const res = await fetch(apiUrl("/api/v1/admin/notifications"), {
        headers: { Authorization: `Bearer ${token}` },
      });
      const data = await res.json();
      if (data.success) setLogs(data.data?.data || []);
      else setError(data.message || "Failed to fetch history");
    } catch {
      setError("Failed to fetch history");
    } finally {
      setLoading(false);
    }
  };

  const searchUsers = useCallback(async (q: string) => {
    if (!q.trim()) {
      setSearchResults([]);
      return;
    }
    setSearching(true);
    try {
      const token = localStorage.getItem("token");
      const res = await fetch(
        apiUrl(`/api/v1/admin/user-management/search?q=${encodeURIComponent(q.trim())}&limit=20`),
        { headers: { Authorization: `Bearer ${token}` } }
      );
      const data = await res.json();
      if (data.success && Array.isArray(data.data)) {
        setSearchResults(data.data);
      } else {
        setSearchResults([]);
      }
    } catch {
      setSearchResults([]);
    } finally {
      setSearching(false);
    }
  }, []);

  useEffect(() => {
    const t = setTimeout(() => searchUsers(searchQuery), 300);
    return () => clearTimeout(t);
  }, [searchQuery, searchUsers]);

  const addUser = (user: SearchUser) => {
    if (selectedUsers.some((u) => u.id === user.id)) return;
    setSelectedUsers((prev) => [...prev, user]);
    setSearchQuery("");
    setSearchResults([]);
  };

  const removeUser = (id: string) => {
    setSelectedUsers((prev) => prev.filter((u) => u.id !== id));
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    setSending(true);
    setError(null);
    setSuccess(null);
    try {
      const token = localStorage.getItem("token");
      const body = {
        title: form.title,
        message: form.body,
        ...(form.audience === "specific" && selectedUsers.length > 0
          ? { user_ids: selectedUsers.map((u) => u.id) }
          : { audience: form.audience }),
      };

      const res = await fetch(apiUrl("/api/v1/admin/notifications/broadcast"), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(
          data.data?.count != null ? `Sent to ${data.data.count} recipient(s)` : "Notification sent successfully"
        );
        setForm((prev) => ({ ...prev, title: "", body: "" }));
        setSelectedUsers([]);
        setSearchQuery("");
        fetchLogs();
      } else {
        setError(data.message || "Failed to send notification");
      }
    } catch {
      setError("Failed to send notification");
    } finally {
      setSending(false);
    }
  };

  const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - d.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    if (diffMins < 1) return "Just now";
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return d.toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" });
  };

  return (
    <div className="p-6 max-w-3xl mx-auto">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-800">Notifications</h1>
        <p className="text-gray-500 text-sm mt-1">Send announcements and messages to your users</p>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 p-1 bg-gray-100 rounded-xl mb-6">
        <button
          type="button"
          onClick={() => setActiveTab("send")}
          className={`flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition ${
            activeTab === "send"
              ? "bg-white text-[#1F6728] shadow-sm"
              : "text-gray-600 hover:text-gray-800"
          }`}
        >
          <BiSend className="text-lg" />
          Send Notification
        </button>
        <button
          type="button"
          onClick={() => setActiveTab("history")}
          className={`flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition ${
            activeTab === "history"
              ? "bg-white text-[#1F6728] shadow-sm"
              : "text-gray-600 hover:text-gray-800"
          }`}
        >
          <BiHistory className="text-lg" />
          History
        </button>
      </div>

      {/* Send Tab */}
      {activeTab === "send" && (
        <form onSubmit={handleSend} className="space-y-6">
          <div className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div className="p-6 space-y-5">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">Recipients</label>
                <select
                  name="audience"
                  value={form.audience}
                  onChange={handleChange}
                  className="w-full border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:ring-2 focus:ring-[#1F6728]/30 focus:border-[#1F6728] outline-none transition"
                >
                  <option value="all">All users</option>
                  <option value="vendor">Vendors only</option>
                  <option value="user">Customers only</option>
                  <option value="specific">Specific users</option>
                </select>
              </div>

              {form.audience === "specific" && (
                <div className="space-y-3">
                  <label className="block text-sm font-semibold text-gray-700">Add recipients</label>
                  <div className="relative">
                    <BiSearch className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg" />
                    <input
                      type="text"
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      placeholder="Search by name or email..."
                      className="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#1F6728]/30 focus:border-[#1F6728] outline-none"
                    />
                  </div>
                  {searching && (
                    <div className="flex items-center gap-2 text-sm text-gray-500">
                      <BiLoaderAlt className="animate-spin" />
                      Searching...
                    </div>
                  )}
                  {searchResults.length > 0 && (
                    <ul className="border border-gray-200 rounded-xl overflow-hidden divide-y divide-gray-100 max-h-48 overflow-y-auto">
                      {searchResults.map((user) => (
                        <li
                          key={user.id}
                          className="px-4 py-3 flex justify-between items-center hover:bg-gray-50 transition"
                        >
                          <div>
                            <span className="font-medium text-gray-800">{user.name || "No name"}</span>
                            <span className="text-gray-500 text-sm ml-2">{user.email}</span>
                            <span className="ml-2 text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                              {USER_TYPE_LABELS[user.user_type] || user.user_type}
                            </span>
                          </div>
                          <button
                            type="button"
                            onClick={() => addUser(user)}
                            className="flex items-center gap-1 text-[#1F6728] font-semibold text-sm hover:underline"
                          >
                            <BiUserPlus />
                            Add
                          </button>
                        </li>
                      ))}
                    </ul>
                  )}
                  {selectedUsers.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                      {selectedUsers.map((user) => (
                        <span
                          key={user.id}
                          className="inline-flex items-center gap-1.5 bg-[#1F6728]/10 text-[#1F6728] px-3 py-1.5 rounded-lg text-sm font-medium"
                        >
                          {user.name || user.email}
                          <button
                            type="button"
                            onClick={() => removeUser(user.id)}
                            className="hover:bg-[#1F6728]/20 rounded p-0.5 transition"
                            aria-label="Remove"
                          >
                            <BiX className="text-base" />
                          </button>
                        </span>
                      ))}
                    </div>
                  )}
                  {form.audience === "specific" && selectedUsers.length === 0 && !searchQuery && (
                    <p className="text-sm text-gray-500">Search and add recipients above</p>
                  )}
                </div>
              )}

              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">Title</label>
                <input
                  name="title"
                  value={form.title}
                  onChange={handleChange}
                  required
                  placeholder="e.g. New feature available"
                  className="w-full border border-gray-200 rounded-xl px-4 py-3 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-[#1F6728]/30 focus:border-[#1F6728] outline-none transition"
                />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">Message</label>
                <textarea
                  name="body"
                  value={form.body}
                  onChange={handleChange}
                  required
                  rows={4}
                  placeholder="Write your notification message..."
                  className="w-full border border-gray-200 rounded-xl px-4 py-3 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-[#1F6728]/30 focus:border-[#1F6728] outline-none transition resize-none"
                />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                <select
                  name="type"
                  value={form.type}
                  onChange={handleChange}
                  className="w-full border border-gray-200 rounded-xl px-4 py-3 text-gray-800 focus:ring-2 focus:ring-[#1F6728]/30 focus:border-[#1F6728] outline-none transition"
                >
                  <option value="info">General Info</option>
                  <option value="order">Order Update</option>
                  <option value="promo">Promotion</option>
                  <option value="alert">Alert</option>
                </select>
              </div>
            </div>

            <div className="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between gap-4">
              {error && <p className="text-red-600 text-sm font-medium">{error}</p>}
              {success && <p className="text-[#1F6728] text-sm font-medium">{success}</p>}
              <div className="ml-auto" />
              <button
                type="submit"
                disabled={sending || (form.audience === "specific" && selectedUsers.length === 0)}
                className="bg-[#1F6728] text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2"
              >
                {sending ? (
                  <>
                    <BiLoaderAlt className="animate-spin text-lg" />
                    Sending...
                  </>
                ) : (
                  <>
                    <BiSend />
                    Send
                  </>
                )}
              </button>
            </div>
          </div>
        </form>
      )}

      {/* History Tab */}
      {activeTab === "history" && (
        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
          {loading ? (
            <div className="flex items-center justify-center gap-2 py-16 text-gray-500">
              <BiLoaderAlt className="animate-spin text-2xl" />
              <span>Loading history...</span>
            </div>
          ) : logs.length === 0 ? (
            <div className="py-16 text-center">
              <BiHistory className="text-5xl text-gray-200 mx-auto mb-4" />
              <p className="text-gray-500 font-medium">No notifications sent yet</p>
              <p className="text-gray-400 text-sm mt-1">Send your first notification from the Send tab</p>
            </div>
          ) : (
            <div className="divide-y divide-gray-100">
              {logs.map((n) => (
                <div key={n.id} className="p-5 hover:bg-gray-50/50 transition">
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex-1 min-w-0">
                      <h3 className="font-semibold text-gray-800 truncate">{n.title}</h3>
                      <p className="text-gray-600 text-sm mt-1 line-clamp-2">{n.body}</p>
                      <div className="flex items-center gap-2 mt-3">
                        <span
                          className={`inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold ${
                            n.type === "alert"
                              ? "bg-amber-100 text-amber-800"
                              : n.type === "promo"
                              ? "bg-purple-100 text-purple-800"
                              : n.type === "order"
                              ? "bg-blue-100 text-blue-800"
                              : "bg-gray-100 text-gray-700"
                          }`}
                        >
                          {TYPE_LABELS[n.type] || n.type}
                        </span>
                        <span className="text-gray-400 text-xs">{formatDate(n.created_at)}</span>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default NotificationBroadcast;
