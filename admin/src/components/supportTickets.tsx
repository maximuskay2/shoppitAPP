import { useCallback, useEffect, useState } from "react";
import { BiDownload } from "react-icons/bi";
import { apiUrl } from "../lib/api";

type SupportTicket = {
  id: string;
  driver_id: string;
  subject: string;
  message: string;
  status: string;
  priority: string;
  meta?: Record<string, any> | null;
  created_at: string;
  resolved_at?: string | null;
  driver?: {
    id: string;
    name: string;
    email: string;
    phone: string;
  };
};

const SupportTickets = () => {
  const [tickets, setTickets] = useState<SupportTicket[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [priorityFilter, setPriorityFilter] = useState("");

  const [selectedTicket, setSelectedTicket] = useState<SupportTicket | null>(null);
  const [updating, setUpdating] = useState(false);
  const [responseText, setResponseText] = useState("");
  const [statusUpdate, setStatusUpdate] = useState("");
  const [priorityUpdate, setPriorityUpdate] = useState("");

  const fetchTickets = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    const params = new URLSearchParams({
      page: currentPage.toString(),
      per_page: "20",
    });

    if (search) params.append("search", search);
    if (statusFilter) params.append("status", statusFilter);
    if (priorityFilter) params.append("priority", priorityFilter);

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/support-tickets?${params.toString()}`),
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
        setTickets(data?.data || []);
        setCurrentPage(data?.current_page || 1);
        setLastPage(data?.last_page || 1);
        setTotal(data?.total || 0);
      }
    } catch (err) {
      console.error("Support tickets fetch error:", err);
    } finally {
      setLoading(false);
    }
  }, [currentPage, priorityFilter, search, statusFilter]);

  useEffect(() => {
    fetchTickets();
  }, [fetchTickets]);

  useEffect(() => {
    if (!selectedTicket) return;
    setResponseText(selectedTicket.meta?.admin_response || "");
    setStatusUpdate(selectedTicket.status || "OPEN");
    setPriorityUpdate(selectedTicket.priority || "NORMAL");
  }, [selectedTicket]);

  const updateTicket = async () => {
    if (!selectedTicket) return;
    setUpdating(true);
    const token = localStorage.getItem("token");

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/support-tickets/${selectedTicket.id}`),
        {
          method: "PUT",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            status: statusUpdate,
            priority: priorityUpdate,
            response: responseText || null,
          }),
        }
      );

      const result = await response.json();
      if (result.success) {
        await fetchTickets();
        setSelectedTicket(result.data);
      } else {
        alert(result.message || "Failed to update ticket.");
      }
    } catch (err) {
      console.error("Support ticket update error:", err);
      alert("Network error. Please try again.");
    } finally {
      setUpdating(false);
    }
  };

  const handleExportCSV = () => {
    if (tickets.length === 0) return alert("No tickets to export.");
    const headers = [
      "id",
      "subject",
      "status",
      "priority",
      "driver_id",
      "created_at",
      "resolved_at",
    ];
    const rows = tickets.map((ticket) =>
      headers.map((key) => `"${(ticket as any)[key] ?? ""}"`).join(",")
    );
    const csvContent = [headers.join(","), ...rows].join("\n");
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute(
      "download",
      `support_tickets_${new Date().toISOString().split("T")[0]}.csv`
    );
    link.click();
  };

  return (
    <div>
      <div className="flex justify-between items-start mb-4">
        <div>
          <p className="text-2xl font-bold text-gray-800">Support Tickets</p>
          <p className="text-gray-500">
            Manage driver support requests and follow ups
          </p>
        </div>
        <button
          onClick={handleExportCSV}
          className="bg-[#2C9139] flex gap-2 items-center justify-between text-white px-5 py-2 rounded-full font-bold text-sm shadow-lg shadow-green-900/20 hover:bg-[#185321] transition active:scale-95"
        >
          <BiDownload className="text-lg" />
          Export CSV
        </button>
      </div>

      <div className="flex flex-wrap items-center gap-3 mb-4">
        <input
          type="text"
          placeholder="Search subject or message"
          className="border border-gray-300 rounded-full px-4 py-2 text-sm w-full md:w-72"
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
          <option value="">All Status</option>
          <option value="OPEN">OPEN</option>
          <option value="IN_PROGRESS">IN_PROGRESS</option>
          <option value="RESOLVED">RESOLVED</option>
          <option value="CLOSED">CLOSED</option>
        </select>
        <select
          className="border border-gray-300 rounded-full px-4 py-2 text-sm"
          value={priorityFilter}
          onChange={(e) => {
            setPriorityFilter(e.target.value);
            setCurrentPage(1);
          }}
        >
          <option value="">All Priority</option>
          <option value="LOW">LOW</option>
          <option value="NORMAL">NORMAL</option>
          <option value="HIGH">HIGH</option>
        </select>
      </div>

      <div className="border border-gray-200 p-4 rounded-xl bg-white relative">
        {loading && (
          <div className="relative w-full h-[40vh] flex items-center justify-center">
            <div className="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-[#1F6728]"></div>
          </div>
        )}
        <table className="w-full text-left text-sm text-gray-600">
          <thead>
            <tr className="text-gray-400 text-[10px] font-bold uppercase border-b border-gray-100">
              <th className="py-3 px-2">Subject</th>
              <th className="py-3">Driver</th>
              <th className="py-3">Status</th>
              <th className="py-3">Priority</th>
              <th className="py-3">Date</th>
              <th className="py-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            {tickets.map((ticket) => (
              <tr
                key={ticket.id}
                className="hover:bg-gray-50 border-b border-gray-50"
              >
                <td className="py-4 px-2 font-medium text-gray-800">
                  {ticket.subject}
                </td>
                <td className="py-4 text-gray-500">
                  {ticket.driver?.name || "Unknown"}
                </td>
                <td className="py-4 text-xs font-bold">
                  <span className="px-3 py-1 rounded-full bg-blue-100 text-blue-700">
                    {ticket.status}
                  </span>
                </td>
                <td className="py-4 text-xs font-bold">
                  <span className="px-3 py-1 rounded-full bg-gray-100 text-gray-700">
                    {ticket.priority}
                  </span>
                </td>
                <td className="py-4 text-gray-400">
                  {new Date(ticket.created_at).toLocaleDateString()}
                </td>
                <td className="py-4 text-center">
                  <button
                    onClick={() => setSelectedTicket(ticket)}
                    className="text-[#1F6728] font-semibold text-xs"
                  >
                    View
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="flex justify-between items-center mt-6">
          <p className="text-gray-400">
            Showing {tickets.length} of {total} tickets
          </p>
          <div className="flex gap-2">
            <button
              disabled={currentPage === 1}
              onClick={() => setCurrentPage((p) => p - 1)}
              className="px-4 py-1 border rounded-full disabled:opacity-30"
            >
              Prev
            </button>
            <span className="px-4 py-1 bg-[#1F6728] text-white rounded-full font-bold">
              {currentPage}
            </span>
            <button
              disabled={currentPage === lastPage}
              onClick={() => setCurrentPage((p) => p + 1)}
              className="px-4 py-1 border rounded-full disabled:opacity-30"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      {selectedTicket && (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-[60] px-4 backdrop-blur-sm">
          <div className="bg-white w-full max-w-2xl rounded-2xl shadow-xl p-8 relative max-h-[90vh] overflow-y-auto">
            <button
              className="absolute top-6 right-6 text-gray-400"
              onClick={() => setSelectedTicket(null)}
            >
              ✕
            </button>
            <h3 className="text-2xl font-black mb-6">Ticket Details</h3>

            <div className="space-y-5">
              <div className="bg-gray-50 p-4 rounded-xl">
                <p className="text-[10px] font-bold text-gray-400 uppercase mb-2">
                  Subject
                </p>
                <p className="font-bold">{selectedTicket.subject}</p>
                <p className="text-xs text-gray-500 mt-2">
                  Driver: {selectedTicket.driver?.name || "Unknown"} (
                  {selectedTicket.driver?.email || "-"})
                </p>
              </div>
              <div>
                <p className="text-[10px] font-bold text-gray-400 uppercase mb-2">
                  Message
                </p>
                <p className="text-sm text-gray-700 whitespace-pre-wrap">
                  {selectedTicket.message}
                </p>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label className="text-xs text-gray-500">Status</label>
                  <select
                    className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                    value={statusUpdate}
                    onChange={(e) => setStatusUpdate(e.target.value)}
                  >
                    <option value="OPEN">OPEN</option>
                    <option value="IN_PROGRESS">IN_PROGRESS</option>
                    <option value="RESOLVED">RESOLVED</option>
                    <option value="CLOSED">CLOSED</option>
                  </select>
                </div>
                <div>
                  <label className="text-xs text-gray-500">Priority</label>
                  <select
                    className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                    value={priorityUpdate}
                    onChange={(e) => setPriorityUpdate(e.target.value)}
                  >
                    <option value="LOW">LOW</option>
                    <option value="NORMAL">NORMAL</option>
                    <option value="HIGH">HIGH</option>
                  </select>
                </div>
              </div>
              <div>
                <label className="text-xs text-gray-500">Admin response</label>
                <textarea
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                  rows={4}
                  value={responseText}
                  onChange={(e) => setResponseText(e.target.value)}
                />
              </div>
              <button
                onClick={updateTicket}
                disabled={updating}
                className="w-full bg-[#1F6728] text-white py-3 rounded-full font-bold text-sm disabled:opacity-60"
              >
                {updating ? "Updating..." : "Save changes"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SupportTickets;
