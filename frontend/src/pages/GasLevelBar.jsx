import React, { useState, useEffect } from "react";
import GasLevelBar from "../components/GasLevelBar";

function GasDashboard() {
  const [gasLevel, setGasLevel] = useState(0);

  const fetchGasLevel = async () => {
    try {
      // เปลี่ยนจาก /Backend/... เป็น Full URL ที่รันบน XAMPP
      const res = await fetch("http://localhost/Backend/models/cylinder/get_gas_status.php");
      const data = await res.json();
      if (data.success) {
        setGasLevel(data.level);
      }
    } catch (err) {
      console.error("Error fetching gas level:", err);
    }
  };

  useEffect(() => {
    fetchGasLevel();
    const interval = setInterval(fetchGasLevel, 1000);
    return () => clearInterval(interval);
  }, []);

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
    <div style={{ padding: "20px", background: "#111827", borderRadius: "12px", color: "white" }}>
      <div style={{ display: "inline-flex", alignItems: "center", gap: "8px", padding: "6px 14px", border: "1px solid #374151", borderRadius: "20px", marginBottom: "16px" }}>
        <span style={{ width: "10px", height: "10px", borderRadius: "50%", backgroundColor: getStatusColor(gasLevel) }}></span>
        <span style={{ fontSize: "14px" }}>แก๊สในคลัง</span>
      </div>

      <h2 style={{ fontSize: "20px", fontWeight: "bold", marginBottom: "16px" }}>สถานะแก๊สในคลัง</h2>
      
      <p style={{ fontSize: "16px", marginBottom: "8px" }}>
        ค่าปัจจุบัน: <strong>{gasLevel}</strong>
      </p>
      <p style={{ fontSize: "16px", marginBottom: "16px" }}>
        สถานะ: <strong>{getStatusText(gasLevel)}</strong>
      </p>

      <GasLevelBar level={gasLevel} />
    </div>
  );
}

export default GasDashboard;