import { useEffect, useMemo, useState } from "react";
import { BiDownload, BiLoaderAlt, BiWallet } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

type Payout = {
  id: string;
  driver_id: string;
  amount: number;
  currency: string;
  status: string;
  reference?: string | null;
  paid_at?: string | null;
  created_at?: string | null;
  driver?: {
    name: string;
    email: string;
    phone: string;
  } | null;
};

type PendingBalance = {
  driver_id: string;
  pending_amount: number;
  driver?: {
    name: string;
    email: string;
    phone: string;
  } | null;
};

const Payouts = () => {
  const [searchTerm, setSearchTerm] = useState("");
  const [payouts, setPayouts] = useState<Payout[]>([]);
  const [pendingBalances, setPendingBalances] = useState<PendingBalance[]>([]);
  const [loading, setLoading] = useState(true);
  const [processingId, setProcessingId] = useState<string | null>(null);
  const [reconcileSummary, setReconcileSummary] = useState<{
    paid_total: number;
    pending_total: number;
    paid_count: number;
    pending_count: number;
    last_paid_at?: string | null;
    currency?: string | null;
  } | null>(null);
  const [reconcileLoading, setReconcileLoading] = useState(false);

  const fetchPayouts = async () => {
    const token = localStorage.getItem("token");

    try {
      const response = await fetch(
        apiUrl("/api/v1/admin/payouts?include_pending=1"),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );

      const result = await response.json();
      if (result.success) {
        const payoutData = result.data?.payouts?.data || [];
        setPayouts(payoutData);
        setPendingBalances(result.data?.pending_balances || []);
      }
    } catch (err) {
      console.error("Failed to fetch payouts:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPayouts();
  }, []);

  const handleApprove = async (driverId: string) => {
    const token = localStorage.getItem("token");
    setProcessingId(driverId);

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/payouts/${driverId}/approve`),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ reference: null }),
        }
      );

      const result = await response.json();
      if (result.success) {
        await fetchPayouts();
      } else {
        alert(result.message || "Failed to approve payout");
      }
    } catch (err) {
      console.error("Failed to approve payout:", err);
      alert("Network error. Please try again.");
    } finally {
      setProcessingId(null);
    }
  };

  const handleExport = async () => {
    const token = localStorage.getItem("token");
    const params = new URLSearchParams();

    if (searchTerm) {
      params.append("search", searchTerm);
    }

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/payouts/export?${params.toString()}`),
        {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "text/csv",
          },
        }
      );

      if (!response.ok) {
        alert("Failed to export payouts.");
        return;
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `driver_payouts_${new Date()
        .toISOString()
        .split("T")[0]}.csv`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      console.error("Failed to export payouts:", err);
      alert("Network error. Please try again.");
    }
  };

  const handleReconcile = async () => {
    const token = localStorage.getItem("token");
    setReconcileLoading(true);

    try {
      const response = await fetch(apiUrl("/api/v1/admin/payouts/reconcile"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      const result = await response.json();
      if (result.success) {
        setReconcileSummary(result.data);
      } else {
        alert(result.message || "Failed to reconcile payouts");
      }
    } catch (err) {
      console.error("Failed to reconcile payouts:", err);
      alert("Network error. Please try again.");
    } finally {
      setReconcileLoading(false);
    }
  };

  const filteredPayouts = useMemo(() => {
    const term = searchTerm.toLowerCase();
    return payouts.filter(
      (p) =>
        p.driver?.name?.toLowerCase().includes(term) ||
        p.driver?.email?.toLowerCase().includes(term) ||
        p.driver?.phone?.includes(term)
    );
  }, [payouts, searchTerm]);

  const filteredPending = useMemo(() => {
    const term = searchTerm.toLowerCase();
    return pendingBalances.filter(
      (p) =>
        p.driver?.name?.toLowerCase().includes(term) ||
        p.driver?.email?.toLowerCase().includes(term) ||
        p.driver?.phone?.includes(term)
    );
  }, [pendingBalances, searchTerm]);

  const totals = useMemo(() => {
    const totalPaid = payouts
      .filter((p) => p.status === "PAID")
      .reduce((sum, p) => sum + p.amount, 0);
    const pendingTotal = pendingBalances.reduce(
      (sum, p) => sum + p.pending_amount,
      0
    );

    return { totalPaid, pendingTotal };
  }, [payouts, pendingBalances]);

  return (
    <div className="border border-gray-200 p-4 rounded-md bg-white">
      {/* Search */}
      <div className="flex justify-between items-center mb-6">
        <input
          type="text"
          placeholder="Search providers..."
          className="border border-gray-300 px-4 py-3 rounded-full w-full md:flex-1 mr-4"
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
        />
      </div>

      {/* Totals */}
      <div className="bg-gray-100 px-4 py-2 rounded-lg grid grid-cols-4">
        <p className="flex flex-col justify-start gap-2">
          <span>Total Payouts</span>
          <span>₦{totals.totalPaid.toLocaleString()}</span>
        </p>

        <p className="flex flex-col justify-start gap-2">
          <span>Pending Balances</span>
          <span>₦{totals.pendingTotal.toLocaleString()}</span>
        </p>

        <p className="flex flex-col justify-start gap-2">
          <span>Completed Payouts</span>
          <span>₦{totals.totalPaid.toLocaleString()}</span>
        </p>

        <p></p>
      </div>

      {/* Header */}
      <div className="flex px-4 py-4 justify-between items-center">
        <p>Driver Payouts</p>
        <div className="flex items-center gap-2">
          <button
            className="px-3 py-2 flex text-white items-center gap-2 justify-center bg-[#1F6728] rounded-full"
            onClick={fetchPayouts}
            disabled={loading}
          >
            <BiWallet className="text-[16px]" />
            Refresh
          </button>
          <button
            className="px-3 py-2 flex text-white items-center gap-2 justify-center bg-[#2C9139] rounded-full"
            onClick={handleExport}
          >
            <BiDownload className="text-[16px]" />
            Export
          </button>
          <button
            className="px-3 py-2 flex text-white items-center gap-2 justify-center bg-[#0F4C1F] rounded-full"
            onClick={handleReconcile}
            disabled={reconcileLoading}
          >
            {reconcileLoading ? "Reconciling..." : "Reconcile"}
          </button>
        </div>
      </div>

      {reconcileSummary && (
        <div className="bg-white border border-gray-200 rounded-lg px-4 py-3 mb-4 text-sm text-gray-600">
          <div className="flex flex-wrap gap-4">
            <span>
              Paid: ₦{reconcileSummary.paid_total.toLocaleString()} ({reconcileSummary.paid_count})
            </span>
            <span>
              Pending: ₦{reconcileSummary.pending_total.toLocaleString()} ({reconcileSummary.pending_count})
            </span>
            {reconcileSummary.last_paid_at && (
              <span>Last paid: {new Date(reconcileSummary.last_paid_at).toLocaleString()}</span>
            )}
          </div>
        </div>
      )}

      {loading && (
        <div className="flex items-center gap-2 text-gray-500 py-6">
          <BiLoaderAlt className="animate-spin" />
          <span>Loading payouts...</span>
        </div>
      )}

      {!loading && (
        <>
          {/* Pending Balances */}
          <div className="mb-6">
            <p className="text-sm font-semibold text-gray-700 mb-2">
              Pending Balances
            </p>
            {filteredPending.length === 0 && (
              <p className="text-sm text-gray-400">No pending balances.</p>
            )}
            {filteredPending.map((pending) => (
              <div
                key={pending.driver_id}
                className="grid grid-cols-4 rounded-lg items-center px-4 py-3 bg-gray-100 mb-3"
              >
                <p className="flex flex-col gap-1">
                  <span>{pending.driver?.name || "Unknown Driver"}</span>
                  <span className="text-xs text-gray-500">
                    {pending.driver?.email}
                  </span>
                </p>
                <p className="text-xs text-gray-600">{pending.driver?.phone}</p>
                <p className="text-sm font-medium">
                  ₦{pending.pending_amount.toLocaleString()}
                </p>
                <div className="flex justify-end">
                  <button
                    className="px-3 py-2 text-white bg-[#1F6728] rounded-full disabled:bg-gray-300"
                    onClick={() => handleApprove(pending.driver_id)}
                    disabled={processingId === pending.driver_id}
                  >
                    {processingId === pending.driver_id
                      ? "Processing..."
                      : "Approve"}
                  </button>
                </div>
              </div>
            ))}
          </div>

          {/* Payout History */}
          <div>
            <p className="text-sm font-semibold text-gray-700 mb-2">
              Payout History
            </p>
            {filteredPayouts.length === 0 && (
              <p className="text-sm text-gray-400">No payouts found.</p>
            )}
            {filteredPayouts.map((payout) => (
              <div
                key={payout.id}
                className="grid grid-cols-4 rounded-lg items-center px-4 py-3 bg-gray-100 mb-3"
              >
                <p className="flex flex-col gap-1">
                  <span>{payout.driver?.name || "Unknown Driver"}</span>
                  <span className="text-xs text-gray-500">
                    {payout.driver?.email}
                  </span>
                </p>
                <p className="text-xs text-gray-600">{payout.driver?.phone}</p>
                <p className="text-sm font-medium">
                  ₦{payout.amount.toLocaleString()} {payout.currency}
                </p>
                <p className="text-xs text-gray-500">
                  {payout.status}
                </p>
              </div>
            ))}
          </div>
        </>
      )}
    </div>
  );
};

export default Payouts;
