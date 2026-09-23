import { API_BASE } from "../config";
import { useState, useEffect } from "react";
import Layout from "../components/Layout";
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  Legend,
  CartesianGrid,
  ResponsiveContainer,
} from "recharts";

function Dashboard() {
  const [summary, setSummary] = useState({
    total_cylinders: 0,
    in_stock: 0,
    ready: 0,
  });

  // State สำหรับเก็บข้อมูลแจ้งเตือน TopBar
  const [topbarStats, setTopbarStats] = useState({
    gasLevel: 0,
    maintenanceCount: 0,
    expiredCount: 0,
    successCount: 0,
  });

  const [staffRounds, setStaffRounds] = useState([]);
  const [nearDueCylinders, setNearDueCylinders] = useState([]);
  const [gasTypeData, setGasTypeData] = useState([]);
  const [deliveryChartData, setDeliveryChartData] = useState([]);

  // ฟังก์ชันช่วย Safe Fetch JSON
  const safeFetchJson = async (url) => {
    try {
      const res = await fetch(url);
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch (e) {
        console.warn(`JSON Parse error at [${url}]:`, text);
        return null;
      }
    } catch (err) {
      console.error(`Fetch error at [${url}]:`, err);
      return null;
    }
  };

  // ดึงข้อมูลสำหรับ TopBar Notifications
  const fetchTopbarStats = async () => {
    const data = await safeFetchJson(`${API_BASE}/get_topbar_stats.php`);
    if (data && data.success) {
      setTopbarStats({
        gasLevel: data.gasLevel || 0,
        maintenanceCount: data.maintenanceCount || 0,
        expiredCount: data.expiredCount || 0,
        successCount: data.successCount || 0,
      });
    }
  };

  const fetchSummary = async () => {
    const summaryData = await safeFetchJson(`${API_BASE}/get_dashboard_summary.php`);
    
    const gasLevelData = await safeFetchJson(`${API_BASE}/get_gas_level.php`);

    if (summaryData && summaryData.success) {
      const readyVal = gasLevelData?.ready ?? gasLevelData?.ready_cylinders ?? summaryData.data?.ready ?? 0;

      setSummary({
        total_cylinders: summaryData.data?.total_cylinders || 0,
        in_stock: summaryData.data?.in_stock || 0,
        ready: readyVal, 
      });
    }
  };

  const fetchStaffRounds = async () => {
    const data = await safeFetchJson(`${API_BASE}/get_staff_delivery_rounds.php`);
    if (data && data.success) setStaffRounds(data.data);
  };

  const fetchNearDueCylinders = async () => {
    const data = await safeFetchJson(`${API_BASE}/get_near_due_cylinders.php`);
    if (data && data.success) setNearDueCylinders(data.data);
  };

  const fetchGasTypeChart = async () => {
    const data = await safeFetchJson(`${API_BASE}/get_gas_type_chart.php`);
    if (data && data.success) setGasTypeData(data.data);
  };

  const fetchDeliveryChart = async () => {
    const data = await safeFetchJson(`${API_BASE}/get_delivery_chart.php`);
    if (data && data.success) setDeliveryChartData(data.data);
  };

  useEffect(() => {
    fetchTopbarStats();
    fetchSummary();
    fetchStaffRounds();
    fetchNearDueCylinders();
    fetchGasTypeChart();
    fetchDeliveryChart();

    // ดึงข้อมูลอัปเดตแจ้งเตือนทุก 5 วินาที
    const interval = setInterval(fetchTopbarStats, 5000);
    return () => clearInterval(interval);
  }, []);

  return (
    <Layout
      gasLevel={topbarStats.gasLevel}
      maintenanceDueItems={new Array(topbarStats.maintenanceCount).fill(0)}
      expiredCylinderItems={new Array(topbarStats.expiredCount).fill(0)}
      deliverySuccessItems={new Array(topbarStats.successCount).fill(0)}
    >
      <div style={pageStyle}>
        <h1 style={titleStyle}>Dashboard</h1>

        {/* ========== SUMMARY CARDS ========== */}
        <div style={cardGridStyle}>
          <Card title="ถังทั้งหมด" value={summary.total_cylinders} />
          <Card title="ในคลัง" value={summary.in_stock} />
          <Card title="พร้อมใช้งาน" value={summary.ready} />
        </div>

        <div style={panelStyle}>
          <h2 style={panelTitleStyle}>จำนวนถังแยกตามประเภทแก๊ส</h2>
          {gasTypeData.length === 0 ? (
            <div style={emptyTextStyle}>ไม่มีข้อมูล</div>
          ) : (
            <ResponsiveContainer width="100%" height={200}>
              <BarChart data={gasTypeData}>
                <YAxis 
                  axisLine={false} 
                  tickLine={false} 
                  tick={{ fill: "#9ca3af", fontSize: 12 }} 
                />
                
                <XAxis 
                  dataKey="gas_type" 
                  axisLine={false} 
                  tickLine={false} 
                  tick={{ fill: "#9ca3af", fontSize: 12 }} 
                />
                
                <Tooltip />
                <Bar dataKey="total" fill="#2563eb" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          )}
        </div>

        {/* ========== GRAPH 2: ออเดอร์รายวัน ========== */}
        <div style={panelStyle}>
          <h2 style={panelTitleStyle}>จำนวนออเดอร์ส่งมอบรายวัน</h2>
          {deliveryChartData.length === 0 ? (
            <div style={emptyTextStyle}>ไม่มีข้อมูล</div>
          ) : (
            <ResponsiveContainer width="100%" height={320}>
              <LineChart data={deliveryChartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="delivery_day" axisLine={false} tickLine={false} />
                <YAxis axisLine={false} tickLine={false} />
                <Tooltip />
                <Legend />
                <Line
                  type="monotone"
                  dataKey="total_orders"
                  stroke="#22c55e"
                  strokeWidth={3}
                  name="จำนวนออเดอร์"
                />
              </LineChart>
            </ResponsiveContainer>
          )}
        </div>

        {/* ========== STAFF DELIVERY ROUNDS ========== */}
        <div style={panelStyle}>
          <h2 style={panelTitleStyle}>รอบส่งพนักงาน</h2>
          {staffRounds.length === 0 ? (
            <div style={emptyTextStyle}>ยังไม่มีข้อมูลการส่ง</div>
          ) : (
            staffRounds.map((item, idx) => (
              <div key={item.staff_name || idx} style={listRowStyle}>
                <span>
                  #{idx + 1} {item.staff_name} {idx === 0 && "🏆"}
                </span>
                <strong>{item.count} รอบ</strong>
              </div>
            ))
          )}
        </div>
      </div>
    </Layout>
  );
}

const Card = ({ title, value }) => (
  <div style={summaryCardStyle}>
    <div>{title}</div>
    <h2>{value}</h2>
  </div>
);

const pageStyle = { padding: "24px", color: "white" };
const titleStyle = { fontSize: "32px", marginBottom: "20px" };
const cardGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(200px, 1fr))", gap: "16px", marginBottom: "20px" };
const summaryCardStyle = { background: "#1f2937", padding: "16px", borderRadius: "12px" };
const panelStyle = { background: "#1f2937", padding: "20px", borderRadius: "12px", marginBottom: "20px" };
const panelTitleStyle = { marginBottom: "16px" };
const emptyTextStyle = { color: "#cbd5e1", textAlign: "center", padding: "40px" };
const listRowStyle = { display: "flex", justifyContent: "space-between", padding: "10px", background: "#111827", borderRadius: "8px", marginBottom: "8px" };
const tableStyle = { width: "100%", borderCollapse: "collapse", textAlign: "left" };
const thStyle = { padding: "12px", borderBottom: "1px solid #374151" };
const tdStyle = { padding: "12px", borderBottom: "1px solid #374151" };

export default Dashboard;