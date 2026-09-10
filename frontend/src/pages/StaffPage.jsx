import { useState, useEffect } from "react";
import Layout from "../components/Layout";

const BASE_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080";
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
      password: staff.password && staff.password.startsWith("$2y$") ? "" : (staff.password || ""),
      address: staff.address || "",
      status: staff.status || "active",
    });
    setEditingStaffId(staff.staff_id);
    setMessage({ type: "", text: "" });
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
      (staff.password && staff.password.toLowerCase().includes(term)) ||
      (staff.address && staff.address.toLowerCase().includes(term));

    const matchesStatus =
      statusFilter === "all" || staff.status === statusFilter;

    return matchesSearch && matchesStatus;
  });

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
      <h1 style={{ marginBottom: "20px", color: "white" }}>จัดการพนักงานส่ง</h1>

      {message.text && (
        <div
          style={{
            padding: "10px 15px",
            borderRadius: "8px",
            marginBottom: "15px",
            backgroundColor: message.type === "success" ? "#22c55e" : "#ef4444",
            color: "white",
            fontWeight: "bold",
          }}
        >
          {message.text}
        </div>
      )}

      {/* Form Card */}
      <form onSubmit={saveStaff} style={formCardStyle}>
        <h2 style={{ marginTop: 0, color: "white" }}>
          {editingStaffId ? "แก้ไขพนักงาน" : "เพิ่มพนักงานใหม่"}
        </h2>
        
        <div style={formGridStyle}>
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ชื่อพนักงาน *</label>
            <input
              value={formData.staff_name}
              onChange={(e) => setFormData({ ...formData, staff_name: e.target.value })}
              style={inputStyle}
              placeholder="ชื่อ-นามสกุล"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>เบอร์โทร *</label>
            <input
              value={formData.staff_phone}
              onChange={(e) => setFormData({ ...formData, staff_phone: e.target.value })}
              style={inputStyle}
              placeholder="0812345678"
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>Username *</label>
            <input
              value={formData.username}
              onChange={(e) => setFormData({ ...formData, username: e.target.value })}
              style={inputStyle}
              placeholder="username"
              disabled={!!editingStaffId}
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>
              {editingStaffId ? "รหัสผ่าน (เว้นว่างไว้ไม่เปลี่ยน)" : "รหัสผ่าน *"}
            </label>
            <input
              type="text"
              value={formData.password}
              onChange={(e) => setFormData({ ...formData, password: e.target.value })}
              style={inputStyle}
              placeholder={editingStaffId ? "รหัสผ่านใหม่ (อย่างน้อย 4 ตัว)" : "รหัสผ่าน (อย่างน้อย 4 ตัว)"}
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
            <label style={labelStyle}>สถานะ</label>
            <select
              value={formData.status}
              onChange={(e) => setFormData({ ...formData, status: e.target.value })}
              style={inputStyle}
            >
              <option value="active">ใช้งาน</option>
              <option value="inactive">ไม่ใช้งาน</option>
            </select>
          </div>
        </div>

        <div style={{ display: "flex", gap: "10px", marginTop: "10px" }}>
          <button type="submit" style={primaryButtonStyle} disabled={isSubmitting}>
            {isSubmitting ? "กำลังบันทึก..." : editingStaffId ? "บันทึก" : "เพิ่ม"}
          </button>
          {editingStaffId && (
            <button type="button" onClick={clearForm} style={secondaryButtonStyle}>
              ยกเลิก
            </button>
          )}
        </div>
      </form>

      {/* Filter Container */}
      <div style={filterContainerStyle}>
        <div style={{ flex: 1, minWidth: "220px" }}>
          <input
            type="text"
            placeholder="ค้นหา (ชื่อ, เบอร์โทร, Username, Password, ที่อยู่)..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            style={inputStyle}
          />
        </div>
        <div style={{ width: "160px" }}>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            style={inputStyle}
          >
            <option value="all">สถานะทั้งหมด</option>
            <option value="active">ใช้งาน</option>
            <option value="inactive">ไม่ใช้งาน</option>
          </select>
        </div>
      </div>

      {/* Table */}
      <div style={{ overflowX: "auto" }}>
        <table style={tableStyle}>
          <thead>
            <tr>
              <th style={{ ...thStyle, width: "50px" }}>ID</th>
              <th style={{ ...thStyle, width: "120px" }}>ชื่อ</th>
              <th style={{ ...thStyle, width: "110px" }}>เบอร์โทร</th>
              <th style={{ ...thStyle, width: "100px" }}>Username</th>
              <th style={{ ...thStyle, width: "100px" }}>Password</th>
              <th style={{ ...thStyle, width: "180px" }}>ที่อยู่</th>
              <th style={{ ...thStyle, width: "100px" }}>สถานะงาน</th>
              <th style={{ ...thStyle, width: "90px" }}>จำนวนงาน</th>
              <th style={{ ...thStyle, width: "140px" }}>จัดการ</th>
            </tr>
          </thead>
        
          <tbody>
            {filteredStaffs.length > 0 ? (
              filteredStaffs.map((item, index) => {
                const pendingCount = Number(item.pending_jobs || 0);
                const isWorking = pendingCount > 0;

                return (
                  <tr key={`${item.staff_id || 'staff'}-${index}`}>
                    <td style={tdStyle}>{item.staff_id}</td>
                    <td style={tdStyle}>
                      <strong>{removeEmojis(item.staff_name)}</strong>
                    </td>
                    <td style={tdStyle}>{removeEmojis(item.staff_phone) || "-"}</td>
                    <td style={tdStyle}>{removeEmojis(item.username)}</td>
                    <td style={tdStyle}>{removeEmojis(item.password) || "-"}</td>
                    <td style={tdStyle}>{removeEmojis(item.address) || "-"}</td>
                    
                    {/* แสดงสถานะงาน: กำลังส่ง / ว่าง */}
                    <td style={tdStyle}>
                      <span
                        style={{
                          ...badgeStyle,
                          backgroundColor: isWorking ? "#f59e0b" : "#10b981",
                          color: "white",
                        }}
                      >
                        {isWorking ? "กำลังส่ง" : "ว่าง"}
                      </span>
                    </td>

                    {/* แสดงจำนวนงานที่รอส่ง */}
                    <td style={tdStyle}>
                      <span style={{ fontWeight: "bold", color: isWorking ? "#f59e0b" : "#9ca3af" }}>
                        {pendingCount} งาน
                      </span>
                    </td>

                    <td style={{ ...tdStyle, whiteSpace: "nowrap" }}>
                      <button type="button" onClick={() => editStaff(item)} style={editButtonStyle}>
                        แก้ไข
                      </button>
                      <button
                        type="button"
                        onClick={() => deleteStaff(item.staff_id, item.staff_name)}
                        style={deleteButtonStyle}
                      >
                        ลบ
                      </button>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td style={{ ...tdStyle, textAlign: "center" }} colSpan="9">
                  ไม่พบข้อมูลพนักงานที่ค้นหา
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </Layout>
  );
}

// ========== Styles ==========
const formCardStyle = {
  background: "#1f2937",
  color: "white",
  padding: "20px",
  borderRadius: "12px",
  marginBottom: "20px",
};

const formGridStyle = {
  display: "grid",
  gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))",
  gap: "12px",
  marginBottom: "16px",
};

const fieldGroupStyle = {
  display: "flex",
  flexDirection: "column",
  gap: "6px",
};

const labelStyle = {
  fontSize: "13px",
  fontWeight: "bold",
  color: "#e5e7eb",
};

const inputStyle = {
  padding: "10px",
  borderRadius: "8px",
  border: "1px solid #4b5563",
  background: "#111827",
  color: "white",
  width: "100%",
  boxSizing: "border-box",
};

const filterContainerStyle = {
  display: "flex",
  gap: "12px",
  marginBottom: "16px",
  flexWrap: "wrap",
};

const tableStyle = {
  width: "100%",
  borderCollapse: "collapse",
  background: "#1f2937",
  color: "white",
  borderRadius: "12px",
  overflow: "hidden",
  tableLayout: "fixed", 
};

const thStyle = {
  padding: "12px 10px",
  textAlign: "left",
  borderBottom: "1px solid #374151",
  fontSize: "13px",
  fontWeight: "bold",
  whiteSpace: "nowrap",
  backgroundColor: "#2d3a4a",
};

const tdStyle = {
  padding: "10px",
  textAlign: "left",
  borderBottom: "1px solid #374151",
  fontSize: "13px",
  wordBreak: "break-word",    
  whiteSpace: "normal",       
  verticalAlign: "middle",   
};

const primaryButtonStyle = {
  padding: "10px 14px",
  border: "none",
  borderRadius: "8px",
  background: "#2563eb",
  color: "white",
  cursor: "pointer",
  fontWeight: "bold",
};

const secondaryButtonStyle = {
  padding: "10px 14px",
  border: "none",
  borderRadius: "8px",
  background: "#6b7280",
  color: "white",
  cursor: "pointer",
};

const editButtonStyle = {
  marginRight: "8px",
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
  background: "#dc2626",
  color: "white",
  cursor: "pointer",
};

const badgeStyle = {
  padding: "4px 10px",
  borderRadius: "999px",
  fontSize: "12px",
  fontWeight: "bold",
  whiteSpace: "nowrap",
  display: "inline-block",
  textAlign: "center",
};

export default StaffPage;