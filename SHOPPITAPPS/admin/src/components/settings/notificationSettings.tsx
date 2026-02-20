import { useState } from "react";

const NotificationSettings = () => {
  const [newOrderNotifs, setNewOrderNotifs] = useState(false);
  const [vendorReg, setVendorReg] = useState(false);
  const [paymentUpdates, setPaymentUpdates] = useState(false);
  const [deliveryUpdates, setDeliveryUpdates] = useState(true);
  const [customerReview, setCustomerReview] = useState(true);
  const [stockAlerts, setStockAlerts] = useState(false);

  return (
    <>
      <p className="text-xl font-semibold mb-4">Notification Preferences</p>

      <div className="w-full py-3 flex flex-col">
        <div className="mb-4 flex justify-between items-center border rounded-md px-5 py-3 border-gray-300">
          <div>
            <p className="font-medium">New Order Notifications</p>
            <p className="text-xs text-gray-600">
              Get notified when new orders are placed.
            </p>
          </div>

          <div
            onClick={() => setNewOrderNotifs(!newOrderNotifs)}
            className={`w-12 h-6 flex items-center rounded-full p-1 cursor-pointer transition
              ${newOrderNotifs ? "bg-green-500" : "bg-gray-300"}`}
          >
            <div
              className={`bg-white w-5 h-5 rounded-full shadow-md transform transition
                ${newOrderNotifs ? "translate-x-6" : ""}`}
            />
          </div>
        </div>

        <div className="mb-4 flex justify-between items-center border rounded-md px-5 py-3 border-gray-300">
          <div>
            <p className="font-medium">Vendor Registration</p>
            <p className="text-xs text-gray-600">
              Alerts for new vendor registrations.
            </p>
          </div>
          <div
            onClick={() => setVendorReg(!vendorReg)}
            className={`w-12 h-6 flex items-center rounded-full p-1 cursor-pointer transition
              ${vendorReg ? "bg-green-500" : "bg-gray-300"}`}
          >
            <div
              className={`bg-white w-5 h-5 rounded-full shadow-md transform transition
                ${vendorReg ? "translate-x-6" : ""}`}
            />
          </div>
        </div>

        <div className="mb-4 flex justify-between items-center border rounded-md px-5 py-3 border-gray-300">
          <div>
            <p className="font-medium">Payment Confirmations</p>
            <p className="text-xs text-gray-600">
              Payment and Transaction Updates.
            </p>
          </div>

          <div
            onClick={() => setPaymentUpdates(!paymentUpdates)}
            className={`w-12 h-6 flex items-center rounded-full p-1 cursor-pointer transition
              ${paymentUpdates ? "bg-green-500" : "bg-gray-300"}`}
          >
            <div
              className={`bg-white w-5 h-5 rounded-full shadow-md transform transition
                ${paymentUpdates ? "translate-x-6" : ""}`}
            />
          </div>
        </div>

        <div className="mb-4 flex justify-between items-center border rounded-md px-5 py-3 border-gray-300">
          <div>
            <p className="font-medium">Delivery Updates</p>
            <p className="text-xs text-gray-600">
              Real time delivery status changes.
            </p>
          </div>

          <div
            onClick={() => setDeliveryUpdates(!deliveryUpdates)}
            className={`w-12 h-6 flex items-center rounded-full p-1 cursor-pointer transition
              ${deliveryUpdates ? "bg-green-500" : "bg-gray-300"}`}
          >
            <div
              className={`bg-white w-5 h-5 rounded-full shadow-md transform transition
                ${deliveryUpdates ? "translate-x-6" : ""}`}
            />
          </div>
        </div>

        <div className="mb-4 flex justify-between items-center border rounded-md px-5 py-3 border-gray-300">
          <div>
            <p className="font-medium">Customer Reviews</p>
            <p className="text-xs text-gray-600">
              Notifications for new reviews and ratings.
            </p>
          </div>

          <div
            onClick={() => setCustomerReview(!customerReview)}
            className={`w-12 h-6 flex items-center rounded-full p-1 cursor-pointer transition
              ${customerReview ? "bg-green-500" : "bg-gray-300"}`}
          >
            <div
              className={`bg-white w-5 h-5 rounded-full shadow-md transform transition
                ${customerReview ? "translate-x-6" : ""}`}
            />
          </div>
        </div>

        <div className="mb-4 flex justify-between items-center border rounded-md px-5 py-3 border-gray-300">
          <div>
            <p className="font-medium">Low Stock Alerts</p>
            <p className="text-xs text-gray-600">
              Alerts when products are running low.
            </p>
          </div>

          <div
            onClick={() => setStockAlerts(!stockAlerts)}
            className={`w-12 h-6 flex items-center rounded-full p-1 cursor-pointer transition
              ${stockAlerts ? "bg-green-500" : "bg-gray-300"}`}
          >
            <div
              className={`bg-white w-5 h-5 rounded-full shadow-md transform transition
                ${stockAlerts ? "translate-x-6" : ""}`}
            />
          </div>
        </div>
      </div>
    </>
  );
};

export default NotificationSettings;
