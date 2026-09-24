import { useState, useEffect } from "react";
import Layout from "../components/Layout";
import { API_BASE } from "../config";
import { Search, CircleCheck, TriangleAlert, PackagePlus, Undo2, Check, User, Phone, MapPin, Truck, ImageOff } from "lucide-react";

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
      <div style={pageStyle}>
        <div style={{ marginBottom: "20px" }}>
          <h1 style={titleStyle}>อนุมัติการจัดส่ง</h1>
          <div style={subtitleStyle}>ตรวจหลักฐานและอนุมัติงานจัดส่ง · รับถังคืนเข้าคลัง</div>
        </div>

        {/* รับถังแก๊สคืน / ลงทะเบียนถังนอกระบบ */}
        <div style={cardStyle}>
          <h2 style={cardTitleStyle}>
            <Undo2 size={18} color="#60a5fa" /> รับถังคืนเข้าคลัง / ลงทะเบียนถังนอกระบบ
          </h2>

          <form onSubmit={handleSearchCylinder} style={{ display: "flex", gap: "10px", flexWrap: "wrap" }}>
            <div style={{ position: "relative", flex: 1, minWidth: "220px" }}>
              <Search size={16} style={inputIconStyle} />
              <input
                type="text"
                placeholder="กรอก Serial Number เพื่อค้นหา"
                value={searchSerial}
                onChange={(e) => {
                  setSearchSerial(e.target.value);
                  setSearchResult(null);
                }}
                style={{ ...inputStyle, paddingLeft: "38px" }}
              />
            </div>
            <button type="submit" disabled={isSearching} style={primaryBtnStyle}>
              {isSearching ? "กำลังค้นหา..." : "ตรวจสอบ"}
            </button>
          </form>

          {/* พบถังในระบบ */}
          {searchResult && searchResult.found && (
            <div style={resultBoxStyle("#10b981")}>
              <div style={resultHeaderStyle("#34d399")}>
                <CircleCheck size={18} /> พบถังในระบบ
              </div>
              <div style={infoGridStyle}>
                <Info label="Serial" value={searchResult.data.serial_number} strong />
                <Info label="ยี่ห้อ / ชนิด / ขนาด" value={[searchResult.data.brand, searchResult.data.gas_type, searchResult.data.size].filter(Boolean).join(" · ")} />
                <Info label="นำเข้าครั้งล่าสุด" value={searchResult.data.import_date || "ยังไม่มีข้อมูล"} />
                {searchResult.data.next_inspection_date && (
                  <Info
                    label="ตรวจสภาพครั้งถัดไป"
                    value={`${searchResult.data.next_inspection_date} (อีก ${searchResult.data.days_left ?? calculateDaysLeft(searchResult.data.next_inspection_date)} วัน)`}
                  />
                )}
              </div>

              <div style={resultActionStyle}>
                <div style={{ display: "flex", flexDirection: "column", gap: "6px" }}>
                  <label style={labelStyle}>วันที่รับคืนเข้าคลัง</label>
                  <input type="date" value={importDate} onChange={(e) => setImportDate(e.target.value)} style={{ ...inputStyle, width: "180px" }} />
                </div>
                <button onClick={handleConfirmReturn} style={{ ...primaryBtnStyle, background: "#059669" }}>
                  <Check size={16} /> ยืนยันรับถังคืนเข้าคลัง
                </button>
              </div>
            </div>
          )}

          {/* ไม่พบถัง -> ลงทะเบียนใหม่ */}
          {searchResult && !searchResult.found && (
            <div style={resultBoxStyle("#f59e0b")}>
              <div style={resultHeaderStyle("#fbbf24")}>
                <TriangleAlert size={18} /> ไม่พบ Serial "{searchSerial}" ในระบบ — ลงทะเบียนนำเข้าคลัง
              </div>

              <form onSubmit={handleAddNewCylinder} style={formGridStyle}>
                <div style={fieldStyle}>
                  <label style={labelStyle}>ยี่ห้อ<span style={reqStyle}> *</span></label>
                  <select value={newCylinderData.brand} onChange={(e) => setNewCylinderData({ ...newCylinderData, brand: e.target.value })} style={inputStyle} required>
                    <option value="">เลือกยี่ห้อ</option>
                    {options.brands.map((b, idx) => <option key={idx} value={b}>{b}</option>)}
                  </select>
                </div>

                <div style={fieldStyle}>
                  <label style={labelStyle}>ชนิดแก๊ส<span style={reqStyle}> *</span></label>
                  <select value={newCylinderData.gas_type} onChange={(e) => setNewCylinderData({ ...newCylinderData, gas_type: e.target.value })} style={inputStyle} required>
                    {options.gas_types.map((g, idx) => <option key={idx} value={g}>{g}</option>)}
                  </select>
                </div>

                <div style={fieldStyle}>
                  <label style={labelStyle}>ขนาดถัง<span style={reqStyle}> *</span></label>
                  <select value={newCylinderData.size} onChange={(e) => setNewCylinderData({ ...newCylinderData, size: e.target.value })} style={inputStyle} required>
                    <option value="">เลือกขนาด</option>
                    {options.sizes.map((s, idx) => <option key={idx} value={s}>{s}</option>)}
                  </select>
                </div>

                <div style={fieldStyle}>
                  <label style={labelStyle}>วันที่นำเข้าคลัง</label>
                  <input type="date" value={importDate} onChange={(e) => setImportDate(e.target.value)} style={inputStyle} required />
                </div>

                <div style={{ gridColumn: "1 / -1" }}>
                  <button type="submit" style={primaryBtnStyle}>
                    <PackagePlus size={16} /> ลงทะเบียนนำเข้าคลัง
                  </button>
                </div>
              </form>
            </div>
          )}
        </div>

        {/* รายการรออนุมัติ */}
        <h2 style={{ ...cardTitleStyle, fontSize: "20px" }}>
          รายการรออนุมัติ <span style={countPillStyle}>{pendingList.length}</span>
        </h2>

        {loading ? (
          <div style={emptyBoxStyle}>กำลังโหลดข้อมูล...</div>
        ) : pendingList.length === 0 ? (
          <div style={emptyBoxStyle}>
            <CircleCheck size={28} color="#10b981" />
            <div style={{ marginTop: "8px" }}>ไม่มีรายการที่รออนุมัติ</div>
          </div>
        ) : (
          <div style={approvalGridStyle}>
            {pendingList.map((item) => (
              <div key={item.delivery_id} style={approvalCardStyle}>
                {item.proof_image_path ? (
                  <a href={`/Backend/uploads/${item.proof_image_path}`} target="_blank" rel="noreferrer">
                    <img src={`/Backend/uploads/${item.proof_image_path}`} alt="หลักฐานการจัดส่ง" style={proofImgStyle} />
                  </a>
                ) : (
                  <div style={{ ...proofImgStyle, ...noImageStyle }}>
                    <ImageOff size={24} />
                    <span style={{ fontSize: "13px" }}>ไม่มีรูปหลักฐาน</span>
                  </div>
                )}

                <div style={{ padding: "16px", display: "flex", flexDirection: "column", gap: "8px", flex: 1 }}>
                  <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                    <span style={{ color: "#fbbf24", fontWeight: 700 }}>#{item.delivery_id}</span>
                    <span style={pendingBadgeStyle}>รออนุมัติ</span>
                  </div>
                  <div style={{ fontWeight: 600, fontSize: "16px", display: "flex", alignItems: "center", gap: "6px" }}>
                    <User size={15} color="#9ca3af" /> {item.customer_name}
                  </div>
                  {item.phone && <div style={metaRowStyle}><Phone size={13} /> {item.phone}</div>}
                  <div style={metaRowStyle}><MapPin size={13} /> {item.address || "-"}</div>
                  <div style={metaRowStyle}><Truck size={13} /> {item.staff_name || item.staff_id || "-"}</div>
                  <div style={gasTagRowStyle}>
                    {[item.brand, item.gas_type, item.size].filter(Boolean).map((t) => (
                      <span key={t} style={gasTagStyle}>{t}</span>
                    ))}
                  </div>

                  <button onClick={() => handleApprove(item.delivery_id)} style={{ ...primaryBtnStyle, background: "#059669", marginTop: "auto", justifyContent: "center" }}>
                    <Check size={16} /> อนุมัติงานจัดส่ง
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
}

const Info = ({ label, value, strong }) => (
  <div>
    <div style={{ fontSize: "12px", color: "#9ca3af" }}>{label}</div>
    <div style={{ fontWeight: strong ? 700 : 500, color: "white" }}>{value}</div>
  </div>
);

// Styles
const pageStyle = { maxWidth: "1100px", margin: "0 auto" };
const titleStyle = { margin: 0, fontSize: "30px", color: "white" };
const subtitleStyle = { color: "#9ca3af", fontSize: "14px", marginTop: "6px" };

const cardStyle = { background: "#1f2937", borderRadius: "14px", padding: "20px", marginBottom: "28px", boxShadow: "0 1px 2px rgba(0,0,0,0.3)" };
const cardTitleStyle = { margin: "0 0 16px", fontSize: "18px", color: "white", display: "flex", alignItems: "center", gap: "10px" };
const countPillStyle = { background: "#374151", color: "#e5e7eb", fontSize: "13px", padding: "2px 10px", borderRadius: "999px", fontWeight: "normal" };

const inputStyle = { height: "40px", padding: "0 12px", borderRadius: "8px", border: "1px solid #374151", background: "#111827", color: "white", width: "100%", fontSize: "14px", boxSizing: "border-box" };
const inputIconStyle = { position: "absolute", left: "12px", top: "50%", transform: "translateY(-50%)", color: "#6b7280", pointerEvents: "none" };
const labelStyle = { fontSize: "13px", fontWeight: 600, color: "#cbd5e1" };
const reqStyle = { color: "#f87171" };
const fieldStyle = { display: "flex", flexDirection: "column", gap: "6px" };
const formGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(180px, 1fr))", gap: "12px" };

const primaryBtnStyle = { display: "inline-flex", alignItems: "center", gap: "6px", height: "40px", padding: "0 18px", background: "#2563eb", color: "white", border: "none", borderRadius: "8px", cursor: "pointer", fontWeight: 600, fontSize: "14px", whiteSpace: "nowrap" };

const resultBoxStyle = (accent) => ({ marginTop: "16px", padding: "16px", borderRadius: "12px", background: `${accent}14`, border: `1px solid ${accent}66` });
const resultHeaderStyle = (color) => ({ display: "flex", alignItems: "center", gap: "8px", color, fontWeight: 600, marginBottom: "12px" });
const infoGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(180px, 1fr))", gap: "12px" };
const resultActionStyle = { display: "flex", alignItems: "flex-end", gap: "12px", flexWrap: "wrap", marginTop: "16px", paddingTop: "14px", borderTop: "1px solid rgba(255,255,255,0.1)" };

const emptyBoxStyle = { padding: "36px", background: "#1f2937", borderRadius: "14px", textAlign: "center", color: "#9ca3af", display: "flex", flexDirection: "column", alignItems: "center" };
const approvalGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(min(300px, 100%), 1fr))", gap: "16px" };
const approvalCardStyle = { background: "#1f2937", borderRadius: "14px", overflow: "hidden", display: "flex", flexDirection: "column", border: "1px solid #2b3647" };
const proofImgStyle = { width: "100%", height: "180px", objectFit: "cover", display: "block", background: "#111827" };
const noImageStyle = { display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", gap: "6px", color: "#6b7280" };
const pendingBadgeStyle = { fontSize: "12px", fontWeight: 600, padding: "2px 10px", borderRadius: "999px", color: "#fbbf24", background: "rgba(245,158,11,0.15)", border: "1px solid #f59e0b" };
const metaRowStyle = { display: "flex", alignItems: "center", gap: "6px", color: "#9ca3af", fontSize: "13px" };
const gasTagRowStyle = { display: "flex", gap: "6px", flexWrap: "wrap", margin: "4px 0 8px" };
const gasTagStyle = { fontSize: "12px", padding: "2px 8px", borderRadius: "6px", background: "#111827", border: "1px solid #374151", color: "#cbd5e1" };
