import { useEffect, useState } from "react";
import { BiEnvelope, BiLoaderAlt, BiPhone } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

type Agent = {
  id: string;
  name: string;
  email: string;
  phone: string;
  status: string;
  isVerified: boolean;
  isOnline: boolean;
};

type DeliveryAgentsProps = {
  modalOpen: boolean;
  setModalOpen: (open: boolean) => void;
};

const DeliveryAgents = (_props: DeliveryAgentsProps) => {
  const [searchTerm, setSearchTerm] = useState("");
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState<string | null>(null);

  const filteredAgents = agents.filter(
    (agent) =>
      agent.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      agent.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
      agent.phone.includes(searchTerm)
  );

  const fetchAgents = async () => {
    const token = localStorage.getItem("token");

    try {
      const response = await fetch(apiUrl("/api/v1/admin/drivers"), {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      const result = await response.json();
      if (result.success) {
        const items = result.data?.data || [];
        setAgents(
          items.map((item: any) => ({
            id: item.id,
            name: item.name,
            email: item.email,
            phone: item.phone,
            status: String(item.status),
            isVerified: Boolean(item.driver?.is_verified),
            isOnline: Boolean(item.driver?.is_online),
          }))
        );
      }
    } catch (err) {
      console.error("Failed to fetch drivers:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAgents();
  }, []);

  const handleVerify = async (agentId: string) => {
    const token = localStorage.getItem("token");
    setBusyId(agentId);

    try {
      const response = await fetch(
        apiUrl(`/api/v1/admin/drivers/${agentId}/verify`),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ approved: true }),
        }
      );

      const result = await response.json();
      if (result.success) {
        await fetchAgents();
      } else {
        alert(result.message || "Failed to verify driver");
      }
    } catch (err) {
      console.error("Failed to verify driver:", err);
      alert("Network error. Please try again.");
    } finally {
      setBusyId(null);
    }
  };

  const handleBlockToggle = async (agentId: string, status: string) => {
    const token = localStorage.getItem("token");
    setBusyId(agentId);

    try {
      const endpoint = status === "BLOCKED" ? "unblock" : "block";
      const response = await fetch(
        apiUrl(`/api/v1/admin/drivers/${agentId}/${endpoint}`),
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        }
      );

      const result = await response.json();
      if (result.success) {
        await fetchAgents();
      } else {
        alert(result.message || "Failed to update driver status");
      }
    } catch (err) {
      console.error("Failed to update driver status:", err);
      alert("Network error. Please try again.");
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div className="border border-gray-200 p-4 rounded-md bg-white">
      {/* Top Bar */}
      <div className="flex justify-between items-center mb-6">
        <input
          type="text"
          placeholder="Search agents..."
          className="border border-gray-300 px-4 py-3 rounded-full w-full md:flex-1 mr-4"
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
        />
      </div>

      {/* Agents Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {loading && (
          <div className="col-span-full flex items-center gap-2 text-gray-500">
            <BiLoaderAlt className="animate-spin" />
            <span>Loading drivers...</span>
          </div>
        )}

        {filteredAgents.map((agent) => (
          <div
            key={agent.id}
            className="border border-gray-200 rounded-md p-4 shadow-sm hover:shadow-md transition"
          >
            <div className="flex items-center justify-between space-x-4 mb-4">
              <div className="flex gap-4">
                <div className="bg-purple-400 w-12 h-12 rounded-full flex items-center justify-center text-white font-bold">
                  {agent.name[0]}
                </div>
                <div>
                  <p className="font-semibold">{agent.name}</p>
                  <p className="text-sm text-gray-500">{agent.email}</p>
                </div>
              </div>
              <p
                className={`px-4 py-1 rounded-full ${
                  agent.status === "ACTIVE"
                    ? "bg-green-100 text-green-800"
                    : "bg-gray-100 text-gray-500"
                }`}
              >
                {agent.status}
              </p>
            </div>

            <p className="text-sm flex gap-2 items-center my-1">
              <BiEnvelope className="text-[16px]" />
              {agent.email}
            </p>
            <p className="text-sm flex gap-2 items-center my-1">
              <BiPhone className="text-[16px]" />
              {agent.phone}
            </p>

            <div className="border-t-2 border-gray-200 pt-2 mt-2">
              <div className="flex gap-2 mt-4 pb-2">
                <button
                  className="bg-[#1F6728] text-white flex items-center gap-3 justify-center flex-1 py-2 rounded-full disabled:bg-gray-300"
                  onClick={() => handleVerify(agent.id)}
                  disabled={busyId === agent.id || agent.isVerified}
                >
                  {agent.isVerified ? "Verified" : "Verify"}
                </button>
                <button
                  className="bg-red-500 text-white font-bold flex-1 rounded-full py-2 flex items-center justify-center disabled:bg-gray-300"
                  onClick={() => handleBlockToggle(agent.id, agent.status)}
                  disabled={busyId === agent.id}
                >
                  {agent.status === "BLOCKED" ? "Unblock" : "Block"}
                </button>
              </div>
              <p className="text-xs text-gray-500">
                {agent.isOnline ? "Online" : "Offline"} • {agent.isVerified ? "Verified" : "Unverified"}
              </p>
            </div>
          </div>
        ))}

        {!loading && filteredAgents.length === 0 && (
          <p className="col-span-full text-center text-gray-400 italic">
            No agents found.
          </p>
        )}
      </div>
    </div>
  );
};

export default DeliveryAgents;
