import { useState, useEffect } from "react";
import ReportsCharts from "./reportsCharts";
import { BiDownload, BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../lib/api";

const Reports = () => {
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState({
    revenue: 0,
    completedOrders: 0,
    refunds: 0,
    newUsers: 0,
  });

  useEffect(() => {
    const fetchReportData = async () => {
      const token = localStorage.getItem("token");
      const headers = {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      };

      try {
        const [orderStatsRes, reportsRes] = await Promise.all([
          fetch(
            apiUrl(
              "/api/v1/admin/order-management/fetch/stats?month=12&year=2025"
            ),
            { headers }
          ),
          fetch(
            apiUrl("/api/v1/admin/reports?year=2025"),
            { headers }
          ),
        ]);

        const orderData = await orderStatsRes.json();
        const reportData = await reportsRes.json();

        if (orderData.success && reportData.success) {
          // Find refunds in the status breakdown array
          const refundStats = orderData.data.status_breakdown.find(
            (s: any) => s.status === "REFUNDED"
          );

          setData({
            revenue: orderData.data.total_revenue || 0,
            completedOrders: orderData.data.total_completed_orders || 0,
            refunds: refundStats ? refundStats.count : 0,
            newUsers: reportData.data.new_users || 0,
          });
        }
      } catch (err) {
        console.error("Reports fetch error:", err);
      } finally {
        setLoading(false);
      }
    };

    fetchReportData();
  }, []);

  const handleExport = () => {
    // 1. Define CSV Headers and Data
    const headers = ["Report Metric", "Value", "Currency/Unit"];
    const rows = [
      ["Total Revenue", data.revenue, "NGN"],
      ["Orders Completed", data.completedOrders, "Units"],
      ["Refunds Issued", data.refunds, "Units"],
      ["New Users Joined", data.newUsers, "Users"],
      ["Report Generated", new Date().toLocaleDateString(), "Date"],
    ];

    // 2. Convert to CSV string
    const csvContent = [
      headers.join(","),
      ...rows.map((row) => row.join(",")),
    ].join("\n");

    // 3. Create a Blob and trigger download
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.setAttribute("href", url);
    link.setAttribute(
      "download",
      `Shopitplus_Report_${new Date().toISOString().split("T")[0]}.csv`
    );
    link.style.visibility = "hidden";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  if (loading) {
    return (
      <div className="flex h-96 items-center justify-center">
        <BiLoaderAlt className="animate-spin text-4xl text-[#1F6728]" />
      </div>
    );
  }

  return (
    <div className="animate-in fade-in duration-500">
      <div className="mb-6 flex justify-between px-4 items-center">
        <div>
          <p className="text-2xl font-bold text-gray-800">
            Reports & Analytics
          </p>
          <p className="text-gray-500 text-sm">
            Analyze financial performance and platform growth.
          </p>
        </div>
        <button
          onClick={handleExport}
          className="bg-[#2C9139] flex items-center gap-2 px-6 py-2.5 rounded-full text-sm text-white font-bold hover:bg-[#185321] transition-all shadow-lg active:scale-95"
        >
          <BiDownload className="text-lg" />
          Export CSV
        </button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-4">
        {/* Revenue */}
        <div className="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
          <p className="text-2xl font-black text-[#1F6728]">
            ₦{data.revenue.toLocaleString()}
          </p>
          <p className="font-bold text-[10px] text-gray-400 uppercase tracking-widest mt-1">
            Total Revenue (Dec)
          </p>
        </div>

        {/* Completed */}
        <div className="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
          <p className="text-2xl font-black text-gray-800">
            {data.completedOrders.toLocaleString()}
          </p>
          <p className="font-bold text-[10px] text-gray-400 uppercase tracking-widest mt-1">
            Orders Completed
          </p>
        </div>

        {/* Refunds */}
        <div className="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
          <p className="text-2xl font-black text-red-500">{data.refunds}</p>
          <p className="font-bold text-[10px] text-gray-400 uppercase tracking-widest mt-1">
            Refunds Issued
          </p>
        </div>

        {/* New Users */}
        <div className="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
          <p className="text-2xl font-black text-blue-600">
            {data.newUsers.toLocaleString()}
          </p>
          <p className="font-bold text-[10px] text-gray-400 uppercase tracking-widest mt-1">
            New Users Joined
          </p>
        </div>
      </div>

      <div className="mt-8">
        <ReportsCharts />
      </div>
    </div>
  );
};

export default Reports;
