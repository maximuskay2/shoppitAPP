import { useState, useEffect } from "react";
import { BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

const FcmTokensSettings = () => {
  const [tokens, setTokens] = useState("");
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);

  useEffect(() => {
    const fetchTokens = async () => {
      const token = localStorage.getItem("token");
      try {
        const response = await fetch(
          apiUrl("/api/v1/admin/settings/fcm-tokens"),
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",  bb
            },
          }
        );
        const result = await response.json();
        if (result.success && result.data.tokens) {
          setTokens(result.data.tokens.join(", "));
        }
      } catch (err) {
        console.error("Failed to fetch FCM tokens:", err);
      } finally {
        setFetching(false);
      }
    };
    fetchTokens();
  }, []);

  const handleSave = async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(
        apiUrl("/api/v1/admin/settings/fcm-tokens"),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ tokens: tokens.split(",").map(t => t.trim()) }),
        }
      );
      const result = await response.json();
      if (result.success) {
        alert("FCM tokens updated successfully!");
      } else {
        alert(result.message || "Failed to update FCM tokens");
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
          Fetching FCM Tokens...
        </p>
      </div>
    );
  }

  return (
    <div className="animate-in fade-in duration-500 flex flex-col min-h-full">
      <p className="text-xl font-bold mb-6 text-gray-800">FCM Tokens</p>
      <div className="space-y-6 flex-1">
        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Driver App FCM Tokens (comma separated)
          </label>
          <input
            type="text"
            value={tokens}
            onChange={e => setTokens(e.target.value)}
            className="border-2 border-gray-200 bg-gray-100 rounded-full px-6 py-3 w-full focus:bg-white focus:border-[#1F6728] focus:ring-4 focus:ring-green-50 outline-none transition-all text-gray-800 font-medium placeholder:text-gray-400"
            placeholder="Enter FCM tokens"
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
          {loading ? "Saving..." : "Save Tokens"}
        </button>
      </div>
    </div>
  );
};

export default FcmTokensSettings;
