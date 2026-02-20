import { useState, useEffect, useCallback } from "react";
import { BiPlus, BiPencil, BiTrash, BiLoaderAlt, BiX } from "react-icons/bi";
import { apiUrl } from "../../lib/api";

interface Role {
  id: string;
  name: string;
  description: string;
  admins_count: number;
}

const AdminRoles = () => {
  const [roles, setRoles] = useState<Role[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [editingRole, setEditingRole] = useState<Role | null>(null);

  // Form State
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");

  const fetchRoles = useCallback(async () => {
    setLoading(true);
    const token = localStorage.getItem("token");
    try {
      const res = await fetch(
        apiUrl("/api/v1/admin/roles"),
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      const result = await res.json();
      if (result.success) setRoles(result.data.data);
    } catch (err) {
      console.error("Fetch error:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchRoles();
  }, [fetchRoles]);

  const handleDelete = async (id: string) => {
    if (!window.confirm("Delete this role?")) return;
    const token = localStorage.getItem("token");
    try {
      await fetch(
        apiUrl(`/api/v1/admin/roles/${id}`),
        {
          method: "DELETE",
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      fetchRoles();
    } catch (err) {
      alert("Failed to delete role");
    }
  };

  const openModal = (role?: Role) => {
    if (role) {
      setEditingRole(role);
      setName(role.name);
      setDescription(role.description);
    } else {
      setEditingRole(null);
      setName("");
      setDescription("");
    }
    setModalOpen(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim() || !description.trim())
      return alert("Please fill all fields");

    setSubmitting(true);
    const token = localStorage.getItem("token");

    try {
      if (editingRole) {
        // UPDATE ROLE (PUT)
        // Per your docs, PUT uses query parameters: /admin/roles/:id?name=...&description=...
        const url = apiUrl(
          `/api/v1/admin/roles/${editingRole.id}?name=${encodeURIComponent(
            name
          )}&description=${encodeURIComponent(description)}`
        );

        const res = await fetch(url, {
          method: "PUT",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        });
        const result = await res.json();
        if (result.success) {
          alert("Role updated successfully");
          setModalOpen(false);
          fetchRoles();
        }
      } else {
        // CREATE ROLE (POST)
        // Per your docs, POST uses FormData
        const formData = new FormData();
        formData.append("name", name);
        formData.append("description", description);

        const res = await fetch(
          apiUrl("/api/v1/admin/roles"),
          {
            method: "POST",
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
            body: formData,
          }
        );
        const result = await res.json();
        if (result.success) {
          alert("New role created successfully");
          setModalOpen(false);
          fetchRoles();
        }
      }
    } catch (err) {
      console.error("Save Error:", err);
      alert("An error occurred while saving the role.");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading)
    return (
      <div className="flex justify-center p-10">
        <BiLoaderAlt className="animate-spin text-3xl text-green-700" />
      </div>
    );

  return (
    <>
      <div className="flex justify-between items-center mb-6">
        <p className="text-xl font-bold text-gray-800 tracking-tight">
          Admin Roles & Permissions
        </p>
      </div>

      <div className="space-y-3 mb-6">
        {roles.map((role) => (
          <div
            key={role.id}
            className="px-5 py-4 rounded-2xl border-2 border-gray-50 bg-gray-50/30 flex justify-between items-center hover:border-green-100 transition-colors"
          >
            <div className="flex gap-4 items-center">
              <div className="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center font-bold text-green-700">
                {role.name.charAt(0)}
              </div>
              <p className="flex flex-col">
                <span className="font-bold text-gray-800">{role.name}</span>
                <span className="text-xs text-gray-500 font-medium">
                  {role.description}
                </span>
              </p>
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => openModal(role)}
                className="p-2 hover:bg-white rounded-full transition-all text-gray-400 hover:text-green-600"
              >
                <BiPencil className="text-xl" />
              </button>
              <button
                onClick={() => handleDelete(role.id)}
                className="p-2 hover:bg-white rounded-full transition-all text-gray-400 hover:text-red-500"
              >
                <BiTrash className="text-xl" />
              </button>
            </div>
          </div>
        ))}
      </div>

      <button
        onClick={() => openModal()}
        className="w-full border-2 border-dashed border-gray-200 text-gray-400 rounded-2xl flex items-center justify-center gap-3 px-4 py-4 hover:border-green-600 hover:text-green-600 transition-all font-bold uppercase text-xs tracking-widest"
      >
        <BiPlus className="text-lg" />
        Add New Role
      </button>

      {/* CREATE/EDIT MODAL */}
      {modalOpen && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-[100] p-4 backdrop-blur-sm">
          <form
            onSubmit={handleSave}
            className="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md relative"
          >
            <button
              type="button"
              onClick={() => setModalOpen(false)}
              className="absolute top-6 right-6 text-gray-400"
            >
              <BiX className="text-2xl" />
            </button>
            <h2 className="text-xl font-bold mb-6">
              {editingRole ? "Edit Role" : "Create Role"}
            </h2>

            <div className="space-y-4">
              <div>
                <label className="block mb-1 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                  Role Name
                </label>
                <input
                  required
                  className="w-full border-2 border-gray-50 bg-gray-50 rounded-full px-5 py-3 outline-none focus:bg-white focus:border-green-600 transition-all"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                />
              </div>
              <div>
                <label className="block mb-1 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                  Description
                </label>
                <textarea
                  required
                  rows={3}
                  className="w-full border-2 border-gray-50 bg-gray-50 rounded-2xl px-5 py-3 outline-none focus:bg-white focus:border-green-600 transition-all"
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                />
              </div>
            </div>

            <button
              disabled={submitting}
              className="w-full bg-[#1F6728] text-white py-4 rounded-full font-bold mt-8 shadow-lg shadow-green-900/20 active:scale-95 transition-all disabled:bg-gray-300"
            >
              {submitting
                ? "Processing..."
                : editingRole
                ? "Update Role"
                : "Create Role"}
            </button>
          </form>
        </div>
      )}
    </>
  );
};

export default AdminRoles;
