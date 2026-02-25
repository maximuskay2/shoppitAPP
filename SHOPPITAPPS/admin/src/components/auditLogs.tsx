import { useCallback, useEffect, useState } from "react";
import { BiDownload } from "react-icons/bi";
import { apiUrl } from "../lib/api";

type AuditLog = {
  id: string;
  actor_id?: string | null;
  actor_type?: string | null;
  action: string;
  auditable_type: string;
  auditable_id: string;
  ip_address?: string | null;
  user_agent?: string | null;
  meta?: Record<string, any> | null;
  created_at: string;
};

const AuditLogs = () => {
  const [logs, setLogs] = useState<AuditLog[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);

  const [actionFilter, setActionFilter] = useState("");
  const [actorIdFilter, setActorIdFilter] = useState("");
  const [auditableTypeFilter, setAuditableTypeFilter] = useState("");
  const [auditableIdFilter, setAuditableIdFilter] = useState("");

  const fetchLogs = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");

    const params = new URLSearchParams({
      page: currentPage.toString(),
      per_page: "20",
    });

    if (actionFilter) params.append("action", actionFilter);
    if (actorIdFilter) params.append("actor_id", actorIdFilter);
    if (auditableTypeFilter)
      params.append("auditable_type", auditableTypeFilter);
    if (auditableIdFilter) params.append("auditable_id", auditableIdFilter);

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/audits?${params.toString()}`),
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
        setLogs(data?.data || []);
        setCurrentPage(data?.current_page || 1);
        setLastPage(data?.last_page || 1);
        setTotal(data?.total || 0);
      }
    } catch (err) {
      console.error("Audit log fetch error:", err);
    } finally {
      setLoading(false);
    }
  }, [
    actionFilter,
    actorIdFilter,
    auditableIdFilter,
    auditableTypeFilter,
    currentPage,
  ]);

  useEffect(() => {
    fetchLogs();
  }, [fetchLogs]);

  const handleExportCSV = () => {
    if (logs.length === 0) return alert("No audit logs to export.");
    const headers = [
      "id",
      "action",
      "actor_id",
      "actor_type",
      "auditable_type",
      "auditable_id",
      "ip_address",
      "created_at",
    ];
    const rows = logs.map((log) =>
      headers.map((key) => `"${(log as any)[key] ?? ""}"`).join(",")
    );
    const csvContent = [headers.join(","), ...rows].join("\n");
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute(
      "download",
      `audit_logs_${new Date().toISOString().split("T")[0]}.csv`
    );
    link.click();
  };

  return (
    <div>
      <div className="flex justify-between items-start mb-4">
        <div>
          <p className="text-2xl font-bold text-gray-800">Audit Logs</p>
          <p className="text-gray-500">
            Track admin actions and system changes
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

      <div className="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <input
          type="text"
          placeholder="Action"
          className="border border-gray-300 rounded-full px-4 py-2 text-sm"
          value={actionFilter}
          onChange={(e) => {
            setActionFilter(e.target.value);
            setCurrentPage(1);
          }}
        />
        <input
          type="text"
          placeholder="Actor ID"
          className="border border-gray-300 rounded-full px-4 py-2 text-sm"
          value={actorIdFilter}
          onChange={(e) => {
            setActorIdFilter(e.target.value);
            setCurrentPage(1);
          }}
        />
        <input
          type="text"
          placeholder="Auditable Type"
          className="border border-gray-300 rounded-full px-4 py-2 text-sm"
          value={auditableTypeFilter}
          onChange={(e) => {
            setAuditableTypeFilter(e.target.value);
            setCurrentPage(1);
          }}
        />
        <input
          type="text"
          placeholder="Auditable ID"
          className="border border-gray-300 rounded-full px-4 py-2 text-sm"
          value={auditableIdFilter}
          onChange={(e) => {
            setAuditableIdFilter(e.target.value);
            setCurrentPage(1);
          }}
        />
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
              <th className="py-3 px-2">Action</th>
              <th className="py-3">Actor ID</th>
              <th className="py-3">Auditable</th>
              <th className="py-3">IP</th>
              <th className="py-3">Date</th>
              <th className="py-3">Meta</th>
            </tr>
          </thead>
          <tbody>
            {logs.map((log) => (
              <tr
                key={log.id}
                className="hover:bg-gray-50 border-b border-gray-50"
              >
                <td className="py-3 px-2 font-semibold text-gray-800">
                  {log.action}
                </td>
                <td className="py-3 font-mono text-xs">{log.actor_id || "-"}</td>
                <td className="py-3">
                  <p className="text-xs text-gray-500">{log.auditable_type}</p>
                  <p className="text-xs font-mono">{log.auditable_id}</p>
                </td>
                <td className="py-3 text-xs">{log.ip_address || "-"}</td>
                <td className="py-3 text-xs">
                  {new Date(log.created_at).toLocaleString()}
                </td>
                <td className="py-3 text-xs">
                  {log.meta ? (
                    <details>
                      <summary className="cursor-pointer text-[#1F6728]">
                        View
                      </summary>
                      <pre className="text-[10px] whitespace-pre-wrap mt-2">
                        {JSON.stringify(log.meta, null, 2)}
                      </pre>
                    </details>
                  ) : (
                    "-"
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        <div className="flex justify-between items-center mt-6">
          <p className="text-gray-400">
            Showing {logs.length} of {total} logs
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
    </div>
  );
};

export default AuditLogs;
