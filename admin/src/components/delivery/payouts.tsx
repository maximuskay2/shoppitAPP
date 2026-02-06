import { useState } from "react";
import { BiWallet } from "react-icons/bi";

const Payouts = () => {
  const [searchTerm, setSearchTerm] = useState("");

  // ---- 7 MOCK PAYOUT DATA ----
  const payouts = [
    {
      senderName: "John Doe",
      receiverName: "Jane Smith",
      bankName: "Zenith Bank",
      accountNumber: "1234567890",
      Amount: 42000,
      deliveryFee: 5000,
      status: "pending",
    },
    {
      senderName: "Mike Johnson",
      receiverName: "Sarah Paul",
      bankName: "GTBank",
      accountNumber: "9988776655",
      Amount: 15000,
      deliveryFee: 2000,
      status: "completed",
    },
    {
      senderName: "Samuel Adedeji",
      receiverName: "Grace Bello",
      bankName: "First Bank",
      accountNumber: "1122334455",
      Amount: 31000,
      deliveryFee: 3500,
      status: "pending",
    },
    {
      senderName: "David Obi",
      receiverName: "Linda Mark",
      bankName: "UBA",
      accountNumber: "4455667788",
      Amount: 9000,
      deliveryFee: 1200,
      status: "completed",
    },
    {
      senderName: "Henry Ibe",
      receiverName: "Chika Okafor",
      bankName: "Access Bank",
      accountNumber: "5566778899",
      Amount: 25000,
      deliveryFee: 3000,
      status: "pending",
    },
    {
      senderName: "Peter James",
      receiverName: "Rita Daniels",
      bankName: "Keystone Bank",
      accountNumber: "6677889900",
      Amount: 18000,
      deliveryFee: 2400,
      status: "completed",
    },
    {
      senderName: "Kelvin Musa",
      receiverName: "Ada Love",
      bankName: "Stanbic IBTC",
      accountNumber: "7744112299",
      Amount: 27000,
      deliveryFee: 2800,
      status: "pending",
    },
  ];

  const totalDeliveryFees = payouts.reduce(
    (sum, item) => sum + item.deliveryFee,
    0
  );

  const pendingPayouts = payouts
    .filter((i) => i.status === "pending")
    .reduce((sum, item) => sum + item.Amount, 0);

  const completedPayouts = payouts
    .filter((i) => i.status === "completed")
    .reduce((sum, item) => sum + item.Amount, 0);

  // Filter by search
  const filteredPayouts = payouts.filter(
    (p) =>
      p.senderName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.receiverName.toLowerCase().includes(searchTerm.toLowerCase())
  );

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
          <span>Total Delivery Fees</span>
          <span>₦{totalDeliveryFees.toLocaleString()}</span>
        </p>

        <p className="flex flex-col justify-start gap-2">
          <span>Pending Payouts</span>
          <span>₦{pendingPayouts.toLocaleString()}</span>
        </p>

        <p className="flex flex-col justify-start gap-2">
          <span>Completed Payouts</span>
          <span>₦{completedPayouts.toLocaleString()}</span>
        </p>

        <p></p>
      </div>

      {/* Header */}
      <div className="flex px-4 py-4 justify-between items-center">
        <p>Agent Payouts</p>
        <button className="px-3 py-2 flex text-white items-center gap-2 justify-center bg-[#1F6728] rounded-full">
          <BiWallet className="text-[16px]" />
          Process Payouts
        </button>
      </div>

      {/* Payout List */}
      {filteredPayouts.map((payout, index) => (
        <div
          key={index}
          className="grid grid-cols-4 rounded-lg items-end px-4 py-2 bg-gray-100 mb-3"
        >
          <p className="flex flex-col justify-start gap-2">
            <span>{payout.senderName}</span>
            <span className="text-xs">Bank Name</span>
            <span>{payout.bankName}</span>
          </p>

          <p className="flex flex-col justify-start gap-2">
            <span className="text-xs">Account Number</span>
            <span>{payout.accountNumber}</span>
          </p>

          <p className="flex flex-col justify-start gap-2">
            <span className="text-xs">Account Name</span>
            <span>{payout.receiverName}</span>
          </p>

          <p className="flex flex-col justify-start gap-2">
            <span className="text-xs">Pending Amount</span>
            <span>₦{payout.Amount.toLocaleString()}</span>
          </p>
        </div>
      ))}
    </div>
  );
};

export default Payouts;
