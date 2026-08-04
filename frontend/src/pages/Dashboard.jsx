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
    expired: 0,
  });

  const [staffRounds, setStaffRounds] = useState([]);
  const [nearDueCylinders, setNearDueCylinders] = useState([]);
  const [gasTypeData, setGasTypeData] = useState([]);
  const [deliveryChartData, setDeliveryChartData] = useState([]);

  // ฟังก์ชันช่วย Safe Fetch JSON
  const safeFetchJson = async (url) => {
    try {
      const res = await fetch(url);
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

  const fetchSummary = async () => {
    const data = await safeFetchJson("http://localhost/Backend/models/get_dashboard_summary.php");
    if (data && data.success) setSummary(data.data);
  };

  const fetchStaffRounds = async () => {
    const data = await safeFetchJson("http://localhost/Backend/models/get_staff_delivery_rounds.php");
    if (data && data.success) setStaffRounds(data.data);
  };

  const fetchNearDueCylinders = async () => {
    const data = await safeFetchJson("http://localhost/Backend/models/get_near_due_cylinders.php");
    if (data && data.success) setNearDueCylinders(data.data);
  };

  const fetchGasTypeChart = async () => {
    const data = await safeFetchJson("http://localhost/Backend/models/get_gas_type_chart.php");
    if (data && data.success) setGasTypeData(data.data);
  };

  const fetchDeliveryChart = async () => {
    const data = await safeFetchJson("http://localhost/Backend/models/get_delivery_chart.php");
    if (data && data.success) setDeliveryChartData(data.data);
  };

  useEffect(() => {
    fetchSummary();
    fetchStaffRounds();
    fetchNearDueCylinders();
    fetchGasTypeChart();
    fetchDeliveryChart();
  }, []);

  return (
    <Layout>
      <div style={pageStyle}>
        <h1 style={titleStyle}>Dashboard</h1>

        {/* ========== SUMMARY CARDS ========== */}
        <div style={cardGridStyle}>
          <Card title="ถังทั้งหมด" value={summary.total_cylinders} />
          <Card title="ในคลัง" value={summary.in_stock} />
          <Card title="พร้อมใช้งาน" value={summary.ready} />
          <Card title="หมดอายุ" value={summary.expired} />
        </div>

        {/* ========== GRAPH 1: ประเภทแก๊ส ========== */}
        <div style={panelStyle}>
          <h2 style={panelTitleStyle}>📊 จำนวนถังแยกตามประเภทแก๊ส</h2>
          {gasTypeData.length === 0 ? (
            <div style={emptyTextStyle}>ไม่มีข้อมูล</div>
          ) : (
            <ResponsiveContainer width="100%" height={320}>
              <BarChart data={gasTypeData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="gas_type" />
                <YAxis />
                <Tooltip />
                <Legend />
                <Bar dataKey="total" fill="#3b82f6" name="จำนวนถัง" />
              </BarChart>
            </ResponsiveContainer>
          )}
        </div>

        {/* ========== GRAPH 2: ออเดอร์รายวัน ========== */}
        <div style={panelStyle}>
          <h2 style={panelTitleStyle}>📈 จำนวนออเดอร์ส่งมอบรายวัน</h2>
          {deliveryChartData.length === 0 ? (
            <div style={emptyTextStyle}>ไม่มีข้อมูล</div>
          ) : (
            <ResponsiveContainer width="100%" height={320}>
              <LineChart data={deliveryChartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="delivery_day" />
                <YAxis />
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
          <h2 style={panelTitleStyle}>🚚 รอบส่งพนักงาน</h2>
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

        {/* ========== NEAR DUE CYLINDERS ========== */}
        <div style={panelStyle}>
          <h2 style={panelTitleStyle}>⚠️ ถังใกล้ถึงกำหนดตรวจ (30 วัน)</h2>
          <table style={tableStyle}>
            <thead>
              <tr>
                <th style={thStyle}>รหัสถัง</th>
                <th style={thStyle}>วันตรวจครั้งถัดไป</th>
                <th style={thStyle}>คงเหลือ (วัน)</th>
              </tr>
            </thead>
            <tbody>
              {nearDueCylinders.length === 0 ? (
                <tr>
                  <td colSpan="3" align="center" style={{ padding: "20px" }}>
                    ไม่มีถังใกล้หมดอายุ
                  </td>
                </tr>
              ) : (
                nearDueCylinders.map((item, index) => (
                  <tr key={item.cylinder_id || item.serial_number || index}>
                    <td style={tdStyle}>{item.serial_number || item.cylinder_id || "-"}</td>
                    <td style={tdStyle}>{item.next_check_date || "-"}</td>
                    <td style={{ ...tdStyle, color: (item.days_left || 0) <= 0 ? "#ef4444" : "#f59e0b" }}>
                      {item.days_left ?? "-"} วัน
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
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