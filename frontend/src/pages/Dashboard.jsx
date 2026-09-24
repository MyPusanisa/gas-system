import { API_BASE } from "../config";
import { useState, useEffect } from "react";
import Layout from "../components/Layout";
import { Cylinder, Warehouse, CircleCheck, TriangleAlert, Trophy } from "lucide-react";
import {
  AreaChart,
  Area,
  BarChart,
  Bar,
  LabelList,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  ResponsiveContainer,
} from "recharts";

// "2026-08-01" -> "01/08/2026"
const formatDate = (dateStr) => {
  if (!dateStr || typeof dateStr !== "string") return "-";
  const [y, m, d] = dateStr.slice(0, 10).split("-");
  return y && m && d ? `${d}/${m}/${y}` : dateStr;
};

// "2026-08-01" -> "01/08"
const formatShortDate = (dateStr) => {
  if (!dateStr || typeof dateStr !== "string") return "";
  const [, m, d] = dateStr.slice(0, 10).split("-");
  return d && m ? `${d}/${m}` : dateStr;
};

const todayThai = new Date().toLocaleDateString("th-TH", {
  weekday: "long",
  day: "numeric",
  month: "long",
  year: "numeric",
});

function Dashboard() {
  const [summary, setSummary] = useState({
    total_cylinders: 0,
    in_stock: 0,
    ready: 0,
    maintenance_due: 0,
    delivered: 0,
  });

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

  const safeFetchJson = async (url) => {
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch {
        return null;
      }
    } catch {
      return null;
    }
  };

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
    // คำนวณจากฐานข้อมูลใน get_dashboard_summary.php
    const summaryData = await safeFetchJson(`${API_BASE}/get_dashboard_summary.php`);
    if (summaryData && summaryData.success) {
      setSummary({
        total_cylinders: summaryData.total_cylinders ?? 0,
        in_stock: summaryData.in_stock ?? 0,
        ready: summaryData.ready_to_use ?? 0,
        maintenance_due: summaryData.maintenance_due ?? 0,
        delivered: summaryData.delivering_or_delivered ?? 0,
      });
    }
  };

  const fetchStaffRounds = async () => {
    const data = await safeFetchJson(`${API_BASE}/get_staff_delivery_rounds.php`);
    if (data && data.success) setStaffRounds(data.data);
  };

  const fetchNearDueCylinders = async () => {
    // ใช้ข้อมูลเดียวกับหน้า Maintenance (วันตรวจจริงของทุกถัง เรียงใกล้กำหนดก่อน)
    const data = await safeFetchJson(`${API_BASE}/get_due_cylinders.php`);
    if (data && data.success && Array.isArray(data.data)) {
      setNearDueCylinders(data.data.filter((row) => row.days_left !== null && row.days_left !== undefined));
    }
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

    const interval = setInterval(fetchTopbarStats, 5000);
    return () => clearInterval(interval);
  }, []);

  const maxRounds = Math.max(1, ...staffRounds.map((s) => Number(s.count) || 0));
  const totalOrders = deliveryChartData.reduce((sum, d) => sum + (Number(d.total_orders) || 0), 0);
  const overdueCount = nearDueCylinders.filter((r) => r.days_left < 0).length;

  return (
    <Layout
      gasLevel={topbarStats.gasLevel}
      maintenanceDueItems={new Array(topbarStats.maintenanceCount).fill(0)}
      expiredCylinderItems={new Array(topbarStats.expiredCount).fill(0)}
      deliverySuccessItems={new Array(topbarStats.successCount).fill(0)}
    >
      <div style={pageStyle}>
        <div style={headerStyle}>
          <div>
            <h1 style={titleStyle}>Dashboard</h1>
            <div style={subtitleStyle}>ภาพรวมระบบถังแก๊ส · {todayThai}</div>
          </div>
        </div>

        {/* ========== SUMMARY CARDS ========== */}
        <div style={cardGridStyle}>
          <StatCard
            icon={Cylinder}
            accent="#3b82f6"
            title="ถังทั้งหมด"
            value={summary.total_cylinders}
            caption={`ในคลัง ${summary.in_stock} + จัดส่ง ${summary.delivered}`}
          />
          <StatCard
            icon={Warehouse}
            accent="#8b5cf6"
            title="ในคลัง"
            value={summary.in_stock}
            caption="ถังสถานะในคลัง"
          />
          <StatCard
            icon={CircleCheck}
            accent="#10b981"
            title="พร้อมใช้งาน"
            value={summary.ready}
            caption="ในคลัง − ถึงกำหนดตรวจ"
          />
          <StatCard
            icon={TriangleAlert}
            accent="#ef4444"
            title="ถึงกำหนดตรวจ"
            value={summary.maintenance_due}
            valueColor={summary.maintenance_due > 0 ? "#f87171" : undefined}
            caption="ต้องส่งตรวจบำรุง"
          />
        </div>

        {/* ========== ROW: ประเภทแก๊ส + รอบส่งพนักงาน ========== */}
        <div style={twoColGridStyle}>
          <div style={panelStyle}>
            <PanelHeader title="ถังแยกตามประเภทแก๊ส" />
            {gasTypeData.length === 0 ? (
              <div style={emptyTextStyle}>ไม่มีข้อมูล</div>
            ) : (
              <ResponsiveContainer width="100%" height={240}>
                <BarChart data={gasTypeData} margin={{ top: 24, right: 8, left: -20, bottom: 0 }}>
                  <CartesianGrid vertical={false} stroke="#2b3647" />
                  <XAxis dataKey="gas_type" axisLine={false} tickLine={false} tick={{ fill: "#9ca3af", fontSize: 12 }} />
                  <YAxis allowDecimals={false} axisLine={false} tickLine={false} tick={{ fill: "#6b7280", fontSize: 11 }} />
                  <Tooltip cursor={{ fill: "rgba(255,255,255,0.04)" }} content={<ChartTooltip unit="ถัง" />} />
                  <Bar dataKey="total" name="จำนวน" fill="#3b82f6" radius={[4, 4, 0, 0]} maxBarSize={56}>
                    <LabelList dataKey="total" position="top" fill="#e5e7eb" fontSize={12} />
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            )}
          </div>

          <div style={panelStyle}>
            <PanelHeader title="รอบส่งพนักงาน" />
            {staffRounds.length === 0 ? (
              <div style={emptyTextStyle}>ยังไม่มีข้อมูลการส่ง</div>
            ) : (
              <div style={{ display: "flex", flexDirection: "column", gap: "14px" }}>
                {staffRounds.map((item, idx) => {
                  const count = Number(item.count) || 0;
                  return (
                    <div key={item.staff_name || idx}>
                      <div style={rankRowStyle}>
                        <span style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                          <span style={rankBadgeStyle(idx)}>{idx === 0 && count > 0 ? <Trophy size={14} /> : idx + 1}</span>
                          <span style={{ fontWeight: 600 }}>{item.staff_name}</span>
                        </span>
                        <span style={{ color: "#e5e7eb", fontWeight: "bold" }}>
                          {count} <span style={{ color: "#9ca3af", fontWeight: "normal" }}>รอบ</span>
                        </span>
                      </div>
                      <div style={progressTrackStyle}>
                        <div style={{ ...progressFillStyle, width: `${(count / maxRounds) * 100}%` }} />
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* ========== ออเดอร์รายวัน ========== */}
        <div style={panelStyle}>
          <PanelHeader title="ออเดอร์ส่งมอบรายวัน" meta={totalOrders > 0 ? `รวม ${totalOrders} ออเดอร์` : null} />
          {deliveryChartData.length === 0 ? (
            <div style={emptyTextStyle}>ไม่มีข้อมูล</div>
          ) : (
            <ResponsiveContainer width="100%" height={280}>
              <AreaChart data={deliveryChartData} margin={{ top: 10, right: 12, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="ordersFill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#10b981" stopOpacity={0.35} />
                    <stop offset="100%" stopColor="#10b981" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid vertical={false} stroke="#2b3647" />
                <XAxis
                  dataKey="delivery_day"
                  tickFormatter={formatShortDate}
                  axisLine={false}
                  tickLine={false}
                  tick={{ fill: "#9ca3af", fontSize: 11 }}
                  minTickGap={16}
                />
                <YAxis allowDecimals={false} axisLine={false} tickLine={false} tick={{ fill: "#6b7280", fontSize: 11 }} />
                <Tooltip
                  cursor={{ stroke: "#4b5563", strokeWidth: 1 }}
                  content={<ChartTooltip unit="ออเดอร์" labelFormatter={formatDate} />}
                />
                <Area
                  type="monotone"
                  dataKey="total_orders"
                  name="จำนวนออเดอร์"
                  stroke="#10b981"
                  strokeWidth={2}
                  fill="url(#ordersFill)"
                  dot={false}
                  activeDot={{ r: 5, stroke: "#1f2937", strokeWidth: 2 }}
                />
              </AreaChart>
            </ResponsiveContainer>
          )}
        </div>

        {/* ========== MAINTENANCE TABLE ========== */}
        <div style={panelStyle}>
          <PanelHeader
            title="รายการตรวจบำรุงถังแก๊ส"
            meta={overdueCount > 0 ? `เลยกำหนด ${overdueCount} ถัง` : null}
            metaColor="#f87171"
          />
          {nearDueCylinders.length === 0 ? (
            <div style={emptyTextStyle}>ไม่มีข้อมูลรายการบำรุง</div>
          ) : (
            <div style={{ overflowX: "auto" }}>
              <table style={tableStyle}>
                <thead>
                  <tr>
                    <th style={thStyle}>รหัสถัง</th>
                    <th style={thStyle}>ยี่ห้อ / ขนาด</th>
                    <th style={thStyle}>วันตรวจครั้งถัดไป</th>
                    <th style={{ ...thStyle, textAlign: "right" }}>คงเหลือ</th>
                  </tr>
                </thead>
                <tbody>
                  {nearDueCylinders.map((row, i) => (
                    <tr key={row.serial_number || i}>
                      <td style={tdStyle}><strong>{row.serial_number || "-"}</strong></td>
                      <td style={{ ...tdStyle, color: "#9ca3af" }}>
                        {[row.brand, row.size].filter(Boolean).join(" · ") || "-"}
                      </td>
                      <td style={tdStyle}>{formatDate(row.next_check_date)}</td>
                      <td style={{ ...tdStyle, textAlign: "right" }}>
                        <DaysBadge days={row.days_left} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </Layout>
  );
}

// ========== Sub components ==========
const StatCard = ({ icon: Icon, accent, title, value, caption, valueColor }) => (
  <div style={{ ...summaryCardStyle, borderTop: `3px solid ${accent}` }}>
    <div style={statHeaderStyle}>
      <span style={statTitleStyle}>{title}</span>
      <span style={{ ...statIconStyle, background: `${accent}26`, color: accent }}><Icon size={18} /></span>
    </div>
    <div style={{ ...statValueStyle, color: valueColor || "white" }}>{value}</div>
    {caption && <div style={statCaptionStyle}>{caption}</div>}
  </div>
);

const PanelHeader = ({ title, meta, metaColor }) => (
  <div style={panelHeaderStyle}>
    <h2 style={panelTitleStyle}>{title}</h2>
    {meta && <span style={{ ...panelMetaStyle, color: metaColor || "#9ca3af" }}>{meta}</span>}
  </div>
);

const ChartTooltip = ({ active, payload, label, unit, labelFormatter }) => {
  if (!active || !payload || !payload.length) return null;
  return (
    <div style={tooltipStyle}>
      <div style={{ color: "#9ca3af", fontSize: "12px", marginBottom: "4px" }}>
        {labelFormatter ? labelFormatter(label) : label}
      </div>
      <div style={{ color: "white", fontWeight: "bold" }}>
        {payload[0].value} {unit}
      </div>
    </div>
  );
};

const DaysBadge = ({ days }) => {
  const n = Number(days);
  if (Number.isNaN(n)) return <span style={{ color: "#9ca3af" }}>-</span>;
  if (n < 0) return <span style={{ ...daysBadgeStyle, ...badgeRed }}>เลย {Math.abs(n)} วัน</span>;
  if (n === 0) return <span style={{ ...daysBadgeStyle, ...badgeAmber }}>ครบกำหนดวันนี้</span>;
  if (n <= 30) return <span style={{ ...daysBadgeStyle, ...badgeAmber }}>อีก {n} วัน</span>;
  return <span style={{ ...daysBadgeStyle, ...badgeMuted }}>อีก {n} วัน</span>;
};

// ========== Styles ==========
const pageStyle = { color: "white" };
const headerStyle = { display: "flex", justifyContent: "space-between", alignItems: "flex-end", marginBottom: "24px", flexWrap: "wrap", gap: "12px" };
const titleStyle = { fontSize: "32px", margin: 0 };
const subtitleStyle = { color: "#9ca3af", fontSize: "14px", marginTop: "6px" };

// มือถือ: การ์ดสรุป 2 ใบต่อแถว
const cardGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(150px, 1fr))", gap: "12px", marginBottom: "20px" };
const summaryCardStyle = { background: "#1f2937", padding: "18px 20px", borderRadius: "14px", boxShadow: "0 1px 2px rgba(0,0,0,0.3)" };
const statHeaderStyle = { display: "flex", justifyContent: "space-between", alignItems: "center" };
const statTitleStyle = { color: "#cbd5e1", fontSize: "15px", fontWeight: 600 };
const statIconStyle = { width: "36px", height: "36px", borderRadius: "10px", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "18px" };
const statValueStyle = { fontSize: "clamp(26px, 7vw, 36px)", fontWeight: "bold", lineHeight: 1.1, marginTop: "12px" };
const statCaptionStyle = { color: "#6b7280", fontSize: "12px", marginTop: "6px" };

const twoColGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(320px, 100%), 1fr))", gap: "20px" };
const panelStyle = { background: "#1f2937", padding: "20px", borderRadius: "14px", marginBottom: "20px", boxShadow: "0 1px 2px rgba(0,0,0,0.3)" };
const panelHeaderStyle = { display: "flex", justifyContent: "space-between", alignItems: "baseline", gap: "12px", marginBottom: "16px", flexWrap: "wrap" };
const panelTitleStyle = { margin: 0, fontSize: "18px" };
const panelMetaStyle = { fontSize: "13px", fontWeight: 600 };
const emptyTextStyle = { color: "#9ca3af", textAlign: "center", padding: "40px" };

const rankRowStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "6px" };
const rankBadgeStyle = (idx) => ({
  width: "28px",
  height: "28px",
  borderRadius: "50%",
  display: "inline-flex",
  alignItems: "center",
  justifyContent: "center",
  fontSize: "13px",
  fontWeight: "bold",
  background: idx === 0 ? "rgba(245,158,11,0.2)" : "#111827",
  color: idx === 0 ? "#fbbf24" : "#9ca3af",
});
const progressTrackStyle = { height: "6px", background: "#111827", borderRadius: "999px", overflow: "hidden" };
const progressFillStyle = { height: "100%", background: "#3b82f6", borderRadius: "999px" };

const tooltipStyle = { background: "#111827", border: "1px solid #374151", borderRadius: "8px", padding: "8px 12px", boxShadow: "0 4px 12px rgba(0,0,0,0.4)" };

const tableStyle = { width: "100%", borderCollapse: "collapse", textAlign: "left" };
const thStyle = { padding: "10px 12px", borderBottom: "1px solid #374151", color: "#9ca3af", fontSize: "13px", fontWeight: 600, whiteSpace: "nowrap" };
const tdStyle = { padding: "12px", borderBottom: "1px solid #2b3647", fontSize: "14px", whiteSpace: "nowrap" };

const daysBadgeStyle = { display: "inline-block", padding: "3px 10px", borderRadius: "999px", fontSize: "12px", fontWeight: "bold", whiteSpace: "nowrap" };
const badgeRed = { background: "rgba(239,68,68,0.15)", color: "#f87171", border: "1px solid #ef4444" };
const badgeAmber = { background: "rgba(245,158,11,0.15)", color: "#fbbf24", border: "1px solid #f59e0b" };
const badgeMuted = { background: "#111827", color: "#9ca3af", border: "1px solid #374151" };

export default Dashboard;
