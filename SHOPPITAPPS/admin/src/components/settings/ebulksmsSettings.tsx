import { useState, useEffect } from "react";
import { BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

const EbulksmsSettings = () => {
  const [baseUrl, setBaseUrl] = useState("https://api.ebulksms.com/sendsms.json");
  const [username, setUsername] = useState("");
  const [apiKey, setApiKey] = useState("");
  const [sender, setSender] = useState("ShopittPlus");
  const [dndsender, setDndsender] = useState(0);
  const [countryCode, setCountryCode] = useState("234");
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);

  const inputStyle =
    "border-2 border-gray-200 bg-gray-100 rounded-full px-6 py-3 w-full focus:bg-white focus:border-[#1F6728] focus:ring-4 focus:ring-green-50 outline-none transition-all text-gray-800 font-medium placeholder:text-gray-400";

  useEffect(() => {
    const fetchSettings = async () => {
      const token = localStorage.getItem("token");
      try {
        const response = await fetch(
          apiUrl("/api/v1/admin/settings/ebulksms"),
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          }
        );
        const result = await response.json();
        if (result.success && result.data) {
          setBaseUrl(result.data.base_url || "https://api.ebulksms.com/sendsms.json");
          setUsername(result.data.username || "");
          setApiKey(result.data.api_key || "");
          setSender(result.data.sender || "ShopittPlus");
          setDndsender(result.data.dndsender ?? 0);
          setCountryCode(result.data.country_code || "234");
        }
      } catch (err) {
        console.error("Failed to fetch EbulkSMS settings:", err);
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
        apiUrl("/api/v1/admin/settings/ebulksms"),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            base_url: baseUrl,
            username,
            api_key: apiKey,
            sender: sender.substring(0, 11),
            dndsender,
            country_code: countryCode,
          }),
        }
      );
      const result = await response.json();
      if (result.success) {
        alert("EbulkSMS settings updated successfully!");
      } else {
        alert(result.message || "Failed to update EbulkSMS settings");
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
          Fetching EbulkSMS Configuration...
        </p>
      </div>
    );
  }

  return (
    <div className="animate-in fade-in duration-500 flex flex-col min-h-full">
      <p className="text-xl font-bold mb-6 text-gray-800">EbulkSMS Settings</p>
      <p className="text-sm text-gray-500 mb-6">
        Configure SMS delivery for OTP codes, order notifications, and driver alerts.
      </p>

      <div className="space-y-6 flex-1">
        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            API Base URL
          </label>
          <input
            type="url"
            value={baseUrl}
            onChange={(e) => setBaseUrl(e.target.value)}
            className={inputStyle}
            placeholder="https://api.ebulksms.com/sendsms.json"
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Username
          </label>
          <input
            type="text"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            className={inputStyle}
            placeholder="EbulkSMS username"
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            API Key
          </label>
          <input
            type="password"
            value={apiKey}
            onChange={(e) => setApiKey(e.target.value)}
            className={inputStyle}
            placeholder="Leave blank to keep existing"
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Sender ID (max 11 characters)
          </label>
          <input
            type="text"
            value={sender}
            onChange={(e) => setSender(e.target.value.substring(0, 11))}
            className={inputStyle}
            placeholder="ShopittPlus"
            maxLength={11}
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            DND Sender
          </label>
          <select
            value={dndsender}
            onChange={(e) => setDndsender(parseInt(e.target.value, 10))}
            className={inputStyle}
          >
            <option value={0}>No (0)</option>
            <option value={1}>Yes (1)</option>
          </select>
          <p className="text-xs text-gray-400 mt-1">Use DND-compliant sender if required in your region</p>
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Default Country Code
          </label>
          <input
            type="text"
            value={countryCode}
            onChange={(e) => setCountryCode(e.target.value.replace(/\D/g, "").substring(0, 5))}
            className={inputStyle}
            placeholder="234"
            maxLength={5}
          />
          <p className="text-xs text-gray-400 mt-1">e.g. 234 for Nigeria, 1 for US</p>
        </div>
      </div>

      <div className="mt-10 pt-6 border-t border-gray-100 flex justify-end">
        <button
          onClick={handleSave}
          disabled={loading}
          className="bg-[#1F6728] text-white px-10 py-3 rounded-full font-bold shadow-lg hover:bg-[#185321] transition-all disabled:bg-gray-300 flex items-center gap-2"
        >
          {loading && <BiLoaderAlt className="animate-spin" />}
          {loading ? "Saving..." : "Save EbulkSMS Settings"}
        </button>
      </div>
    </div>
  );
};

export default EbulksmsSettings;
