import React, { useState } from "react";
import { useNavigate } from "react-router-dom";

function TopBar({
  role: propsRole,
  username: propsUsername,
  gasLevel = 0,
  deliverySuccessItems = [],
}) {
  const navigate = useNavigate();
  const [showDetails, setShowDetails] = useState(true);

  const localRole = (localStorage.getItem("role") || propsRole || "").toLowerCase();
  const localUsername = localStorage.getItem("username") || propsUsername || "ไม่ระบุชื่อ";
  const isAdmin = localRole === "admin" || localRole === "ผู้ดูแลระบบ";

  const count = Array.isArray(deliverySuccessItems) 
    ? deliverySuccessItems.length 
    : (Number(deliverySuccessItems) || 0);

  const getStatusText = (level) => {
    if (level <= 520) return "ปกติ";
    if (level <= 720) return "เฝ้าระวัง";
    return "อันตราย";
  };

  const getStatusColor = (level) => {
    if (level <= 520) return "#22c55e";
    if (level <= 720) return "#facc15";
    return "#ef4444";
  };

  return (
    <div style={{ background: "#0b1329", padding: "16px 24px", color: "white", position: "relative" }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        
        {/* แสดงค่าแก๊สในคลัง */}
        <button
          onClick={() => setShowDetails(!showDetails)}
          style={{
            display: "inline-flex",
            alignItems: "center",
            gap: "8px",
            padding: "6px 14px",
            border: "1px solid #374151",
            borderRadius: "20px",
            background: "#1f2937",
            color: "white",
            cursor: "pointer"
          }}
        >
          {/* แก้ไขวงกลมบอกสถานะที่นี่ */}
          <span 
            style={{ 
              display: "inline-block",
              width: "10px", 
              height: "10px", 
              borderRadius: "50%", 
              backgroundColor: getStatusColor(gasLevel),
              flexShrink: 0
            }} 
          />
          <span style={{ fontSize: "14px", color: "#d1d5db" }}>แก๊สในคลัง</span>
          {showDetails && (
            <strong style={{ fontSize: "14px", color: getStatusColor(gasLevel), marginLeft: "4px" }}>
              {gasLevel} ({getStatusText(gasLevel)})
            </strong>
          )}
        </button>

        {/* แสดงผลรายการรออนุมัติส่ง */}
        {isAdmin ? (
          <div style={{ display: "flex", alignItems: "center", gap: "12px", fontSize: "14px" }}>
            <button
              onClick={() => navigate("/approval")}
              style={{
                background: "#1f2937",
                border: "1px solid #374151",
                padding: "6px 14px",
                borderRadius: "8px",
                color: "white",
                cursor: "pointer",
                display: "flex",
                alignItems: "center",
                gap: "6px"
              }}
            >
              <span>รออนุมัติส่ง</span>
              <strong style={{ color: count > 0 ? "#facc15" : "#22c55e", fontSize: "15px" }}>{count}</strong>
            </button>
            <span style={{ color: "#9ca3af", marginLeft: "8px" }}>
              ผู้ใช้: <strong style={{ color: "white" }}>{localUsername}</strong>
            </span>
          </div>
        ) : (
          <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
            <span style={{ color: "#9ca3af", fontSize: "14px" }}>
              พนักงาน: <strong style={{ color: "white" }}>{localUsername}</strong>
            </span>
          </div>
        )}
      </div>
    </div>
  );
}

export default TopBar;