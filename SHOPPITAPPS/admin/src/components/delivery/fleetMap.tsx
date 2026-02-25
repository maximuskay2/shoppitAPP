import { useEffect, useState, useCallback } from "react";
import { BiLoaderAlt, BiRefresh, BiMapAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";
import { MapContainer, TileLayer, Marker, Popup, useMap } from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

type DriverLocation = {
  driver_id: string;
  user_id?: string;
  latitude?: number;
  longitude?: number;
  lat?: number;
  lng?: number;
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
    name?: string;
    email?: string;
    phone?: string;
    is_online?: boolean;
    is_verified?: boolean;
    vehicle_type?: string;
  };
};

const LAGOS_CENTER: [number, number] = [6.5244, 3.3792];

const createDriverIcon = (isOnline: boolean) =>
  L.divIcon({
    html: `<div style="
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: ${isOnline ? "#22c55e" : "#9ca3af"};
      border: 3px solid white;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    "></div>`,
    className: "driver-marker",
    iconSize: [24, 24],
    iconAnchor: [12, 12],
  });

const createSelectedIcon = () =>
  L.divIcon({
    html: `<div style="
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #1f6728;
      border: 3px solid white;
      box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    "></div>`,
    className: "driver-marker-selected",
    iconSize: [32, 32],
    iconAnchor: [16, 16],
  });

function getLatLng(d: DriverLocation): [number, number] {
  const lat = d.latitude ?? d.lat ?? 0;
  const lng = d.longitude ?? d.lng ?? 0;
  return [lat, lng];
}

function getDriverName(d: DriverLocation): string {
  return d.user?.name ?? d.driver?.name ?? "Unknown Driver";
}

function getDriverEmail(d: DriverLocation): string {
  return d.user?.email ?? d.driver?.email ?? "-";
}

function MapCenterController({
  center,
  driver,
}: {
  center: [number, number] | null;
  driver: DriverLocation | null;
}) {
  const map = useMap();
  useEffect(() => {
    if (center && driver) {
      map.flyTo(center, 12, { duration: 0.5 });
    }
  }, [map, center, driver]);
  return null;
}

function MapResizeFix() {
  const map = useMap();
  useEffect(() => {
    const timer = setTimeout(() => {
      map.invalidateSize();
    }, 100);
    return () => clearTimeout(timer);
  }, [map]);
  return null;
}

