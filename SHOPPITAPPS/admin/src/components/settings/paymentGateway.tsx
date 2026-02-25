import { useState } from "react";

const PaymentGateway = () => {
  const [paystackEnabled, setPaystackEnabled] = useState(false);
  const [flutterwaveEnabled, setFlutterwaveEnabled] = useState(false);

  const [paystackPublic, setPaystackPublic] = useState("");
  const [paystackSecret, setPaystackSecret] = useState("");

  const [flutterPublic, setFlutterPublic] = useState("");
  const [flutterSecret, setFlutterSecret] = useState("");

  return (
    <>
      <p className="text-xl font-semibold mb-4">Payment Gateways</p>

      {/* PAYSTACK */}
      <div className="bg-gray-100 p-6 rounded-lg shadow-sm mb-6">
        <div className="flex justify-between items-center mb-4">
          <div>
            <p className="font-semibold text-lg">Paystack</p>
            <p className="text-sm text-gray-500">Nigerian Payment Gateway</p>
          </div>

          {/* Toggle */}
          <div
            onClick={() => setPaystackEnabled(!paystackEnabled)}
            className={`w-12 h-6 flex items-center rounded-full p-1 cursor-pointer transition
              ${paystackEnabled ? "bg-green-500" : "bg-gray-300"}`}
          >
            <div
              className={`bg-white w-5 h-5 rounded-full shadow-md transform transition
                ${paystackEnabled ? "translate-x-6" : ""}`}
            />
          </div>
        </div>

        {/* Only show inputs if enabled */}
        {paystackEnabled && (
          <div>
            <div className="mb-3">
              <label className="block mb-1 font-medium">Public Key</label>
              <input
                type="text"
                value={paystackPublic}
                onChange={(e) => setPaystackPublic(e.target.value)}
                className="px-3 py-2 rounded-full w-full border border-gray-300"
              />
            </div>
            <div className="mb-3">
              <label className="block mb-1 font-medium">Secret Key</label>
              <input
                type="text"
                value={paystackSecret}
                onChange={(e) => setPaystackSecret(e.target.value)}
                className="px-3 py-2 rounded-full w-full border border-gray-300"
              />
            </div>
          </div>
        )}
      </div>

      {/* FLUTTERWAVE */}
      <div className="bg-gray-100 p-6 rounded-lg shadow-sm">
        <div className="flex justify-between items-center mb-4">
          <div>
            <p className="font-semibold text-lg">Flutterwave</p>
            <p className="text-sm text-gray-500">African Payment Solution</p>
          </div>

          {/* Toggle */}
          <div
            onClick={() => setFlutterwaveEnabled(!flutterwaveEnabled)}
            className={`w-12 h-6 flex items-center rounded-full p-1 cursor-pointer transition
              ${flutterwaveEnabled ? "bg-green-500" : "bg-gray-300"}`}
          >
            <div
              className={`bg-white w-5 h-5 rounded-full shadow-md transform transition
                ${flutterwaveEnabled ? "translate-x-6" : ""}`}
            />
          </div>
        </div>

        {/* Show inputs only if enabled */}
        {flutterwaveEnabled && (
          <div>
            <div className="mb-3">
              <label className="block mb-1 font-medium">Public Key</label>
              <input
                type="text"
                value={flutterPublic}
                onChange={(e) => setFlutterPublic(e.target.value)}
                className="px-3 py-2 rounded-full w-full border border-gray-300"
              />
            </div>

            <div className="mb-3">
              <label className="block mb-1 font-medium">Secret Key</label>
              <input
                type="text"
                value={flutterSecret}
                onChange={(e) => setFlutterSecret(e.target.value)}
                className="px-3 py-2 rounded-full w-full border border-gray-300"
              />
            </div>
          </div>
        )}
      </div>
    </>
  );
};

export default PaymentGateway;
