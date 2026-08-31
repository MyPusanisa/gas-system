import React, { useState, useEffect } from "react";
import Layout from "../components/Layout";

const BASE_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost";
const API_BASE = "/Backend/models";


function GasPage() {
  const [cylinders, setCylinders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [editingSerial, setEditingSerial] = useState(null);

  const initialFormState = {
    serial_number: "",
    brand: "",
    gas_type: "LPG",
    size: "",
    manufacture_date: "",
    expiry_date: "",
    last_check_date: "",
    next_check_date: "",
    delivered_date: "",
    current_location: "",
    status: "ในคลัง",
  };

  const [formData, setFormData] = useState(initialFormState);

  const fetchCylinders = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/get_cylinders.php`);
      const data = await res.json();
      if (data.success) {
        setCylinders(data.data || []);
      }
    } catch (err) {
      console.error("Error fetching data:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCylinders();
  }, []);

  const clearForm = () => {
    setEditingSerial(null);
    setFormData(initialFormState);
  };

  const editCylinder = (item) => {
    setEditingSerial(item.serial_number);
    setFormData({
      serial_number: item.serial_number || "",
      brand: item.brand || item.brand_id || "",
      gas_type: item.gas_type || item.gas_type_id || "LPG",
      size: item.size || item.size_id || "",
      manufacture_date: item.manufacture_date || "",
      expiry_date: item.expiry_date || item.expire_date || "",
      last_check_date: item.last_check_date || item.last_checked || "",
      next_check_date: item.next_check_date || item.next_check || "",
      delivered_date: item.delivered_date || item.delivery_date || "",
      current_location: item.current_location || item.location_id || "",
      status: item.status || "ในคลัง",
    });
  };

  const handleSave = async (e) => {
    if (e) e.preventDefault();
    if (!formData.serial_number.trim()) {
      alert("กรุณากรอก Serial Number");
      return;
    }

    setIsSubmitting(true);

    const payload = {
      serial_number: formData.serial_number.trim(),
      brand: formData.brand,
      gas_type: formData.gas_type,
      size: formData.size,
      manufacture_date: formData.manufacture_date || null,
      expiry_date: formData.expiry_date || null,
      last_check_date: formData.last_check_date || null,
      next_check_date: formData.next_check_date || null,
      delivered_date: formData.delivered_date || null,
      current_location: formData.current_location,
      status: formData.status,
    };

    const url = editingSerial
      ? `${API_BASE}/update_cylinder.php`
      : `${API_BASE}/create_cylinder.php`;

    try {
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const data = await res.json();
      if (data.success) {
        alert(editingSerial ? "แก้ไขข้อมูลสำเร็จ" : "เพิ่มถังแก๊สสำเร็จ");
        clearForm();
        fetchCylinders();
      } else {
        alert(data.message || "เกิดข้อผิดพลาดในการบันทึก");
      }
    } catch (err) {
      console.error(err);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์");
    } finally {
      setIsSubmitting(false);
    }
  };

  if (loading) {
    return (
      <Layout>
        <div style={{ color: "white", textAlign: "center", padding: "50px" }}>
          กำลังโหลดข้อมูล...
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <h1 style={{ marginBottom: "20px", color: "white" }}>จัดการถังแก๊ส</h1>

      <form onSubmit={handleSave} style={formCardStyle}>
        <div style={formGridStyle}>
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>Serial Number *</label>
            <input
              type="text"
              value={formData.serial_number}
              onChange={(e) => setFormData({ ...formData, serial_number: e.target.value })}
              style={inputStyle}
              placeholder="SN-001"
              disabled={!!editingSerial}
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ยี่ห้อ *</label>
            <input
              type="text"
              value={formData.brand}
              onChange={(e) => setFormData({ ...formData, brand: e.target.value })}
              style={inputStyle}
              placeholder="เลือกยี่ห้อ"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ชนิดแก๊ส *</label>
            <input
              type="text"
              value={formData.gas_type}
              onChange={(e) => setFormData({ ...formData, gas_type: e.target.value })}
              style={inputStyle}
              placeholder="เลือกชนิด"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ขนาดถัง *</label>
            <input
              type="text"
              value={formData.size}
              onChange={(e) => setFormData({ ...formData, size: e.target.value })}
              style={inputStyle}
              placeholder="เลือกขนาด"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันที่ผลิต *</label>
            <input
              type="date"
              value={formData.manufacture_date}
              onChange={(e) => setFormData({ ...formData, manufacture_date: e.target.value })}
              style={inputStyle}
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันหมดอายุ (คำนวณอัตโนมัติ)</label>
            <input
              type="date"
              value={formData.expiry_date}
              onChange={(e) => setFormData({ ...formData, expiry_date: e.target.value })}
              style={inputStyle}
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันที่ตรวจล่าสุด</label>
            <input
              type="date"
              value={formData.last_check_date}
              onChange={(e) => setFormData({ ...formData, last_check_date: e.target.value })}
              style={inputStyle}
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันที่ตรวจครั้งถัดไป (คำนวณ)</label>
            <input
              type="date"
              value={formData.next_check_date}
              onChange={(e) => setFormData({ ...formData, next_check_date: e.target.value })}
              style={inputStyle}
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันที่ส่งมอบให้ลูกค้า</label>
            <input
              type="date"
              value={formData.delivered_date}
              onChange={(e) => setFormData({ ...formData, delivered_date: e.target.value })}
              style={inputStyle}
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>สถานที่ปัจจุบัน</label>
            <input
              type="text"
              value={formData.current_location}
              onChange={(e) => setFormData({ ...formData, current_location: e.target.value })}
              style={inputStyle}
              placeholder="เลือกสถานที่"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>สถานะเริ่มต้น</label>
            <select
              value={formData.status}
              onChange={(e) => setFormData({ ...formData, status: e.target.value })}
              style={inputStyle}
            >
              <option value="ในคลัง">ในคลัง</option>
              <option value="ปกติ">ปกติ</option>
              <option value="ส่งแล้ว">ส่งแล้ว</option>
              <option value="ชำรุด">ชำรุด</option>
            </select>
          </div>
        </div>

        <div style={{ display: "flex", gap: "10px", marginTop: "15px" }}>
          <button type="submit" style={primaryButtonStyle} disabled={isSubmitting}>
            {isSubmitting ? "กำลังบันทึก..." : editingSerial ? "บันทึก" : "เพิ่ม"}
          </button>
          {editingSerial && (
            <button type="button" onClick={clearForm} style={secondaryButtonStyle}>
              ยกเลิก
            </button>
          )}
        </div>
      </form>

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
            {cylinders.map((item) => (
              <tr key={item.serial_number}>
                <td style={tdStyle}>{item.serial_number}</td>
                <td style={tdStyle}>{item.brand || item.brand_name || "-"}</td>
                <td style={tdStyle}>{item.gas_type || item.gas_type_name || "-"}</td>
                <td style={tdStyle}>{item.size || item.size_name || "-"}</td>
                <td style={tdStyle}>{item.manufacture_date || "-"}</td>
                <td style={tdStyle}>{item.expiry_date || item.expire_date || "-"}</td>
                <td style={tdStyle}>{item.next_check_date || item.next_check || "-"}</td>
                <td style={tdStyle}>{item.status}</td>
                <td style={tdStyle}>{item.current_location || item.location_name || "-"}</td>
                <td style={tdStyle}>{item.delivered_date || item.delivery_date || "-"}</td>
                <td style={tdStyle}>
                  <div style={{ display: "flex", gap: "5px" }}>
                    <button type="button" style={qrButtonStyle}>
                      QR
                    </button>
                    <button type="button" onClick={() => editCylinder(item)} style={editButtonStyle}>
                      แก้ไข
                    </button>
                    <button type="button" style={deleteButtonStyle}>
                      ลบ
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Layout>
  );
}

const formCardStyle = {
  background: "#1f2937",
  padding: "20px",
  borderRadius: "12px",
  marginBottom: "20px",
};

const formGridStyle = {
  display: "grid",
  gridTemplateColumns: "repeat(auto-fit, minmax(200px, 1fr))",
  gap: "12px",
};

const fieldGroupStyle = {
  display: "flex",
  flexDirection: "column",
  gap: "6px",
};

const labelStyle = {
  fontSize: "13px",
  color: "#e5e7eb",
};

const inputStyle = {
  padding: "8px 12px",
  borderRadius: "8px",
  border: "1px solid #4b5563",
  background: "#111827",
  color: "white",
  width: "100%",
  boxSizing: "border-box",
};

const primaryButtonStyle = {
  padding: "10px 18px",
  border: "none",
  borderRadius: "8px",
  background: "#2563eb",
  color: "white",
  cursor: "pointer",
  fontWeight: "bold",
};

const secondaryButtonStyle = {
  padding: "10px 18px",
  border: "none",
  borderRadius: "8px",
  background: "#6b7280",
  color: "white",
  cursor: "pointer",
};

const qrButtonStyle = {
  padding: "6px 10px",
  border: "none",
  borderRadius: "6px",
  background: "#10b981",
  color: "white",
  cursor: "pointer",
};

const editButtonStyle = {
  padding: "6px 10px",
  border: "none",
  borderRadius: "6px",
  background: "#f59e0b",
  color: "white",
  cursor: "pointer",
};

const deleteButtonStyle = {
  padding: "6px 10px",
  border: "none",
  borderRadius: "6px",
  background: "#ef4444",
  color: "white",
  cursor: "pointer",
};

const tableStyle = {
  width: "100%",
  borderCollapse: "collapse",
  background: "#1f2937",
  color: "white",
  borderRadius: "12px",
};

const thStyle = {
  padding: "12px",
  textAlign: "left",
  borderBottom: "1px solid #374151",
  backgroundColor: "#2d3a4a",
};

const tdStyle = {
  padding: "10px",
  borderBottom: "1px solid #374151",
};

export default GasPage;