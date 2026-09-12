// src/components/RegisterCylinderModal.jsx
import { useState } from "react";
import API_BASE_URL from "../config";

function RegisterCylinderModal({ isOpen, onClose, initialSerial, defaultLocation = "คลัง", onSuccess }) {
  const [formData, setFormData] = useState({
    serial_number: initialSerial || "",
    brand: "",
    gas_type: "LPG",
    size: "",
    manufacture_date: new Date().toISOString().split("T")[0],
    expiry_date: "",
    qr_code: initialSerial || "",
    current_location: defaultLocation,
  });

  if (!isOpen) return null;

  const calculateExpiry = (manuDate) => {
    if (!manuDate) return "";
    const d = new Date(manuDate);
    d.setFullYear(d.getFullYear() + 5);
    return d.toISOString().split("T")[0];
  };

  const handleChange = (field, value) => {
    setFormData((prev) => {
      const updated = { ...prev, [field]: value };
      if (field === "manufacture_date") {
        updated.expiry_date = calculateExpiry(value);
      }
      return updated;
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.serial_number || !formData.brand || !formData.size || !formData.manufacture_date) {
      alert("กรุณากรอกข้อมูลที่จำเป็น (*) ให้ครบถ้วน");
      return;
    }

    const payload = {
      ...formData,
      expiry_date: formData.expiry_date || calculateExpiry(formData.manufacture_date),
      last_check_date: formData.manufacture_date,
      next_check_date: formData.expiry_date || calculateExpiry(formData.manufacture_date),
    };

    try {
      const token = localStorage.getItem("token") || "";
      const res = await fetch(`${API_BASE_URL}/cylinder/create.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();
      if (data.success || data.status === "success") {
        alert("ลงทะเบียนถังแก๊สใหม่เข้าฐานข้อมูลสำเร็จ");
        onSuccess(payload);
        onClose();
      } else {
        alert(data.message || "ไม่สามารถเพิ่มถังแก๊สได้");
      }
    } catch (err) {
      console.error(err);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์");
    }
  };

  return (
    <div style={modalStyles.overlay}>
      <div style={modalStyles.content}>
        <h3 style={{ marginTop: 0, color: "#fff" }}>➕ ลงทะเบียนถังแก๊สใหม่เข้าระบบ</h3>
        <p style={{ fontSize: "13px", color: "#9ca3af" }}>
          ไม่พบ Serial Number <strong style={{ color: "#facc15" }}>{formData.serial_number}</strong> ในฐานข้อมูล
        </p>

        <form onSubmit={handleSubmit} style={modalStyles.formGrid}>
          <div>
            <label style={modalStyles.label}>Serial Number *</label>
            <input
              style={modalStyles.input}
              value={formData.serial_number}
              onChange={(e) => handleChange("serial_number", e.target.value)}
              required
            />
          </div>

          <div>
            <label style={modalStyles.label}>ยี่ห้อ *</label>
            <select
              style={modalStyles.input}
              value={formData.brand}
              onChange={(e) => handleChange("brand", e.target.value)}
              required
            >
              <option value="">-- เลือกยี่ห้อ --</option>
              <option value="ปตท.">ปตท.</option>
              <option value="World Gas">World Gas</option>
              <option value="สยามแก๊ส">สยามแก๊ส</option>
              <option value="ยูนิคแก๊ส">ยูนิคแก๊ส</option>
              <option value="PT Gas">PT Gas</option>
              <option value="พีเอพี">พีเอพี</option>
            </select>
          </div>

          <div>
            <label style={modalStyles.label}>ชนิดแก๊ส *</label>
            <select
              style={modalStyles.input}
              value={formData.gas_type}
              onChange={(e) => handleChange("gas_type", e.target.value)}
            >
              <option value="LPG">LPG</option>
            </select>
          </div>

          <div>
            <label style={modalStyles.label}>ขนาดถัง *</label>
            <select
              style={modalStyles.input}
              value={formData.size}
              onChange={(e) => handleChange("size", e.target.value)}
              required
            >
              <option value="">-- เลือกขนาด --</option>
              <option value="4 กก.">4 กก.</option>
              <option value="7 กก.">7 กก.</option>
              <option value="11.5 กก.">11.5 กก.</option>
              <option value="13.5 กก.">13.5 กก.</option>
              <option value="15 กก.">15 กก.</option>
              <option value="48 กก.">48 กก.</option>
            </select>
          </div>

          <div>
            <label style={modalStyles.label}>วันที่ผลิต *</label>
            <input
              type="date"
              style={modalStyles.input}
              value={formData.manufacture_date}
              onChange={(e) => handleChange("manufacture_date", e.target.value)}
              required
            />
          </div>

          <div>
            <label style={modalStyles.label}>วันหมดอายุ (อัตโนมัติ 5 ปี)</label>
            <input
              type="date"
              style={{ ...modalStyles.input, background: "#374151", color: "#9ca3af" }}
              value={formData.expiry_date || calculateExpiry(formData.manufacture_date)}
              disabled
            />
          </div>

          <div>
            <label style={modalStyles.label}>สถานที่ปัจจุบัน</label>
            <input
              style={modalStyles.input}
              value={formData.current_location}
              onChange={(e) => handleChange("current_location", e.target.value)}
            />
          </div>

          <div style={{ display: "flex", gap: "8px", marginTop: "16px" }}>
            <button type="button" onClick={onClose} style={modalStyles.cancelBtn}>
              ยกเลิก
            </button>
            <button type="submit" style={modalStyles.submitBtn}>
              💾 บันทึกเข้าฐานข้อมูล
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

const modalStyles = {
  overlay: {
    position: "fixed",
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    background: "rgba(0,0,0,0.75)",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    zIndex: 9999,
    padding: "16px",
  },
  content: {
    background: "#1f2937",
    padding: "20px",
    borderRadius: "12px",
    width: "100%",
    maxWidth: "480px",
    maxHeight: "90vh",
    overflowY: "auto",
    boxSizing: "border-box",
  },
  formGrid: {
    display: "flex",
    flexDirection: "column",
    gap: "12px",
  },
  label: {
    display: "block",
    fontSize: "12px",
    color: "#9ca3af",
    marginBottom: "4px",
  },
  input: {
    width: "100%",
    padding: "10px",
    borderRadius: "8px",
    border: "1px solid #4b5563",
    background: "#ffffff",
    color: "#111827",
    fontSize: "14px",
    boxSizing: "border-box",
  },
  submitBtn: {
    flex: 1,
    padding: "12px",
    border: "none",
    borderRadius: "8px",
    background: "#2563eb",
    color: "white",
    fontWeight: "bold",
    cursor: "pointer",
  },
  cancelBtn: {
    flex: 1,
    padding: "12px",
    border: "none",
    borderRadius: "8px",
    background: "#4b5563",
    color: "white",
    fontWeight: "bold",
    cursor: "pointer",
  },
};

export default RegisterCylinderModal;