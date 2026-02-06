import { useState, useEffect } from "react";
import { TiTick } from "react-icons/ti";
import Charts from "./dashboardCharts";
import users from "../assets/users.png";
import revenue from "../assets/revenue.png";
import orders from "../assets/orders.png";
import vendors from "../assets/vendors.png";
import pendingApproval from "../assets/pendingApproval.png";
import completedOrders from "../assets/completedOrders.png";
import { apiUrl } from "../lib/api";

const Dashboard = () => {
  const [stats, setStats] = useState({
    totalUsers: 0,
    activeVendors: 0, // Derived from stats or recent activity
    ordersToday: 0,
    totalRevenue: 0,
    completedOrders: 0,
    pendingApproval: 0,
  });
  const [recentOrders, setRecentOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDashboardData = async () => {
      const token = localStorage.getItem("token");
      const headers = {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      };

      try {
        // Fetch all required data in parallel
        const [orderStatsRes, promoStatsRes, recentOrdersRes, reportsRes] =
          await Promise.all([
            fetch(
              apiUrl(
                "/api/v1/admin/order-management/fetch/stats?month=12&year=2025"
              ),
              { headers }
            ),
            fetch(
              apiUrl("/api/v1/admin/promotion-management/stats"),
              { headers }
            ),
            fetch(
              apiUrl("/api/v1/admin/order-management?per_page=5"),
              { headers }
            ),
            fetch(
              apiUrl("/api/v1/admin/reports?year=2025"),
              { headers }
            ),
          ]);

        const orderData = await orderStatsRes.json();
        const promoData = await promoStatsRes.json();
        const ordersListData = await recentOrdersRes.json();
        const reportsData = await reportsRes.json();

        if (
          orderData.success &&
          promoData.success &&
          ordersListData.success &&
          reportsData.success
        ) {
          setStats({
            totalUsers: reportsData.data.new_users || 0,
            activeVendors: promoData.data.total_promotions || 0, // Representative of vendor engagement
            ordersToday: orderData.data.total_orders_today || 0,
            totalRevenue: orderData.data.total_revenue || 0,
            completedOrders: orderData.data.total_completed_orders || 0,
            pendingApproval: promoData.data.scheduled_promotions || 0,
          });
          setRecentOrders(ordersListData.data.data);
        }
      } catch (error) {
        console.error("Dashboard fetch error:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchDashboardData();
  }, []);

  if (loading) {
    return (
      <div className="relative w-full h-[60vh] flex flex-col items-center justify-center">
        <div className="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-[#1F6728]"></div>
        <p className="mt-4 text-gray-500 animate-pulse text-sm">
          Loading dashboard data...
        </p>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-4">
        <p className="text-2xl font-bold text-gray-800">Dashboard Overview</p>
        <p className="text-gray-500">
          Welcome back! Here's what's happening with your marketplace today.
        </p>
      </div>

      {/* KPI Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div className="rounded-md p-6 text-sm text-gray-600 border border-gray-200">
          <img src={users} className="w-9" />
          <p className="text-2xl font-bold text-black mt-2">
            {stats.totalUsers.toLocaleString()}
          </p>
          <p className="font-semibold text-gray-400 uppercase tracking-wider">
            Total Users
          </p>
        </div>
        <div className="rounded-md p-6 text-sm text-gray-600 border border-gray-200">
          <img src={vendors} className="w-9" />
          <p className="text-2xl font-bold text-black mt-2">
            {stats.activeVendors}
          </p>
          <p className="font-semibold text-gray-400 uppercase tracking-wider">
            Active Vendors
          </p>
        </div>
        <div className="rounded-md p-6 text-sm text-gray-600 border border-gray-200">
          <img src={orders} className="w-9" />
          <p className="text-2xl font-bold text-black mt-2">
            {stats.ordersToday}
          </p>
          <p className="font-semibold text-gray-400 uppercase tracking-wider">
            Orders Today
          </p>
        </div>
        <div className="rounded-md p-6 text-sm text-gray-600 border border-gray-200">
          <img src={revenue} className="w-9" />
          <p className="text-2xl font-bold text-black mt-2">
            ₦{stats.totalRevenue.toLocaleString()}
          </p>
          <p className="font-semibold text-gray-400 uppercase tracking-wider">
            Total Revenue
          </p>
        </div>
        <div className="rounded-md p-6 text-sm text-gray-600 border border-gray-200">
          <img src={completedOrders} className="w-9" />
          <p className="text-2xl font-bold text-black mt-2">
            {stats.completedOrders}
          </p>
          <p className="font-semibold text-gray-400 uppercase tracking-wider">
            Completed Orders
          </p>
        </div>
        <div className="rounded-md p-6 text-sm text-gray-600 border border-gray-200">
          <img src={pendingApproval} className="w-9" />
          <p className="text-2xl font-bold text-black mt-2">
            {stats.pendingApproval}
          </p>
          <p className="font-semibold text-gray-400 uppercase tracking-wider">
            Pending Approval
          </p>
        </div>
      </div>

      <Charts />

      <div className="flex flex-col lg:flex-row gap-3 justify-between my-5">
        {/* Recent Orders Table */}
        <div className="border border-gray-200 p-4 rounded-md w-full lg:w-1/2">
          <p className="text-gray-700 font-bold mb-3 border-b border-gray-100 pb-3 uppercase text-xs tracking-widest">
            Recent Orders
          </p>
          <table className="w-full text-left text-sm text-gray-600">
            <thead>
              <tr className="text-gray-400 text-[10px] uppercase font-bold border-b border-gray-50">
                <th className="py-2">Order ID</th>
                <th className="py-2">Customer</th>
                <th className="py-2">Total</th>
                <th className="py-2">Status</th>
              </tr>
            </thead>
            <tbody>
              {recentOrders.map((order) => (
                <tr
                  key={order.id}
                  className="hover:bg-gray-50 transition-colors border-b border-gray-50"
                >
                  <td className="py-3 font-mono text-[10px]">
                    {order.tracking_id}
                  </td>
                  <td className="font-medium text-gray-800">
                    {order.receiver_name}
                  </td>
                  <td className="font-bold">
                    ₦{order.net_total_amount.toLocaleString()}
                  </td>
                  <td>
                    <span
                      className={`text-[10px] font-bold px-2 py-1 rounded-full ${
                        order.status === "COMPLETED"
                          ? "bg-green-100 text-green-700"
                          : "bg-yellow-100 text-yellow-700"
                      }`}
                    >
                      {order.status}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Recent Vendors (Static or mapped from dynamic vendor list) */}
        <div className="border border-gray-200 p-4 rounded-md w-full lg:w-1/2">
          <p className="text-gray-700 font-bold mb-3 border-b border-gray-100 pb-3 uppercase text-xs tracking-widest">
            Recent Vendor Activity
          </p>
          <ul className="space-y-4 text-sm text-gray-600">
            {/* Example of dynamic mapping if you fetch vendor list */}
            <li className="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
              <div>
                <p className="font-bold text-gray-800">Fish pie place</p>
                <p className="text-xs text-gray-400">Akwa Ibom</p>
              </div>
              <p className="text-[#1F6728] text-xs font-bold gap-1 flex items-center bg-green-50 px-3 py-1 rounded-full">
                <TiTick /> Approved
              </p>
            </li>
          </ul>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
