import React, { useState } from "react";
import { BiX } from "react-icons/bi";

interface AddBankModalProps {
  onClose: () => void;
  onSubmit: (data: {
    bankName: string;
    accountNumber: string;
    accountName: string;
  }) => void;
}

const AddBankModal: React.FC<AddBankModalProps> = ({ onClose, onSubmit }) => {
  const [bankName, setBankName] = useState("");
  const [accountNumber, setAccountNumber] = useState("");
  const [accountName, setAccountName] = useState("");

  const [errors, setErrors] = useState({
    bankName: false,
    accountNumber: false,
    accountName: false,
  });

  const handleSubmit = () => {
    const newErrors = {
      bankName: bankName.trim() === "",
      accountNumber: accountNumber.trim() === "",
      accountName: accountName.trim() === "",
    };

    setErrors(newErrors);

    // stop submission if any field is empty
    if (Object.values(newErrors).includes(true)) return;

    onSubmit({ bankName, accountNumber, accountName });
    onClose();
  };

  return (
    <div
      className="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      onClick={onClose}
    >
      <div
        className="bg-white p-8 rounded-lg shadow-lg w-[460px] relative"
        onClick={(e) => e.stopPropagation()}
      >
        <button
          onClick={onClose}
          className="absolute top-5 right-6 text-gray-500 hover:text-black text-xl"
        >
          <BiX className="text-[22px]" />
        </button>

        <h2 className="text-xl font-semibold mb-4">Add Bank Account</h2>

        {/* Bank Name */}
        <div className="mb-5">
          <label className="block mb-1">Bank Name</label>
          <input
            type="text"
            placeholder="Enter Bank Name"
            value={bankName}
            onChange={(e) => {
              setBankName(e.target.value);
              setErrors((prev) => ({ ...prev, bankName: false }));
            }}
            className={`border rounded-full px-3 py-2 w-full ${
              errors.bankName ? "border-red-500" : "border-gray-300"
            }`}
          />
          {errors.bankName && (
            <p className="text-red-500 text-sm mt-1">Bank name is required</p>
          )}
        </div>

        {/* Account Number */}
        <div className="mb-5">
          <label className="block mb-1">Account Number</label>
          <input
            type="text"
            placeholder="Enter Account Number"
            value={accountNumber}
            onChange={(e) => {
              setAccountNumber(e.target.value);
              setErrors((prev) => ({ ...prev, accountNumber: false }));
            }}
            className={`border rounded-full px-3 py-2 w-full ${
              errors.accountNumber ? "border-red-500" : "border-gray-300"
            }`}
          />
          {errors.accountNumber && (
            <p className="text-red-500 text-sm mt-1">
              Account number is required
            </p>
          )}
        </div>

        {/* Account Name */}
        <div className="mb-5">
          <label className="block mb-1">Account Name</label>
          <input
            type="text"
            placeholder="Enter Account Name"
            value={accountName}
            onChange={(e) => {
              setAccountName(e.target.value);
              setErrors((prev) => ({ ...prev, accountName: false }));
            }}
            className={`border rounded-full px-3 py-2 w-full ${
              errors.accountName ? "border-red-500" : "border-gray-300"
            }`}
          />
          {errors.accountName && (
            <p className="text-red-500 text-sm mt-1">
              Account name is required
            </p>
          )}
        </div>

        {/* Buttons */}
        <div className="flex gap-3 mt-5">
          <button
            onClick={onClose}
            className="px-4 py-2 w-1/2 rounded-full bg-gray-200 text-gray-700"
          >
            Cancel
          </button>

          <button
            onClick={handleSubmit}
            className="px-4 py-2 w-1/2 rounded-full bg-[#1F6728] text-white"
          >
            Add Bank
          </button>
        </div>
      </div>
    </div>
  );
};

export default AddBankModal;
