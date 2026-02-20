import { useState, useEffect, useRef, useCallback } from "react";
import { BiBell, BiCheckDouble, BiTrash } from "react-icons/bi";
import { apiUrl } from "../lib/api";

interface Notification {
  id: string; // Changed to string to match UUIDs usually sent by backends
  title: string;
  message: string;
  read_at: string | null;
  created_at: string;
}

const NotificationBell = () => {
  const [open, setOpen] = useState(false);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const bellRef = useRef<HTMLDivElement>(null);

  const fetchNotifications = useCallback(async () => {
    const token = localStorage.getItem("token");
    const headers = {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    };

    try {
      // 1. Fetch unread count
      const countRes = await fetch(
        apiUrl("/api/v1/admin/notifications/unread-count"),
        { headers }
      );
      const countData = await countRes.json();
      if (countData.success) setUnreadCount(countData.data.unread);

      // 2. Fetch notification list
      const listRes = await fetch(
        apiUrl("/api/v1/admin/notifications"),
        { headers }
      );
      const listData = await listRes.json();
      if (listData.success) setNotifications(listData.data.data);
    } catch (err) {
      console.error("Failed to fetch notifications:", err);
    }
  }, []);

  useEffect(() => {
    fetchNotifications();
    // Optional: Set up an interval to poll for new notifications every 60 seconds
    const interval = setInterval(fetchNotifications, 60000);
    return () => clearInterval(interval);
  }, [fetchNotifications]);

  const toggleOpen = () => setOpen((prev) => !prev);

  const markAsRead = async (id: string) => {
    const token = localStorage.getItem("token");
    try {
      const res = await fetch(
        apiUrl(`/api/v1/admin/notifications/${id}/read`),
        {
          method: "POST",
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      if (res.ok) fetchNotifications(); // Refresh list and count
    } catch (err) {
      console.error("Error marking as read:", err);
    }
  };

  const markAllRead = async () => {
    const token = localStorage.getItem("token");
    try {
      await fetch(
        apiUrl("/api/v1/admin/notifications/mark-all-read"),
        {
          method: "POST",
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      fetchNotifications();
    } catch (err) {
      console.error("Error marking all read:", err);
    }
  };

  const deleteAll = async () => {
    if (!window.confirm("Delete all notifications?")) return;
    const token = localStorage.getItem("token");
    try {
      await fetch(
        apiUrl("/api/v1/admin/notifications"),
        {
          method: "DELETE",
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      fetchNotifications();
    } catch (err) {
      console.error("Error deleting all:", err);
    }
  };

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (bellRef.current && !bellRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div className="relative" ref={bellRef}>
      <button
        onClick={toggleOpen}
        className="relative p-2 rounded-full hover:bg-gray-100 transition-colors"
      >
        <BiBell className="text-2xl text-gray-700" />
        {unreadCount > 0 && (
          <span className="absolute top-1.5 right-1.5 w-4 h-4 bg-green-500 text-white text-[10px] flex items-center justify-center rounded-full font-bold">
            {unreadCount > 9 ? "9+" : unreadCount}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 mt-2 w-80 bg-white border border-gray-100 shadow-2xl rounded-2xl overflow-hidden z-[100] animate-in fade-in slide-in-from-top-2 duration-200">
          <div className="px-4 py-3 font-bold border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <span className="text-gray-800">Notifications</span>
            <div className="flex gap-2">
              <button
                onClick={markAllRead}
                title="Mark all as read"
                className="text-gray-400 hover:text-green-600 transition-colors"
              >
                <BiCheckDouble className="text-xl" />
              </button>
              <button
                onClick={deleteAll}
                title="Clear all"
                className="text-gray-400 hover:text-red-500 transition-colors"
              >
                <BiTrash className="text-lg" />
              </button>
            </div>
          </div>

          <div className="max-h-80 overflow-y-auto">
            {notifications.map((notif) => (
              <div
                key={notif.id}
                className={`px-4 py-3 border-b border-gray-50 flex items-start gap-3 cursor-pointer transition-colors ${
                  !notif.read_at
                    ? "bg-green-50/30 hover:bg-green-50"
                    : "hover:bg-gray-50"
                }`}
                onClick={() => !notif.read_at && markAsRead(notif.id)}
              >
                <div className="flex-1">
                  <p
                    className={`text-sm ${
                      !notif.read_at
                        ? "font-bold text-gray-900"
                        : "font-medium text-gray-600"
                    }`}
                  >
                    {notif.title}
                  </p>
                  <p className="text-gray-500 text-xs mt-0.5 line-clamp-2">
                    {notif.message}
                  </p>
                  <p className="text-gray-400 text-[10px] mt-1 uppercase font-bold tracking-wider">
                    {new Date(notif.created_at).toLocaleDateString()} at{" "}
                    {new Date(notif.created_at).toLocaleTimeString([], {
                      hour: "2-digit",
                      minute: "2-digit",
                    })}
                  </p>
                </div>
                {!notif.read_at && (
                  <span className="w-2 h-2 mt-1.5 bg-green-500 rounded-full flex-shrink-0 animate-pulse"></span>
                )}
              </div>
            ))}
            {notifications.length === 0 && (
              <div className="px-4 py-10 text-center">
                <BiBell className="text-4xl text-gray-200 mx-auto mb-2" />
                <p className="text-gray-400 text-sm">No new notifications</p>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default NotificationBell;
