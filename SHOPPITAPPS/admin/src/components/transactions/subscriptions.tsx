import { useState, useEffect, useCallback } from "react";
import { apiUrl } from "../../lib/api";

interface VendorPaymentsProps {
  searchTerm: string;
  statusFilter: string;
  tierFilter: string; // Used as the 'search' parameter in this API logic
}

type SubscriptionData = {
  id: string;
  user: string;
  user_email: string;
  tier: string;
  plan: string;
  amount: number;
  status: string;
  starts_at: string;
  ends_at: string;
  created_at: string;
};

const Subscriptions = ({
  searchTerm,
  statusFilter,
  tierFilter,
}: VendorPaymentsProps) => {
  const [subscriptions, setSubscriptions] = useState<SubscriptionData[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);

  const fetchSubscriptions = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");

    // Construct Query Params based on API documentation
    // Note: Documentation suggests 'plan' can be monthly/annual
    const params = new URLSearchParams({
      page: currentPage.toString(),
    });

    if (searchTerm) params.append("search", searchTerm);
    if (statusFilter !== "All") params.append("status", statusFilter);

    // If tierFilter is used to filter by Tier 1, Tier 2, etc.
    if (tierFilter !== "All") params.append("search", tierFilter);

    try {
      const response = await fetch(
        apiUrl(
          `/api/v1/admin/subscription-management?${params.toString()}`
        ),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );
      const result = await response.json();

      if (result.success) {
        setSubscriptions(result.data.data);
        setLastPage(result.data.last_page);
        setTotal(result.data.total);
      }
    } catch (err) {
      console.error("Subscription fetch error:", err);
    } finally {
      setLoading(false);
    }
  }, [currentPage, searchTerm, statusFilter, tierFilter]);

  useEffect(() => {
    fetchSubscriptions();
  }, [fetchSubscriptions]);

  return (
    <div className="border border-gray-200 p-4 rounded-md bg-white relative">
      {loading && (
        <div className="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1F6728]"></div>
        </div>
      )}

      <table className="w-full text-left text-sm text-gray-600">
        <thead>
          <tr className="text-gray-400 text-xs uppercase border-b border-gray-100">
            <th className="py-2">Subscription ID</th>
            <th className="py-2">User</th>
            <th className="py-2">Tier</th>
            <th className="py-2">Plan</th>
            <th className="py-2">Amount</th>
            <th className="py-2">Status</th>
            <th className="py-2">Start Date</th>
            <th className="py-2">Next Billing</th>
          </tr>
        </thead>

        <tbody>
          {subscriptions.length > 0
            ? subscriptions.map((t) => (
                <tr
                  key={t.id}
                  className="hover:bg-gray-50 transition-colors border-b border-gray-50"
                >
                  <td className="py-3 font-mono text-[10px]">{t.id}</td>
                  <td className="py-3 font-semibold">
                    <div>{t.user}</div>
                    <div className="text-[10px] text-gray-400 font-normal">
                      {t.user_email}
                    </div>
                  </td>
                  <td className="py-3">
                    <span
                      className={`px-2 py-1 rounded-full text-[10px] font-bold ${
                        t.tier.includes("Tier 1")
                          ? "bg-gray-100 text-gray-700"
                          : "bg-blue-100 text-blue-700"
                      }`}
                    >
                      {t.tier}
                    </span>
                  </td>
                  <td className="py-3">
                    <span className="capitalize">{t.plan}</span>
                  </td>
                  <td className="py-3 font-bold">
                    ₦{t.amount.toLocaleString()}
                  </td>
                  <td className="py-3">
                    <span
                      className={`px-2 py-1 rounded-full text-[10px] font-bold ${
                        t.status === "ACTIVE"
                          ? "bg-green-100 text-green-700"
                          : "bg-yellow-100 text-yellow-700"
                      }`}
                    >
                      {t.status}
                    </span>
                  </td>
                  <td className="py-3 text-gray-400">{t.starts_at}</td>
                  <td className="py-3 text-gray-400">{t.ends_at}</td>
                </tr>
              ))
            : !loading && (
                <tr>
                  <td
                    colSpan={8}
                    className="text-center py-10 text-gray-400 italic"
                  >
                    No subscriptions found.
                  </td>
                </tr>
              )}
        </tbody>
      </table>

      {/* Pagination */}
      <div className="flex justify-between items-center mt-4 text-sm text-gray-600">
        <p>
          Showing {subscriptions.length} of {total} results
        </p>

        <div className="flex items-center space-x-2">
          <button
            className="disabled:opacity-30"
            disabled={currentPage === 1}
            onClick={() => setCurrentPage((p) => p - 1)}
          >
            Prev
          </button>
          <span className="px-3 py-1 bg-[#1F6728] text-white rounded-full font-semibold">
            {currentPage}
          </span>
          <button
            className="disabled:opacity-30"
            disabled={currentPage === lastPage}
            onClick={() => setCurrentPage((p) => p + 1)}
          >
            Next
          </button>
        </div>
      </div>
    </div>
  );
};

export default Subscriptions;
