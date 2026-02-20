import { useState, useEffect, useCallback } from "react";
import { apiUrl } from "../lib/api";

interface UnifiedNotification {
  id: string;
  type: string;
  notifiable_type: string;
  notifiable_id: string;
  title: string;
  body: string;
  data: any;
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

const NotificationBroadcast = () => {
  const [logs, setLogs] = useState<UnifiedNotification[]>([]);
  const [loading, setLoading] = useState(false);
  const [form, setForm] = useState({
    title: "",
    body: "",
    type: "info",
    data: "",
    notifiable_type: "broadcast",
    notifiable_id: "",
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
      else setError(data.message || "Failed to fetch logs");
    } catch (err) {
      setError("Failed to fetch logs");
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
    const t = setTimeout(() => {
      searchUsers(searchQuery);
    }, 300);
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
      const isAdminBroadcast = true;
      const url = isAdminBroadcast
        ? apiUrl("/api/v1/admin/notifications/broadcast")
        : apiUrl("/api/v1/notifications/unified/send");

      const body = isAdminBroadcast
        ? {
            title: form.title,
            message: form.body,
            ...(form.audience === "specific" && selectedUsers.length > 0
              ? { user_ids: selectedUsers.map((u) => u.id) }
              : { audience: form.audience }),
          }
        : {
            ...form,
            data: form.data ? JSON.parse(form.data) : {},
          };

      const res = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.data?.count != null ? `Sent to ${data.data.count} user(s).` : "Notification sent!");
        setForm((prev) => ({ ...prev, title: "", body: "", data: "" }));
        setSelectedUsers([]);
        setSearchQuery("");
        fetchLogs();
      } else {
        setError(data.message || "Failed to send notification");
      }
    } catch (err) {
      setError("Failed to send notification");
    } finally {
      setSending(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto p-6">
      <h2 className="text-2xl font-bold mb-4">Broadcast Notification</h2>
      <form onSubmit={handleSend} className="space-y-4 bg-white p-4 rounded-xl shadow">
        <div>
          <label className="block text-xs font-bold mb-1">Send to</label>
          <select
            name="audience"
            value={form.audience}
            onChange={handleChange}
            className="w-full border px-3 py-2 rounded"
          >
            <option value="all">All users</option>
            <option value="vendor">Vendors only</option>
            <option value="user">Customers only</option>
            <option value="specific">Specific users</option>
          </select>
        </div>

        {form.audience === "specific" && (
          <div className="space-y-2">
            <label className="block text-xs font-bold mb-1">Search users to send to</label>
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search by name, email, or username..."
              className="w-full border px-3 py-2 rounded"
            />
            {searching && <div className="text-sm text-gray-500">Searching...</div>}
            {searchResults.length > 0 && (
              <ul className="border rounded max-h-48 overflow-y-auto divide-y bg-gray-50">
                {searchResults.map((user) => (
                  <li key={user.id} className="px-3 py-2 flex justify-between items-center hover:bg-gray-100">
                    <div className="text-sm">
                      <span className="font-medium">{user.name || "—"}</span>
                      <span className="text-gray-500 ml-2">{user.email}</span>
                      <span className="text-gray-400 text-xs ml-2">({user.user_type})</span>
                    </div>
                    <button
                      type="button"
                      onClick={() => addUser(user)}
                      className="text-green-600 text-sm font-medium"
                    >
                      Add
                    </button>
                  </li>
                ))}
              </ul>
            )}
            {selectedUsers.length > 0 && (
              <div className="flex flex-wrap gap-2 mt-2">
                {selectedUsers.map((user) => (
                  <span
                    key={user.id}
                    className="inline-flex items-center gap-1 bg-green-100 text-green-800 px-2 py-1 rounded text-sm"
                  >
                    {user.name || user.email}
                    <button
                      type="button"
                      onClick={() => removeUser(user.id)}
                      className="text-green-600 hover:text-green-800 font-bold"
                      aria-label="Remove"
                    >
                      ×
                    </button>
                  </span>
                ))}
              </div>
            )}
            {form.audience === "specific" && selectedUsers.length === 0 && !searchQuery && (
              <p className="text-sm text-gray-500">Search and add users above, then send.</p>
            )}
          </div>
        )}

        <div>
          <label className="block text-xs font-bold mb-1">Title</label>
          <input name="title" value={form.title} onChange={handleChange} required className="w-full border px-3 py-2 rounded" />
        </div>
        <div>
          <label className="block text-xs font-bold mb-1">Body</label>
          <textarea name="body" value={form.body} onChange={handleChange} required className="w-full border px-3 py-2 rounded" />
        </div>
        <div>
          <label className="block text-xs font-bold mb-1">Type</label>
          <select name="type" value={form.type} onChange={handleChange} className="w-full border px-3 py-2 rounded">
            <option value="info">Info</option>
            <option value="order">Order</option>
            <option value="promo">Promo</option>
            <option value="alert">Alert</option>
          </select>
        </div>
        <div>
          <label className="block text-xs font-bold mb-1">Data (JSON)</label>
          <input name="data" value={form.data} onChange={handleChange} className="w-full border px-3 py-2 rounded font-mono" placeholder='{"key":"value"}' />
        </div>
        <button
          type="submit"
          disabled={sending || (form.audience === "specific" && selectedUsers.length === 0)}
          className="bg-green-600 text-white px-6 py-2 rounded font-bold disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {sending ? "Sending..." : "Send Notification"}
        </button>
        {error && <div className="text-red-500 text-sm mt-2">{error}</div>}
        {success && <div className="text-green-600 text-sm mt-2">{success}</div>}
      </form>
      <h3 className="text-xl font-bold mt-8 mb-2">Notification Logs</h3>
      {loading ? (
        <div>Loading...</div>
      ) : logs.length === 0 ? (
        <div className="text-gray-400">No notifications sent yet.</div>
      ) : (
        <div className="bg-white rounded-xl shadow divide-y mt-2">
          {logs.map((n) => (
            <div key={n.id} className="p-4 flex flex-col gap-1">
              <div className="flex justify-between items-center">
                <span className="font-bold">{n.title}</span>
                <span className="text-xs text-gray-400">{new Date(n.created_at).toLocaleString()}</span>
              </div>
              <span className="text-sm text-gray-700">{n.body}</span>
              <span className="text-xs text-gray-500">Type: {n.type}</span>
              <span className="text-xs text-gray-500">Data: {JSON.stringify(n.data)}</span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default NotificationBroadcast;
