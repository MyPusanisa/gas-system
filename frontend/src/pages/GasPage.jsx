import { useState, useEffect } from "react";
import Layout from "../components/Layout";
// 1. นำเข้าไลบรารีสำหรับสร้างภาพ QR Code
import { QRCodeSVG } from 'qrcode.react';

function GasPage({ cylinders = [], setCylinders, deliveries }) {
  const [newSerialNumber, setNewSerialNumber] = useState("");
  const [newBrand, setNewBrand] = useState("");
  const [newGasType, setNewGasType] = useState("LPG");
  const [newSize, setNewSize] = useState("");
  const [newManufactureDate, setNewManufactureDate] = useState("");
  const [newExpiryDate, setNewExpiryDate] = useState("");
  const [newQrCode, setNewQrCode] = useState("");
  const [newLastCheckDate, setNewLastCheckDate] = useState("");
  const [newNextCheckDate, setNewNextCheckDate] = useState("");
  const [newDeliveredDate, setNewDeliveredDate] = useState("");
  const [newCurrentLocation, setNewCurrentLocation] = useState("");
  const [newStatus, setNewStatus] = useState("ในคลัง");
  const [editingSerial, setEditingSerial] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");

  // State สำหรับเก็บ URL คิวอาร์โค้ดที่แอดมินเลือกเปิดดู
  const [selectedQR, setSelectedQR] = useState(null);

  const API_BASE = "http://localhost/Backend/models";
  
  const calculateNextCheckDate = (manufactureDate) => {
    if (!manufactureDate) return "";
    const date = new Date(manufactureDate);
    date.setFullYear(date.getFullYear() + 3);
    return date.toISOString().split("T")[0];
  };

  const calculateExpiryDate = (manufactureDate) => {
    if (!manufactureDate) return "";
    const date = new Date(manufactureDate);
    date.setFullYear(date.getFullYear() + 10);
    return date.toISOString().split("T")[0];
  };

  const fetchCylinders = async () => {
    try {
      const res = await fetch(`${API_BASE}/get_cylinders.php`);
      const json = await res.json();
      if (json.success) setCylinders(json.data);
    } catch (err) {
      console.error(err);
      alert("โหลดข้อมูลไม่สำเร็จ");
    }
  };

  useEffect(() => {
    fetchCylinders();
  }, []);

  const clearForm = () => {
    setNewSerialNumber("");
    setNewBrand("");
    setNewGasType("LPG");
    setNewSize("");
    setNewManufactureDate("");
    setNewExpiryDate("");
    setNewQrCode("");
    setNewLastCheckDate("");
    setNewNextCheckDate("");
    setNewDeliveredDate("");
    setNewCurrentLocation("");
    setNewStatus("ในคลัง");
    setEditingSerial(null);
  };

  const addOrUpdateCylinder = async () => {
    if (
      !newSerialNumber ||
      !newBrand ||
      !newGasType ||
      !newSize ||
      !newManufactureDate
    ) {
      alert("กรุณากรอกข้อมูลที่จำเป็นให้ครบ (Serial Number, ยี่ห้อ, ชนิด, ขนาด, วันที่ผลิต)");
      return;
    }

    const expiryDate = newExpiryDate || calculateExpiryDate(newManufactureDate);
    const nextCheckDate = newNextCheckDate || calculateNextCheckDate(newManufactureDate);

    // สร้าง QR Code จาก Serial Number
    const qrCode = `http://192.168.1.176:5173/cylinder/${newSerialNumber}`;

    const cylinderData = {
      serial_number: newSerialNumber,
      brand: newBrand,
      gas_type: newGasType,
      size: newSize,
      manufacture_date: newManufactureDate,
      expiry_date: expiryDate,
      qr_code: qrCode,
      last_check_date: newLastCheckDate,
      next_check_date: nextCheckDate,
      delivered_date: newDeliveredDate,
      current_location: newCurrentLocation,
      status: newStatus,
    };

    const url = editingSerial ? `${API_BASE}/update_cylinder.php` : `${API_BASE}/add_cylinder.php`;
    try {
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(cylinderData),
      });
      const result = await res.json();
      if (result.success) {
        await fetchCylinders();
        clearForm();
        alert(result.message);
      } else {
        alert(result.message);
      }
    } catch (err) {
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อ");
    }
  };

  const deleteCylinder = async (serialNumber) => {
    if (!window.confirm(`ยืนยันลบถัง Serial: ${serialNumber}?`)) return;
    try {
      const res = await fetch(`${API_BASE}/delete_cylinder.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ serial_number: serialNumber }),
      });
      const result = await res.json();
      if (result.success) {
        await fetchCylinders();
        if (editingSerial === serialNumber) clearForm();
        alert(result.message);
      } else {
        alert(result.message);
      }
    } catch (err) {
      alert("ลบไม่สำเร็จ");
    }
  };

  const editCylinder = (item) => {
    setNewSerialNumber(item.serial_number);
    setNewBrand(item.brand || "");
    setNewGasType(item.gas_type || "LPG");
    setNewSize(item.size || "");
    setNewManufactureDate(item.manufacture_date || "");
    setNewExpiryDate(item.expiry_date || "");
    setNewQrCode(item.qr_code || "");
    setNewLastCheckDate(item.last_check_date || "");
    setNewNextCheckDate(item.next_check_date || "");
    setNewDeliveredDate(item.delivered_date || "");
    setNewCurrentLocation(item.current_location || "");
    setNewStatus(item.status || "ในคลัง");
    setEditingSerial(item.serial_number);
  };

  const getStatusStyle = (status) => {
    switch (status) {
      case "ในคลัง": return { background: "#6b7280", color: "white" };
      case "กำลังส่ง":
      case "กำลังจัดส่ง": return { background: "#3b82f6", color: "white" };
      case "ปกติ": return { background: "#22c55e", color: "white" };
      case "รอซ่อม": return { background: "#f59e0b", color: "black" };
      case "ชำรุด": return { background: "#ef4444", color: "white" };
      default: return { background: "#374151", color: "white" };
    }
  };

  const filteredCylinders = cylinders.filter((item) => {
    const searchableText = [
      item.serial_number,
      item.brand,
      item.gas_type,
      item.size,
      item.manufacture_date,
      item.expiry_date,
      item.next_check_date,
      item.status,
      item.current_location,
      item.delivered_date,
    ].filter(Boolean).join(" ").toLowerCase();
    return searchableText.includes(searchTerm.toLowerCase());
  });

  const dueForMaintenance = cylinders.filter((item) => {
    if (!item.next_check_date) return false;
    return new Date(item.next_check_date) <= new Date();
  });

  const expiredCylinders = cylinders.filter((item) => {
    if (!item.expiry_date) return false;
    return new Date(item.expiry_date) <= new Date();
  });

  return (
    <Layout
      gasLevel={610}
      maintenanceDueItems={dueForMaintenance}
      expiredCylinderItems={expiredCylinders}
      deliverySuccessItems={[]}
      newAssignedJobItems={[]}
    >
      <h1 style={{ marginBottom: "20px", color: "white" }}>จัดการถังแก๊ส 🛢</h1>

      {/* ฟอร์มเพิ่ม/แก้ไข (เอาช่อง รหัสถัง ออกเรียบร้อย) */}
      <div style={formGridStyle}>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>Serial Number *</label>
          <input 
            value={newSerialNumber} 
            onChange={(e) => setNewSerialNumber(e.target.value)} 
            style={inputStyle} 
            placeholder="SN-001" 
            disabled={!!editingSerial} // ถ้าแก้ไข ห้ามแก้ Serial Number เพราะเป็น Primary Key
          />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>ยี่ห้อ *</label>
          <select value={newBrand} onChange={(e) => setNewBrand(e.target.value)} style={inputStyle}>
            <option value="">-- เลือกยี่ห้อ --</option>
            <option value="ปตท.">ปตท.</option>
            <option value="World Gas">เวิลด์แก๊ส</option>
            <option value="สยามแก๊ส">สยามแก๊ส</option>
            <option value="ยูนิคแก๊ส">ยูนิคแก๊ส</option>
            <option value="PT Gas">พีที (PT Gas)</option>
            <option value="พีเอพี">พีเอพี</option>
          </select>
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>ชนิดแก๊ส *</label>
          <select value={newGasType} onChange={(e) => setNewGasType(e.target.value)} style={inputStyle}>
            <option value="LPG">LPG</option>
          </select>
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>ขนาดถัง *</label>
          <select value={newSize} onChange={(e) => setNewSize(e.target.value)} style={inputStyle}>
            <option value="">-- เลือกขนาด --</option>
            <option value="4 กก.">4 กก.</option>
            <option value="7 กก.">7 กก.</option>
            <option value="11.5 กก.">11.5 กก.</option>
            <option value="13.5 กก.">13.5 กก.</option>
            <option value="15 กก.">15 กก.</option>
            <option value="48 กก.">48 กก.</option>
          </select>
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>วันที่ผลิต *</label>
          <input type="date" value={newManufactureDate} onChange={(e) => setNewManufactureDate(e.target.value)} style={inputStyle} />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>วันหมดอายุ (คำนวณอัตโนมัติ)</label>
          <input type="date" value={newExpiryDate || calculateExpiryDate(newManufactureDate)} readOnly style={{ ...inputStyle, background: "#e5e7eb" }} />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>QR Code (URL)</label>
          <input value={newQrCode} onChange={(e) => setNewQrCode(e.target.value)} style={inputStyle} placeholder="https://..." />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>วันที่ตรวจล่าสุด</label>
          <input type="date" value={newLastCheckDate} onChange={(e) => setNewLastCheckDate(e.target.value)} style={inputStyle} />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>วันที่ตรวจครั้งถัดไป (คำนวณ)</label>
          <input type="date" value={newNextCheckDate || calculateNextCheckDate(newManufactureDate)} readOnly style={{ ...inputStyle, background: "#e5e7eb" }} />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>วันที่ส่งมอบให้ลูกค้า</label>
          <input type="date" value={newDeliveredDate} onChange={(e) => setNewDeliveredDate(e.target.value)} style={inputStyle} />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>สถานที่ปัจจุบัน (UI)</label>
          <input value={newCurrentLocation} onChange={(e) => setNewCurrentLocation(e.target.value)} style={inputStyle} placeholder="คลังเชียงใหม่" />
        </div>
        {!editingSerial && (
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>สถานะเริ่มต้น</label>
            <input type="text" value="ในคลัง" readOnly style={{ ...inputStyle, background: "#e5e7eb" }} />
          </div>
        )}
        {editingSerial && (
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>สถานะ</label>
            <select value={newStatus} onChange={(e) => setNewStatus(e.target.value)} style={inputStyle}>
              <option value="ในคลัง">ในคลัง</option>
              <option value="กำลังส่ง">กำลังส่ง</option>
              <option value="ปกติ">ปกติ</option>
              <option value="รอซ่อม">รอซ่อม</option>
              <option value="ชำรุด">ชำรุด</option>
            </select>
          </div>
        )}
      </div>

      <div style={{ marginTop: "14px", marginBottom: "20px" }}>
        <button onClick={addOrUpdateCylinder} style={primaryButtonStyle}>
          {editingSerial ? "💾 บันทึก" : "➕ เพิ่ม"}
        </button>
        {editingSerial && <button onClick={clearForm} style={secondaryButtonStyle}>ยกเลิก</button>}
      </div>

      <div style={{ marginBottom: "16px" }}>
        <input 
          type="text" 
          placeholder="ค้นหาจาก Serial Number, ยี่ห้อ, ชนิด, ขนาด, สถานะ, สถานที่..." 
          value={searchTerm} 
          onChange={(e) => setSearchTerm(e.target.value)} 
          style={searchInputStyle} 
        />
      </div>

      {/* ตารางแสดงผล (ลบคอลัมน์ รหัสถัง ออกเรียบร้อย) */}
      <div style={{ overflowX: "auto" }}>
        <table style={tableStyle}>
          <thead>
            <tr>
              <th style={thStyle}>Serial</th>
              <th style={thStyle}>ยี่ห้อ</th>
              <th style={thStyle}>ชนิด</th>
              <th style={thStyle}>ขนาด</th>
              <th style={thStyle}>วันที่ผลิต</th>
              <th style={thStyle}>วันหมดอายุ</th>
              <th style={thStyle}>วันตรวจครั้งถัดไป</th>
              <th style={thStyle}>สถานะ</th>
              <th style={thStyle}>สถานที่ปัจจุบัน</th>
              <th style={thStyle}>วันที่ส่งมอบ</th>
              <th style={thStyle}>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            {filteredCylinders.length > 0 ? filteredCylinders.map((item) => (
              <tr key={item.serial_number}>
                <td style={tdStyle}><strong>{item.serial_number}</strong></td>
                <td style={tdStyle}>{item.brand || "-"}</td>
                <td style={tdStyle}>{item.gas_type || "-"}</td>
                <td style={tdStyle}>{item.size || "-"}</td>
                <td style={tdStyle}>{item.manufacture_date || "-"}</td>
                <td style={tdStyle}>{item.expiry_date || "-"}</td>
                <td style={tdStyle}>{item.next_check_date || "-"}</td>
                <td style={tdStyle}>
                  <span style={{ padding: "4px 10px", borderRadius: "999px", fontSize: "12px", ...getStatusStyle(item.status) }}>
                    {item.status}
                  </span>
                </td>
                <td style={tdStyle}>{item.current_location || "-"}</td>
                <td style={tdStyle}>{item.delivered_date || "-"}</td>
                <td style={{ ...tdStyle, whiteSpace: "nowrap" }}>
                  <button 
                    onClick={() => setSelectedQR(`http://192.168.1.176:5173/cylinder/${item.serial_number}`)}
                    style={qrButtonStyle}
                  >
                    📱 QR
                  </button>
                  <button onClick={() => editCylinder(item)} style={editButtonStyle}>✏️ แก้ไข</button>
                  <button onClick={() => deleteCylinder(item.serial_number)} style={deleteButtonStyle}>❌ ลบ</button>
                </td>
              </tr>
            )) : (
              <tr>
                <td style={{ ...tdStyle, textAlign: "center" }} colSpan="11">ไม่พบข้อมูลถังแก๊ส</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {/* Pop-up แสดงภาพ QR Code */}
      {selectedQR && (
        <div style={modalOverlayStyle}>
          <div style={modalContentStyle}>
            <h3 style={{ margin: "0 0 16px 0", fontSize: "16px", color: "white" }}>QR Code ติดถังแก๊ส</h3>
            <div style={{ background: "white", padding: "16px", borderRadius: "12px", display: "inline-block" }}>
              <QRCodeSVG value={selectedQR} size={180} />
            </div>
            <p style={{ color: "#9ca3af", fontSize: "11px", marginTop: "12px", wordBreak: "break-all", fontFamily: "monospace" }}>
              {selectedQR}
            </p>
            <div style={{ marginTop: "20px", display: "flex", justifyContent: "center", gap: "10px" }}>
              <button onClick={() => window.print()} style={printButtonStyle}>
                🖨️ สั่งพิมพ์
              </button>
              <button onClick={() => setSelectedQR(null)} style={closeButtonStyle}>
                ปิด
              </button>
            </div>
          </div>
        </div>
      )}
    </Layout>
  );
}

