import { useState } from "react";
import DeliveriesTable from "./deliveryTable";
import DeliveryAgents from "./deliveryAgents";
import Providers from "./providers";
import Payouts from "./payouts";
import ProviderModal from "./providerModal";
import AddBankModal from "../transactions/addBankModal";
import { BiDownload } from "react-icons/bi";

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
type ProviderFormData = {
  name: string;
  status: string;
  location: string;
  webhookURL: string;
  APIKey: string;
};

type TabType = "deliveries" | "agents" | "providers" | "payouts";

const DeliveryIndex = () => {
  const [activeTab, setActiveTab] = useState<TabType>("deliveries");

  // Modal state
  const [agentModalOpen, setAgentModalOpen] = useState(false);
  const [providerModalOpen, setProviderModalOpen] = useState(false);
  const [payoutModalOpen, setPayoutModalOpen] = useState(false);

  // Provider Modal state
  const [providerMode, setProviderMode] = useState<"add" | "edit">("add");
  const [providerEditData, setProviderEditData] =
    useState<ProviderFormData | null>(null);

  // Sample delivery data
  const deliveries: Delivery[] = [
    {
      deliveryId: "D001",
      orderId: "#001",
      customer: "John Doe",
      agent: "Agent One",
      status: "Completed",
      fee: "₦1,500",
      date: "2025-10-21",
    },
    {
      deliveryId: "D002",
      orderId: "#002",
      customer: "Jane Smith",
      agent: "Agent Two",
      status: "In Progress",
      fee: "₦1,200",
      date: "2025-10-22",
    },
    {
      deliveryId: "D003",
      orderId: "#003",
      customer: "Alice Brown",
      agent: "Agent One",
      status: "Failed",
      fee: "₦1,000",
      date: "2025-09-15",
    },
    {
      deliveryId: "D004",
      orderId: "#004",
      customer: "Michael Johnson",
      agent: "Agent Three",
      status: "Pending",
      fee: "₦1,800",
      date: "2025-08-19",
    },
  ];

  // Stats
  const totalDeliveries = deliveries.length;
  const completed = deliveries.filter((d) => d.status === "Completed").length;
  const inProgress = deliveries.filter(
    (d) => d.status === "In Progress"
  ).length;
  const activeAgents = new Set(deliveries.map((d) => d.agent)).size;

  // Tab actions
  const tabActions: Record<
    TabType,
    { label: string | null; action: (() => void) | null }
  > = {
    deliveries: { label: null, action: null },
    agents: { label: "Add Agent", action: () => setAgentModalOpen(true) },
    providers: {
      label: "Add Provider",
      action: () => {
        setProviderMode("add");
        setProviderEditData(null);
        setProviderModalOpen(true);
      },
    },
    payouts: { label: "Add Payout", action: () => setPayoutModalOpen(true) },
  };

  // Provider handlers
  const handleEditProvider = (data: ProviderFormData) => {
    setProviderMode("edit");
    setProviderEditData(data);
    setProviderModalOpen(true);
  };

  const handleDeleteProvider = (id: string) => {
    console.log("Delete provider:", id);
    // Implement deletion logic here
  };

  return (
    <div className="p-4">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <div>
          <p className="text-2xl font-bold text-gray-800">
            Delivery Management
          </p>
          <p className="text-gray-500">
            Manage deliveries, agents and third-party providers
          </p>
        </div>
        <div className="flex gap-2">
          {tabActions[activeTab].label && (
            <button
              className="bg-purple-800 px-4 py-2 text-white rounded-full"
              onClick={tabActions[activeTab].action!}
            >
              {tabActions[activeTab].label}
            </button>
          )}
          <button className="bg-[#1F6728] px-4 py-2 text-white flex rounded-full items-center hover:bg-green-700 transition">
            <BiDownload className="mr-2 text-[16px]" />
            Export CSV
          </button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-4 gap-6 my-6">
        <div className="border border-gray-200 shadow-sm rounded-md p-6">
          <p className="text-sm text-gray-500">Total Deliveries</p>
          <p className="font-medium text-xl">{totalDeliveries}</p>
        </div>
        <div className="border border-gray-200 shadow-sm rounded-md p-6">
          <p className="text-sm text-gray-500">Completed</p>
          <p className="font-medium text-xl">{completed}</p>
        </div>
        <div className="border border-gray-200 shadow-sm rounded-md p-6">
          <p className="text-sm text-gray-500">In Progress</p>
          <p className="font-medium text-xl">{inProgress}</p>
        </div>
        <div className="border border-gray-200 shadow-sm rounded-md p-6">
          <p className="text-sm text-gray-500">Active Agents</p>
          <p className="font-medium text-xl">{activeAgents}</p>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex items-center border-b border-gray-200 mb-6">
        {(["deliveries", "agents", "providers", "payouts"] as TabType[]).map(
          (tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`px-4 py-2 font-semibold capitalize ${
                activeTab === tab
                  ? "border-b-2 border-[#1F6728] text-[#1F6728]"
                  : "text-gray-500 hover:text-gray-700"
              }`}
            >
              {tab}
            </button>
          )
        )}
      </div>

      {/* Render Tabs */}
      {activeTab === "deliveries" && (
        <DeliveriesTable deliveries={deliveries} />
      )}
      {activeTab === "agents" && (
        <DeliveryAgents
          modalOpen={agentModalOpen}
          setModalOpen={setAgentModalOpen}
        />
      )}
      {activeTab === "providers" && (
        <Providers
          onEditProvider={handleEditProvider}
          onDeleteProvider={handleDeleteProvider}
        />
      )}
      {activeTab === "payouts" && <Payouts />}

      {/* Modals */}
      {providerModalOpen && (
        <ProviderModal
          mode={providerMode}
          initialData={providerEditData || undefined}
          onClose={() => setProviderModalOpen(false)}
          onSubmit={(data) => {
            console.log("Provider saved:", data);
            setProviderModalOpen(false);
          }}
        />
      )}

      {payoutModalOpen && (
        <AddBankModal
          onClose={() => setPayoutModalOpen(false)}
          onSubmit={(data) => {
            console.log("New payout bank added:", data);
            setPayoutModalOpen(false);
          }}
        />
      )}
    </div>
  );
};

export default DeliveryIndex;
