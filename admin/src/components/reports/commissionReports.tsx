import { useState, useEffect, useCallback } from "react";
import { apiUrl } from "../../lib/api";

interface CommissionData {
  vendor_id: string;
  vendor_name: string;
  total_orders: number;
  total_sales: number;
  commission_rate: number;
  commission_earned: number;
  pending_payout: number;
  last_payout_date: string;
}

interface ReportSummary {
  total_sales: number;
  total_commission: number;
  total_vendors: number;
  pending_payouts: number;
}

const CommissionReports = () => {
  const [commissions, setCommissions] = useState<CommissionData[]>([]);
  const [summary, setSummary] = useState<ReportSummary | null>(null);
  const [loading, setLoading] = useState(true);
  const [dateRange, setDateRange] = useState({
    start_date: new Date(new Date().setDate(1)).toISOString().split("T")[0],
    end_date: new Date().toISOString().split("T")[0],
  });
  const [sortBy, setSortBy] = useState("commission_earned");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");

  const fetchCommissions = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const params = new URLSearchParams({
        start_date: dateRange.start_date,
        end_date: dateRange.end_date,
        sort_by: sortBy,
        sort_order: sortOrder,
      });
      const response = await fetch(apiUrl(`/api/v1/admin/reports/commissions?${params}`), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      const result = await response.json();
      if (result.success) {
        setCommissions(result.data.commissions || []);
        setSummary(result.data.summary || null);
      }
    } catch (err) {
      console.error("Failed to fetch commissions:", err);
    } finally {
      setLoading(false);
    }
  }, [dateRange, sortBy, sortOrder]);

  useEffect(() => {
    fetchCommissions();
  }, [fetchCommissions]);

  const exportReport = async (format: "csv" | "pdf") => {
    const token = localStorage.getItem("token");
    try {
      const params = new URLSearchParams({
        start_date: dateRange.start_date,
        end_date: dateRange.end_date,
        format,
      });
      const response = await fetch(apiUrl(`/api/v1/admin/reports/commissions/export?${params}`), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: format === "csv" ? "text/csv" : "application/pdf",
        },
      });
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `commission_report_${dateRange.start_date}_${dateRange.end_date}.${format}`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      console.error("Failed to export report:", err);
      alert("Failed to export report");
    }
  };

  const handleSort = (column: string) => {
    if (sortBy === column) {
      setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    } else {
      setSortBy(column);
      setSortOrder("desc");
    }
  };

  const SortIcon = ({ column }: { column: string }) => {
    if (sortBy !== column) return <span className="text-gray-300 ml-1">↕</span>;
    return <span className="ml-1">{sortOrder === "asc" ? "↑" : "↓"}</span>;
  };

  return (
    <div className="p-6">
      <div className="flex justify-between items-start mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-800">Commission Reports</h2>
          <p className="text-gray-500 text-sm">View and export vendor commission data</p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={() => exportReport("csv")}
            className="border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 text-sm"
          >
            Export CSV
          </button>
          <button
            onClick={() => exportReport("pdf")}
            className="border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 text-sm"
          >
            Export PDF
          </button>
        </div>
      </div>

      {/* Date Range Filter */}
      <div className="bg-white border rounded-lg p-4 mb-6">
        <div className="flex flex-wrap gap-4 items-end">
          <div>
            <label className="block text-sm font-medium text-gray-600 mb-1">Start Date</label>
            <input
              type="date"
              value={dateRange.start_date}
              onChange={(e) => setDateRange({ ...dateRange, start_date: e.target.value })}
              className="border rounded px-3 py-2"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-600 mb-1">End Date</label>
            <input
              type="date"
              value={dateRange.end_date}
              onChange={(e) => setDateRange({ ...dateRange, end_date: e.target.value })}
              className="border rounded px-3 py-2"
            />
          </div>
          <button
            onClick={fetchCommissions}
            className="bg-[#1F6728] text-white px-4 py-2 rounded hover:bg-green-700"
          >
            Apply Filter
          </button>
        </div>
      </div>

      {/* Summary Cards */}
      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white border rounded-lg p-4">
            <div className="text-sm text-gray-500">Total Sales</div>
            <div className="text-2xl font-bold text-gray-800">
              ₦{summary.total_sales?.toLocaleString()}
            </div>
          </div>
          <div className="bg-white border rounded-lg p-4">
            <div className="text-sm text-gray-500">Total Commission</div>
            <div className="text-2xl font-bold text-[#1F6728]">
              ₦{summary.total_commission?.toLocaleString()}
            </div>
          </div>
          <div className="bg-white border rounded-lg p-4">
            <div className="text-sm text-gray-500">Active Vendors</div>
            <div className="text-2xl font-bold text-gray-800">{summary.total_vendors}</div>
          </div>
          <div className="bg-white border rounded-lg p-4">
            <div className="text-sm text-gray-500">Pending Payouts</div>
            <div className="text-2xl font-bold text-orange-500">
              ₦{summary.pending_payouts?.toLocaleString()}
            </div>
          </div>
        </div>
      )}

      {/* Commission Table */}
      {loading ? (
        <div className="flex justify-center py-10">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1F6728]"></div>
        </div>
      ) : commissions.length === 0 ? (
        <div className="text-center py-10 bg-gray-50 rounded-lg">
          <div className="text-gray-400 text-5xl mb-4">📊</div>
          <h3 className="text-lg font-semibold text-gray-600 mb-2">No commission data</h3>
          <p className="text-gray-500">No commissions found for the selected date range</p>
        </div>
      ) : (
        <div className="bg-white border rounded-lg overflow-hidden">
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-sm font-semibold text-gray-600">Vendor</th>
                <th
                  className="px-4 py-3 text-right text-sm font-semibold text-gray-600 cursor-pointer hover:bg-gray-100"
                  onClick={() => handleSort("total_orders")}
                >
                  Orders <SortIcon column="total_orders" />
                </th>
                <th
                  className="px-4 py-3 text-right text-sm font-semibold text-gray-600 cursor-pointer hover:bg-gray-100"
                  onClick={() => handleSort("total_sales")}
                >
                  Sales <SortIcon column="total_sales" />
                </th>
                <th className="px-4 py-3 text-right text-sm font-semibold text-gray-600">Rate</th>
                <th
                  className="px-4 py-3 text-right text-sm font-semibold text-gray-600 cursor-pointer hover:bg-gray-100"
                  onClick={() => handleSort("commission_earned")}
                >
                  Commission <SortIcon column="commission_earned" />
                </th>
                <th
                  className="px-4 py-3 text-right text-sm font-semibold text-gray-600 cursor-pointer hover:bg-gray-100"
                  onClick={() => handleSort("pending_payout")}
                >
                  Pending <SortIcon column="pending_payout" />
                </th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {commissions.map((item) => (
                <tr key={item.vendor_id} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <div className="font-medium">{item.vendor_name}</div>
                    <div className="text-xs text-gray-500">
                      Last payout:{" "}
                      {item.last_payout_date
                        ? new Date(item.last_payout_date).toLocaleDateString()
                        : "Never"}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-right">{item.total_orders}</td>
                  <td className="px-4 py-3 text-right">₦{item.total_sales?.toLocaleString()}</td>
                  <td className="px-4 py-3 text-right">{item.commission_rate}%</td>
                  <td className="px-4 py-3 text-right font-semibold text-[#1F6728]">
                    ₦{item.commission_earned?.toLocaleString()}
                  </td>
                  <td className="px-4 py-3 text-right">
                    {item.pending_payout > 0 ? (
                      <span className="text-orange-500 font-medium">
                        ₦{item.pending_payout?.toLocaleString()}
                      </span>
                    ) : (
                      <span className="text-gray-400">-</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-gray-50 font-semibold">
              <tr>
                <td className="px-4 py-3">Total</td>
                <td className="px-4 py-3 text-right">
                  {commissions.reduce((sum, c) => sum + c.total_orders, 0)}
                </td>
                <td className="px-4 py-3 text-right">
                  ₦{commissions.reduce((sum, c) => sum + c.total_sales, 0).toLocaleString()}
                </td>
                <td className="px-4 py-3 text-right">-</td>
                <td className="px-4 py-3 text-right text-[#1F6728]">
                  ₦{commissions.reduce((sum, c) => sum + c.commission_earned, 0).toLocaleString()}
                </td>
                <td className="px-4 py-3 text-right text-orange-500">
                  ₦{commissions.reduce((sum, c) => sum + c.pending_payout, 0).toLocaleString()}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      )}
    </div>
  );
};

export default CommissionReports;
