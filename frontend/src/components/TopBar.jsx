import React, { useState } from "react";

function TopBar({
  role,
  username,
  gasLevel,
  maintenanceDueItems = [],
  expiredCylinderItems = [],
  deliverySuccessItems = [],
  newAssignedJobItems = [],
}) {
  // State สำหรับควบคุมการซ่อน/แสดงรายละเอียดค่าแก๊ส
  const [showDetails, setShowDetails] = useState(true);

  // คำนวณสถานะแก๊ส
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
    <div style={{ background: "#0b1329", padding: "16px 24px", color: "white" }}>
      <div style={{ display: "flex", justifyContent: "space-between", items: "center" }}>
        
        {/* 1. ปุ่มแก๊สในคลัง (กดปุ่มนี้เพื่อสลับการซ่อน/แสดงค่า) */}
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
            cursor: "pointer",
            transition: "all 0.2s"
          }}
        >
          <span style={{ width: "10px", height: "10px", borderRadius: "50%", backgroundColor: getStatusColor(gasLevel) }}></span>
          <span style={{ fontSize: "14px", color: "#d1d5db" }}>แก๊สในคลัง</span>
          
          {/* แสดงค่าเฉพาะตอนที่ showDetails เป็น true */}
          {showDetails && (
            <strong style={{ fontSize: "14px", color: getStatusColor(gasLevel), marginLeft: "4px" }}>
              {gasLevel} ({getStatusText(gasLevel)})
            </strong>
          )}
        </button>

        {/* 2. เมนูฝั่งขวาบน (แยกตาม Role) */}
        {role === "admin" ? (
          /* --- แถบฝั่ง ADMIN --- */
          <div style={{ display: "flex", alignItems: "center", gap: "12px", fontSize: "14px" }}>
            <span style={{ background: "#1f2937", padding: "6px 12px", borderRadius: "6px" }}>
              ตรวจสภาพ <strong style={{ color: "#facc15" }}>{maintenanceDueItems.length}</strong>
            </span>
            <span style={{ background: "#1f2937", padding: "6px 12px", borderRadius: "6px" }}>
              หมดอายุ <strong style={{ color: "#ef4444" }}>{expiredCylinderItems.length}</strong>
            </span>
            <span style={{ background: "#1f2937", padding: "6px 12px", borderRadius: "6px" }}>
              ส่งสำเร็จ <strong style={{ color: "#22c55e" }}>{deliverySuccessItems.length}</strong>
            </span>
            <span style={{ color: "#9ca3af", marginLeft: "8px" }}>
              ผู้ใช้: <strong style={{ color: "white" }}>{username || "ผู้ดูแลระบบ"}</strong>
            </span>
          </div>
        ) : (
          /* --- แถบฝั่ง พนักงาน (Staff / Delivery) --- */
          <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
            <button
              style={{
                display: "flex",
                alignItems: "center",
                gap: "8px",
                background: "#1e293b",
                border: "1px solid #334155",
                padding: "6px 16px",
                borderRadius: "20px",
                color: "white",
                cursor: "pointer",
                fontSize: "14px",
              }}
            >
              <span>🔔 แจ้งเตือนงานเข้า</span>
              <span
                style={{
                  background: "#ef4444",
                  color: "white",
                  fontSize: "12px",
                  fontWeight: "bold",
                  padding: "2px 8px",
                  borderRadius: "10px",
                }}
              >
                {newAssignedJobItems.length}
              </span>
            </button>
            
            <span style={{ color: "#9ca3af", fontSize: "14px" }}>
              พนักงาน: <strong style={{ color: "white" }}>{username}</strong>
            </span>
          </div>
        )}

      </div>
    </div>
  );
}

export default TopBar;