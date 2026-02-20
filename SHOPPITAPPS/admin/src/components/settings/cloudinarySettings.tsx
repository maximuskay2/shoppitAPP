import { useState, useEffect } from "react";
import { BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

const CloudinarySettings = () => {
  const [cloudName, setCloudName] = useState("");
  const [apiKey, setApiKey] = useState("");
  const [apiSecret, setApiSecret] = useState("");
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);

  const inputStyle =
    "border-2 border-gray-200 bg-gray-100 rounded-full px-6 py-3 w-full focus:bg-white focus:border-[#1F6728] focus:ring-4 focus:ring-green-50 outline-none transition-all text-gray-800 font-medium placeholder:text-gray-400";

  useEffect(() => {
    const fetchSettings = async () => {
      const token = localStorage.getItem("token");
      try {
        const response = await fetch(
          apiUrl("/api/v1/admin/settings/cloudinary"),
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          }
        );
        const result = await response.json();
        if (result.success && result.data) {
          setCloudName(result.data.cloud_name || "");
          setApiKey(result.data.api_key || "");
          setApiSecret(result.data.api_secret || "");
        }
      } catch (err) {
        console.error("Failed to fetch Cloudinary settings:", err);
      } finally {
        setFetching(false);
      }
    };
    fetchSettings();
  }, []);

  const handleSave = async () => {
    if (!cloudName.trim() || !apiKey.trim()) {
      alert("Cloud Name and API Key are required.");
      return;
    }
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(
        apiUrl("/api/v1/admin/settings/cloudinary"),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            cloud_name: cloudName.trim(),
            api_key: apiKey.trim(),
            api_secret: apiSecret || undefined,
          }),
        }
      );
      const result = await response.json();
      if (result.success) {
        alert("Cloudinary settings updated successfully!");
        if (apiSecret) setApiSecret("");
      } else {
        alert(result.message || "Failed to update Cloudinary settings");
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
          Fetching Cloudinary Configuration...
        </p>
      </div>
    );
  }

  return (
    <div className="animate-in fade-in duration-500 flex flex-col min-h-full">
      <p className="text-xl font-bold mb-6 text-gray-800">Cloudinary Settings</p>
      <p className="text-sm text-gray-500 mb-6">
        Configure Cloudinary for document and image uploads (driver documents,
        avatars, product images, etc.). Get credentials at{" "}
        <a
          href="https://cloudinary.com"
          target="_blank"
          rel="noopener noreferrer"
          className="text-[#1F6728] hover:underline"
        >
          cloudinary.com
        </a>
        . Format: <code className="bg-gray-100 px-1 rounded">cloudinary://API_KEY:API_SECRET@CLOUD_NAME</code>
      </p>

      <div className="space-y-6 flex-1">
        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Cloud Name
          </label>
          <input
            type="text"
            value={cloudName}
            onChange={(e) => setCloudName(e.target.value)}
            className={inputStyle}
            placeholder="your-cloud-name"
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            API Key
          </label>
          <input
            type="text"
            value={apiKey}
            onChange={(e) => setApiKey(e.target.value)}
            className={inputStyle}
            placeholder="123456789012345"
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            API Secret
          </label>
          <input
            type="password"
            value={apiSecret}
            onChange={(e) => setApiSecret(e.target.value)}
            className={inputStyle}
            placeholder="Leave blank to keep existing"
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
          {loading ? "Saving..." : "Save Cloudinary Settings"}
        </button>
      </div>
    </div>
  );
};

export default CloudinarySettings;
