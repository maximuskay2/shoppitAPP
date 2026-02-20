import { useState } from "react";
import {
  BiTrash,
  BiEdit,
  BiKey,
  BiLocationPlus,
  BiLink,
  BiCalendar,
} from "react-icons/bi";

export type ProviderFormData = {
  name: string;
  status: string;
  location: string;
  webhookURL: string;
  APIKey: string;
};

type Provider = {
  id: string;
  name: string;
  status: string;
  joinDate: string;
  location: string;
  webhookURL: string;
  APIKey: string;
};

type ProvidersProps = {
  onEditProvider: (data: ProviderFormData) => void;
  onDeleteProvider: (id: string) => void;
};

const Providers = ({ onEditProvider, onDeleteProvider }: ProvidersProps) => {
  const [searchTerm, setSearchTerm] = useState("");

  const providers: Provider[] = [
    {
      id: "P001",
      name: "Provider One",
      status: "Active",
      joinDate: "2025-01-10",
      location: "Lagos",
      webhookURL: "https://providerone.com/webhook",
      APIKey: "ABC123XYZ",
    },
    {
      id: "P002",
      name: "Provider Two",
      status: "Inactive",
      joinDate: "2025-03-15",
      location: "Abuja",
      webhookURL: "https://providertwo.com/webhook",
      APIKey: "XYZ789ABC",
    },
    {
      id: "P003",
      name: "Provider Three",
      status: "Active",
      joinDate: "2025-06-05",
      location: "Port Harcourt",
      webhookURL: "https://providerthree.com/webhook",
      APIKey: "LMN456OPQ",
    },
  ];

  // Filter providers by search term
  const filteredProviders = providers.filter(
    (p) =>
      p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.location.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.status.toLowerCase().includes(searchTerm.toLowerCase())
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

      {/* Providers Grid */}
      <div className="flex flex-col gap-6">
        {filteredProviders.map((provider) => (
          <div
            key={provider.id}
            className="rounded-md shadow-md border gap-6 border-gray-200 p-4 flex items-center justify-between"
          >
            <div className="flex-1">
              <p className="flex gap-3 items-center">
                <span>{provider.name}</span>
                <span
                  className={`px-2 py-1 rounded-full text-xs font-semibold ${
                    provider.status === "Active"
                      ? "bg-green-100 text-green-700"
                      : "bg-red-100 text-red-700"
                  }`}
                >
                  {provider.status}
                </span>
              </p>

              <div className="flex mt-2 text-sm w-full">
                <div className="flex-1">
                  <div className="flex gap-4 text-gray-600">
                    <BiKey className="text-2xl" />
                    <p className="flex justify-start flex-col mb-4">
                      <span>API Key</span>
                      <span>{provider.APIKey}</span>
                    </p>
                  </div>

                  <div className="flex gap-2 text-gray-600">
                    <BiLocationPlus className="text-2xl" />
                    <p className="flex justify-start flex-col">
                      <span>Region</span>
                      <span>{provider.location}</span>
                    </p>
                  </div>
                </div>

                <div className="flex-1">
                  <div className="flex gap-4 text-gray-600">
                    <BiLink className="text-2xl" />
                    <p className="flex justify-start flex-col mb-4">
                      <span>Webhook URL</span>
                      <span>{provider.webhookURL}</span>
                    </p>
                  </div>

                  <div className="flex gap-2 text-gray-600">
                    <BiCalendar className="text-2xl" />
                    <p className="flex justify-start flex-col">
                      <span>Created At</span>
                      <span>{provider.joinDate}</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="flex flex-col gap-4">
              <button
                className="flex-1 bg-gray-100 px-4 py-2 rounded-full flex items-center justify-center gap-2 hover:bg-gray-200 transition"
                onClick={() =>
                  onEditProvider({
                    name: provider.name,
                    status: provider.status,
                    location: provider.location,
                    webhookURL: provider.webhookURL,
                    APIKey: provider.APIKey,
                  })
                }
              >
                <BiEdit />
                Edit
              </button>
              <button
                className="flex-1 bg-red-500 text-white px-4 py-2 rounded-full flex items-center justify-center gap-2 hover:bg-red-600 transition"
                onClick={() => onDeleteProvider(provider.id)}
              >
                <BiTrash />
                Delete
              </button>
            </div>
          </div>
        ))}

        {filteredProviders.length === 0 && (
          <p className="col-span-full text-center text-gray-400 italic">
            No providers found.
          </p>
        )}
      </div>
    </div>
  );
};

export default Providers;
