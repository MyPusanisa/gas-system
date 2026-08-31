import React, { useState, useEffect } from "react";

function GasStatusCard() {
  const [gasLevel, setGasLevel] = useState(0);

  const fetchGasLevel = async () => {
    try {
      // ใช้ 127.0.0.1 เพื่อหลีกเลี่ยงปัญหา DNS localhost บน Windows
      const res = await fetch("http://127.0.0.1/Backend/models/cylinder/get_gas_status.php", {
        method: "GET",
        headers: {
          "Accept": "application/json"
        }
      });
      
      const data = await res.json();
      console.log("Response data from PHP:", data); // แสดงค่าออกทาง Console เพื่อตรวจสอบ

      if (data && data.success) {
        setGasLevel(data.level);
      }
    } catch (err) {
      console.error("Fetch Error:", err);
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
    <div style={{ color: "white", marginBottom: "20px" }}>
      <div style={{ display: "inline-flex", alignItems: "center", gap: "8px", padding: "4px 12px", border: "1px solid #374151", borderRadius: "20px", backgroundColor: "#1f2937", marginBottom: "12px" }}>
        <span style={{ width: "10px", height: "10px", borderRadius: "50%", backgroundColor: getStatusColor(gasLevel) }}></span>
        <span style={{ fontSize: "14px" }}>แก๊สในคลัง</span>
      </div>

      <h3 style={{ fontSize: "20px", fontWeight: "bold", margin: "8px 0" }}>สถานะแก๊สในคลัง</h3>
      <p style={{ fontSize: "16px", margin: "4px 0" }}>ค่าปัจจุบัน: <strong>{gasLevel}</strong></p>
      <p style={{ fontSize: "16px", margin: "4px 0" }}>สถานะ: <strong>{getStatusText(gasLevel)}</strong></p>
    </div>
  );
}

export default GasStatusCard;