import { useEffect, useRef, useState } from "react";
import { BiCalendar, BiEnvelope, BiMap, BiPhone, BiX } from "react-icons/bi";
import { BsEye } from "react-icons/bs";
import { FiFile } from "react-icons/fi";
import { TiTick } from "react-icons/ti";

interface VendorDocument {
  name: string;
  type: string;
  uploadedAt: string;
}

interface Vendor {
  name: string;
  owner: string;
  category: string;
  email: string;
  phone: string;
  joinDate: string;
  address: string;
  noOfDocs: number;
  request: string;
  documents: VendorDocument[];
}

const VendorApproval = () => {
  const modalRef = useRef<HTMLDivElement>(null);
  const [selectedVendor, setSelectedVendor] = useState<Vendor | null>(null);
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        modalRef.current &&
        !modalRef.current.contains(event.target as Node)
      ) {
        setSelectedVendor(null);
      }
    };

    if (selectedVendor) {
      document.addEventListener("mousedown", handleClickOutside);
    } else {
      document.removeEventListener("mousedown", handleClickOutside);
    }

    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [selectedVendor]);

  const pending_approvals: Vendor[] = [
    {
      name: "Kilamanjaro",
      owner: "Bright Moses",
      category: "Food",
      email: "bright@kilamanjaro.com",
      phone: "0803-111-2222",
      joinDate: "2024-01-10",
      address: "Plot 4, Lagos Island",
      noOfDocs: 4,
      request: "Tier 2",
      documents: [
        { name: "Business Certificate", type: "PDF", uploadedAt: "2024-01-11" },
        { name: "Owner ID", type: "JPG", uploadedAt: "2024-01-11" },
        { name: "Tax Clearance", type: "PDF", uploadedAt: "2024-01-12" },
        { name: "Utility Bill", type: "PNG", uploadedAt: "2024-01-12" },
      ],
    },
    {
      name: "Swift Logistics",
      owner: "Ada Obi",
      category: "Logistics",
      email: "ada@swiftlogistics.com",
      phone: "0802-555-7890",
      joinDate: "2024-02-15",
      address: "Abuja - Garki Area 3",
      noOfDocs: 3,
      request: "Tier 3",
      documents: [
        { name: "Business License", type: "PDF", uploadedAt: "2024-02-16" },
        { name: "ID Card", type: "PNG", uploadedAt: "2024-02-16" },
        { name: "CAC Form", type: "PDF", uploadedAt: "2024-02-17" },
      ],
    },
    {
      name: "FreshMart",
      owner: "John West",
      category: "Groceries",
      email: "info@freshmart.com",
      phone: "0701-777-8888",
      joinDate: "2024-03-02",
      address: "Yaba, Lagos",
      noOfDocs: 5,
      request: "Tier 1",
      documents: [
        { name: "Business License", type: "PDF", uploadedAt: "2024-02-16" },
        { name: "ID Card", type: "PNG", uploadedAt: "2024-02-16" },
        { name: "CAC Form", type: "PDF", uploadedAt: "2024-02-17" },
        { name: "Tax Clearance", type: "PDF", uploadedAt: "2024-01-12" },
        { name: "Utility Bill", type: "PNG", uploadedAt: "2024-01-12" },
      ],
    },
    {
      name: "TechHub",
      owner: "Samson Ayo",
      category: "Electronics",
      email: "support@techhub.com",
      phone: "0903-333-4444",
      joinDate: "2024-04-22",
      address: "Port Harcourt",
      noOfDocs: 2,
      request: "Tier 2",
      documents: [
        { name: "Tax Clearance", type: "PDF", uploadedAt: "2024-01-12" },
        { name: "Utility Bill", type: "PNG", uploadedAt: "2024-01-12" },
      ],
    },
    {
      name: "Bella Fashion",
      owner: "Bella James",
      category: "Fashion",
      email: "hello@bellafashion.com",
      phone: "0812-998-2211",
      joinDate: "2024-05-01",
      address: "Ibadan",
      noOfDocs: 6,
      request: "Tier 1",
      documents: [
        { name: "Business License", type: "PDF", uploadedAt: "2024-02-16" },
        { name: "ID Card", type: "PNG", uploadedAt: "2024-02-16" },
        { name: "CAC Form", type: "PDF", uploadedAt: "2024-02-17" },
        { name: "Business License", type: "PDF", uploadedAt: "2024-02-16" },
        { name: "ID Card", type: "PNG", uploadedAt: "2024-02-16" },
        { name: "CAC Form", type: "PDF", uploadedAt: "2024-02-17" },
      ],
    },
    {
      name: "Luxe Interiors",
      owner: "Olu Ade",
      category: "Home & Decor",
      email: "olu@luxeinteriors.com",
      phone: "0804-444-9090",
      joinDate: "2024-06-18",
      address: "Lekki, Lagos",
      noOfDocs: 4,
      request: "Tier 3",
      documents: [
        { name: "Business License", type: "PDF", uploadedAt: "2024-02-16" },
        { name: "ID Card", type: "PNG", uploadedAt: "2024-02-16" },
        { name: "CAC Form", type: "PDF", uploadedAt: "2024-02-17" },
        { name: "Utility Bill", type: "PNG", uploadedAt: "2024-01-12" },
      ],
    },
  ];

  return (
    <div className="p-4">
      {/* Header */}
      <div className="mb-4">
        <p className="text-2xl font-bold text-gray-800">Vendor Approval</p>
        <p className="text-gray-500">
          Review and Approve new vendor registrations.
        </p>
      </div>

      {/* Summary */}
      <div className="grid grid-cols-3 gap-4 mb-6">
        <div className="border border-gray-200 rounded-md p-6">
          <p className="font-medium text-gray-700">Pending Requests</p>
          <p className="text-3xl font-bold">{pending_approvals.length}</p>
        </div>
        <div className="border border-gray-200 rounded-md p-6">
          <p className="font-medium text-gray-700">Approved Today</p>
          <p className="text-3xl font-bold">{pending_approvals.length || 0}</p>
        </div>
        <div className="border border-gray-200 rounded-md p-6">
          <p className="font-medium text-gray-700">Total Requests</p>
          <p className="text-3xl font-bold">{pending_approvals.length}</p>
        </div>
      </div>

      {/* VENDOR LIST */}
      <div className="flex flex-col gap-4">
        {pending_approvals.map((vendor, index) => (
          <div
            key={index}
            className="border flex items-center justify-between border-gray-200 rounded-md p-4 shadow-sm hover:shadow-md transition"
          >
            <div className="flex flex-col">
              <div className="flex gap-3 mb-3">
                <p className="font-semibold text-gray-800">{vendor.name}</p>
                <p className="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                  Pending
                </p>
                <p
                  className={`inline-block px-3 py-1 rounded-full text-xs font-semibold ${
                    vendor.request === "Tier 1"
                      ? "bg-gray-100 text-gray-800"
                      : vendor.request === "Tier 2"
                      ? "bg-blue-100 text-blue-800"
                      : "bg-purple-100 text-purple-800"
                  }`}
                >
                  Requested: {vendor.request}
                </p>
              </div>

              <div className="">
                <p className="text-sm text-gray-600">Owner: {vendor.owner}</p>
                <p className="text-sm text-gray-600">
                  Category: {vendor.category}
                </p>
              </div>

              <div className="flex gap-3 text-gray-600 mt-3">
                <p className="flex items-center">
                  <BiEnvelope className="mr-2" />
                  {vendor.email}
                </p>
                <p className="flex items-center">
                  <BiPhone className="mr-2" />
                  {vendor.phone}
                </p>
                <p className="flex items-center">
                  <BiCalendar className="mr-2" />
                  {vendor.joinDate}
                </p>
              </div>

              <div className="flex gap-2 text-gray-600">
                <p className="flex items-center">
                  <BiMap className="mr-2" />
                  {vendor.address}
                </p>
                <p className="flex items-center">
                  <FiFile className="mr-2" />
                  {vendor.noOfDocs} Documents
                </p>
              </div>
            </div>

            <div className="mt-4 flex flex-col justify-between gap-3">
              <button
                className="px-4 py-1 border-gray-200 border-2 rounded-full flex items-center"
                onClick={() => setSelectedVendor(vendor)}
              >
                <BsEye className="mr-2" />
                View Details
              </button>
              <button className="px-4 py-1 bg-green-600 text-white rounded-full">
                Approve
              </button>
              <button className="px-4 py-1 bg-red-500 text-white rounded-full">
                Reject
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* MODAL */}
      {selectedVendor && (
        <div className="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
          <div className="bg-white w-[650px] rounded-lg shadow-lg p-6 relative animate-fadeIn">
            {/* Close Button */}
            <button
              className="absolute top-3 right-3 text-gray-600 hover:text-black"
              onClick={() => setSelectedVendor(null)}
            >
              <BiX size={28} />
            </button>

            {/* Modal Content */}
            <h2 className="text-2xl font-bold mb-4 py-3 text-gray-800">
              Vendor Application Details
            </h2>
            <div className="px-3">
              <h2 className="mb-3">Business Information</h2>
              <div className="flex space-y-2 gap-3 text-gray-700 justify-between items-center">
                <div>
                  <p>Owner name</p>
                  <p className="mb-3 text-black">{selectedVendor.owner}</p>

                  <p>Phone number</p>
                  <p className="mb-3 text-black">{selectedVendor.phone}</p>

                  <p>Business Address</p>
                  <p className="mb-3 text-black">{selectedVendor.address}</p>

                  <p>Category</p>
                  <p className="mb-3 text-black">{selectedVendor.category}</p>
                </div>
                <div>
                  <p>Email</p>
                  <p className="mb-3 text-black">{selectedVendor.email}</p>

                  <p>Owner name</p>
                  <p className="mb-3 text-black">{selectedVendor.name}</p>

                  <p>Tax ID</p>
                  <p className="mb-3 text-black">{selectedVendor.name}</p>

                  <p>Tier</p>
                  <p
                    className={`inline-block px-3 py-1 rounded-full text-xs font-semibold mb-4 ${
                      selectedVendor.request === "Tier 1"
                        ? "bg-gray-100 text-gray-800"
                        : selectedVendor.request === "Tier 2"
                        ? "bg-blue-100 text-blue-800"
                        : "bg-purple-100 text-purple-800"
                    }`}
                  >
                    {selectedVendor.name}
                  </p>
                </div>
              </div>
            </div>

            <div className="mt-6">
              <h2 className="font-semibold mb-3">
                KYC Documents ({selectedVendor.documents.length})
              </h2>

              <div className="space-y-2 gap-5 grid grid-cols-2">
                {selectedVendor.documents.map((doc, index) => (
                  <div
                    key={index}
                    className="flex justify-between items-center border rounded-md p-3"
                  >
                    <FiFile className="mr-2" />
                    <div>
                      <p className="font-sm text-gray-800">{doc.name}</p>
                      <p className="text-xs text-gray-500">
                        {doc.type} • Uploaded: {doc.uploadedAt}
                      </p>
                    </div>
                    <BsEye className="text-green-500" />
                  </div>
                ))}
              </div>
            </div>

            {/* Modal Buttons */}
            <div className="flex justify-between mt-6">
              <button
                onClick={() => setSelectedVendor(null)}
                className="px-5 py-2 bg-gray-200 rounded-full"
              >
                Close
              </button>
              <button className="px-5 py-2 bg-green-600 flex items-center gap-2 text-white rounded-full">
                <TiTick />
                Approve Application
              </button>
              <button className="px-5 py-2 bg-red-500 flex items-center gap-2 text-white rounded-full">
                <BiX />
                Reject Application
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default VendorApproval;
