import { useState } from "react";

type DeliveryStatus = "Completed" | "In Progress" | "Failed" | "Pending";

type Delivery = {
  deliveryId: string;
  orderId: string;
  customer: string;
  agent: string;
  status: DeliveryStatus;
  fee: string;
  date: string;
};

const DeliveriesTable = ({ deliveries }: { deliveries: Delivery[] }) => {
  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("All");
  const [currentPage, setCurrentPage] = useState(1);

  const itemsPerPage = 5;

  // FILTER LOGIC
  const filteredDeliveries = deliveries.filter((d) => {
    const matchesSearch =
      d.customer.toLowerCase().includes(searchTerm.toLowerCase()) ||
      d.orderId.toLowerCase().includes(searchTerm.toLowerCase()) ||
      d.deliveryId.toLowerCase().includes(searchTerm.toLowerCase());

    const matchesStatus = statusFilter === "All" || d.status === statusFilter;

    return matchesSearch && matchesStatus;
  });

  const statusColors: Record<DeliveryStatus, string> = {
    Completed: "bg-green-100 text-green-700",
    "In Progress": "bg-purple-100 text-purple-700",
    Failed: "bg-red-100 text-red-700",
    Pending: "bg-yellow-100 text-yellow-700",
  };

  const totalPages = Math.ceil(filteredDeliveries.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;

  const handlePrevious = () => {
    if (currentPage > 1) setCurrentPage((prev) => prev - 1);
  };

  const handleNext = () => {
    if (currentPage < totalPages) setCurrentPage((prev) => prev + 1);
  };

  return (
    <div className="border border-gray-200 p-4 rounded-md bg-white">
      {/* Filters */}
      <div className="flex flex-col md:flex-row gap-4 mt-1 items-center w-full">
        {/* SEARCH */}
        <input
          type="text"
          placeholder="Search deliveries..."
          className="border border-gray-300 px-4 py-3 rounded-full w-full md:flex-1"
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
        />

        <div className="flex gap-4 w-full md:w-auto">
          {/* STATUS FILTER */}
          <select
            className="border border-gray-300 px-4 py-3 rounded-full w-full md:w-40"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="All">All Status</option>
            <option value="Completed">Completed</option>
            <option value="In Progress">In Progress</option>
            <option value="Pending">Pending</option>
            <option value="Failed">Failed</option>
          </select>
        </div>
      </div>

      {/* TABLE */}
      <table className="w-full text-left text-sm text-gray-600 mt-4">
        <thead>
          <tr className="text-gray-400 text-xs uppercase border-b border-gray-100">
            <th className="py-2">Delivery ID</th>
            <th className="py-2">Order ID</th>
            <th className="py-2">Customer</th>
            <th className="py-2">Agent</th>
            <th className="py-2">Status</th>
            <th className="py-2">Fee</th>
            <th className="py-2">Date</th>
          </tr>
        </thead>

        <tbody>
          {filteredDeliveries.map((d) => (
            <tr key={d.deliveryId} className="hover:bg-gray-50">
              <td className="py-2">{d.deliveryId}</td>
              <td className="py-2">{d.orderId}</td>
              <td className="py-2">{d.customer}</td>
              <td className="py-2">{d.agent}</td>

              <td className="py-2">
                <span
                  className={`px-2 py-1 text-xs rounded-full ${
                    statusColors[d.status]
                  }`}
                >
                  {d.status}
                </span>
              </td>

              <td className="py-2">{d.fee}</td>
              <td className="py-2">{d.date}</td>
            </tr>
          ))}

          {filteredDeliveries.length === 0 && (
            <tr>
              <td colSpan={8} className="py-4 text-center text-gray-400 italic">
                No deliveries found.
              </td>
            </tr>
          )}
        </tbody>
      </table>
      {/* Pagination */}
      <div className="flex justify-between items-center mt-4 text-sm text-gray-600">
        <p>
          Showing{" "}
          {Math.min(startIndex + itemsPerPage, filteredDeliveries.length)} of{" "}
          {filteredDeliveries.length} order
          {filteredDeliveries.length !== 1 ? "s" : ""}
        </p>

        <div className="flex items-center space-x-2">
          <button
            onClick={handlePrevious}
            disabled={currentPage === 1}
            className={`px-3 py-1 rounded-full ${
              currentPage === 1
                ? "bg-gray-100 text-gray-400 cursor-not-allowed"
                : "hover:bg-gray-100"
            }`}
          >
            Previous
          </button>

          <span className="px-3 py-1 bg-[#1F6728] text-white rounded-full font-semibold">
            {currentPage}
          </span>

          <button
            onClick={handleNext}
            disabled={currentPage === totalPages || totalPages === 0}
            className={`px-3 py-1 rounded-full ${
              currentPage === totalPages || totalPages === 0
                ? "bg-gray-100 text-gray-400 cursor-not-allowed"
                : "hover:bg-gray-100"
            }`}
          >
            Next
          </button>
        </div>
      </div>
    </div>
  );
};

export default DeliveriesTable;
