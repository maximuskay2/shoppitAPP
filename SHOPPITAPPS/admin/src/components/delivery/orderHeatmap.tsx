import { useEffect, useMemo, useState } from "react";
import { BiLoaderAlt, BiRefresh, BiMapAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

type HeatmapPoint = {
  lat: number;
  lng: number;
  count: number;
};

type HeatmapResponse = {
  precision: number;
  points: HeatmapPoint[];
};

const OrderHeatmap = () => {
  const [points, setPoints] = useState<HeatmapPoint[]>([]);
  const [loading, setLoading] = useState(true);
  const [startDate, setStartDate] = useState<string>("");
  const [endDate, setEndDate] = useState<string>("");
  const [precision, setPrecision] = useState<number>(2);

  const fetchHeatmap = async () => {
    const token = localStorage.getItem("token");
    setLoading(true);

    const params = new URLSearchParams();
    if (startDate) params.append("start_date", startDate);
    if (endDate) params.append("end_date", endDate);
    params.append("precision", String(precision));

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/analytics/heatmap?${params.toString()}`),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );

      const result = await response.json();
      if (result.success) {
        const data: HeatmapResponse = result.data;
        setPoints(data.points || []);
      } else {
        setPoints([]);
      }
    } catch (err) {
      console.error("Failed to fetch heatmap data:", err);
      setPoints([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchHeatmap();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const maxCount = useMemo(() => {
    if (points.length === 0) return 1;
    return Math.max(...points.map((p) => p.count));
  }, [points]);

  const getIntensity = (count: number) => {
    const ratio = Math.min(count / maxCount, 1);
    if (ratio > 0.75) return "bg-red-500";
    if (ratio > 0.5) return "bg-orange-500";
    if (ratio > 0.25) return "bg-yellow-500";
    return "bg-green-500";
  };

  return (
    <div className="space-y-4">
      <div className="border border-gray-200 rounded-lg bg-white overflow-hidden">
        <div className="flex flex-col gap-4 p-4 bg-gray-50 border-b lg:flex-row lg:items-center lg:justify-between">
          <div className="flex items-center gap-2">
            <BiMapAlt className="text-lg text-[#1F6728]" />
            <h3 className="font-semibold">Order Density Heatmap</h3>
            <span className="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded">
              {points.length} Hotspots
            </span>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <div className="flex items-center gap-2">
              <label className="text-xs text-gray-600">Start</label>
              <input
                type="date"
                className="border border-gray-300 rounded px-2 py-1 text-sm"
                value={startDate}
                onChange={(event) => setStartDate(event.target.value)}
              />
            </div>
            <div className="flex items-center gap-2">
              <label className="text-xs text-gray-600">End</label>
              <input
                type="date"
                className="border border-gray-300 rounded px-2 py-1 text-sm"
                value={endDate}
                onChange={(event) => setEndDate(event.target.value)}
              />
            </div>
            <div className="flex items-center gap-2">
              <label className="text-xs text-gray-600">Precision</label>
              <select
                className="border border-gray-300 rounded px-2 py-1 text-sm"
                value={precision}
                onChange={(event) => setPrecision(Number(event.target.value))}
              >
                <option value={1}>Low</option>
                <option value={2}>Medium</option>
                <option value={3}>High</option>
              </select>
            </div>
            <button
              onClick={fetchHeatmap}
              disabled={loading}
              className="flex items-center gap-2 px-3 py-2 bg-[#1F6728] text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
            >
              <BiRefresh className={loading ? "animate-spin" : ""} />
              {loading ? "Updating..." : "Refresh"}
            </button>
          </div>
        </div>

        <div className="p-4">
          {loading && (
            <div className="flex items-center justify-center gap-2 text-gray-500 h-48">
              <BiLoaderAlt className="animate-spin" />
              <span>Loading heatmap...</span>
            </div>
          )}

          {!loading && points.length === 0 && (
            <div className="flex items-center justify-center h-48 text-gray-400">
              <p>No heatmap data available for the selected range.</p>
            </div>
          )}

          {!loading && points.length > 0 && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <div className="border border-gray-200 rounded-lg p-4">
                <p className="text-xs font-semibold text-gray-600 mb-2">
                  Hotspot distribution
                </p>
                <div className="space-y-2">
                  {points.slice(0, 10).map((point) => (
                    <div
                      key={`${point.lat}-${point.lng}`}
                      className="flex items-center gap-3"
                    >
                      <span
                        className={`inline-block w-3 h-3 rounded-full ${getIntensity(
                          point.count
                        )}`}
                      ></span>
                      <div className="flex-1">
                        <p className="text-sm text-gray-700 font-mono">
                          {point.lat.toFixed(3)}, {point.lng.toFixed(3)}
                        </p>
                        <div className="h-2 bg-gray-100 rounded overflow-hidden">
                          <div
                            className={`h-2 ${getIntensity(point.count)}`}
                            style={{
                              width: `${Math.max(
                                10,
                                (point.count / maxCount) * 100
                              )}%`,
                            }}
                          ></div>
                        </div>
                      </div>
                      <span className="text-sm font-semibold text-gray-600">
                        {point.count}
                      </span>
                    </div>
                  ))}
                </div>
              </div>

              <div className="border border-gray-200 rounded-lg p-4">
                <p className="text-xs font-semibold text-gray-600 mb-2">
                  Heatmap points
                </p>
                <div className="grid grid-cols-2 gap-2 max-h-80 overflow-y-auto">
                  {points.map((point) => (
                    <div
                      key={`${point.lat}-${point.lng}-tile`}
                      className="flex items-center gap-2 border border-gray-100 rounded p-2"
                    >
                      <span
                        className={`inline-block w-2.5 h-2.5 rounded-full ${getIntensity(
                          point.count
                        )}`}
                      ></span>
                      <div className="text-xs text-gray-600">
                        <p className="font-mono">
                          {point.lat.toFixed(2)}, {point.lng.toFixed(2)}
                        </p>
                        <p className="font-semibold">{point.count} orders</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default OrderHeatmap;
