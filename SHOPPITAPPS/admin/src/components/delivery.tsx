import { useState } from "react";
import { BiMapAlt, BiTrendingUp, BiUser, BiWallet } from "react-icons/bi";
import FleetMap from "./delivery/fleetMap";
import DriverAnalytics from "./delivery/driverAnalytics";
import DeliveryAgents from "./delivery/deliveryAgents";
import Payouts from "./delivery/payouts";

type TabKey = "map" | "analytics" | "agents" | "payouts";

const Delivery = () => {
  const [activeTab, setActiveTab] = useState<TabKey>("map");
  const [modalOpen, setModalOpen] = useState(false);

  const tabs: {
    id: TabKey;
    label: string;
    icon: React.ReactNode;
    component: React.ReactNode;
  }[] = [
    {
      id: "map",
      label: "Live Fleet Map",
      icon: <BiMapAlt />,
      component: <FleetMap />,
    },
    {
      id: "analytics",
      label: "Analytics Dashboard",
      icon: <BiTrendingUp />,
      component: <DriverAnalytics />,
    },
    {
      id: "agents",
      label: "Driver Management",
      icon: <BiUser />,
      component: <DeliveryAgents modalOpen={modalOpen} setModalOpen={setModalOpen} />,
    },
    {
      id: "payouts",
      label: "Driver Payouts",
      icon: <BiWallet />,
      component: <Payouts />,
    },
  ];

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="bg-white border border-gray-200 rounded-lg p-4">
        <h1 className="text-2xl font-bold text-gray-900">Delivery & Driver Management</h1>
        <p className="text-sm text-gray-600 mt-1">
          Monitor live driver locations, view analytics, manage drivers, and process payouts
        </p>
      </div>

      {/* Tab Navigation */}
      <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div className="flex border-b bg-gray-50">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-2 px-4 py-3 font-medium transition ${
                activeTab === tab.id
                  ? "text-[#1F6728] border-b-2 border-[#1F6728] bg-white"
                  : "text-gray-600 hover:text-gray-900"
              }`}
            >
              <span className="text-lg">{tab.icon}</span>
              <span className="hidden sm:inline">{tab.label}</span>
            </button>
          ))}
        </div>

        {/* Tab Content */}
        <div className="p-4">
          {tabs.map((tab) => (
            <div key={tab.id} className={activeTab === tab.id ? "block" : "hidden"}>
              {tab.component}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default Delivery;
