import { useState, useEffect } from "react";
import { Line, Bar, Pie } from "react-chartjs-2";
import {
  Chart as ChartJS,
  LineElement,
  BarElement,
  ArcElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Filler,
  Title,
  Tooltip,
  Legend,
} from "chart.js";
import { apiUrl } from "../lib/api";

ChartJS.register(
  LineElement,
  BarElement,
  ArcElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Title,
  Filler,
  Tooltip,
  Legend
);

const Charts = () => {
  const [reportData, setReportData] = useState<any>(null);
  const [orderStats, setOrderStats] = useState<any>(null);

  // Array to map month numbers (1-12) to names
  const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];

  useEffect(() => {
    const fetchChartData = async () => {
      const token = localStorage.getItem("token");
      const headers = { Authorization: `Bearer ${token}` };

      try {
        const [repRes, ordRes] = await Promise.all([
          fetch(
            apiUrl("/api/v1/admin/reports?year=2025"),
            { headers }
          ),
          fetch(
            apiUrl(
              "/api/v1/admin/order-management/fetch/stats?month=12&year=2025"
            ),
            { headers }
          ),
        ]);

        const repJson = await repRes.json();
        const ordJson = await ordRes.json();

        if (repJson.success) setReportData(repJson.data.charts);
        if (ordJson.success) setOrderStats(ordJson.data);
      } catch (err) {
        console.error("Error fetching chart data", err);
      }
    };
    fetchChartData();
  }, []);

  // Transformation for Sales Overview (Line Chart)
  const salesChartData = {
    // Convert Month 1 -> January, Month 2 -> February
    labels:
      reportData?.monthly_sales_overview.map(
        (m: any) => monthNames[m.month - 1]
      ) || [],
    datasets: [
      {
        label: "Monthly Sales (₦)",
        data: reportData?.monthly_sales_overview.map((m: any) => m.total) || [],
        borderColor: "#1F6728",
        backgroundColor: "rgba(31, 103, 40, 0.1)",
        fill: true,
        tension: 0.4,
      },
    ],
  };

  // Transformation for User Growth (Bar Chart)
  const userGrowthData = {
    labels:
      reportData?.user_growth_by_month.map(
        (m: any) => monthNames[m.month - 1]
      ) || [],
    datasets: [
      {
        label: "New Users",
        data: reportData?.user_growth_by_month.map((m: any) => m.count) || [],
        backgroundColor: "#1F6728",
        borderRadius: 5,
      },
    ],
  };

  // Transformation for Order Breakdown (Pie Chart)
  const orderBreakdownData = {
    labels: orderStats?.status_breakdown.map((s: any) => s.status) || [],
    datasets: [
      {
        data: orderStats?.status_breakdown.map((s: any) => s.count) || [],
        backgroundColor: [
          "#1F6728",
          "#facc15",
          "#3b82f6",
          "#ef4444",
          "#8b5cf6",
          "#6b7280",
          "#ec4899",
        ],
        hoverOffset: 10,
      },
    ],
  };

  if (!reportData || !orderStats)
    return (
      <div className="p-10 text-center text-gray-400 font-bold">
        Updating Analytics...
      </div>
    );

  return (
    <div className="mt-6 space-y-6">
      <div className="flex flex-col lg:flex-row gap-6 my-5">
        <div className="w-full lg:w-1/2 p-6 border border-gray-100 rounded-2xl">
          <p className="text-gray-400 font-bold mb-4 uppercase text-[10px] tracking-widest">
            Sales Overview
          </p>
          <Line
            data={salesChartData}
            options={{
              responsive: true,
              plugins: { legend: { display: false } },
            }}
          />
        </div>
        <div className="w-full lg:w-1/2 p-6 border border-gray-100 rounded-2xl">
          <p className="text-gray-400 font-bold mb-4 uppercase text-[10px] tracking-widest">
            User Growth
          </p>
          <Bar
            data={userGrowthData}
            options={{
              responsive: true,
              plugins: { legend: { display: false } },
            }}
          />
        </div>
      </div>

      <div className="border p-8 border-gray-100 rounded-2xl bg-white shadow-sm">
        <p className="text-gray-400 font-bold mb-6 uppercase text-[10px] tracking-widest text-center">
          Order Status Distribution
        </p>
        <div className="max-w-md mx-auto">
          <Pie
            data={orderBreakdownData}
            options={{
              plugins: {
                legend: {
                  position: "bottom",
                  labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 12, weight: "bold" },
                  },
                },
              },
            }}
          />
        </div>
      </div>
    </div>
  );
};

export default Charts;
