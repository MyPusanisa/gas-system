import { useState, useEffect, useRef } from "react";
import { Pencil, Trash2, Plus, Save, UserPlus, Phone, MapPin, CircleCheck, TriangleAlert } from "lucide-react";
import Layout from "../components/Layout";

// ใช้ path แบบ relative เพื่อให้เรียกผ่าน http/https เดียวกับหน้าเว็บ (กัน mixed content)
const BASE_URL = import.meta.env.VITE_API_BASE_URL || "";
const API_BASE = `${BASE_URL}/Backend/models/staff`;

// ฟังก์ชันสำหรับลบอิโมจิและสัญลักษณ์พิเศษออกจากข้อความ
const removeEmojis = (text) => {
  if (!text) return "";
  return String(text)
    .replace(
      /([\u2700-\u27BF]|[\uE000-\uF8FF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|[\u2011-\u26FF]|\uD83E[\uDD10-\uDDFF])/g,
      ""
    )
    .trim();
};

function StaffPage() {
  const [staffs, setStaffs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [editingStaffId, setEditingStaffId] = useState(null);
  const [message, setMessage] = useState({ type: "", text: "" });
  const formRef = useRef(null);

  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");

  const [formData, setFormData] = useState({
    staff_name: "",
    staff_phone: "",
    username: "",
    password: "",
    address: "",
    status: "active",
  });

  const [selectedStaff, setSelectedStaff] = useState(1);
  const [period, setPeriod] = useState("day");
  const [history, setHistory] = useState([]);
  const [summary, setSummary] = useState(0);

  useEffect(() => {
    const fetchHistory = async () => {
      try {
        const res = await fetch(
          `${BASE_URL}/Backend/models/history.php?staff_id=${selectedStaff}&period=${period}`
        );
        if (!res.ok) throw new Error("Network response was not ok");
        
        const data = await res.json();
        if (data.success) {
          setHistory(data.data);
          setSummary(data.total_completed);
        }
      } catch (err) {
        console.error("Fetch history error:", err);
      }
    };

    if (selectedStaff) {
      fetchHistory();
    }
  }, [selectedStaff, period]);

  useEffect(() => {
    if (message.text) {
      const timer = setTimeout(() => setMessage({ type: "", text: "" }), 5000);
      return () => clearTimeout(timer);
    }
  }, [message]);

  const validatePhone = (phone) => {
    const phoneRegex = /^[0-9]{9,10}$/;
    return phoneRegex.test(phone.replace(/[-\s]/g, ""));
  };

  const fetchStaffs = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/list.php`);
      
      // อ่านค่าตอบกลับเป็น Text ก่อนเพื่อป้องกัน Crash
      const text = await res.text();
      let data;
      
      try {
        data = JSON.parse(text);
      } catch (jsonErr) {
        console.error("PHP Error Output:", text);
        setMessage({ 
          type: "error", 
          text: "Server ตอบกลับไม่ถูกต้อง (มี PHP Error ดูรายละเอียดใน Console)" 
        });
        return;
      }

      if (data.success) {
        setStaffs(data.data);
      } else {
        setMessage({ type: "error", text: data.message || "โหลดข้อมูลไม่สำเร็จ" });
      }
    } catch (err) {
      console.error(err);
      setMessage({ type: "error", text: "เกิดข้อผิดพลาดในการเชื่อมต่อ" });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchStaffs();
  }, []);

  const validateForm = () => {
    if (!formData.staff_name.trim()) {
      setMessage({ type: "error", text: "กรุณากรอกชื่อพนักงาน" });
      return false;
    }
    if (!formData.staff_phone.trim()) {
      setMessage({ type: "error", text: "กรุณากรอกเบอร์โทร" });
      return false;
    }
    if (!validatePhone(formData.staff_phone)) {
      setMessage({ type: "error", text: "กรุณากรอกเบอร์โทรให้ถูกต้อง (ตัวเลข 9-10 หลัก)" });
      return false;
    }
    if (!formData.username.trim()) {
      setMessage({ type: "error", text: "กรุณากรอก Username" });
      return false;
    }
    if (!editingStaffId && formData.password.trim().length < 4) {
      setMessage({ type: "error", text: "รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร" });
      return false;
    }
    if (editingStaffId && formData.password.trim() && formData.password.trim().length < 4) {
      setMessage({ type: "error", text: "รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร" });
      return false;
    }
    return true;
  };

  const clearForm = () => {
    setFormData({
      staff_name: "",
      staff_phone: "",
      username: "",
      password: "",
      address: "",
      status: "active",
    });
    setEditingStaffId(null);
    setMessage({ type: "", text: "" });
  };

  const saveStaff = async (e) => {
    if (e) e.preventDefault();
    if (!validateForm()) return;

    setIsSubmitting(true);
    const url = editingStaffId
      ? `${API_BASE}/update_staff.php`
      : `${API_BASE}/create_staff.php`;

    const payload = {
      staff_name: formData.staff_name.trim(),
      staff_phone: formData.staff_phone.trim().replace(/[-\s]/g, ""),
      username: formData.username.trim(),
      address: formData.address.trim(),
      status: formData.status,
    };

    if (editingStaffId) {
      payload.staff_id = editingStaffId;
    }

    if (formData.password.trim() !== "") {
      payload.password = formData.password.trim();
    }

    try {
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("JSON Parse Error:", text);
        setMessage({ type: "error", text: "Server error: " + text.substring(0, 100) });
        return;
      }

      if (data.success) {
        setMessage({
          type: "success",
          text: editingStaffId ? "แก้ไขข้อมูลพนักงานสำเร็จ" : "เพิ่มพนักงานสำเร็จ",
        });
        await fetchStaffs();
        clearForm();
      } else {
        setMessage({ type: "error", text: data.message || "บันทึกไม่สำเร็จ" });
      }
    } catch (err) {
      console.error(err);
      setMessage({ type: "error", text: "เกิดข้อผิดพลาดในการเชื่อมต่อ: " + err.message });
    } finally {
      setIsSubmitting(false);
    }
  };

  const editStaff = (staff) => {
    setFormData({
      staff_name: staff.staff_name || "",
      staff_phone: staff.staff_phone || "",
      username: staff.username || "",
      password: "",
      address: staff.address || "",
      status: staff.status || "active",
    });
    setEditingStaffId(staff.staff_id);
    setMessage({ type: "", text: "" });
    formRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  const deleteStaff = async (staffId, staffName) => {
    if (!window.confirm(`ยืนยันลบพนักงาน "${staffName}"?`)) return;

    try {
      const res = await fetch(`${API_BASE}/delete_staff.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ staff_id: staffId }),
      });
      const data = await res.json();

      if (data.success) {
        setMessage({ type: "success", text: "ลบพนักงานสำเร็จ" });
        await fetchStaffs();
        if (editingStaffId === staffId) clearForm();
      } else {
        setMessage({ type: "error", text: data.message || "ลบไม่สำเร็จ" });
      }
    } catch (err) {
      console.error(err);
      setMessage({ type: "error", text: "เกิดข้อผิดพลาดในการเชื่อมต่อ" });
    }
  };

  const filteredStaffs = staffs.filter((staff) => {
    const term = searchTerm.toLowerCase().trim();
    const matchesSearch =
      (staff.staff_name && staff.staff_name.toLowerCase().includes(term)) ||
      (staff.staff_phone && staff.staff_phone.includes(term)) ||
      (staff.username && staff.username.toLowerCase().includes(term)) ||
      (staff.address && staff.address.toLowerCase().includes(term));

    const matchesStatus =
      statusFilter === "all" || staff.status === statusFilter;

    return matchesSearch && matchesStatus;
  });

  const busyCount = staffs.filter((s) => Number(s.pending_jobs || 0) > 0).length;

  if (loading) {
    return (
      <Layout>
        <div style={{ color: "#9ca3af", textAlign: "center", padding: "50px" }}>กำลังโหลดข้อมูล...</div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div style={headerRowStyle}>
        <h1 style={titleStyle}>จัดการพนักงานส่ง</h1>
        <div style={subtitleStyle}>
          พนักงานทั้งหมด {staffs.length} คน · กำลังส่ง {busyCount} · ว่าง {staffs.length - busyCount}
        </div>
      </div>

      {/* Form Card */}
      <form ref={formRef} onSubmit={saveStaff} style={{ ...cardStyle, ...(editingStaffId ? editingCardStyle : {}) }}>
        <h2 style={cardTitleStyle}>
          {editingStaffId
            ? <><Pencil size={18} color="#fbbf24" /> แก้ไขพนักงาน <span style={{ color: "#fbbf24" }}>{formData.username}</span></>
            : <><UserPlus size={18} color="#60a5fa" /> เพิ่มพนักงานใหม่</>}
        </h2>

        <div style={formGridStyle}>
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ชื่อพนักงาน<span style={reqStyle}> *</span></label>
            <input
              value={formData.staff_name}
              onChange={(e) => setFormData({ ...formData, staff_name: e.target.value })}
              style={inputStyle}
              placeholder="ชื่อ-นามสกุล"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>เบอร์โทร<span style={reqStyle}> *</span></label>
            <input
              value={formData.staff_phone}
              onChange={(e) => setFormData({ ...formData, staff_phone: e.target.value })}
              style={inputStyle}
              placeholder="0812345678"
              inputMode="tel"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>Username<span style={reqStyle}> *</span></label>
            <input
              value={formData.username}
              onChange={(e) => setFormData({ ...formData, username: e.target.value })}
              style={{ ...inputStyle, ...(editingStaffId ? readOnlyStyle : {}) }}
              placeholder="username"
              disabled={!!editingStaffId}
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>
              รหัสผ่าน{editingStaffId ? <span style={hintStyle}> (เว้นว่าง = ไม่เปลี่ยน)</span> : <span style={reqStyle}> *</span>}
            </label>
            <input
              type="text"
              value={formData.password}
              onChange={(e) => setFormData({ ...formData, password: e.target.value })}
              style={inputStyle}
              placeholder="อย่างน้อย 4 ตัวอักษร"
              autoComplete="new-password"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ที่อยู่</label>
            <input
              value={formData.address}
              onChange={(e) => setFormData({ ...formData, address: e.target.value })}
              style={inputStyle}
              placeholder="ที่อยู่"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>สถานะบัญชี</label>
            <select
              value={formData.status}
              onChange={(e) => setFormData({ ...formData, status: e.target.value })}
              style={inputStyle}
            >
              <option value="active">ใช้งาน</option>
              <option value="inactive">ปิดใช้งาน</option>
            </select>
          </div>
        </div>

        <div style={formActionsStyle}>
          <button type="submit" style={primaryButtonStyle} disabled={isSubmitting}>
            {isSubmitting ? "กำลังบันทึก..." : editingStaffId ? <><Save size={16} /> บันทึกการแก้ไข</> : <><Plus size={16} /> เพิ่มพนักงาน</>}
          </button>
          {editingStaffId && (
            <button type="button" onClick={clearForm} style={secondaryButtonStyle}>ยกเลิก</button>
          )}
        </div>
      </form>

      {/* ตารางพนักงาน */}
      <div style={cardStyle}>
        <div style={tableHeaderStyle}>
          <h2 style={{ ...cardTitleStyle, margin: 0 }}>
            รายชื่อพนักงาน <span style={countPillStyle}>{filteredStaffs.length}</span>
          </h2>
          <div style={filterContainerStyle}>
            <input
              type="text"
              placeholder="ค้นหาชื่อ, เบอร์โทร, Username, ที่อยู่..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              style={{ ...inputStyle, width: "260px", maxWidth: "100%" }}
            />
            <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} style={{ ...inputStyle, width: "140px" }}>
              <option value="all">ทุกสถานะ</option>
              <option value="active">ใช้งาน</option>
              <option value="inactive">ปิดใช้งาน</option>
            </select>
          </div>
        </div>

        <div style={{ overflowX: "auto" }}>
          <table style={tableStyle}>
            <thead>
              <tr>
                <th style={thStyle}>พนักงาน</th>
                <th style={thStyle}>เบอร์โทร</th>
                <th style={thStyle}>ที่อยู่</th>
                <th style={thStyle}>สถานะงาน</th>
                <th style={{ ...thStyle, textAlign: "right" }}>งานค้าง</th>
                <th style={{ ...thStyle, textAlign: "right" }}>จัดการ</th>
              </tr>
            </thead>
            <tbody>
              {filteredStaffs.length > 0 ? (
                filteredStaffs.map((item, index) => {
                  const pendingCount = Number(item.pending_jobs || 0);
                  const isWorking = pendingCount > 0;
                  const name = removeEmojis(item.staff_name);
                  const inactive = item.status === "inactive";
                  return (
                    <tr key={`${item.staff_id || "staff"}-${index}`} style={editingStaffId === item.staff_id ? { background: "rgba(245,158,11,0.08)" } : undefined}>
                      <td style={tdStyle}>
                        <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                          <span style={{ ...avatarStyle, opacity: inactive ? 0.4 : 1 }}>{name.charAt(0) || "?"}</span>
                          <div>
                            <div style={{ fontWeight: 600 }}>
                              {name}
                              {inactive && <span style={inactiveTagStyle}>ปิดใช้งาน</span>}
                            </div>
                            <div style={{ fontSize: "12px", color: "#9ca3af" }}>@{removeEmojis(item.username)}</div>
                          </div>
                        </div>
                      </td>
                      <td style={tdMutedStyle}>
                        <span style={iconTextStyle}><Phone size={13} /> {removeEmojis(item.staff_phone) || "-"}</span>
                      </td>
                      <td style={{ ...tdMutedStyle, whiteSpace: "normal", minWidth: "160px" }}>
                        {item.address ? <span style={iconTextStyle}><MapPin size={13} /> {removeEmojis(item.address)}</span> : "-"}
                      </td>
                      <td style={tdStyle}>
                        <span style={{ ...badgeStyle, ...(isWorking ? badgeAmber : badgeGreen) }}>
                          {isWorking ? "กำลังส่ง" : "ว่าง"}
                        </span>
                      </td>
                      <td style={{ ...tdStyle, textAlign: "right", fontWeight: 600, color: isWorking ? "#fbbf24" : "#6b7280" }}>
                        {pendingCount} งาน
                      </td>
                      <td style={{ ...tdStyle, textAlign: "right" }}>
                        <div style={{ display: "inline-flex", gap: "6px" }}>
                          <button type="button" onClick={() => editStaff(item)} style={iconBtnStyle("#f59e0b")} title="แก้ไข">
                            <Pencil size={14} />
                          </button>
                          <button type="button" onClick={() => deleteStaff(item.staff_id, item.staff_name)} style={iconBtnStyle("#ef4444")} title="ลบ">
                            <Trash2 size={14} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              ) : (
                <tr>
                  <td style={emptyCellStyle} colSpan="6">ไม่พบข้อมูลพนักงานที่ค้นหา</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* แจ้งผล */}
      {message.text && (
        <div style={{ ...toastStyle, borderColor: message.type === "success" ? "#10b981" : "#ef4444" }}>
          {message.type === "success" ? <CircleCheck size={18} color="#10b981" /> : <TriangleAlert size={18} color="#f87171" />}
          <span>{message.text}</span>
        </div>
      )}
    </Layout>
  );
}

// ========== Styles ==========
const headerRowStyle = { marginBottom: "20px" };
const titleStyle = { margin: 0, fontSize: "30px", color: "white" };
const subtitleStyle = { color: "#9ca3af", fontSize: "14px", marginTop: "6px" };

const cardStyle = { background: "#1f2937", color: "white", padding: "20px", borderRadius: "14px", marginBottom: "20px", boxShadow: "0 1px 2px rgba(0,0,0,0.3)" };
const editingCardStyle = { boxShadow: "0 0 0 2px #f59e0b" };
const cardTitleStyle = { margin: "0 0 16px", fontSize: "18px", color: "white", display: "flex", alignItems: "center", gap: "10px" };
const countPillStyle = { background: "#374151", color: "#e5e7eb", fontSize: "13px", padding: "2px 10px", borderRadius: "999px", fontWeight: "normal" };

const formGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))", gap: "14px 16px" };
const fieldGroupStyle = { display: "flex", flexDirection: "column", gap: "6px" };
const labelStyle = { fontSize: "13px", fontWeight: 600, color: "#cbd5e1" };
const reqStyle = { color: "#f87171" };
const hintStyle = { color: "#6b7280", fontWeight: "normal" };
const inputStyle = { height: "40px", padding: "0 12px", borderRadius: "8px", border: "1px solid #374151", background: "#111827", color: "white", width: "100%", boxSizing: "border-box", fontSize: "14px" };
const readOnlyStyle = { background: "#1a2230", color: "#9ca3af", cursor: "not-allowed" };
const formActionsStyle = { display: "flex", gap: "10px", marginTop: "18px" };

const tableHeaderStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", gap: "12px", flexWrap: "wrap", marginBottom: "16px" };
const filterContainerStyle = { display: "flex", gap: "10px", flexWrap: "wrap" };

const tableStyle = { width: "100%", borderCollapse: "collapse", color: "white", fontSize: "14px" };
const thStyle = { padding: "10px 12px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px", fontWeight: 600, whiteSpace: "nowrap", background: "#273244", color: "#cbd5e1" };
const tdStyle = { padding: "12px", textAlign: "left", borderBottom: "1px solid #2b3647", verticalAlign: "middle", whiteSpace: "nowrap" };
const tdMutedStyle = { ...tdStyle, color: "#9ca3af", fontSize: "13px" };
const emptyCellStyle = { padding: "28px", textAlign: "center", color: "#9ca3af" };
const iconTextStyle = { display: "inline-flex", alignItems: "center", gap: "6px" };

const avatarStyle = { width: "34px", height: "34px", borderRadius: "50%", background: "#2563eb", color: "white", display: "flex", alignItems: "center", justifyContent: "center", fontWeight: 600, flexShrink: 0 };
const inactiveTagStyle = { marginLeft: "8px", fontSize: "11px", padding: "1px 8px", borderRadius: "999px", background: "#374151", color: "#9ca3af", fontWeight: "normal" };

const primaryButtonStyle = { display: "inline-flex", alignItems: "center", gap: "6px", height: "40px", padding: "0 18px", border: "none", borderRadius: "8px", background: "#2563eb", color: "white", cursor: "pointer", fontWeight: 600, fontSize: "14px" };
const secondaryButtonStyle = { height: "40px", padding: "0 18px", border: "1px solid #4b5563", borderRadius: "8px", background: "transparent", color: "#e5e7eb", cursor: "pointer", fontSize: "14px" };
const iconBtnStyle = (color) => ({ display: "inline-flex", alignItems: "center", padding: "6px 9px", border: `1px solid ${color}`, borderRadius: "6px", background: `${color}1f`, color: "white", cursor: "pointer" });

const badgeStyle = { padding: "3px 10px", borderRadius: "999px", fontSize: "12px", fontWeight: 600, whiteSpace: "nowrap", display: "inline-block" };
const badgeAmber = { background: "rgba(245,158,11,0.15)", color: "#fbbf24", border: "1px solid #f59e0b" };
const badgeGreen = { background: "rgba(16,185,129,0.15)", color: "#34d399", border: "1px solid #10b981" };

const toastStyle = { position: "fixed", right: "24px", bottom: "24px", zIndex: 1100, display: "flex", gap: "10px", alignItems: "center", padding: "12px 16px", background: "#111827", color: "white", border: "1px solid", borderRadius: "10px", boxShadow: "0 8px 24px rgba(0,0,0,0.4)", fontSize: "14px" };

export default StaffPage;
