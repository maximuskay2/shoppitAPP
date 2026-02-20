import { useState, useEffect, useCallback } from "react";
import { apiUrl } from "../../lib/api";

interface WalletProps {
  searchTerm: string;
  statusFilter: string;
}

type TransactionData = {
  transactionId: string;
  user: string;
  amount: number;
  type: string;
  status: string;
  date: string;
};

const WalletTransactions = ({ searchTerm, statusFilter }: WalletProps) => {
  const [transactions, setTransactions] = useState<TransactionData[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);

  const fetchTransactions = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");

    // Construct Query Params
    const statusQuery = statusFilter !== "All" ? `&status=${statusFilter}` : "";

    // FIX: Ensure 'search' matches your backend's expected key
    const searchQuery = searchTerm ? `&search=${searchTerm}` : "";

    try {
      const response = await fetch(
        apiUrl(
          `/api/v1/admin/transaction-management?page=${currentPage}${statusQuery}${searchQuery}`
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
        setTransactions(result.data.data); //
        setLastPage(result.data.last_page); //
        setTotal(result.data.total); //
      }
    } catch (err) {
      console.error("Fetch error:", err);
    } finally {
      setLoading(false);
    }
    // IMPORTANT: Ensure searchTerm is a dependency
  }, [currentPage, searchTerm, statusFilter]);

  useEffect(() => {
    fetchTransactions();
  }, [fetchTransactions]);

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
            <th className="py-2">Transaction ID</th>
            <th className="py-2">User</th>
            <th className="py-2">Type</th>
            <th className="py-2">Amount</th>
            <th className="py-2">Status</th>
            <th className="py-2">Date</th>
          </tr>
        </thead>

        <tbody>
          {transactions.map((t) => (
            <tr
              key={t.transactionId}
              className="hover:bg-gray-50 border-b border-gray-50"
            >
              <td className="py-3 font-mono text-[10px]">{t.transactionId}</td>
              <td className="py-3 font-semibold">{t.user}</td>
              <td className="py-3 text-xs text-gray-500">{t.type}</td>
              <td className="py-3 font-bold">₦{t.amount.toLocaleString()}</td>
              <td className="py-3">
                <span
                  className={`px-2 py-1 rounded-full text-[10px] font-bold ${
                    t.status === "SUCCESSFUL"
                      ? "bg-green-100 text-green-700"
                      : "bg-yellow-100 text-yellow-700"
                  }`}
                >
                  {t.status}
                </span>
              </td>
              <td className="py-3 text-gray-400 text-xs">{t.date}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {/* Pagination Controls */}
      <div className="flex justify-between items-center mt-4 text-sm text-gray-600">
        <p>
          Showing {transactions.length} of {total} results
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

export default WalletTransactions;
