import { useCallback, useEffect, useState } from "react";
import { BiRefresh } from "react-icons/bi";
import { apiUrl } from "../lib/api";

type AlertSummary = {
  stuck_orders_count: number;
  driver_location_stale_count: number;
  notification_failure_rate: number;
  notification_failed: number;
  notification_total: number;
};

type AlertStatus = {
  notifications: {
    last_run: string | null;
    last_alert_at: string | null;
    last_total: number;
    last_failed: number;
    last_rate: number;
  };
  stuck_orders: {
    last_run: string | null;
    last_alert_at: string | null;
    last_count: number;
    last_oldest_created_at: string | null;
  };
  driver_locations: {
    last_run: string | null;
    last_alert_at: string | null;
    last_count: number;
    last_oldest_recorded_at: string | null;
  };
};

type AlertHistoryItem = {
  type: string;
  last_run: string | null;
  last_alert_at: string | null;
  last_total?: number;
  last_failed?: number;
  last_rate?: number;
  last_count?: number;
  last_oldest_created_at?: string | null;
  last_oldest_recorded_at?: string | null;
};

type HealthResponse = {
  db: boolean;
  cache: boolean;
  queue_connection: string;
  time: string;
};

const HealthMonitor = () => {
  const [summary, setSummary] = useState<AlertSummary | null>(null);
  const [status, setStatus] = useState<AlertStatus | null>(null);
  const [history, setHistory] = useState<AlertHistoryItem[]>([]);
  const [health, setHealth] = useState<HealthResponse | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchAll = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");

    try {
      const [summaryRes, statusRes, historyRes, healthRes] = await Promise.all([
        fetch(apiUrl("/api/v1/admin/alerts/summary"), {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }),
        fetch(apiUrl("/api/v1/admin/alerts/status"), {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }),
        fetch(apiUrl("/api/v1/admin/alerts/history"), {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }),
        fetch(apiUrl("/api/v1/admin/health"), {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }),
      ]);

      const summaryJson = await summaryRes.json();
      const statusJson = await statusRes.json();
      const historyJson = await historyRes.json();
      const healthJson = await healthRes.json();

      if (summaryJson.success) setSummary(summaryJson.data);
      if (statusJson.success) setStatus(statusJson.data);
      if (historyJson.success) setHistory(historyJson.data?.data || []);
      if (healthJson.success) setHealth(healthJson.data);
    } catch (err) {
      console.error("Health monitor fetch error:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchAll();
  }, [fetchAll]);

  return (
    <div>
      <div className="flex justify-between items-start mb-4">
        <div>
          <p className="text-2xl font-bold text-gray-800">Health Monitor</p>
          <p className="text-gray-500">
            Live alert status, history, and system checks
          </p>
        </div>
        <button
          onClick={fetchAll}
          disabled={loading}
          className="bg-[#1F6728] px-4 py-2 text-white rounded-full flex items-center gap-2 disabled:opacity-50"
        >
          <BiRefresh className={loading ? "animate-spin" : ""} />
          {loading ? "Refreshing..." : "Refresh"}
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="border border-gray-200 rounded-xl p-5 bg-white">
          <p className="text-xs text-gray-500 uppercase font-semibold">
            Stuck orders
          </p>
          <p className="text-2xl font-bold text-red-600">
            {summary?.stuck_orders_count ?? 0}
          </p>
        </div>
        <div className="border border-gray-200 rounded-xl p-5 bg-white">
          <p className="text-xs text-gray-500 uppercase font-semibold">
            Stale driver locations
          </p>
          <p className="text-2xl font-bold text-amber-600">
            {summary?.driver_location_stale_count ?? 0}
          </p>
        </div>
        <div className="border border-gray-200 rounded-xl p-5 bg-white">
          <p className="text-xs text-gray-500 uppercase font-semibold">
            Notification failure rate
          </p>
          <p className="text-2xl font-bold text-orange-600">
            {summary?.notification_failure_rate ?? 0}%
          </p>
        </div>
        <div className="border border-gray-200 rounded-xl p-5 bg-white">
          <p className="text-xs text-gray-500 uppercase font-semibold">
            Notifications failed
          </p>
          <p className="text-2xl font-bold text-purple-600">
            {summary?.notification_failed ?? 0} /{" "}
            {summary?.notification_total ?? 0}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div className="border border-gray-200 rounded-xl p-5 bg-white">
          <p className="text-xs text-gray-500 uppercase font-semibold mb-2">
            System health
          </p>
          <div className="space-y-2 text-sm text-gray-700">
            <p>
              Database:{" "}
              <span
                className={`font-semibold ${
                  health?.db ? "text-green-600" : "text-red-600"
                }`}
              >
                {health?.db ? "OK" : "Down"}
              </span>
            </p>
            <p>
              Cache:{" "}
              <span
                className={`font-semibold ${
                  health?.cache ? "text-green-600" : "text-red-600"
                }`}
              >
                {health?.cache ? "OK" : "Down"}
              </span>
            </p>
            <p>Queue: {health?.queue_connection || "-"}</p>
            <p>Checked at: {health?.time || "-"}</p>
          </div>
        </div>

        <div className="border border-gray-200 rounded-xl p-5 bg-white lg:col-span-2">
          <p className="text-xs text-gray-500 uppercase font-semibold mb-2">
            Alert status
          </p>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            {status && (
              <>
                <div className="border border-gray-100 rounded-lg p-3">
                  <p className="font-semibold text-gray-700">Notifications</p>
                  <p className="text-xs text-gray-500">
                    Last run: {status.notifications.last_run || "-"}
                  </p>
                  <p className="text-xs text-gray-500">
                    Last alert: {status.notifications.last_alert_at || "-"}
                  </p>
                  <p className="text-xs text-gray-500">
                    Fail rate: {status.notifications.last_rate}%
                  </p>
                </div>
                <div className="border border-gray-100 rounded-lg p-3">
                  <p className="font-semibold text-gray-700">Stuck orders</p>
                  <p className="text-xs text-gray-500">
                    Last run: {status.stuck_orders.last_run || "-"}
                  </p>
                  <p className="text-xs text-gray-500">
                    Last alert: {status.stuck_orders.last_alert_at || "-"}
                  </p>
                  <p className="text-xs text-gray-500">
                    Count: {status.stuck_orders.last_count}
                  </p>
                </div>
                <div className="border border-gray-100 rounded-lg p-3">
                  <p className="font-semibold text-gray-700">
                    Driver locations
                  </p>
                  <p className="text-xs text-gray-500">
                    Last run: {status.driver_locations.last_run || "-"}
                  </p>
                  <p className="text-xs text-gray-500">
                    Last alert: {status.driver_locations.last_alert_at || "-"}
                  </p>
                  <p className="text-xs text-gray-500">
                    Count: {status.driver_locations.last_count}
                  </p>
                </div>
              </>
            )}
          </div>
        </div>
      </div>

      <div className="border border-gray-200 rounded-xl p-5 bg-white">
        <p className="text-xs text-gray-500 uppercase font-semibold mb-3">
          Alert history snapshot
        </p>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
          {history.map((item) => (
            <div key={item.type} className="border border-gray-100 rounded-lg p-3">
              <p className="font-semibold text-gray-700 capitalize">
                {item.type.replace("_", " ")}
              </p>
              <p className="text-xs text-gray-500">
                Last run: {item.last_run || "-"}
              </p>
              <p className="text-xs text-gray-500">
                Last alert: {item.last_alert_at || "-"}
              </p>
              {item.last_count != null && (
                <p className="text-xs text-gray-500">
                  Count: {item.last_count}
                </p>
              )}
              {item.last_rate != null && (
                <p className="text-xs text-gray-500">
                  Failure rate: {item.last_rate}%
                </p>
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default HealthMonitor;
