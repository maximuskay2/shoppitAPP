import { useState, useEffect } from "react";
import { BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

const SmtpSettings = () => {
  const [mailer, setMailer] = useState("smtp");
  const [host, setHost] = useState("");
  const [port, setPort] = useState("587");
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [encryption, setEncryption] = useState("tls");
  const [fromAddress, setFromAddress] = useState("");
  const [fromName, setFromName] = useState("");
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);

  const inputStyle =
    "border-2 border-gray-200 bg-gray-100 rounded-full px-6 py-3 w-full focus:bg-white focus:border-[#1F6728] focus:ring-4 focus:ring-green-50 outline-none transition-all text-gray-800 font-medium placeholder:text-gray-400";

  useEffect(() => {
    const fetchSettings = async () => {
      const token = localStorage.getItem("token");
      try {
        const response = await fetch(
          apiUrl("/api/v1/admin/settings/smtp"),
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          }
        );
        const result = await response.json();
        if (result.success && result.data) {
          setMailer(result.data.mailer || "smtp");
          setHost(result.data.host || "");
          setPort(String(result.data.port || "587"));
          setUsername(result.data.username || "");
          setPassword(result.data.password || "");
          setEncryption(result.data.encryption || "tls");
          setFromAddress(result.data.from_address || "");
          setFromName(result.data.from_name || "");
        }
      } catch (err) {
        console.error("Failed to fetch SMTP settings:", err);
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
        apiUrl("/api/v1/admin/settings/smtp"),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            mailer,
            host,
            port,
            username,
            password: password || undefined,
            encryption,
            from_address: fromAddress,
            from_name: fromName,
          }),
        }
      );
      const result = await response.json();
      if (result.success) {
        alert("SMTP settings updated successfully!");
        if (password) setPassword("");
      } else {
        alert(result.message || "Failed to update SMTP settings");
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
          Fetching SMTP Configuration...
        </p>
      </div>
    );
  }

  return (
    <div className="animate-in fade-in duration-500 flex flex-col min-h-full">
      <p className="text-xl font-bold mb-6 text-gray-800">SMTP Settings</p>
      <p className="text-sm text-gray-500 mb-6">
        Configure email delivery for notifications, OTP emails, and transactional emails.
      </p>

      <div className="space-y-6 flex-1">
        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Mailer
          </label>
          <select
            value={mailer}
            onChange={(e) => setMailer(e.target.value)}
            className={inputStyle}
          >
            <option value="smtp">SMTP</option>
            <option value="log">Log (testing)</option>
          </select>
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Host
          </label>
          <input
            type="text"
            value={host}
            onChange={(e) => setHost(e.target.value)}
            className={inputStyle}
            placeholder="smtp.mailtrap.io"
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Port
          </label>
          <input
            type="text"
            value={port}
            onChange={(e) => setPort(e.target.value)}
            className={inputStyle}
            placeholder="587"
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
            placeholder="SMTP username"
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Password
          </label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className={inputStyle}
            placeholder="Leave blank to keep existing"
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            Encryption
          </label>
          <select
            value={encryption}
            onChange={(e) => setEncryption(e.target.value)}
            className={inputStyle}
          >
            <option value="">None</option>
            <option value="tls">TLS</option>
            <option value="ssl">SSL</option>
          </select>
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            From Address
          </label>
          <input
            type="email"
            value={fromAddress}
            onChange={(e) => setFromAddress(e.target.value)}
            className={inputStyle}
            placeholder="noreply@example.com"
          />
        </div>

        <div>
          <label className="block mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            From Name
          </label>
          <input
            type="text"
            value={fromName}
            onChange={(e) => setFromName(e.target.value)}
            className={inputStyle}
            placeholder="ShopittPlus"
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
          {loading ? "Saving..." : "Save SMTP Settings"}
        </button>
      </div>
    </div>
  );
};

export default SmtpSettings;
