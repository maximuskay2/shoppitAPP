import { useEffect, useState, useCallback } from "react";
import { BiLoaderAlt, BiRefresh, BiTrendingUp, BiUser, BiDollar, BiMessageRounded } from "react-icons/bi";
import { LuTruck } from "react-icons/lu";
import { apiUrl } from "../../lib/api";

type Driver = {
  id: string;
  name: string;
  email: string;
  phone: string;
  status: string;
  driver?: {
    is_verified: boolean;
    is_online: boolean;
    vehicle_type?: string;
  };
};

type DriverStats = {
  id: string;
  total_deliveries: number;
  total_earnings: {
    amount: number;
    currency: string;
  };
  average_rating?: number;
  acceptance_rate?: number;
  cancellation_rate?: number;
  average_delivery_time?: string;
};

type SystemMetrics = {
  total_drivers: number;
  online_drivers: number;
  verified_drivers: number;
  total_deliveries: number;
  average_rating: number;
  total_earnings: {
    amount: number;
    currency: string;
  };
  average_delivery_time: string;
  system_efficiency: number;
};

const DriverAnalytics = () => {
  const [drivers, setDrivers] = useState<Driver[]>([]);
  const [driverStats, setDriverStats] = useState<Record<string, DriverStats>>({});
  const [systemMetrics, setSystemMetrics] = useState<SystemMetrics | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedDriverId, setSelectedDriverId] = useState<string | null>(null);
  const [sortBy, setSortBy] = useState<"earnings" | "deliveries" | "rating">("earnings");
  const [messagingDriver, setMessagingDriver] = useState<Driver | null>(null);
  const [conversation, setConversation] = useState<{ id: string; other: { name: string } } | null>(null);
  const [messages, setMessages] = useState<Array<{ id: string; content: string; sender_name: string; sender_type?: string; is_mine?: boolean; created_at: string }>>([]);
  const [newMessage, setNewMessage] = useState("");
  const [sendingMessage, setSendingMessage] = useState(false);
  const [loadingMessages, setLoadingMessages] = useState(false);

  const fetchAnalytics = async () => {
    const token = localStorage.getItem("token");
    setLoading(true);

    try {
      // Fetch drivers
      const driversResponse = await fetch(apiUrl("/api/v1/admin/drivers"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      const driversResult = await driversResponse.json();
      if (driversResult.success) {
        const driversList = driversResult.data?.data || [];
        setDrivers(driversList);

        // Fetch stats for each driver
        const statsMap: Record<string, DriverStats> = {};
        for (const driver of driversList) {
          try {
            const statsResponse = await fetch(
              apiUrl(`/api/v1/admin/drivers/${driver.id}/stats`),
              {
                headers: {
                  Authorization: `Bearer ${token}`,
                  Accept: "application/json",
                },
              }
            );
            const statsResult = await statsResponse.json();
            if (statsResult.success) {
              statsMap[driver.id] = statsResult.data;
            }
          } catch (err) {
            console.error(`Failed to fetch stats for driver ${driver.id}:`, err);
          }
        }
        setDriverStats(statsMap);

        const totalDrivers = driversList.length;
        const onlineDrivers = driversList.filter((d: Driver) => d.driver?.is_online).length;
        const verifiedDrivers = driversList.filter((d: Driver) => d.driver?.is_verified).length;
        const statsValues = Object.values(statsMap);
        const totalDeliveries = statsValues.reduce(
          (sum, item) => sum + (item.total_deliveries || 0),
          0
        );
        const totalEarningsAmount = statsValues.reduce(
          (sum, item) => sum + (item.total_earnings?.amount || 0),
          0
        );
        const ratings = statsValues
          .map((item) => item.average_rating)
          .filter((value): value is number => typeof value === "number");
        const averageRating =
          ratings.length > 0
            ? ratings.reduce((sum, value) => sum + value, 0) / ratings.length
            : 0;
        const currency = statsValues.find((item) => item.total_earnings?.currency)
          ?.total_earnings.currency || "NGN";

        setSystemMetrics((prev) => ({
          total_drivers: totalDrivers,
          online_drivers: onlineDrivers,
          verified_drivers: verifiedDrivers,
          total_deliveries: prev?.total_deliveries ?? totalDeliveries,
          average_rating: prev?.average_rating ?? averageRating,
          total_earnings: {
            amount: prev?.total_earnings?.amount ?? totalEarningsAmount,
            currency: prev?.total_earnings?.currency ?? currency,
          },
          average_delivery_time: prev?.average_delivery_time ?? "N/A",
          system_efficiency: prev?.system_efficiency ?? 0,
        }));
      }

      // Fetch system-wide performance metrics
      try {
        const performanceResponse = await fetch(apiUrl("/api/v1/admin/analytics/performance"), {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        });
        const performanceResult = await performanceResponse.json();
        if (performanceResult.success) {
          const summary = performanceResult.data?.summary ?? {};
          setSystemMetrics((prev) =>
            prev
              ? {
                  ...prev,
                  total_deliveries:
                    summary.total_deliveries ?? prev.total_deliveries,
                  average_delivery_time:
                    summary.avg_end_to_end_minutes != null
                      ? `${Math.round(summary.avg_end_to_end_minutes)} min`
                      : prev.average_delivery_time,
                }
              : null
          );
        }
      } catch (err) {
        console.error("Failed to fetch system metrics:", err);
      }
    } catch (err) {
      console.error("Failed to fetch analytics:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAnalytics();
    // Refresh every 60 seconds
    const interval = setInterval(fetchAnalytics, 60000);
    return () => clearInterval(interval);
  }, []);

  const getSortedDrivers = () => {
    return [...drivers].sort((a, b) => {
      const statsA = driverStats[a.id];
      const statsB = driverStats[b.id];

      if (!statsA || !statsB) return 0;

      switch (sortBy) {
        case "earnings":
          return (
            (statsB.total_earnings?.amount || 0) -
            (statsA.total_earnings?.amount || 0)
          );
        case "deliveries":
          return statsB.total_deliveries - statsA.total_deliveries;
        case "rating":
          return (statsB.average_rating || 0) - (statsA.average_rating || 0);
        default:
          return 0;
      }
    });
  };

  const selectedStats = selectedDriverId ? driverStats[selectedDriverId] : null;
  const selectedDriver = selectedDriverId
    ? drivers.find((d) => d.id === selectedDriverId)
    : null;

  const openMessaging = useCallback(async (driver: Driver) => {
    setMessagingDriver(driver);
    setConversation(null);
    setMessages([]);
    setNewMessage("");
    setLoadingMessages(true);
    const token = localStorage.getItem("token");
    if (!token) return;
    try {
      const res = await fetch(apiUrl("/api/v1/admin/messaging/conversations"), {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          type: "admin_driver",
          other_id: driver.id,
        }),
      });
      const data = await res.json();
      if (data.success && data.data) {
        setConversation(data.data);
        const msgRes = await fetch(
          apiUrl(`/api/v1/admin/messaging/conversations/${data.data.id}/messages`),
          { headers: { Authorization: `Bearer ${token}` } }
        );
        const msgData = await msgRes.json();
        if (msgData.success && msgData.data?.data) {
          setMessages(msgData.data.data);
        }
      }
    } catch (err) {
      console.error("Failed to open messaging:", err);
    } finally {
      setLoadingMessages(false);
    }
  }, []);

  const sendMessage = async () => {
    if (!conversation || !newMessage.trim() || sendingMessage) return;
    const token = localStorage.getItem("token");
    if (!token) return;
    setSendingMessage(true);
    try {
      const res = await fetch(
        apiUrl(`/api/v1/admin/messaging/conversations/${conversation.id}/messages`),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({ content: newMessage.trim() }),
        }
      );
      const data = await res.json();
      if (data.success && data.data) {
        setMessages((prev) => [...prev, { ...data.data, is_mine: true }]);
        setNewMessage("");
      }
    } catch (err) {
      console.error("Failed to send message:", err);
    } finally {
      setSendingMessage(false);
    }
  };

  const closeMessaging = () => {
    setMessagingDriver(null);
    setConversation(null);
    setMessages([]);
    setNewMessage("");
  };

  return (
    <div className="space-y-4">
      {/* System Metrics Overview */}
      <div className="bg-white border border-gray-200 rounded-lg p-4">
        <div className="flex justify-between items-center mb-4">
          <h3 className="font-semibold flex items-center gap-2">
            <BiTrendingUp className="text-[#1F6728]" />
            System Performance Overview
          </h3>
          <button
            onClick={fetchAnalytics}
            disabled={loading}
            className="flex items-center gap-2 px-3 py-2 bg-[#1F6728] text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            <BiRefresh className={loading ? "animate-spin" : ""} />
            Refresh
          </button>
        </div>

        {loading && !systemMetrics ? (
          <div className="flex items-center justify-center gap-2 text-gray-500 h-32">
            <BiLoaderAlt className="animate-spin" />
            <span>Loading analytics...</span>
          </div>
        ) : systemMetrics ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Drivers Card */}
            <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
              <div className="flex items-center justify-between mb-2">
                <BiUser className="text-2xl text-blue-600" />
                <span className="text-xs bg-blue-200 text-blue-700 px-2 py-1 rounded">
                  Total
                </span>
              </div>
              <p className="text-3xl font-bold text-blue-600 mb-1">
                {systemMetrics.total_drivers}
              </p>
              <p className="text-xs text-gray-600">
                <span className="text-green-600 font-semibold">
                  {systemMetrics.online_drivers}
                </span>
                {" "}online • {" "}
                <span className="text-emerald-600 font-semibold">
                  {systemMetrics.verified_drivers}
                </span>
                {" "}verified
              </p>
            </div>

            {/* Deliveries Card */}
            <div className="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
              <div className="flex items-center justify-between mb-2">
                <LuTruck className="text-2xl text-green-600" />
                <span className="text-xs bg-green-200 text-green-700 px-2 py-1 rounded">
                  Total
                </span>
              </div>
              <p className="text-3xl font-bold text-green-600 mb-1">
                {(systemMetrics.total_deliveries ?? 0).toLocaleString()}
              </p>
              <p className="text-xs text-gray-600">
                Completed deliveries
              </p>
            </div>

            {/* Earnings Card */}
            <div className="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 border border-yellow-200">
              <div className="flex items-center justify-between mb-2">
                <BiDollar className="text-2xl text-yellow-600" />
                <span className="text-xs bg-yellow-200 text-yellow-700 px-2 py-1 rounded">
                  Total
                </span>
              </div>
              <p className="text-3xl font-bold text-yellow-600 mb-1">
                ₦{((systemMetrics.total_earnings?.amount ?? 0) / 1000).toFixed(1)}K
              </p>
              <p className="text-xs text-gray-600">
                Total driver earnings
              </p>
            </div>

            {/* Efficiency Card */}
            <div className="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
              <div className="flex items-center justify-between mb-2">
                <BiTrendingUp className="text-2xl text-purple-600" />
                <span className="text-xs bg-purple-200 text-purple-700 px-2 py-1 rounded">
                  Score
                </span>
              </div>
              <p className="text-3xl font-bold text-purple-600 mb-1">
                {(systemMetrics.system_efficiency || 0).toFixed(0)}%
              </p>
              <p className="text-xs text-gray-600">
                System efficiency
              </p>
            </div>
          </div>
        ) : null}
      </div>

      {/* Driver Performance Rankings */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {/* Drivers List */}
        <div className="border border-gray-200 rounded-lg bg-white">
          <div className="p-4 border-b bg-gray-50">
            <div className="flex justify-between items-center mb-3">
              <h3 className="font-semibold">Top Drivers</h3>
            </div>
            <select
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value as any)}
              className="w-full text-xs border border-gray-300 rounded px-2 py-1"
            >
              <option value="earnings">By Earnings</option>
              <option value="deliveries">By Deliveries</option>
              <option value="rating">By Rating</option>
            </select>
          </div>

          <div className="overflow-y-auto max-h-96">
            <div className="divide-y">
              {loading ? (
                <div className="p-4 flex items-center justify-center text-gray-500">
                  <BiLoaderAlt className="animate-spin" />
                </div>
              ) : (
                getSortedDrivers().slice(0, 10).map((driver) => {
                  const stats = driverStats[driver.id];
                  return (
                    <div
                      key={driver.id}
                      className={`p-3 hover:bg-gray-50 cursor-pointer transition ${
                        selectedDriverId === driver.id ? "bg-blue-50" : ""
                      }`}
                      onClick={() => setSelectedDriverId(driver.id)}
                    >
                      <div className="flex items-start justify-between mb-1">
                        <p className="font-medium text-sm text-gray-900 truncate">
                          {driver.name}
                        </p>
                        <div className="flex items-center gap-1">
                          <button
                            type="button"
                            onClick={(e) => {
                              e.stopPropagation();
                              openMessaging(driver);
                            }}
                            className="p-1.5 rounded hover:bg-green-100 text-green-600 hover:text-green-700"
                            title="Message driver"
                          >
                            <BiMessageRounded className="text-lg" />
                          </button>
                          <span
                            className={`text-xs font-semibold px-2 py-1 rounded ${
                              driver.driver?.is_online
                                ? "bg-green-100 text-green-700"
                                : "bg-gray-100 text-gray-700"
                            }`}
                          >
                            {driver.driver?.is_online ? "Online" : "Offline"}
                          </span>
                        </div>
                      </div>

                      {stats ? (
                        <>
                          <p className="text-xs text-gray-500 mb-2">
                            {stats.total_deliveries} deliveries
                          </p>
                          <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold text-green-600">
                              ₦{(stats.total_earnings?.amount || 0).toLocaleString()}
                            </span>
                            {stats.average_rating && (
                              <span className="text-xs text-yellow-600">
                                ⭐ {stats.average_rating.toFixed(1)}
                              </span>
                            )}
                          </div>
                        </>
                      ) : (
                        <p className="text-xs text-gray-400">No stats available</p>
                      )}
                    </div>
                  );
                })
              )}
            </div>
          </div>
        </div>

        {/* Selected Driver Details */}
        <div className="lg:col-span-2 border border-gray-200 rounded-lg bg-white">
          {selectedDriver && selectedStats ? (
            <>
              <div className="p-4 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
                <p className="font-semibold text-gray-900">{selectedDriver.name}</p>
                <p className="text-xs text-gray-600">{selectedDriver.email}</p>
                <p className="text-xs text-gray-600">{selectedDriver.phone}</p>
              </div>

              <div className="p-4 space-y-4">
                {/* Key Metrics Grid */}
                <div className="grid grid-cols-2 gap-3">
                  <div className="bg-blue-50 rounded p-3 border border-blue-200">
                    <p className="text-xs text-gray-600 mb-1">Total Deliveries</p>
                    <p className="text-2xl font-bold text-blue-600">
                      {selectedStats.total_deliveries}
                    </p>
                  </div>

                  <div className="bg-green-50 rounded p-3 border border-green-200">
                    <p className="text-xs text-gray-600 mb-1">Total Earnings</p>
                    <p className="text-2xl font-bold text-green-600">
                      ₦{(selectedStats.total_earnings?.amount || 0).toLocaleString()}
                    </p>
                  </div>

                  {selectedStats.average_rating && (
                    <div className="bg-yellow-50 rounded p-3 border border-yellow-200">
                      <p className="text-xs text-gray-600 mb-1">Average Rating</p>
                      <p className="text-2xl font-bold text-yellow-600">
                        {selectedStats.average_rating.toFixed(1)} ⭐
                      </p>
                    </div>
                  )}

                  {selectedStats.acceptance_rate && (
                    <div className="bg-purple-50 rounded p-3 border border-purple-200">
                      <p className="text-xs text-gray-600 mb-1">Acceptance Rate</p>
                      <p className="text-2xl font-bold text-purple-600">
                        {selectedStats.acceptance_rate.toFixed(0)}%
                      </p>
                    </div>
                  )}
                </div>

                {/* Additional Stats */}
                <div className="bg-gray-50 rounded p-3 border border-gray-200 space-y-2">
                  {selectedStats.cancellation_rate && (
                    <div className="flex justify-between">
                      <span className="text-sm text-gray-600">Cancellation Rate:</span>
                      <span className="font-semibold text-red-600">
                        {selectedStats.cancellation_rate.toFixed(1)}%
                      </span>
                    </div>
                  )}

                  {selectedStats.average_delivery_time && (
                    <div className="flex justify-between">
                      <span className="text-sm text-gray-600">Avg Delivery Time:</span>
                      <span className="font-semibold text-gray-900">
                        {selectedStats.average_delivery_time}
                      </span>
                    </div>
                  )}

                  <div className="flex justify-between">
                    <span className="text-sm text-gray-600">Status:</span>
                    <span className="font-semibold">
                      {selectedDriver.driver?.is_verified ? "✓ Verified" : "Pending"}
                    </span>
                  </div>

                  {selectedDriver.driver?.vehicle_type && (
                    <div className="flex justify-between">
                      <span className="text-sm text-gray-600">Vehicle Type:</span>
                      <span className="font-semibold capitalize">
                        {selectedDriver.driver.vehicle_type}
                      </span>
                    </div>
                  )}
                </div>

                {/* Action Buttons */}
                <div className="flex gap-2">
                  <button
                    type="button"
                    className="flex-1 px-3 py-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 text-sm font-semibold"
                  >
                    View Details
                  </button>
                  <button
                    type="button"
                    onClick={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                      if (selectedDriver) openMessaging(selectedDriver);
                    }}
                    className="flex-1 px-3 py-2 bg-green-50 text-green-600 rounded hover:bg-green-100 text-sm font-semibold flex items-center justify-center gap-2"
                  >
                    <BiMessageRounded className="text-lg" />
                    Message
                  </button>
                </div>
              </div>
            </>
          ) : (
            <div className="flex items-center justify-center h-48 text-gray-400">
              <p>Select a driver to view detailed analytics</p>
            </div>
          )}
        </div>
      </div>

      {/* Messaging Modal */}
      {messagingDriver && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[80vh] flex flex-col">
            <div className="p-4 border-b flex justify-between items-center">
              <h3 className="font-semibold">Message {messagingDriver.name}</h3>
              <button
                onClick={closeMessaging}
                className="text-gray-500 hover:text-gray-700 text-2xl leading-none"
              >
                ×
              </button>
            </div>
            <div className="flex-1 overflow-y-auto p-4 space-y-3 min-h-[200px]">
              {loadingMessages ? (
                <div className="flex justify-center py-8">
                  <BiLoaderAlt className="animate-spin text-2xl text-green-600" />
                </div>
              ) : (
                messages.map((m) => (
                  <div
                    key={m.id}
                    className={`flex ${m.sender_type === "admin" ? "justify-end" : "justify-start"}`}
                  >
                    <div
                      className={`max-w-[80%] px-3 py-2 rounded-lg ${
                        m.sender_type === "admin"
                          ? "bg-green-100 text-green-900"
                          : "bg-gray-100 text-gray-900"
                      }`}
                    >
                      <p className="text-sm">{m.content}</p>
                      <p className="text-xs text-gray-500 mt-1">{m.sender_name}</p>
                    </div>
                  </div>
                ))
              )}
            </div>
            <div className="p-4 border-t flex gap-2">
              <input
                type="text"
                value={newMessage}
                onChange={(e) => setNewMessage(e.target.value)}
                onKeyDown={(e) => e.key === "Enter" && sendMessage()}
                placeholder="Type a message..."
                className="flex-1 px-3 py-2 border rounded-lg text-sm"
              />
              <button
                onClick={sendMessage}
                disabled={!newMessage.trim() || sendingMessage}
                className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 text-sm font-semibold"
              >
                {sendingMessage ? "..." : "Send"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DriverAnalytics;
