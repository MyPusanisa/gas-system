import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";

const formatDate = (date) => date || "-";

// 1. ฟังก์ชันจัดการสีและข้อความของป้ายสถานะ
const getInspectionStatus = (status) => {
  const s = (status || "").toLowerCase().trim();
  
  // สถานะเมื่อ Admin ยืนยันจัดส่งเรียบร้อยแล้ว -> แสดง "จัดส่งแล้ว" (สีเขียว)
  if (
    s === "success" || 
    s === "จัดส่งสำเร็จ" || 
    s === "จัดส่งแล้ว" || 
    s === "ใช้งานอยู่" || 
    s === "ลูกค้า"
  ) {
    return { text: "จัดส่งแล้ว", color: "#22c55e" };
  }

  // สถานะระหว่างการจัดส่ง -> แสดง "กำลังส่ง" (สีส้ม)
  if (s === "delivering" || s === "กำลังส่ง" || s === "กำลังจัดส่ง" || s === "pending_approval") {
    return { text: "กำลังส่ง", color: "#f97316" };
  }

  // สถานะปกติ / ปลอดภัย / ในคลัง
  if (s === "ปลอดภัย" || s === "ปกติ" || s === "ในคลัง") {
    return { text: status, color: "#22c55e" };
  }

  return { text: status || "ปกติ", color: "#ef4444" };
};

function QRCodePage() {
  let params = {};
  try {
    params = useParams() || {};
  } catch (e) {
    params = {};
  }
  
  const pathId = window.location.pathname.split("/").pop();
  const id = params.id || pathId;

  const [cylinder, setCylinder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchCylinderData = async () => {
      try {
        setLoading(true);
        setError(null);
        const cleanId = id ? decodeURIComponent(id).trim().replace(/\s+/g, '') : '';

        if (!cleanId) {
          setError("ไม่ได้ระบุรหัสถังแก๊ส");
          setLoading(false);
          return;
        }

        const host = window.location.hostname;
        const apiUrl = `http://${host}:8080/Backend/models/get_cylinder_by_id.php?id=${encodeURIComponent(cleanId)}`;

        const res = await fetch(apiUrl, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
          },
        });

        if (!res.ok) {
          throw new Error(`HTTP Status ${res.status}`);
        }

        const result = await res.json();
        
        if (result.success && result.data) {
          setCylinder(result.data);
        } else {
          setError(result.message || "ไม่พบข้อมูลถังแก๊สใบนี้ในระบบ");
        }
      } catch (err) {
        setError(err.message || "ไม่สามารถเชื่อมต่อ Backend ได้");
      } finally {
        setLoading(false);
      }
    };

    fetchCylinderData();
  }, [id]);

  if (loading) {
    return (
      <div style={pageStyle}>
        <div style={cardStyle}>
          <h2 style={{ textAlign: "center", margin: 0, fontSize: "16px", color: "#9ca3af" }}>กำลังดึงข้อมูลถังแก๊ส...</h2>
        </div>
      </div>
    );
  }

  if (error || !cylinder) {
    return (
      <div style={pageStyle}>
        <div style={cardStyle}>
          <div style={{ display: "flex", alignItems: "center", justifyContent: "center", gap: "10px", marginBottom: "16px" }}>
            <span style={{ fontSize: "28px" }}>❌</span>
            <h1 style={{ margin: 0, fontSize: "20px", color: "#ef4444", fontWeight: "bold" }}>ไม่พบข้อมูลถังแก๊ส</h1>
          </div>
          <p style={{ color: "#9ca3af", margin: 0, textAlign: "center", fontSize: "14px" }}>สาเหตุ: {error}</p>
        </div>
      </div>
    );
  }

  const inspectionStatus = getInspectionStatus(cylinder.status);
  const rawStatus = (cylinder.status || "").toLowerCase().trim();
  
  // 2. เช็คว่าเป็นสถานะที่ Admin ยืนยันการส่งสำเร็จแล้วหรือไม่
  const isDelivered = (
    rawStatus === "success" || 
    rawStatus === "จัดส่งสำเร็จ" || 
    rawStatus === "จัดส่งแล้ว" || 
    rawStatus === "ใช้งานอยู่" ||
    rawStatus === "ลูกค้า"
  );

  // ดึงค่านำมาแสดงในกล่องเขียว
  const deliveredDateValue = cylinder.delivered_date || cylinder.deliveredDate || cylinder.updated_at;

  return (
    <div style={pageStyle}>
      <div style={cardStyle}>
        
        {/* หัวข้อข้อมูลถังแก๊ส + ป้ายสถานะ */}
        <div style={headerStyle}>
          <h1 style={{ margin: 0, fontSize: "24px", fontWeight: "bold", color: "#e2e8f0" }}>
            ข้อมูลถังแก๊ส
          </h1>
          <span style={{ ...statusBadgeStyle, backgroundColor: inspectionStatus.color }}>
            {inspectionStatus.text}
          </span>
        </div>

        {/* รายการข้อมูลทั่วไป */}
        <div style={rowStyle}>
          <span style={labelStyle}>หมายเลข Serial</span>
          <span style={valueStyle}>{cylinder.serial_number || cylinder.serial || "-"}</span>
        </div>

        <div style={rowStyle}>
          <span style={labelStyle}>ยี่ห้อ</span>
          <span style={valueStyle}>{cylinder.brand || cylinder.req_brand || "-"}</span>
        </div>

        <div style={rowStyle}>
          <span style={labelStyle}>ชนิดแก๊ส</span>
          <span style={valueStyle}>{cylinder.gas_type || cylinder.req_gas_type || "-"}</span>
        </div>

        <div style={rowStyle}>
          <span style={labelStyle}>ขนาดถัง</span>
          <span style={valueStyle}>{cylinder.size || cylinder.req_size || "-"}</span>
        </div>

        <div style={rowStyle}>
          <span style={labelStyle}>วันที่ผลิต</span>
          <span style={valueStyle}>{formatDate(cylinder.manufacture_date)}</span>
        </div>

        <div style={rowStyle}>
          <span style={labelStyle}>วันตรวจล่าสุด</span>
          <span style={valueStyle}>{formatDate(cylinder.last_check_date)}</span>
        </div>

        <div style={rowStyle}>
          <span style={labelStyle}>วันตรวจครั้งถัดไป</span>
          <span style={valueStyle}>{formatDate(cylinder.next_check_date || cylinder.next_maintenance_date)}</span>
        </div>

        {/* 3. กล่องสีเขียวจะปรากฏเฉพาะเมื่อ isDelivered เป็นจริง (Admin กดยืนยันแล้ว) */}
        {isDelivered && (
          <div style={stackedRowStyle}>
            <span style={{ ...labelStyle, color: "#4ade80", fontWeight: "bold" }}>วันที่ลูกค้าได้รับถังแก๊สสำเร็จ</span>
            <span style={{ ...valueStyle, marginTop: "4px", color: "#4ade80" }}>
              {formatDate(deliveredDateValue)}
            </span>
          </div>
        )}

        {/* ปุ่มโทรติดต่อร้านค้า */}
        <div style={{ marginTop: "28px" }}>
          <a href="tel:024643519" style={phoneButtonStyle}>
            📞 โทรติดต่อร้านค้า 02-464-3519
          </a>
        </div>

      </div>
    </div>
  );
}

