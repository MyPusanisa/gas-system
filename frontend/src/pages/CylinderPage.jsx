import { useParams } from "react-router-dom"
import { useEffect, useState } from "react"
import { API_BASE } from "../config";

const formatDate = (date) => {
  if (!date) return "-"
  return date
}

// ฟังก์ชันคำนวณวันหมดอายุถัง/แก๊สอัตโนมัติ (นับไป 5 ปีจากวันที่ผลิต)
const calculateExpiryDate = (manufactureDate) => {
  if (!manufactureDate) return "-"
  const date = new Date(manufactureDate)
  date.setFullYear(date.getFullYear() + 5)
  return date.toISOString().split("T")[0]
}

// ตรวจสอบสถานะความปลอดภัย
const getInspectionStatus = (statusFromDb) => {
  if (statusFromDb === "ปลอดภัย" || statusFromDb === "ปกติ" || statusFromDb === "ในคลัง" || !statusFromDb) {
    return { text: statusFromDb || "ปกติ", color: "#22c55e" }
  }
  return { text: statusFromDb, color: "#ef4444" }
}

// ฟังก์ชันสำหรับแปลง URL ของรูปภาพให้เรียกผ่าน Vite Proxy
const getImageUrl = (imagePath) => {
  if (!imagePath) return null;
  if (imagePath.startsWith("http")) return imagePath;
  
  const cleanPath = imagePath.startsWith("/") ? imagePath.slice(1) : imagePath;
  
  if (cleanPath.startsWith("Backend/")) {
    return `/${cleanPath}`;
  }
  
  return `/Backend/uploads/${cleanPath}`;
};

