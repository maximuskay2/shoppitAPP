import { useCallback, useEffect, useState } from "react";
import { BiLoaderAlt } from "react-icons/bi";
import { apiUrl } from "../lib/api";

type RefundOrder = {
  id: string;
  status: string;
  refund_status?: string | null;
  refund_reason?: string | null;
  refund_requested_at?: string | null;
  refund_processed_at?: string | null;
  user?: {
    id: string;
    name: string;
    email: string;
  };
  vendor?: {
    id: string;
    business_name?: string | null;
    user?: {
      id: string;
      name: string;
      email: string;
    };
  };
  created_at?: string;
};

const Refunds = () => {
  const [refunds, setRefunds] = useState<RefundOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [statusFilter, setStatusFilter] = useState("");
  const [search, setSearch] = useState("");

  const fetchRefunds = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    const params = new URLSearchParams({
      page: currentPage.toString(),
      per_page: "20",
    });
    if (statusFilter) params.append("status", statusFilter);
    if (search) params.append("search", search);

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/refunds?${params.toString()}`),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();
      if (result.success) {
        const data = result.data;
        setRefunds(data?.data || []);
        setCurrentPage(data?.current_page || 1);
        setLastPage(data?.last_page || 1);
        setTotal(data?.total || 0);
      }
    } catch (err) {
      console.error("Refunds fetch error:", err);
    } finally {
      setLoading(false);
    }
  }, [currentPage, search, statusFilter]);

  useEffect(() => {
    fetchRefunds();
  }, [fetchRefunds]);

  const handleApprove = async (id: string) => {
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(apiUrl(`/api/v1/admin/refunds/${id}/approve`), {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      const result = await response.json();
      if (result.success) {
        fetchRefunds();
      } else {
        alert(result.message || "Failed to approve refund.");
      }
    } catch (err) {
      console.error("Refund approve error:", err);
      alert("Network error.");
    }
  };

  const handleReject = async (id: string) => {
    const reason = window.prompt("Reason for rejection?");
    if (reason === null) return;
    const token = localStorage.getItem("token");
    try {
      const response = await fetch(apiUrl(`/api/v1/admin/refunds/${id}/reject`), {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ reason: reason.trim() || null }),
      });
      const result = await response.json();
      if (result.success) {
        fetchRefunds();
      } else {
        alert(result.message || "Failed to reject refund.");
      }
    } catch (err) {
      console.error("Refund reject error:", err);
      alert("Network error.");
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-800">Refunds</h2>
          <p className="text-gray-500">Review and action refund requests.</p>
        </div>
      </div>

      <div className="flex items-center gap-3 mb-6">
        <input
          type="text"
          placeholder="Search by order ID or customer..."
          className="flex-1 border border-gray-300 rounded-full px-5 py-2 text-sm focus:ring-2 focus:ring-[#1F6728] outline-none"
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setCurrentPage(1);
          }}
        />
        <select
          className="border border-gray-300 rounded-full px-4 py-2 text-sm"
          value={statusFilter}
          onChange={(e) => {
            setStatusFilter(e.target.value);
            setCurrentPage(1);
          }}
        >
          <option value="">All statuses</option>
          <option value="REQUESTED">Requested</option>
          <option value="APPROVED">Approved</option>
          <option value="REJECTED">Rejected</option>
        </select>
      </div>

      <div className="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden relative">
        {loading && (
          <div className="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-20">
            <BiLoaderAlt className="animate-spin text-2xl text-[#1F6728]" />
          </div>
        )}

        <table className="w-full text-left text-sm">
          <thead className="bg-gray-50 text-gray-400 text-xs uppercase font-medium">
            <tr>
              <th className="px-6 py-4">Order</th>
              <th className="px-6 py-4">Customer</th>
              <th className="px-6 py-4">Status</th>
              <th className="px-6 py-4">Refund Status</th>
              <th className="px-6 py-4">Requested</th>
              <th className="px-6 py-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            {refunds.length > 0 ? (
              refunds.map((refund) => (
                <tr
                  key={refund.id}
                  className="border-t border-gray-100 hover:bg-gray-50 transition"
                >
                  <td className="px-6 py-4 font-semibold text-gray-800">
                    {refund.id.slice(0, 8).toUpperCase()}
                  </td>
                  <td className="px-6 py-4 text-gray-600">
                    {refund.user?.name || refund.user?.email || "-"}
                  </td>
                  <td className="px-6 py-4">
                    <span className="px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-600">
                      {refund.status}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <span className="px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-yellow-100 text-yellow-700">
                      {refund.refund_status || "NONE"}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-gray-500">
                    {refund.refund_requested_at
                      ? new Date(refund.refund_requested_at).toLocaleString()
                      : "-"}
                  </td>
                  <td className="px-6 py-4 text-center">
                    <div className="flex justify-center gap-3">
                      <button
                        className="text-xs bg-green-100 text-green-700 px-3 py-1 rounded"
                        onClick={() => handleApprove(refund.id)}
                        disabled={refund.refund_status === "APPROVED"}
                      >
                        Approve
                      </button>
                      <button
                        className="text-xs bg-red-100 text-red-700 px-3 py-1 rounded"
                        onClick={() => handleReject(refund.id)}
                        disabled={refund.refund_status === "REJECTED"}
                      >
                        Reject
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td
                  colSpan={6}
                  className="text-center py-20 text-gray-400 italic"
                >
                  No refund requests found.
                </td>
              </tr>
            )}
          </tbody>
        </table>

        <div className="px-6 py-4 bg-gray-50 flex justify-between items-center border-t border-gray-100 text-gray-500 text-xs">
          <p>
            Showing{" "}
            <span className="font-semibold text-gray-700">{refunds.length}</span>{" "}
            of <span className="font-semibold text-gray-700">{total}</span>{" "}
            requests
          </p>
          <div className="flex items-center space-x-2">
            <button
              disabled={currentPage === 1}
              onClick={() => setCurrentPage((p) => p - 1)}
              className="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <div className="bg-[#1F6728] text-white px-4 py-2 rounded-md font-bold">
              {currentPage}
            </div>
            <button
              disabled={currentPage === lastPage}
              onClick={() => setCurrentPage((p) => p + 1)}
              className="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Refunds;
