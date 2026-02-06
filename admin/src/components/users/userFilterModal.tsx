import { useState, useEffect, useRef } from "react";

type UserFiltersModalProps = {
  show: boolean;
  onClose: () => void;
  onSave: (status: string, dateRange: { from: string; to: string }) => void;
};

const UserFiltersModal = ({ show, onClose, onSave }: UserFiltersModalProps) => {
  const modalRef = useRef<HTMLDivElement>(null);

  const [status, setStatus] = useState("All");
  const [dateRange, setDateRange] = useState({ from: "", to: "" });

  // Close modal on clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        modalRef.current &&
        !modalRef.current.contains(event.target as Node)
      ) {
        onClose();
      }
    };

    if (show) {
      document.addEventListener("mousedown", handleClickOutside);
    } else {
      document.removeEventListener("mousedown", handleClickOutside);
    }

    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [show, onClose]);

  const handleSave = () => {
    onSave(status, dateRange);
    onClose();
  };

  const handleReset = () => {
    setStatus("All");
    setDateRange({ from: "", to: "" });
  };

  if (!show) return null;

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div
        ref={modalRef}
        className="bg-white rounded-md shadow-lg w-96 p-6 relative"
      >
        {/* Close button */}
        <button
          onClick={onClose}
          className="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-lg font-bold"
        >
          &times;
        </button>

        <h2 className="text-lg font-semibold mb-4">Filter Users</h2>

        {/* Status Field */}
        <div className="mb-4">
          <label className="block mb-1 font-medium">Status</label>
          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#1F6728]"
          >
            <option>All</option>
            <option>Active</option>
            <option>Pending</option>
            <option>Suspended</option>
          </select>
        </div>

        {/* Date Range */}
        <div className="mb-4">
          <label className="block mb-1 font-medium">From</label>
          <input
            type="date"
            value={dateRange.from}
            onChange={(e) =>
              setDateRange((prev) => ({ ...prev, from: e.target.value }))
            }
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#1F6728]"
          />
        </div>

        <div className="mb-4">
          <label className="block mb-1 font-medium">To</label>
          <input
            type="date"
            value={dateRange.to}
            onChange={(e) =>
              setDateRange((prev) => ({ ...prev, to: e.target.value }))
            }
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#1F6728]"
          />
        </div>

        {/* Buttons */}
        <div className="flex justify-between gap-3 mt-6">
          <button
            onClick={handleSave}
            className="px-4 py-2 w-1/2 rounded-full bg-[#1F6728] text-white hover:bg-[#185321]"
          >
            Save Changes
          </button>
          <button
            onClick={handleReset}
            className="px-4 py-2 w-1/2 rounded-full border border-gray-300 hover:bg-gray-100"
          >
            Reset
          </button>
        </div>
      </div>
    </div>
  );
};

export default UserFiltersModal;