function CylinderPage() {
  const { id } = useParams() 
  const [cylinder, setCylinder] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    const fetchCylinder = async () => {
      try {
        setLoading(true)
        setError(null)
        
        const res = await fetch(`${API_BASE}/get_cylinder.php?serial_number=${encodeURIComponent(id)}`)
        
        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`)
        }

        const result = await res.json()

        if (result.success) {
          setCylinder(result.data)
        } else {
          setError(result.message || "ไม่พบข้อมูลถังแก๊ส")
        }
      } catch (err) {
        setError("ไม่สามารถเชื่อมต่อฐานข้อมูลหลังบ้านได้")
      } finally {
        setLoading(false)
      }
    }

    if (id) {
      fetchCylinder()
    }
  }, [id])

  if (loading) {
    return (
      <div style={pageStyle}>
        <div style={cardStyle}>
          <h2 style={{ textAlign: "center", margin: 0, fontSize: "18px", color: "#9ca3af" }}>กำลังโหลดข้อมูลถังแก๊ส...</h2>
        </div>
      </div>
    )
  }

  if (error || !cylinder) {
    return (
      <div style={pageStyle}>
        <div style={cardStyle}>
          <div style={{ display: "flex", alignItems: "center", justifyContent: "center", gap: "10px", marginBottom: "16px" }}>
            <span style={{ fontSize: "28px", color: "#ef4444" }}>❌</span>
            <h1 style={{ margin: 0, fontSize: "24px", color: "#ef4444", fontWeight: "bold" }}>ไม่พบข้อมูลถังแก๊ส</h1>
          </div>
          <p style={{ margin: "0 0 8px 0", textAlign: "center" }}>หมายเลข Serial ที่ค้นหา: <strong>{id}</strong></p>
          <p style={{ color: "#9ca3af", margin: 0, textAlign: "center", fontSize: "14px" }}>สาเหตุ: {error}</p>
        </div>
      </div>
    )
  }

  const inspectionStatus = getInspectionStatus(cylinder.status)
  const expiryDate = cylinder.expiry_date || calculateExpiryDate(cylinder.manufacture_date)
  const cylinderImage = cylinder.image || cylinder.proof_image || cylinder.cylinder_image

  return (
    <div style={pageStyle}>
      <div style={cardStyle}>
        {/* ส่วนหัวแสดงชื่อถังแก๊สและ Badge สถานะสีเขียวมุมบนขวา */}
        <div style={headerStyle}>
          <h1 style={titleStyle}>
            ข้อมูลถังแก๊ส <span style={{ marginLeft: "4px" }}>🛢️</span>
          </h1>
          <span style={{ ...statusBadgeStyle, backgroundColor: inspectionStatus.color }}>
            {inspectionStatus.text}
          </span>
        </div>

        {/* ส่วนแสดงรูปภาพถังแก๊ส (แสดงผลเมื่อมีข้อมูลรูปภาพในฐานข้อมูล) */}
        {cylinderImage && (
          <div style={{ textAlign: "center", marginBottom: "20px" }}>
            <img
              src={getImageUrl(cylinderImage)}
              alt="รูปภาพถังแก๊ส"
              style={{
                width: "100%",
                maxHeight: "240px",
                borderRadius: "12px",
                objectFit: "contain",
                backgroundColor: "#0f172a",
                border: "1px solid #2d3748",
                padding: "8px",
                boxSizing: "border-box"
              }}
              onError={(e) => {
                e.target.style.display = "none";
              }}
            />
          </div>
        )}

        {/* ข้อมูลเนื้อหาตารางแสดงผลตามลำดับ */}
        <div style={rowStyle}>
          <strong style={labelStyle}>หมายเลข Serial</strong>
          <span style={{ ...valueStyle, fontWeight: "bold", color: "#38bdf8" }}>
            {cylinder.serial_number || id}
          </span>
        </div>

        <div style={rowStyle}>
          <strong style={labelStyle}>ยี่ห้อ</strong>
          <span style={valueStyle}>{cylinder.brand || "-"}</span>
        </div>

        <div style={rowStyle}>
          <strong style={labelStyle}>ชนิดแก๊ส</strong>
          <span style={valueStyle}>{cylinder.gas_type || "-"}</span>
        </div>

        <div style={rowStyle}>
          <strong style={labelStyle}>ขนาดถัง</strong>
          <span style={valueStyle}>{cylinder.size || "-"}</span>
        </div>

        <div style={rowStyle}>
          <strong style={labelStyle}>วันที่ผลิต</strong>
          <span style={valueStyle}>{formatDate(cylinder.manufacture_date)}</span>
        </div>

        <div style={rowStyle}>
          <strong style={labelStyle}>วันหมดอายุแก๊ส</strong>
          <span style={{ ...valueStyle, color: "#f87171", fontWeight: "bold" }}>
            {formatDate(expiryDate)}
          </span>
        </div>

        <div style={rowStyle}>
          <strong style={labelStyle}>วันตรวจล่าสุด</strong>
          <span style={valueStyle}>{formatDate(cylinder.last_check_date)}</span>
        </div>

        <div style={rowStyle}>
          <strong style={labelStyle}>วันตรวจครั้งถัดไป</strong>
          <span style={valueStyle}>{formatDate(cylinder.next_check_date || cylinder.next_maintenance_date)}</span>
        </div>

        <div style={rowStyle}>
          <strong style={labelStyle}>วันที่ลูกค้าได้รับถังแก๊สสำเร็จ</strong>
          <span style={valueStyle}>{formatDate(cylinder.delivered_date)}</span>
        </div>

        {/* แถวล่างสุดเป็นปุ่มกดโทรศัพท์ */}
        <div style={{ ...rowStyle, borderBottom: "none", paddingBottom: 0, marginTop: "24px", alignItems: "center" }}>
          <strong style={labelStyle}>เบอร์โทรบริการ</strong>
          <a href="tel:024643519" style={phoneButtonStyle}>
            โทร 02-464-3519
          </a>
        </div>
      </div>
    </div>
  )
}

// --- Styles สำหรับการจัดวาง UI ธีมมืด ---
const pageStyle = {
  minHeight: "100vh",
  display: "flex",
  justifyContent: "center",
  alignItems: "center",
  background: "#0f172a",
  color: "white",
  padding: "20px",
  boxSizing: "border-box",
  fontFamily: "system-ui, -apple-system, sans-serif",
}

const cardStyle = {
  width: "100%",
  maxWidth: "520px",
  background: "#1e2530",
  borderRadius: "16px",
  padding: "32px",
  boxShadow: "0 10px 30px rgba(0,0,0,0.4)",
  boxSizing: "border-box",
}

const headerStyle = {
  display: "flex",
  justifyContent: "space-between",
  alignItems: "center",
  marginBottom: "28px",
}

const titleStyle = {
  margin: 0,
  fontSize: "26px",
  fontWeight: "bold",
}

const statusBadgeStyle = {
  padding: "5px 14px",
  borderRadius: "999px",
  fontWeight: "600",
  fontSize: "12px",
  color: "white",
}

const rowStyle = {
  display: "flex",
  justifyContent: "space-between",
  alignItems: "baseline",
  paddingBottom: "12px",
  marginBottom: "12px",
  borderBottom: "1px solid #2d3748",
}

const labelStyle = {
  fontSize: "14px",
  color: "#9ca3af",
  fontWeight: "500",
}

const valueStyle = {
  fontSize: "14px",
  color: "#e5e7eb",
  fontWeight: "500",
}

const phoneButtonStyle = {
  backgroundColor: "#1d4ed8",
  color: "white",
  padding: "6px 16px",
  borderRadius: "999px",
  fontWeight: "600",
  textDecoration: "none",
  display: "inline-block",
  fontSize: "13px",
  border: "none",
}

export default CylinderPage;