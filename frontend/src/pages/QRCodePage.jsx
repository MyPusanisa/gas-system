import { useParams } from "react-router-dom"
import { useEffect, useState } from "react"
import { QRCodeSVG } from 'qrcode.react'

const formatDate = (date) => {
  if (!date) return "-"
  return date
}

const getInspectionStatus = (status) => {
  if (status === "ปลอดภัย" || status === "ปกติ" || !status) {
    return { text: "ปกติ", color: "#22c55e" }
  }
  return { text: status, color: "#ef4444" }
}

function QRCodePage() {
  const { id } = useParams() // รับรหัสถังจาก URL เช่น CY001
  const [cylinder, setCylinder] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [showQR, setShowQR] = useState(false)

  useEffect(() => {
    const fetchCylinderData = async () => {
      try {
        setLoading(true)
        setError(null)
        // ยิงข้อมูลไปที่ PHP หลังบ้าน
        const res = await fetch(`http://192.168.1.176/Backend/models/get_cylinder_by_id.php?id=${id}`);
        const result = await res.json()

        if (result.success) {
          setCylinder(result.data) // เก็บข้อมูลลง State
        } else {
          setError(result.message || "ไม่พบข้อมูลถังแก๊สใบนี้ในระบบ")
        }
      } catch (err) {
        setError("ไม่สามารถดึงข้อมูลได้ (เกิดข้อผิดพลาดในการเชื่อมต่อหลังบ้าน หรือติดปัญหา CORS)")
      } finally {
        setLoading(false)
      }
    }

    if (id) {
      fetchCylinderData()
    }
  }, [id])

  if (loading) {
    return (
      <div style={pageStyle}>
        <div style={cardStyle}>
          <h2 style={{ textAlign: "center", margin: 0, fontSize: "18px", color: "#9ca3af" }}>กำลังดึงข้อมูลถังแก๊ส...</h2>
        </div>
      </div>
    )
  }

  if (error || !cylinder) {
    return (
      <div style={pageStyle}>
        <div style={cardStyle}>
          <div style={{ display: "flex", alignItems: "center", justifyContent: "center", gap: "10px", marginBottom: "16px" }}>
            <span style={{ fontSize: "28px" }}>❌</span>
            <h1 style={{ margin: 0, fontSize: "24px", color: "#ef4444", fontWeight: "bold" }}>ไม่พบข้อมูลถังแก๊ส</h1>
          </div>
          <p style={{ margin: "0 0 8px 0", textAlign: "center" }}>รหัสถังที่ค้นหา: <strong>{id}</strong></p>
          <p style={{ color: "#9ca3af", margin: 0, textAlign: "center", fontSize: "14px" }}>สาเหตุ: {error}</p>
        </div>
      </div>
    )
  }

  const inspectionStatus = getInspectionStatus(cylinder.status)
  const currentUrl = `http://10.60.1.228:5173/cylinder/${cylinder.cylinder_id}`

  return (
    <div style={pageStyle}>
      <div style={cardStyle}>
        <div style={headerStyle}>
          <h1 style={{ margin: 0, fontSize: "26px", fontWeight: "bold" }}>ข้อมูลถังแก๊ส 🛢</h1>
          <span style={{ ...statusBadgeStyle, backgroundColor: inspectionStatus.color }}>
            {inspectionStatus.text}
          </span>
        </div>

        {/* ปรับแก้ Key ด้านในทั้งหมดให้ตรงกับฐานข้อมูลจริงที่เป็นตัวพิมพ์เล็กและมีขีดล่าง */}
        <div style={rowStyle}>
          <strong style={labelStyle}>รหัสถัง</strong>
          <span style={valueStyle}>{cylinder.cylinder_id || "-"}</span>
        </div>

        <div style={rowStyle}>
          <strong style={labelStyle}>หมายเลข Serial</strong>
          <span style={valueStyle}>{cylinder.serial_number || "-"}</span>
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
          <span style={{ ...valueStyle, color: "#f87171", fontWeight: "bold" }}>{formatDate(cylinder.expiry_date)}</span>
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

        <div style={{ ...rowStyle, borderBottom: "none", paddingBottom: 0, marginTop: "24px" }}>
          <button
            onClick={() => setShowQR(true)}
            style={{ ...phoneButtonStyle, backgroundColor: "#059669", cursor: "pointer" }}
          >
          แสดง QR Code ถังนี้
          </button>
          
          <a href="tel:024643519" style={phoneButtonStyle}>
            โทร 02-464-3519
          </a>
        </div>
      </div>

      {showQR && (
        <div style={modalOverlayStyle}>
          <div style={modalContentStyle}>
            <h3 style={{ margin: "0 0 16px 0", fontSize: "18px" }}>QR Code สำหรับถังรหัส: {cylinder.cylinder_id}</h3>
            <div style={{ background: "white", padding: "16px", borderRadius: "12px", display: "inline-block" }}>
              <QRCodeSVG value={currentUrl} size={180} />
            </div>
            <p style={{ color: "#9ca3af", fontSize: "12px", marginTop: "12px", wordBreak: "break-all" }}>{currentUrl}</p>
            
            <div style={{ marginTop: "20px", display: "flex", justifyContent: "center", gap: "10px" }}>
              <button onClick={() => window.print()} style={{ ...phoneButtonStyle, backgroundColor: "#10b981", cursor: "pointer" }}>
                สั่งพิมพ์
              </button>
              <button onClick={() => setShowQR(false)} style={{ ...phoneButtonStyle, backgroundColor: "#4b5563", cursor: "pointer" }}>
                ปิด
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

// --- Styles สำหรับหน้าจอ ---
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
  maxWidth: "540px",
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
  marginBottom: "24px",
}

const statusBadgeStyle = {
  padding: "6px 14px",
  borderRadius: "999px",
  fontWeight: "bold",
  fontSize: "13px",
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

const labelStyle = { fontSize: "14px", color: "#9ca3af" }
const valueStyle = { fontSize: "14px", color: "#e5e7eb" }

const phoneButtonStyle = {
  backgroundColor: "#2563eb",
  color: "white",
  padding: "8px 16px",
  borderRadius: "999px",
  fontWeight: "bold",
  textDecoration: "none",
  display: "inline-block",
  fontSize: "13px",
  border: "none",
}

const modalOverlayStyle = {
  position: "fixed", top: 0, left: 0, right: 0, bottom: 0,
  backgroundColor: "rgba(0, 0, 0, 0.7)",
  display: "flex", justifyContent: "center", alignItems: "center", zIndex: 100,
}

const modalContentStyle = {
  background: "#1f2937", padding: "24px", borderRadius: "16px", textAlign: "center", width: "290px",
}

export default QRCodePage