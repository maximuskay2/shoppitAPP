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
  Title,
  Tooltip,
  Legend,
  Filler,
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
  Tooltip,
  Legend,
  Filler
);

const ReportsCharts = () => {
  const [reportData, setReportData] = useState<any>(null);
  const [setOrderStats] = useState<any>(null);

  useEffect(() => {
    const fetchData = async () => {
      const token = localStorage.getItem("token");
      const headers = {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      };

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

        if (repJson.success) setReportData(repJson.data);
        if (ordJson.success) setOrderStats(ordJson.data);
      } catch (err) {
        console.error("Error fetching report charts:", err);
      }
    };
    fetchData();
  }, []);

  // 1. REVENUE BY CATEGORY (Bar Chart)
  // Mapping API category data if available, otherwise using top-performing segments
  const revenueData = {
    labels: reportData?.top_categories?.map((c: any) => c.name) || [
      "Electronics",
      "Fashion",
      "Home",
      "Health",
      "Groceries",
    ],
    datasets: [
      {
        label: "Revenue (₦)",
        data: reportData?.top_categories?.map((c: any) => c.total_revenue) || [
          12000, 9000, 7000, 5000, 3000,
        ],
        backgroundColor: "#1F6728",
        borderRadius: 8,
      },
    ],
  };

  // 2. USER GROWTH OVER TIME (Line Chart)
  const growthData = {
    labels: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ],
    datasets: [
      {
        fill: true,
        label: "User Growth",
        data:
          reportData?.charts?.user_growth_by_month?.map((m: any) => m.count) ||
          [],
        borderColor: "#1F6728",
        backgroundColor: "rgba(31, 103, 40, 0.1)",
        tension: 0.4,
        pointRadius: 4,
      },
    ],
  };

  // 3. VENDOR PERFORMANCE SHARE (Pie Chart)
  // We use the top_vendors array from the reports API to show performance share
  const vendorData = {
    labels: reportData?.top_vendors?.map((v: any) => v.store_name) || [
      "Top Vendors",
      "Others",
    ],
    datasets: [
      {
        data: reportData?.top_vendors?.map((v: any) => v.total_sales) || [
          70, 30,
        ],
        backgroundColor: [
          "#1F6728",
          "#facc15",
          "#3b82f6",
          "#ef4444",
          "#8b5cf6",
        ],
        hoverOffset: 15,
        borderWidth: 0,
      },
    ],
  };

  const options = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { grid: { display: false }, beginAtZero: true },
      x: { grid: { display: false } },
    },
  };

  if (!reportData)
    return (
      <div className="p-20 text-center text-gray-400 animate-pulse">
        Analyzing Market Data...
      </div>
    );

  return (
    <div className="p-4">
      <div className="flex flex-col lg:flex-row gap-6 mb-6">
        {/* Revenue By Category */}
        <div className="w-full lg:w-1/2 p-6 bg-white border border-gray-100 rounded-2xl shadow-sm">
          <p className="text-gray-400 font-bold mb-4 uppercase text-[10px] tracking-widest">
            Revenue By Category
          </p>
          <Bar data={revenueData} options={options} />
        </div>

        {/* User Growth */}
        <div className="w-full lg:w-1/2 p-6 bg-white border border-gray-100 rounded-2xl shadow-sm">
          <p className="text-gray-400 font-bold mb-4 uppercase text-[10px] tracking-widest">
            User Growth Over Time
          </p>
          <Line data={growthData} options={options} />
        </div>
      </div>

      {/* Vendor Performance */}
      <div className="bg-white border border-gray-100 rounded-2xl shadow-sm p-8">
        <p className="text-gray-400 font-bold mb-6 uppercase text-[10px] tracking-widest text-center">
          Vendor Performance Share
        </p>
        <div className="max-w-sm mx-auto">
          <Pie
            data={vendorData}
            options={{
              plugins: {
                legend: {
                  position: "bottom",
                  labels: {
                    usePointStyle: true,
                    padding: 25,
                    font: { weight: "bold" },
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

export default ReportsCharts;