// --- Styles ---
const formGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))", gap: "12px", marginBottom: "16px" };
const fieldGroupStyle = { display: "flex", flexDirection: "column", gap: "6px" };
const labelStyle = { fontSize: "13px", fontWeight: "bold", color: "#e5e7eb" };
const inputStyle = { padding: "10px", borderRadius: "8px", border: "1px solid #ccc", width: "100%", boxSizing: "border-box" };
const searchInputStyle = { padding: "10px", borderRadius: "8px", border: "1px solid #ccc", width: "100%", boxSizing: "border-box" };
const tableStyle = { width: "100%", borderCollapse: "collapse", background: "#1f2937", color: "white", borderRadius: "12px", overflow: "hidden" };
const thStyle = { padding: "10px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px", whiteSpace: "nowrap" };
const tdStyle = { padding: "10px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px", wordBreak: "break-word" };
const primaryButtonStyle = { marginRight: "10px", padding: "10px 14px", border: "none", borderRadius: "8px", background: "#2563eb", color: "white", cursor: "pointer" };
const secondaryButtonStyle = { padding: "10px 14px", border: "none", borderRadius: "8px", background: "#6b7280", color: "white", cursor: "pointer" };
const editButtonStyle = { marginRight: "8px", padding: "6px 10px", border: "none", borderRadius: "6px", background: "#f59e0b", color: "white", cursor: "pointer" };
const deleteButtonStyle = { padding: "6px 10px", border: "none", borderRadius: "6px", background: "#dc2626", color: "white", cursor: "pointer" };

const qrButtonStyle = { marginRight: "8px", padding: "6px 10px", border: "none", borderRadius: "6px", background: "#059669", color: "white", cursor: "pointer", fontWeight: "bold" };
const printButtonStyle = { padding: "8px 14px", border: "none", borderRadius: "8px", background: "#2563eb", color: "white", cursor: "pointer", fontWeight: "bold" };
const closeButtonStyle = { padding: "8px 14px", border: "none", borderRadius: "8px", background: "#4b5563", color: "white", cursor: "pointer" };
const modalOverlayStyle = { position: "fixed", top: 0, left: 0, right: 0, bottom: 0, backgroundColor: "rgba(0, 0, 0, 0.6)", display: "flex", justifyContent: "center", alignItems: "center", zIndex: 999 };
const modalContentStyle = { background: "#1f2937", padding: "24px", borderRadius: "16px", textAlign: "center", boxShadow: "0 20px 25px -5px rgba(0, 0, 0, 0.5)", border: "1px solid #374151", width: "300px" };

export default GasPage;