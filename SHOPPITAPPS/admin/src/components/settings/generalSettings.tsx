import { useState, useEffect } from "react";
import { BiSolidFileExport, BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

const GeneralSettings = () => {
  // Form States
  const [applicationName, setApplicationName] = useState("");
  const [contactEmail, setContactEmail] = useState("");
  const [supportPhone, setSupportPhone] = useState("");
  const [appLogo, setAppLogo] = useState(""); // This stores the preview URL

  // UI States
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);

  const inputStyle =
    "border-2 border-gray-200 bg-gray-100 rounded-full px-6 py-3 w-full focus:bg-white focus:border-[#1F6728] focus:ring-4 focus:ring-green-50 outline-none transition-all text-gray-800 font-medium placeholder:text-gray-400";

  // 1. FETCH CURRENT SETTINGS (Prefill Fields)
  useEffect(() => {
    const fetchSettings = async () => {
      const token = localStorage.getItem("token");
      try {
        const response = await fetch(
          apiUrl("/api/v1/admin/settings/general"),
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          }
        );
        const result = await response.json();

        if (result.success) {
          // Mapping API keys to state
          setApplicationName(result.data.app_name || "");
          setContactEmail(result.data.contact_email || "");
          setSupportPhone(result.data.support_phone || "");
          setAppLogo(result.data.app_logo || "");
        }
      } catch (err) {
        console.error("Failed to fetch settings:", err);
      } finally {
        setFetching(false);
      }
    };

    fetchSettings();
  }, []);

  // 2. SAVE SETTINGS (Handle Update)
  const handleSave = async () => {
    setLoading(true);
    const token = localStorage.getItem("token");

    // Using FormData for Multipart/Form-Data (Logo Upload)
    const data = new FormData();
    data.append("app_name", applicationName);
    data.append("contact_email", contactEmail);
    data.append("support_phone", supportPhone);

    if (logoFile) {
      data.append("app_logo", logoFile);
    }

    try {
      const response = await fetch(
        apiUrl("/api/v1/admin/settings/general"),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
          body: data,
        }
      );

      const result = await response.json();
      if (result.success) {
        alert("General settings updated successfully!");
      } else {
        alert(result.message || "Failed to update settings");
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
          Fetching Configuration...
        </p>
      </div>
    );
  }

  return (
    <div className="animate-in fade-in duration-500 flex flex-col min-h-full">
      <p className="text-xl font-bold mb-6 text-gray-800">General Settings</p>

      <div className="space-y-6 flex-1">
        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Application Name
          </label>
          <input
            type="text"
            value={applicationName}
            onChange={(e) => setApplicationName(e.target.value)}
            className={inputStyle}
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Contact Email
          </label>
          <input
            type="email"
            value={contactEmail}
            onChange={(e) => setContactEmail(e.target.value)}
            className={inputStyle}
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Support Phone
          </label>
          <input
            type="tel"
            value={supportPhone}
            onChange={(e) => setSupportPhone(e.target.value)}
            className={inputStyle}
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Application Logo
          </label>
          <div
            className="border-2 border-dashed border-gray-200 bg-gray-100 rounded-2xl p-10 text-center cursor-pointer hover:border-[#1F6728] hover:bg-white transition-all group"
            onClick={() => document.getElementById("logoUpload")?.click()}
          >
            {appLogo ? (
              <div className="flex flex-col items-center">
                <img
                  src={appLogo}
                  alt="Logo"
                  className="h-20 w-auto object-contain mb-4"
                />
                <p className="text-xs font-bold text-[#1F6728] uppercase">
                  Click to replace
                </p>
              </div>
            ) : (
              <div className="text-gray-400">
                <BiSolidFileExport className="text-5xl mx-auto mb-3 group-hover:text-[#1F6728]" />
                <p className="font-bold text-sm uppercase tracking-tight">
                  Drop logo here or click to upload
                </p>
              </div>
            )}
          </div>
          <input
            id="logoUpload"
            type="file"
            accept="image/*"
            className="hidden"
            onChange={(e) => {
              const file = e.target.files?.[0];
              if (file) {
                setLogoFile(file);
                // Create preview URL
                setAppLogo(URL.createObjectURL(file));
              }
            }}
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
          {loading ? "Saving..." : "Save Settings"}
        </button>
      </div>
    </div>
  );
};

export default GeneralSettings;
