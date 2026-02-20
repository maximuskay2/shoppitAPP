import { useEffect, useState } from "react";
import { BiLoaderAlt, BiRefresh, BiMapAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

type DriverLocation = {
  driver_id: string;
  user_id?: string;
  latitude: number;
  longitude: number;
  bearing?: number | null;
  speed?: number | null;
  recorded_at?: string | null;
  user?: {
    id: string;
    name: string;
    email: string;
    phone: string;
  };
  driver?: {
    is_online?: boolean;
    is_verified?: boolean;
    vehicle_type?: string;
  };
};

const FleetMap = () => {
  const [locations, setLocations] = useState<DriverLocation[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedDriver, setSelectedDriver] = useState<DriverLocation | null>(null);
  const [mapCenter, setMapCenter] = useState({ lat: 6.5244, lng: 3.3792 }); // Lagos default

  const fetchLocations = async () => {
    const token = localStorage.getItem("token");
    setLoading(true);

    try {
      const response = await fetch(apiUrl("/api/v1/admin/drivers/locations"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      const result = await response.json();
      if (result.success) {
        const data = Array.isArray(result.data) ? result.data : result.data?.data || [];
        setLocations(data);
        
        // Calculate center if drivers exist
        if (data.length > 0) {
          const avgLat = data.reduce((sum: number, d: DriverLocation) => sum + (d.latitude || 0), 0) / data.length;
          const avgLng = data.reduce((sum: number, d: DriverLocation) => sum + (d.longitude || 0), 0) / data.length;
          setMapCenter({ lat: avgLat, lng: avgLng });
        }
      }
    } catch (err) {
      console.error("Failed to fetch fleet locations:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLocations();
    // Refresh every 30 seconds
    const interval = setInterval(fetchLocations, 30000);
    return () => clearInterval(interval);
  }, []);

  const getStatusColor = (driver: DriverLocation) => {
    if (!driver.driver?.is_online) return "bg-gray-400";
    if (driver.driver?.is_verified) return "bg-green-500";
    return "bg-yellow-500";
  };

  const getStatusText = (driver: DriverLocation) => {
    if (!driver.driver?.is_online) return "Offline";
    if (driver.driver?.is_verified) return "Online (Verified)";
    return "Online (Unverified)";
  };

  return (
    <div className="space-y-4">
      {/* Map Container */}
      <div className="border border-gray-200 rounded-lg bg-white overflow-hidden">
        <div className="flex justify-between items-center p-4 bg-gray-50 border-b">
          <div className="flex items-center gap-2">
            <BiMapAlt className="text-lg text-[#1F6728]" />
            <h3 className="font-semibold">Live Fleet Map</h3>
            <span className="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">
              {locations.length} Drivers
            </span>
          </div>
          <button
            onClick={fetchLocations}
            disabled={loading}
            className="flex items-center gap-2 px-3 py-2 bg-[#1F6728] text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            <BiRefresh className={loading ? "animate-spin" : ""} />
            {loading ? "Updating..." : "Refresh"}
          </button>
        </div>

        {/* ASCII Map / Grid Display */}
        <div className="p-4">
          {loading && (
            <div className="flex items-center justify-center gap-2 text-gray-500 h-48">
              <BiLoaderAlt className="animate-spin" />
              <span>Loading driver locations...</span>
            </div>
          )}

          {!loading && locations.length === 0 && (
            <div className="flex items-center justify-center h-48 text-gray-400">
              <p>No driver locations available.</p>
            </div>
          )}

          {!loading && locations.length > 0 && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
              {/* Coordinates Display */}
              <div className="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                <p className="text-xs font-semibold text-gray-600 mb-2">MAP CENTER</p>
                <p className="font-mono text-sm text-gray-700">
                  Lat: {mapCenter.lat.toFixed(4)}° N
                </p>
                <p className="font-mono text-sm text-gray-700">
                  Lng: {mapCenter.lng.toFixed(4)}° E
                </p>
                <p className="text-xs text-gray-500 mt-2">
                  {locations.length} driver{locations.length !== 1 ? "s" : ""} in view
                </p>
              </div>

              {/* Quick Stats */}
              <div className="grid grid-cols-3 gap-2">
                <div className="bg-green-50 rounded-lg p-3 border border-green-200 text-center">
                  <p className="text-2xl font-bold text-green-600">
                    {locations.filter((d) => d.driver?.is_online).length}
                  </p>
                  <p className="text-xs text-gray-600">Online</p>
                </div>
                <div className="bg-blue-50 rounded-lg p-3 border border-blue-200 text-center">
                  <p className="text-2xl font-bold text-blue-600">
                    {locations.filter((d) => d.driver?.is_verified).length}
                  </p>
                  <p className="text-xs text-gray-600">Verified</p>
                </div>
                <div className="bg-amber-50 rounded-lg p-3 border border-amber-200 text-center">
                  <p className="text-2xl font-bold text-amber-600">
                    {locations.length}
                  </p>
                  <p className="text-xs text-gray-600">Total</p>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Drivers List */}
      {!loading && locations.length > 0 && (
        <div className="border border-gray-200 rounded-lg bg-white">
          <div className="p-4 border-b bg-gray-50">
            <h3 className="font-semibold">Driver Locations</h3>
          </div>

          <div className="overflow-y-auto max-h-96">
            <div className="divide-y">
              {locations.map((location) => (
                <div
                  key={location.driver_id}
                  className={`p-4 hover:bg-gray-50 cursor-pointer transition ${
                    selectedDriver?.driver_id === location.driver_id ? "bg-blue-50" : ""
                  }`}
                  onClick={() => setSelectedDriver(location)}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <div className={`w-2 h-2 rounded-full ${getStatusColor(location)}`}></div>
                        <p className="font-medium text-gray-900">
                          {location.user?.name || "Unknown Driver"}
                        </p>
                      </div>
                      <p className="text-xs text-gray-500">
                        {location.user?.email}
                      </p>
                      <p className="text-xs text-gray-500 mt-1">
                        📍 {location.latitude.toFixed(5)}, {location.longitude.toFixed(5)}
                      </p>
                      {location.speed && location.speed > 0 && (
                        <p className="text-xs text-blue-600">
                          ⚡ {location.speed.toFixed(1)} km/h
                        </p>
                      )}
                    </div>
                    <div className="text-right">
                      <span className={`text-xs font-semibold px-2 py-1 rounded-full ${
                        getStatusColor(location) === "bg-green-500"
                          ? "bg-green-100 text-green-700"
                          : getStatusColor(location) === "bg-yellow-500"
                          ? "bg-yellow-100 text-yellow-700"
                          : "bg-gray-100 text-gray-700"
                      }`}>
                        {getStatusText(location)}
                      </span>
                      {location.driver?.vehicle_type && (
                        <p className="text-xs text-gray-500 mt-1">
                          {location.driver.vehicle_type}
                        </p>
                      )}
                    </div>
                  </div>
                  {location.recorded_at && (
                    <p className="text-xs text-gray-400 mt-2">
                      Updated: {new Date(location.recorded_at).toLocaleTimeString()}
                    </p>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default FleetMap;
