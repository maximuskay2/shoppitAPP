import { useState, useEffect } from "react";
import { BiDownload } from "react-icons/bi";
import WalletTransactions from "./walletTransactions";
import Subscriptions from "./subscriptions";
import { apiUrl } from "../../lib/api";

type TabType = "transactions" | "subscriptions";

const Transactions = () => {
  const [activeTab, setActiveTab] = useState<TabType>("transactions");
  const [searchTerm, setSearchTerm] = useState("");
  const [inputValue, setInputValue] = useState("");
  const [statusFilter, setStatusFilter] = useState("All");
  const [tierFilter, setTierFilter] = useState("All");

  const [stats, setStats] = useState({
    total_withdrawal: 0,
    total_settlement: 0,
    total_balance_volume: 0,
  });

  // Fetch Stats for the Summary Cards
  useEffect(() => {
    const fetchStats = async () => {
      const token = localStorage.getItem("token");
      try {
        const response = await fetch(
          apiUrl("/api/v1/admin/transaction-management/fetch/stats"),
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          }
        );
        const result = await response.json();
        if (result.success) setStats(result.data);
      } catch (err) {
        console.error("Stats fetch error:", err);
      }
    };
    fetchStats();
  }, []);

  // Debounce Search
  useEffect(() => {
    const delayDebounceFn = setTimeout(() => {
      setSearchTerm(inputValue);
    }, 500);
    return () => clearTimeout(delayDebounceFn);
  }, [inputValue]);

  const handleExport = async () => {
    const token = localStorage.getItem("token");
    const endpoint =
      activeTab === "transactions"
        ? "/api/v1/admin/transaction-management"
        : "/api/v1/admin/subscription-management";

    try {
      // We fetch the data specifically for export (ideally without pagination if the API supports it)
      const response = await fetch(
        apiUrl(endpoint),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();

      if (result.success) {
        const dataToExport = result.data.data;
        if (dataToExport.length === 0)
          return alert("No data available to export.");

        // Generate CSV
        const headers = Object.keys(dataToExport[0]).join(",");
        const rows = dataToExport.map((item: any) =>
          Object.values(item)
            .map((val) => `"${val}"`)
            .join(",")
        );
        const csvContent = [headers, ...rows].join("\n");

        // Download
        const blob = new Blob([csvContent], {
          type: "text/csv;charset=utf-8;",
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute(
          "download",
          `${activeTab}_export_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
    } catch (err) {
      console.error("Export error:", err);
      alert("Failed to export data.");
    }
  };

  return (
    <div>
      {/* Header */}
      <div className="mb-4 flex justify-between items-center">
        <div>
          <p className="text-2xl font-bold text-gray-800">Transactions</p>
          <p className="text-gray-500">
            Manage all financial transactions and payouts
          </p>
        </div>
        <button
          onClick={handleExport}
          className="bg-[#2C9139] px-4 py-2 flex rounded-full items-center text-white text-sm font-bold active:scale-95 transition-transform"
        >
          <BiDownload className="mr-2 text-[16px]" />
          Export CSV
        </button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-3 gap-4 mb-6">
        {/* total_balance_volume: The total money currently held in system wallets */}
        <div className="border border-gray-200 rounded-xl p-6 bg-white shadow-sm">
          <p className="text-3xl font-bold text-gray-800">
            ₦{stats.total_balance_volume.toLocaleString()}
          </p>
          <p className="text-xs text-gray-600 font-bold uppercase tracking-wider">
            Wallet Volume
          </p>
        </div>

        {/* total_withdrawal: Cleared/Finished withdrawals */}
        <div className="border border-gray-200 rounded-xl p-6 bg-white shadow-sm">
          <p className="text-3xl font-bold text-gray-800">
            ₦{stats.total_withdrawal.toLocaleString()}
          </p>
          <p className="text-xs text-gray-600 font-bold uppercase tracking-wider">
            Processed Withdrawals
          </p>
        </div>

        {/* total_settlement: Vendor payouts that have been completed */}
        <div className="border border-gray-200 rounded-xl p-6 bg-white shadow-sm">
          <p className="text-3xl font-bold text-[#1F6728]">
            ₦{stats.total_settlement.toLocaleString()}
          </p>
          <p className="text-xs text-gray-600 font-bold uppercase tracking-wider">
            Completed Settlements
          </p>
        </div>
      </div>

      {/* Tab Switcher */}
      <div className="flex items-center border-b border-gray-200 px-4 mb-4">
        <button
          onClick={() => setActiveTab("transactions")}
          className={`px-4 py-2 font-semibold transition-all ${
            activeTab === "transactions"
              ? "border-b-2 border-[#1F6728] text-[#1F6728]"
              : "text-gray-500 hover:text-gray-700"
          }`}
        >
          Transactions
        </button>
        <button
          onClick={() => setActiveTab("subscriptions")}
          className={`px-4 py-2 font-semibold transition-all ${
            activeTab === "subscriptions"
              ? "border-b-2 border-[#1F6728] text-[#1F6728]"
              : "text-gray-500 hover:text-gray-700"
          }`}
        >
          Subscriptions
        </button>
      </div>

      {/* Filters */}
      <div className="flex justify-between items-center px-4 mb-4 gap-4">
        <input
          type="text"
          placeholder="Search transactions..."
          value={inputValue}
          onChange={(e) => setInputValue(e.target.value)}
          className="border border-gray-300 rounded-full px-5 py-2 text-sm w-1/2 focus:outline-none focus:ring-2 focus:ring-[#1F6728]"
        />

        <div className="flex gap-3">
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="border border-gray-300 rounded-full px-4 py-2 text-sm outline-none"
          >
            <option value="All">All Status</option>
            <option value="SUCCESSFUL">SUCCESSFUL</option>
            <option value="PENDING">PENDING</option>
            <option value="FAILED">FAILED</option>
          </select>

          {/* Tier filter only active for subscriptions/vendors */}
          <select
            value={tierFilter}
            onChange={(e) => setTierFilter(e.target.value)}
            disabled={activeTab === "transactions"}
            className={`border border-gray-300 rounded-full px-4 py-2 text-sm outline-none ${
              activeTab === "transactions"
                ? "opacity-50 cursor-not-allowed"
                : ""
            }`}
          >
            <option value="All">All Tiers</option>
            <option value="Tier 1">Tier 1</option>
            <option value="Tier 2">Tier 2</option>
          </select>
        </div>
      </div>

      {/* Dynamic Table Rendering */}
      <div className="mt-4">
        {activeTab === "transactions" ? (
          <WalletTransactions
            searchTerm={searchTerm}
            statusFilter={statusFilter}
          />
        ) : (
          <Subscriptions
            searchTerm={searchTerm}
            statusFilter={statusFilter}
            tierFilter={tierFilter}
          />
        )}
      </div>
    </div>
  );
};

export default Transactions;
