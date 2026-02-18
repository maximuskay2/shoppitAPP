import { useState } from "react";
import GeneralSettings from "./generalSettings";
import CommissionSettings from "./commissionSettings";
import NotificationSettings from "./notificationSettings";
import AdminRoles from "./adminRoles";
import MapsApiSettings from "./mapsApiSettings";
import FcmTokensSettings from "./fcmTokensSettings";
import DeliveryZones from "./deliveryZones";
import SubscriptionPlans from "./subscriptionPlans";
import NotificationTemplates from "./notificationTemplates";
import FeatureFlags from "./featureFlags";

const Settings = () => {
  const [activeSection, setActiveSection] =
    useState<SectionKey>("General Settings");

  const sections = {
    "General Settings": <GeneralSettings />,
    "Commission Settings": <CommissionSettings />,
    Notifications: <NotificationSettings />,
    "Admin Roles & Permissions": <AdminRoles />,
    "Google Maps API Key": <MapsApiSettings />,
    "FCM Tokens": <FcmTokensSettings />,
    "Delivery Zones": <DeliveryZones />,
    "Subscription Plans": <SubscriptionPlans />,
    "Notification Templates": <NotificationTemplates />,
    "Feature Flags": <FeatureFlags />,
  };

  type SectionKey = keyof typeof sections;

  return (
    <div className="min-h-[calc(100vh-100px)] flex flex-col">
      <div className="mb-5">
        <p className="text-2xl font-bold text-gray-800">Settings</p>
        <p className="text-gray-500">
          Configure your marketplace settings and preferences.
        </p>
      </div>

      {/* Main Content Area */}
      <div className="flex flex-1 justify-between gap-5 mb-10">
        {/* LEFT SIDE NAVIGATION */}
        <div className="w-1/3 h-fit rounded-xl shadow-xl p-4 bg-white border border-gray-100">
          {Object.keys(sections).map((section) => (
            <button
              key={section}
              className={`px-5 py-3 w-full my-1 text-left transition-all duration-200 rounded-lg font-medium ${
                activeSection === section
                  ? "bg-[#1F6728] text-white shadow-md shadow-green-900/20"
                  : "text-gray-600 hover:bg-gray-50"
              }`}
              onClick={() => setActiveSection(section as SectionKey)}
            >
              {section}
            </button>
          ))}
        </div>

        {/* RIGHT SIDE CONTENT */}
        <div className="w-2/3 rounded-xl shadow-xl p-8 bg-white border border-gray-100 min-h-[500px]">
          {sections[activeSection]}
        </div>
      </div>
    </div>
  );
};

export default Settings;
