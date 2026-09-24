import { useNavigate } from "react-router-dom";

const getGasStatus = (level) => {
  if (level <= 520) return { text: "ปกติ", color: "#22c55e", bg: "rgba(34,197,94,0.12)" };
  if (level <= 720) return { text: "เฝ้าระวัง", color: "#facc15", bg: "rgba(250,204,21,0.12)" };
  return { text: "อันตราย", color: "#ef4444", bg: "rgba(239,68,68,0.12)" };
};

function TopBar({
  role: propsRole,
  username: propsUsername,
  gasLevel = 0,
  deliverySuccessItems = [],
}) {
  const navigate = useNavigate();

  const localRole = (localStorage.getItem("role") || propsRole || "").toLowerCase();
  const localUsername = localStorage.getItem("username") || propsUsername || "ไม่ระบุชื่อ";
  const isAdmin = localRole === "admin" || localRole === "ผู้ดูแลระบบ";

  const count = Array.isArray(deliverySuccessItems)
    ? deliverySuccessItems.length
    : (Number(deliverySuccessItems) || 0);

  const status = getGasStatus(gasLevel);
  const initial = String(localUsername).trim().charAt(0).toUpperCase() || "?";

  return (
    <header style={barStyle}>
      {/* แก๊สในคลัง */}
      <div style={{ ...gasPillStyle, borderColor: `${status.color}55` }} title="ค่าแก๊สในคลัง (อัปเดตอัตโนมัติ)">
        <span style={{ ...dotStyle, background: status.color, boxShadow: `0 0 0 4px ${status.bg}` }} />
        <span style={{ color: "#9ca3af", fontSize: "13px" }}>แก๊สในคลัง</span>
        <strong style={{ fontSize: "16px", color: "white" }}>{gasLevel}</strong>
        <span style={{ ...statusChipStyle, color: status.color, background: status.bg }}>{status.text}</span>
      </div>

      <div style={rightGroupStyle}>
        {/* รออนุมัติส่ง */}
        {isAdmin && (
          <button onClick={() => navigate("/approval")} style={approvalButtonStyle} title="ไปหน้าอนุมัติการจัดส่ง">
            <span style={{ fontSize: "16px" }}>🔔</span>
            <span className="topbar-hide-sm">รออนุมัติส่ง</span>
            <span
              style={{
                ...countBadgeStyle,
                background: count > 0 ? "#ef4444" : "#374151",
                color: count > 0 ? "white" : "#9ca3af",
              }}
            >
              {count}
            </span>
          </button>
        )}

        {/* ผู้ใช้ */}
        <div style={userChipStyle}>
          <span style={{ ...avatarStyle, background: isAdmin ? "#2563eb" : "#10b981" }}>{initial}</span>
          <span className="topbar-hide-sm" style={{ display: "flex", flexDirection: "column", lineHeight: 1.2 }}>
            <strong style={{ fontSize: "14px", color: "white" }}>{localUsername}</strong>
            <span style={{ fontSize: "12px", color: "#9ca3af" }}>{isAdmin ? "ผู้ดูแลระบบ" : "พนักงานส่ง"}</span>
          </span>
        </div>
      </div>

      <style>{`
        @media (max-width: 600px) {
          .topbar-hide-sm { display: none !important; }
        }
      `}</style>
    </header>
  );
}

const barStyle = {
  position: "sticky",
  top: 0,
  zIndex: 50,
  display: "flex",
  justifyContent: "space-between",
  alignItems: "center",
  gap: "12px",
  padding: "12px 24px",
  background: "rgba(11,19,36,0.95)",
  backdropFilter: "blur(6px)",
  borderBottom: "1px solid #1f2937",
  color: "white",
};

const gasPillStyle = {
  display: "inline-flex",
  alignItems: "center",
  gap: "10px",
  padding: "8px 14px",
  border: "1px solid",
  borderRadius: "999px",
  background: "#111827",
};

const dotStyle = { width: "10px", height: "10px", borderRadius: "50%", flexShrink: 0 };

const statusChipStyle = { fontSize: "12px", fontWeight: "bold", padding: "2px 10px", borderRadius: "999px" };

const rightGroupStyle = { display: "flex", alignItems: "center", gap: "12px" };

const approvalButtonStyle = {
  display: "inline-flex",
  alignItems: "center",
  gap: "8px",
  padding: "8px 12px",
  background: "#111827",
  border: "1px solid #374151",
  borderRadius: "10px",
  color: "#e5e7eb",
  fontSize: "14px",
  cursor: "pointer",
};

const countBadgeStyle = {
  minWidth: "22px",
  height: "22px",
  padding: "0 6px",
  borderRadius: "999px",
  display: "inline-flex",
  alignItems: "center",
  justifyContent: "center",
  fontSize: "12px",
  fontWeight: "bold",
  boxSizing: "border-box",
};

const userChipStyle = {
  display: "flex",
  alignItems: "center",
  gap: "10px",
  padding: "4px 12px 4px 4px",
  background: "#111827",
  border: "1px solid #374151",
  borderRadius: "999px",
};

const avatarStyle = {
  width: "32px",
  height: "32px",
  borderRadius: "50%",
  display: "flex",
  alignItems: "center",
  justifyContent: "center",
  fontWeight: "bold",
  fontSize: "14px",
  color: "white",
  flexShrink: 0,
};

export default TopBar;
