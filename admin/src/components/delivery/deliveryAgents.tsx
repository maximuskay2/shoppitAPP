import { useState } from "react";
import { BiEdit, BiEnvelope, BiPhone, BiTrash, BiX } from "react-icons/bi";

type Agent = {
  id: string;
  name: string;
  location: string;
  email: string;
  phone: string;
  status: "Active" | "Inactive";
  deliveries: number;
};

type DeliveryAgentsProps = {
  modalOpen: boolean;
  setModalOpen: (open: boolean) => void;
};

const DeliveryAgents = ({ modalOpen, setModalOpen }: DeliveryAgentsProps) => {
  const [searchTerm, setSearchTerm] = useState("");
  const [editingAgent, setEditingAgent] = useState<Agent | null>(null);

  const [agents, setAgents] = useState<Agent[]>([
    {
      id: "A001",
      name: "Agent One",
      location: "Lagos",
      email: "agentone@example.com",
      phone: "08012345678",
      status: "Active",
      deliveries: 30,
    },
    {
      id: "A002",
      name: "Agent Two",
      location: "Abuja",
      email: "agenttwo@example.com",
      phone: "08087654321",
      status: "Active",
      deliveries: 20,
    },
    {
      id: "A003",
      name: "Agent Three",
      location: "Port Harcourt",
      email: "agentthree@example.com",
      phone: "08123456789",
      status: "Active",
      deliveries: 5,
    },
  ]);

  const filteredAgents = agents.filter(
    (agent) =>
      agent.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      agent.location.toLowerCase().includes(searchTerm.toLowerCase()) ||
      agent.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
      agent.phone.includes(searchTerm)
  );

  const [formData, setFormData] = useState({
    name: "",
    location: "",
    email: "",
    phone: "",
    status: "Active" as "Active" | "Inactive",
    deliveries: 0,
  });

  const handleEdit = (agent: Agent) => {
    setEditingAgent(agent);
    setFormData({ ...agent });
    setModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingAgent) {
      // Update only name and status
      setAgents((prev) =>
        prev.map((a) =>
          a.id === editingAgent.id
            ? { ...a, name: formData.name, status: formData.status }
            : a
        )
      );
    } else {
      // Add new agent
      const newAgent: Agent = { ...formData, id: `A${Date.now()}` };
      setAgents((prev) => [...prev, newAgent]);
    }
    setModalOpen(false);
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
                  <p className="text-sm text-gray-500">{agent.location}</p>
                </div>
              </div>
              <p
                className={`px-4 py-1 rounded-full ${
                  agent.status === "Active"
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
              <p className="flex flex-col">
                <span>Deliveries</span>
                <span>{agent.deliveries}</span>
              </p>

              <div className="flex gap-2 mt-4 pb-4">
                <button
                  className="bg-[#1F6728] text-white flex items-center gap-3 justify-center flex-1 py-2 rounded-full"
                  onClick={() => handleEdit(agent)}
                >
                  <BiEdit />
                  Edit
                </button>
                <button
                  className="bg-red-500 text-white font-bold flex-[0.2] rounded-full py-3 flex items-center justify-center"
                  onClick={() =>
                    setAgents((prev) => prev.filter((a) => a.id !== agent.id))
                  }
                >
                  <BiTrash />
                </button>
              </div>
            </div>
          </div>
        ))}

        {filteredAgents.length === 0 && (
          <p className="col-span-full text-center text-gray-400 italic">
            No agents found.
          </p>
        )}
      </div>

      {/* Modal */}
      {modalOpen && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
          <div className="bg-white w-full max-w-md rounded-lg shadow-lg p-6 relative">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-bold text-gray-800">
                {editingAgent ? "Edit Agent" : "Add Delivery Agent"}
              </h2>
              <button
                className="text-gray-500 hover:text-gray-700"
                onClick={() => setModalOpen(false)}
              >
                <BiX className="text-2xl" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-3">
              {/* Add Mode: all fields */}
              {!editingAgent && (
                <>
                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      Full Name
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.name}
                      onChange={(e) =>
                        setFormData({ ...formData, name: e.target.value })
                      }
                      className="border border-gray-300 px-3 py-2 rounded-md w-full"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      Email Address
                    </label>
                    <input
                      type="email"
                      required
                      value={formData.email}
                      onChange={(e) =>
                        setFormData({ ...formData, email: e.target.value })
                      }
                      className="border border-gray-300 px-3 py-2 rounded-md w-full"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      Phone Number
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.phone}
                      onChange={(e) =>
                        setFormData({ ...formData, phone: e.target.value })
                      }
                      className="border border-gray-300 px-3 py-2 rounded-md w-full"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      Region
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.location}
                      onChange={(e) =>
                        setFormData({ ...formData, location: e.target.value })
                      }
                      className="border border-gray-300 px-3 py-2 rounded-md w-full"
                    />
                  </div>
                </>
              )}

              {editingAgent && (
                <>
                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      Name
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.name}
                      onChange={(e) =>
                        setFormData({ ...formData, name: e.target.value })
                      }
                      className="border border-gray-300 px-3 py-2 rounded-md w-full"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700">
                      Status
                    </label>
                    <select
                      value={formData.status}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          status: e.target.value as "Active" | "Inactive",
                        })
                      }
                      className="border border-gray-300 px-3 py-2 rounded-md w-full"
                    >
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>
                  </div>
                </>
              )}

              <div className="flex justify-end gap-2 mt-4">
                <button
                  type="button"
                  className="w-1/2 px-4 py-2 rounded-full border border-gray-300 hover:bg-gray-100"
                  onClick={() => setModalOpen(false)}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="w-1/2 px-4 py-2 rounded-full bg-[#1F6728] text-white hover:bg-green-700"
                >
                  {editingAgent ? "Sanve Changes" : "Add Agent"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default DeliveryAgents;