const FleetMap = () => {
  const [locations, setLocations] = useState<DriverLocation[]>([]);
  const [loading, setLoading] = useState(true);
  const [fetchError, setFetchError] = useState<string | null>(null);
  const [selectedDriver, setSelectedDriver] = useState<DriverLocation | null>(null);
  const [mapCenter, setMapCenter] = useState<[number, number]>(LAGOS_CENTER);
  const [flyToCenter, setFlyToCenter] = useState<[number, number] | null>(null);

  const fetchLocations = useCallback(async () => {
    const token = localStorage.getItem("token");
    setLoading(true);
    setFetchError(null);

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

        if (data.length > 0) {
          const valid = data.filter((d: DriverLocation) => {
            const [lat, lng] = getLatLng(d);
            return lat !== 0 && lng !== 0;
          });
          if (valid.length > 0) {
            const avgLat = valid.reduce((a: number, d: DriverLocation) => a + getLatLng(d)[0], 0) / valid.length;
            const avgLng = valid.reduce((a: number, d: DriverLocation) => a + getLatLng(d)[1], 0) / valid.length;
            setMapCenter([avgLat, avgLng]);
          }
        }
      } else {
        setFetchError(result.message || "Failed to load driver locations");
      }
    } catch (err) {
      console.error("Failed to fetch fleet locations:", err);
      setFetchError("Could not connect. Check if the API is running.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchLocations();
    const interval = setInterval(fetchLocations, 30000);
    return () => clearInterval(interval);
  }, [fetchLocations]);

  const handleSelectDriver = (location: DriverLocation) => {
    setSelectedDriver(location);
    const [lat, lng] = getLatLng(location);
    if (lat !== 0 && lng !== 0) {
      setFlyToCenter([lat, lng]);
    }
  };

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

  const validLocations = locations.filter((d) => {
    const [lat, lng] = getLatLng(d);
    return lat !== 0 && lng !== 0;
  });

  return (
    <div className="space-y-4">
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

        <div className="relative">
          {loading && (
            <div className="absolute inset-0 z-[1000] flex items-center justify-center gap-2 bg-white/80">
              <BiLoaderAlt className="animate-spin text-xl text-[#1F6728]" />
              <span className="text-gray-600">Loading driver locations...</span>
            </div>
          )}

          {fetchError && (
            <div className="absolute top-4 left-1/2 -translate-x-1/2 z-[1001] px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium shadow">
              {fetchError}
            </div>
          )}

          {/* Always render map so Leaflet initializes - map needs explicit dimensions */}
          <div style={{ height: 400, width: "100%" }}>
            <MapContainer
              center={mapCenter}
              zoom={11}
              style={{ height: "100%", width: "100%" }}
              className="z-0"
            >
              <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
              />
              <MapCenterController center={flyToCenter} driver={selectedDriver} />
              <MapResizeFix />
              {validLocations.map((location) => {
                const pos = getLatLng(location);
                const isSelected = selectedDriver?.driver_id === location.driver_id;
                return (
                  <Marker
                    key={location.driver_id}
                    position={pos}
                    icon={
                      isSelected
                        ? createSelectedIcon()
                        : createDriverIcon(location.driver?.is_online ?? false)
                    }
                    eventHandlers={{
                      click: () => handleSelectDriver(location),
                    }}
                  >
                    <Popup>
                      <div className="min-w-[200px] p-1">
                        <p className="font-semibold text-gray-900">{getDriverName(location)}</p>
                        <p className="text-xs text-gray-500">{getDriverEmail(location)}</p>
                        <p className="text-xs text-gray-600 mt-2 font-mono">
                          📍 {pos[0].toFixed(6)}, {pos[1].toFixed(6)}
                        </p>
                        <span
                          className={`inline-block mt-2 text-xs px-2 py-1 rounded-full ${
                            getStatusColor(location) === "bg-green-500"
                              ? "bg-green-100 text-green-700"
                              : getStatusColor(location) === "bg-yellow-500"
                              ? "bg-yellow-100 text-yellow-700"
                              : "bg-gray-100 text-gray-700"
                          }`}
                        >
                          {getStatusText(location)}
                        </span>
                      </div>
                    </Popup>
                  </Marker>
                );
              })}
            </MapContainer>
          </div>

          {!loading && validLocations.length === 0 && (
            <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-[1000] px-4 py-2 bg-gray-800/80 text-white text-sm rounded-lg text-center">
              {locations.length > 0
                ? `${locations.length} driver(s) online — waiting for location updates from rider app`
                : "No driver locations available yet"}
            </div>
          )}
        </div>
      </div>

      {!loading && locations.length > 0 && (
        <div className="border border-gray-200 rounded-lg bg-white">
          <div className="p-4 border-b bg-gray-50">
            <h3 className="font-semibold">Driver Locations — Tap to view on map</h3>
          </div>

          <div className="overflow-y-auto max-h-96">
            <div className="divide-y">
              {locations.map((location) => {
                const [lat, lng] = getLatLng(location);
                const hasValidCoords = lat !== 0 && lng !== 0;
                return (
                  <div
                    key={location.driver_id}
                    className={`p-4 hover:bg-gray-50 cursor-pointer transition ${
                      selectedDriver?.driver_id === location.driver_id ? "bg-green-50 border-l-4 border-[#1F6728]" : ""
                    }`}
                    onClick={() => hasValidCoords && handleSelectDriver(location)}
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <div className={`w-2 h-2 rounded-full ${getStatusColor(location)}`}></div>
                          <p className="font-medium text-gray-900">{getDriverName(location)}</p>
                        </div>
                        <p className="text-xs text-gray-500">{getDriverEmail(location)}</p>
                        {hasValidCoords ? (
                          <p className="text-xs text-gray-500 mt-1">
                            📍 {lat.toFixed(5)}, {lng.toFixed(5)}
                          </p>
                        ) : (
                          <p className="text-xs text-amber-600 mt-1">No location data</p>
                        )}
                        {location.speed && location.speed > 0 && (
                          <p className="text-xs text-blue-600">⚡ {location.speed.toFixed(1)} km/h</p>
                        )}
                      </div>
                      <div className="text-right">
                        <span
                          className={`text-xs font-semibold px-2 py-1 rounded-full ${
                            getStatusColor(location) === "bg-green-500"
                              ? "bg-green-100 text-green-700"
                              : getStatusColor(location) === "bg-yellow-500"
                              ? "bg-yellow-100 text-yellow-700"
                              : "bg-gray-100 text-gray-700"
                          }`}
                        >
                          {getStatusText(location)}
                        </span>
                        {location.driver?.vehicle_type && (
                          <p className="text-xs text-gray-500 mt-1">{location.driver.vehicle_type}</p>
                        )}
                      </div>
                    </div>
                    {location.recorded_at && (
                      <p className="text-xs text-gray-400 mt-2">
                        Updated: {new Date(location.recorded_at).toLocaleTimeString()}
                      </p>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default FleetMap;