// Custom CSS Styles
const pageStyle = {
  minHeight: "100vh",
  display: "flex",
  justifyContent: "center",
  alignItems: "center",
  background: "#0f172a",
  color: "#f3f4f6",
  padding: "16px",
  boxSizing: "border-box",
  fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
};

const cardStyle = {
  width: "100%",
  maxWidth: "400px",
  background: "#1e293b",
  borderRadius: "20px",
  padding: "24px",
  boxShadow: "0 10px 25px rgba(0,0,0,0.5)",
  boxSizing: "border-box"
};

const headerStyle = {
  display: "flex",
  justifyContent: "space-between",
  alignItems: "center",
  marginBottom: "24px"
};

const statusBadgeStyle = {
  padding: "4px 14px",
  borderRadius: "999px",
  fontWeight: "bold",
  fontSize: "14px",
  color: "white"
};

const rowStyle = {
  display: "flex",
  justifyContent: "space-between",
  alignItems: "center",
  paddingBottom: "14px",
  marginBottom: "14px",
  borderBottom: "1px solid #334155"
};

const stackedRowStyle = {
  display: "flex",
  flexDirection: "column",
  alignItems: "flex-start",
  padding: "12px",
  marginBottom: "14px",
  backgroundColor: "#064e3b",
  borderRadius: "12px",
  border: "1px solid #059669"
};

const labelStyle = {
  fontSize: "15px",
  color: "#94a3b8",
  fontWeight: "500"
};

const valueStyle = {
  fontSize: "15px",
  color: "#f8fafc",
  fontWeight: "600"
};

const phoneButtonStyle = {
  backgroundColor: "#2563eb",
  color: "white",
  padding: "14px 20px",
  borderRadius: "14px",
  fontWeight: "bold",
  textDecoration: "none",
  display: "block",
  fontSize: "16px",
  border: "none",
  textAlign: "center",
  boxShadow: "0 4px 12px rgba(37, 99, 235, 0.4)",
  transition: "all 0.2s ease-in-out"
};

export default QRCodePage;