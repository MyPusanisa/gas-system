import React, { useState, useEffect } from "react";
import Layout from "../components/Layout";
import { API_BASE } from "../config";

const FONT_FAMILY = "'Kanit', 'Sarabun', sans-serif";

export default function AdminApprovalPage() {
  const [pendingList, setPendingList] = useState([]);
  const [loading, setLoading] = useState(true);

  // State สำหรับรับถังแก๊สคืน / ลงทะเบียนถังนอกระบบ
  const [searchSerial, setSearchSerial] = useState("");
  const [searchResult, setSearchResult] = useState(null);
  const [isSearching, setIsSearching] = useState(false);
  
  const [importDate, setImportDate] = useState(new Date().toISOString().split("T")[0]);
  const [newCylinderData, setNewCylinderData] = useState({
    brand: "",
    gas_type: "LPG",
    size: "",
  });

  // State สำหรับเก็บ Master Data (ดึงยี่ห้อและขนาดถังมาตรฐาน)
  const [options, setOptions] = useState({
    brands: ["ปตท.", "World Gas", "สยามแก๊ส", "ยูนิคแก๊ส", "PT Gas", "พีเอพี"],
    gas_types: ["LPG"],
    sizes: ["4 กก.", "7 กก.", "11.5 กก.", "13.5 กก.", "15 กก.", "48 กก."]
  });

  useEffect(() => {
    fetchPendingDeliveries();
    fetchCylinderOptions();
  }, []);

  const fetchCylinderOptions = async () => {
    try {
      const res = await fetch(`${API_BASE}/cylinder/return_cylinder.php?action=get_options`);
      const data = await res.json();
      if (data.success && data.brands?.length) {
        setOptions({
          brands: data.brands,
          gas_types: data.gas_types || ["LPG"],
          sizes: data.sizes
        });
      }
    } catch (err) {
      console.warn("ใช้ตัวเลือกถังแก๊สเริ่มต้นแทนการดึง Master Data:", err);
    }
  };

  const fetchPendingDeliveries = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/delivery/get_pending.php`);
      const data = await res.json();
      if (data.success) {
        setPendingList(data.data);
      } else {
        alert(data.message || "ไม่สามารถดึงข้อมูลรายการรออนุมัติได้");
      }
    } catch (err) {
      console.error(err);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์");
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (deliveryId) => {
    if (!confirm(`ยืนยันการอนุมัติงานจัดส่ง รหัส #${deliveryId} ใช่หรือไม่?`)) return;

    try {
      const res = await fetch(`${API_BASE}/delivery/update_status.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "approve",
          delivery_id: deliveryId
        })
      });

      const data = await res.json();

      if (data.success) {
        alert("อนุมัติงานจัดส่งเรียบร้อยแล้ว");
        fetchPendingDeliveries();
      } else {
        alert(data.message || "เกิดข้อผิดพลาดในการอนุมัติ");
      }
    } catch (err) {
      console.error(err);
      alert("เกิดข้อผิดพลาดในการสื่อสารกับเซิร์ฟเวอร์");
    }
  };

  const calculateDaysLeft = (targetDateStr) => {
    if (!targetDateStr) return null;
    const targetDate = new Date(targetDateStr);
    const today = new Date();
    const diffTime = targetDate - today;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  };

  const handleSearchCylinder = async (e) => {
    if (e) e.preventDefault();
    if (!searchSerial.trim()) return alert("กรุณากรอก Serial Number");

    setIsSearching(true);
    setSearchResult(null);

    try {
      const res = await fetch(`${API_BASE}/cylinder/return_cylinder.php?action=search&serial=${encodeURIComponent(searchSerial.trim())}`);
      const data = await res.json();

      if (data.success) {
        setSearchResult(data);
      } else {
        alert(data.message || "เกิดข้อผิดพลาดในการค้นหา");
      }
    } catch (err) {
      console.error(err);
      alert("ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้");
    } finally {
      setIsSearching(false);
    }
  };

  const handleConfirmReturn = async () => {
    try {
      const res = await fetch(`${API_BASE}/cylinder/return_cylinder.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "return_existing",
          serial_number: searchSerial.trim(),
          import_date: importDate
        })
      });
      const data = await res.json();

      if (data.success) {
        alert(data.message);
        setSearchSerial("");
        setSearchResult(null);
      } else {
        alert(data.message || "ไม่สามารถรับคืนถังได้");
      }
    } catch (err) {
      console.error(err);
      alert("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
    }
  };

  // ลงทะเบียนนำเข้าถังใหม่ลงตารางแก๊สหลัก (เข้าคลัง)
  const handleAddNewCylinder = async (e) => {
    e.preventDefault();
    if (!newCylinderData.brand || !newCylinderData.gas_type || !newCylinderData.size) {
      return alert("กรุณาเลือกข้อมูลถังแก๊สใหม่ให้ครบถ้วน");
    }

    try {
      const res = await fetch(`${API_BASE}/cylinder/return_cylinder.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "add_new",
          serial_number: searchSerial.trim(),
          brand: newCylinderData.brand,
          gas_type: newCylinderData.gas_type,
          size: newCylinderData.size,
          import_date: importDate,
          current_location: "คลัง"
        })
      });
      const data = await res.json();

      if (data.success) {
        alert(data.message || "ลงทะเบียนนำเข้าคลังสำเร็จ");
        setSearchSerial("");
        setSearchResult(null);
        setNewCylinderData({ brand: "", gas_type: "LPG", size: "" });
      } else {
        alert(data.message || "เกิดข้อผิดพลาดในการเพิ่มถังใหม่");
      }
    } catch (err) {
      console.error(err);
      alert("เกิดข้อผิดพลาดในการส่งข้อมูล");
    }
  };

  return (
    <Layout>
      <div style={{ maxWidth: "1000px", margin: "0 auto" }}>
        
        {/* Card ค้นหา/รับถังแก๊สคืน */}
        <div style={cardContainerStyle}>
          <h3 style={{ margin: "0 0 12px 0", color: "#38bdf8", fontSize: "18px" }}>
             รับถังแก๊สคืนเข้าคลัง / ลงทะเบียนถังนอกระบบ
          </h3>

          <form onSubmit={handleSearchCylinder} style={{ display: "flex", gap: "10px" }}>
            <input
              type="text"
              placeholder="กรอกเลข SERIAL NUMBER เพื่อค้นหา..."
              value={searchSerial}
              onChange={(e) => {
                setSearchSerial(e.target.value);
                setSearchResult(null);
              }}
              style={inputStyle}
            />
            <button type="submit" disabled={isSearching} style={primaryBtnStyle}>
              {isSearching ? "กำลังค้นหา..." : "ตรวจสอบ"}
            </button>
          </form>

          {/* กรณีพบข้อมูลถังเดิม */}
          {searchResult && searchResult.found && (
            <div style={resultBoxStyle("#166534", "#bbf7d0")}>
              <p style={{ margin: "0 0 8px 0", fontWeight: "bold" }}>
                ✅ พบข้อมูลถังแก๊สในระบบ:
              </p>
              <div style={{ fontSize: "14px", lineHeight: "1.6" }}>
                <div><strong>Serial:</strong> {searchResult.data.serial_number}</div>
                <div><strong>ยี่ห้อ/ประเภท/ขนาด:</strong> {searchResult.data.brand} | {searchResult.data.gas_type} | {searchResult.data.size}</div>
                <div><strong>วันที่นำเข้าครั้งล่าสุด:</strong> {searchResult.data.import_date || "ยังไม่มีข้อมูล"}</div>
                
                {searchResult.data.next_inspection_date && (
                  <div style={{ marginTop: "6px", background: "rgba(0,0,0,0.2)", padding: "8px", borderRadius: "6px" }}>
                    <strong>กำหนดตรวจสภาพครั้งถัดไป:</strong> {searchResult.data.next_inspection_date} 
                    <span style={{ color: "#facc15", fontWeight: "bold", marginLeft: "10px" }}>
                      (เหลือเวลาอีก {searchResult.data.days_left ?? calculateDaysLeft(searchResult.data.next_inspection_date)} วัน)
                    </span>
                  </div>
                )}
              </div>

              <div style={{ marginTop: "12px", borderTop: "1px solid rgba(255,255,255,0.2)", paddingTop: "10px" }}>
                <label style={{ ...labelStyle, color: "#fff" }}>ระบุวันที่นำเข้ากลับคลังครั้งนี้:</label>
                <input
                  type="date"
                  value={importDate}
                  onChange={(e) => setImportDate(e.target.value)}
                  style={{ ...inputStyle, width: "auto", marginBottom: "10px" }}
                />
                <button onClick={handleConfirmReturn} style={{ ...primaryBtnStyle, width: "100%", background: "#22c55e" }}>
                  ยืนยันรับถังนี้กลับเข้าคลังและเริ่มนับรอบตรวจสอบใหม่
                </button>
              </div>
            </div>
          )}

          {/* กรณีไม่พบข้อมูล -> เลือก ยี่ห้อ / ชนิด / ขนาด จาก Master Data เดียวกัน */}
          {searchResult && !searchResult.found && (
            <div style={resultBoxStyle("#991b1b", "#fecaca")}>
              <p style={{ margin: "0 0 12px 0", fontWeight: "bold" }}>
                ⚠️ ไม่พบ Serial Number "{searchSerial}" ในระบบ กรุณาลงทะเบียนนำถังเข้าคลัง
              </p>

              <form onSubmit={handleAddNewCylinder} style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: "10px" }}>
                
                {/* ยี่ห้อ Dropdown */}
                <div>
                  <label style={labelStyle}>ยี่ห้อ *</label>
                  <select
                    value={newCylinderData.brand}
                    onChange={(e) => setNewCylinderData({ ...newCylinderData, brand: e.target.value })}
                    style={selectStyle}
                    required
                  >
                    <option value="">เลือกยี่ห้อ</option>
                    {options.brands.map((b, idx) => (
                      <option key={idx} value={b}>{b}</option>
                    ))}
                  </select>
                </div>

                {/* ชนิดแก๊ส Dropdown */}
                <div>
                  <label style={labelStyle}>ชนิดแก๊ส *</label>
                  <select
                    value={newCylinderData.gas_type}
                    onChange={(e) => setNewCylinderData({ ...newCylinderData, gas_type: e.target.value })}
                    style={selectStyle}
                    required
                  >
                    {options.gas_types.map((g, idx) => (
                      <option key={idx} value={g}>{g}</option>
                    ))}
                  </select>
                </div>

                {/* ขนาดถัง Dropdown */}
                <div>
                  <label style={labelStyle}>ขนาดถัง *</label>
                  <select
                    value={newCylinderData.size}
                    onChange={(e) => setNewCylinderData({ ...newCylinderData, size: e.target.value })}
                    style={selectStyle}
                    required
                  >
                    <option value="">เลือกขนาด</option>
                    {options.sizes.map((s, idx) => (
                      <option key={idx} value={s}>{s}</option>
                    ))}
                  </select>
                </div>
                
                <div style={{ gridColumn: "span 3" }}>
                  <label style={labelStyle}>วันที่นำเข้าคลัง</label>
                  <input
                    type="date"
                    value={importDate}
                    onChange={(e) => setImportDate(e.target.value)}
                    style={inputStyle}
                    required
                  />
                </div>

                <div style={{ gridColumn: "span 3", marginTop: "5px" }}>
                  <button type="submit" style={{ ...primaryBtnStyle, width: "100%", background: "#3b82f6" }}>
                    ลงทะเบียนนำเข้าคลัง
                  </button>
                </div>
              </form>
            </div>
          )}
        </div>

        {/* อนุมัติการจัดส่ง */}
        <h2 style={{ fontSize: "24px", marginBottom: "20px" }}>อนุมัติการจัดส่ง (Admin Approval)</h2>

        {loading ? (
          <div>กำลังโหลดข้อมูล...</div>
        ) : pendingList.length === 0 ? (
          <div style={{ padding: "30px", background: "#1f2937", borderRadius: "12px", textAlign: "center" }}>
            ไม่มีรายการที่รออนุมัติในขณะนี้
          </div>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: "20px" }}>
            {pendingList.map((item) => (
              <div
                key={item.delivery_id}
                style={{
                  background: "#1f2937",
                  borderRadius: "12px",
                  padding: "20px",
                  display: "grid",
                  gridTemplateColumns: "1fr 260px",
                  gap: "20px"
                }}
              >
                <div>
                  <h3 style={{ marginTop: 0 }}>รหัสงาน: #{item.delivery_id}</h3>
                  <p><strong>ลูกค้า:</strong> {item.customer_name} ({item.phone})</p>
                  <p><strong>ที่อยู่:</strong> {item.address}</p>
                  <p><strong>ประเภทแก๊ส:</strong> {item.brand} | {item.gas_type} | {item.size}</p>
                  <p><strong>ผู้จัดส่ง:</strong> {item.staff_name || item.staff_id}</p>

                  <button
                    onClick={() => handleApprove(item.delivery_id)}
                    style={{
                      marginTop: "15px",
                      padding: "10px 20px",
                      background: "#22c55e",
                      color: "#fff",
                      border: "none",
                      borderRadius: "8px",
                      cursor: "pointer",
                      fontWeight: "bold",
                      fontSize: "16px"
                    }}
                  >
                    อนุมัติงานจัดส่ง
                  </button>
                </div>

                <div>
                  <p style={{ fontWeight: "bold", marginTop: 0, marginBottom: "8px" }}>รูปหลักฐานจัดส่ง:</p>
                  {item.proof_image_path ? (
                    <img
                      src={`${API_BASE.replace('/models', '')}/uploads/${item.proof_image_path}`}
                      alt="หลักฐาน"
                      style={{
                        width: "100%",
                        height: "180px",
                        objectFit: "cover",
                        borderRadius: "8px",
                        border: "1px solid #374151"
                      }}
                    />
                  ) : (
                    <div
                      style={{
                        height: "180px",
                        background: "#0f172a",
                        borderRadius: "8px",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        color: "#64748b"
                      }}
                    >
                      ไม่มีรูปภาพ
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
}

// Styles
const cardContainerStyle = {
  background: "#1f2937",
  borderRadius: "12px",
  padding: "20px",
  marginBottom: "30px",
  border: "1px solid #374151",
  fontFamily: FONT_FAMILY,
};

const inputStyle = {
  padding: "10px 14px",
  borderRadius: "8px",
  border: "1px solid #4b5563",
  background: "#111827",
  color: "white",
  width: "100%",
  fontSize: "14px",
  boxSizing: "border-box",
  fontFamily: FONT_FAMILY,
};

const selectStyle = {
  ...inputStyle,
  cursor: "pointer",
  fontFamily: FONT_FAMILY,
};

const labelStyle = {
  display: "block",
  fontSize: "12px",
  color: "#9ca3af",
  marginBottom: "4px",
  fontFamily: FONT_FAMILY,
};

const primaryBtnStyle = {
  padding: "10px 20px",
  background: "#2563eb",
  color: "white",
  border: "none",
  borderRadius: "8px",
  cursor: "pointer",
  fontWeight: "bold",
  fontSize: "14px",
  whiteSpace: "nowrap",
  fontFamily: FONT_FAMILY,
};

const resultBoxStyle = (bgColor, textColor) => ({
  marginTop: "15px",
  padding: "15px",
  borderRadius: "8px",
  background: bgColor,
  color: textColor,
  fontFamily: FONT_FAMILY,
});