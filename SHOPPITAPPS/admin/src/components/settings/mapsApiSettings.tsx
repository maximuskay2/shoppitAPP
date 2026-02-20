import { useState, useEffect } from "react";
import { BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

const MapsApiSettings = () => {
  const [mapsApiKey, setMapsApiKey] = useState("");
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);

  useEffect(() => {
    const fetchSettings = async () => {
      const token = localStorage.getItem("token");
      try {
        const response = await fetch(
          apiUrl("/api/v1/admin/settings/maps-api-key"),
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          }
        );
        const result = await response.json();
        if (result.success && result.data.setting) {
          setMapsApiKey(result.data.setting.value || "");
        }
      } catch (err) {
        console.error("Failed to fetch Maps API key:", err);
      } finally {
        setFetching(false);
      }
    };
    fetchSettings();
  }, []);

  const handleSave = async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(
        apiUrl("/api/v1/admin/settings"),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            name: "maps_api_key",
            value: mapsApiKey,
            description: "Google Maps API key for driver app"
          }),
        }
      );
      const result = await response.json();
      if (result.success) {
        alert("Google Maps API key updated successfully!");
      } else {
        alert(result.message || "Failed to update Maps API key");
      }
    } catch (err) {
      console.error("Update Error:", err);
      alert("Network error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  if (fetching) {
    return (
      <div className="flex flex-col items-center justify-center py-20 text-gray-400">
        <BiLoaderAlt className="animate-spin text-4xl mb-2" />
        <p className="font-bold text-xs uppercase tracking-widest">
          Fetching Maps API Key...
        </p>
      </div>
    );
  }

  return (
    <div className="animate-in fade-in duration-500 flex flex-col min-h-full">
      <p className="text-xl font-bold mb-6 text-gray-800">Google Maps API Key</p>
      <p className="text-sm text-amber-700 bg-amber-50 rounded-lg p-4 mb-6">
        <strong>Driver app:</strong> After saving, add this key to{" "}
        <code className="bg-amber-100 px-1 rounded">riderFlutter/android/local.properties</code> as{" "}
        <code className="bg-amber-100 px-1 rounded">GOOGLE_MAPS_API_KEY=your_key</code>, then run{" "}
        <code className="bg-amber-100 px-1 rounded">flutter run</code> or rebuild the app.
      </p>
      <div className="space-y-6 flex-1">
        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Driver App Google Maps API Key
          </label>
          <input
            type="text"
            value={mapsApiKey}
            onChange={(e) => setMapsApiKey(e.target.value)}
            className="border-2 border-gray-200 bg-gray-100 rounded-full px-6 py-3 w-full focus:bg-white focus:border-[#1F6728] focus:ring-4 focus:ring-green-50 outline-none transition-all text-gray-800 font-medium placeholder:text-gray-400"
            placeholder="Enter Google Maps API Key"
          />
        </div>
      </div>
      <div className="mt-10 pt-6 border-t border-gray-100 flex justify-end">
        <button
          onClick={handleSave}
          disabled={loading}
          className="bg-[#1F6728] text-white px-10 py-3 rounded-full font-bold shadow-lg hover:bg-[#185321] transition-all disabled:bg-gray-300 flex items-center gap-2"
        >
          {loading && <BiLoaderAlt className="animate-spin" />}
          {loading ? "Saving..." : "Save Key"}
        </button>
      </div>
    </div>
  );
};

export default MapsApiSettings;
