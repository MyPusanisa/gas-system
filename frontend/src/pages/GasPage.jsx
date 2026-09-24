import { useState, useEffect, useRef } from "react";
import Layout from "../components/Layout";
import { QRCodeSVG } from "qrcode.react";

// path แบบ absolute — แบบ relative ("../Backend") พังเมื่อ URL มี / ต่อท้าย เช่น /gas/
const API_BASE = "/Backend/models";

// สถานะถัง (ค่าที่บันทึกลงฐานข้อมูล)
const STATUS_OPTIONS = ["ในคลัง", "ปกติ", "ชำรุด", "กำลังจัดส่ง", "จัดส่งสำเร็จ"];

const STATUS_COLORS = {
  "ในคลัง": "#3b82f6",
  "ปกติ": "#10b981",
  "ชำรุด": "#ef4444",
  "กำลังจัดส่ง": "#f59e0b",
  "จัดส่งสำเร็จ": "#8b5cf6",
  pending: "#f59e0b",
  success: "#8b5cf6",
};

// "2026-08-01" -> "01/08/2026"
const formatDate = (dateStr) => {
  if (!dateStr || typeof dateStr !== "string") return "-";
  const [y, m, d] = dateStr.slice(0, 10).split("-");
  return y && m && d ? `${d}/${m}/${y}` : dateStr;
};

const isPastDate = (dateStr) => {
  if (!dateStr) return false;
  const d = new Date(dateStr.slice(0, 10));
  if (isNaN(d.getTime())) return false;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return d < today;
};

// จัดส่งเกิน 3 ปี (365 * 3 วัน) ถือว่าสูญหาย
const isOver3Years = (dateString) => {
  if (!dateString) return false;
  const deliveryDate = new Date(dateString);
  if (isNaN(deliveryDate.getTime())) return false;
  const diffDays = (new Date() - deliveryDate) / (1000 * 60 * 60 * 24);
  return diffDays > 365 * 3;
};

const hasValue = (v) => v !== null && v !== undefined && String(v).trim() !== "" && String(v) !== "null" && String(v) !== "undefined";

function GasPage() {
  const [cylinders, setCylinders] = useState([]);
  const [deliveries, setDeliveries] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [editingSerial, setEditingSerial] = useState(null);
  const [toast, setToast] = useState(null);
  const formRef = useRef(null);

  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");

  // ตัวเลือกดร็อปดาวน์
  const [brandOptions, setBrandOptions] = useState([]);
  const [gasTypeOptions, setGasTypeOptions] = useState([]);
  const [sizeOptions, setSizeOptions] = useState([]);
  const [locationOptions, setLocationOptions] = useState([]);

  const [manageModal, setManageModal] = useState({ open: false, type: "", title: "" });
  const [newItemText, setNewItemText] = useState("");
  const [qrModal, setQrModal] = useState({ open: false, cylinder: null });
  const [imageModal, setImageModal] = useState({ open: false, imgSrc: "", rawPath: "", serial: "" });

  const initialFormState = {
    serial_number: "",
    brand: "",
    gas_type: "",
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

  const showToast = (type, text) => {
    setToast({ type, text });
    setTimeout(() => setToast(null), 3500);
  };

  const getQrUrl = (item) => {
    const serial = item && (item.serial_number || item.serial);
    if (!hasValue(serial)) return "";
    return `${window.location.origin}/cylinder/${encodeURIComponent(String(serial).trim())}`;
  };

  // 1. ดึงตัวเลือกยี่ห้อ ชนิด ขนาด สถานที่
  const fetchOptions = async () => {
    try {
      const res = await fetch(`${API_BASE}/Gas/get_options.php`);
      const result = await res.json();
      if (result.success) {
        setBrandOptions(result.brands || []);
        setGasTypeOptions(result.types || []);
        setSizeOptions(result.sizes || []);
        setLocationOptions(result.locations || []);
      }
    } catch (err) {
      console.error("Error fetching options:", err);
    }
  };

  // 2. ดึงรายการถังแก๊สทั้งหมด
  const fetchCylinders = async () => {
    try {
      const res = await fetch(`${API_BASE}/get_cylinders.php`);
      const data = await res.json();
      if (data.success) setCylinders(data.data || []);
    } catch (err) {
      console.error("Error fetching cylinders:", err);
    }
  };

  // 3. ดึงรายการจัดส่งทั้งหมด
  const fetchDeliveries = async () => {
    try {
      const res = await fetch(`${API_BASE}/get_delivery_success.php`);
      const data = await res.json();
      if (Array.isArray(data)) setDeliveries(data);
      else if (data.success && Array.isArray(data.data)) setDeliveries(data.data);
    } catch (err) {
      console.error("Error fetching deliveries:", err);
    }
  };

  const loadAllData = async () => {
    setLoading(true);
    await Promise.all([fetchOptions(), fetchCylinders(), fetchDeliveries()]);
    setLoading(false);
  };

  useEffect(() => {
    loadAllData();
  }, []);

  // วันหมดอายุ = ผลิต + 10 ปี, ตรวจครั้งถัดไป = (ตรวจล่าสุด หรือ ผลิต) + 5 ปี
  const calculateDates = (mfgDate, lastCheckDate) => {
    let expiry = "";
    let nextCheck = "";
    if (mfgDate) {
      const d = new Date(mfgDate);
      d.setFullYear(d.getFullYear() + 10);
      expiry = d.toISOString().split("T")[0];
    }
    const baseDate = lastCheckDate || mfgDate;
    if (baseDate) {
      const d = new Date(baseDate);
      d.setFullYear(d.getFullYear() + 5);
      nextCheck = d.toISOString().split("T")[0];
    }
    return { expiry, nextCheck };
  };

  const handleManufactureDateChange = (e) => {
    const mfgDate = e.target.value;
    const { expiry, nextCheck } = calculateDates(mfgDate, formData.last_check_date);
    setFormData((prev) => ({ ...prev, manufacture_date: mfgDate, expiry_date: expiry, next_check_date: nextCheck }));
  };

  const handleLastCheckDateChange = (e) => {
    const lastCheck = e.target.value;
    const { nextCheck } = calculateDates(formData.manufacture_date, lastCheck);
    setFormData((prev) => ({ ...prev, last_check_date: lastCheck, next_check_date: nextCheck }));
  };

  const clearForm = () => {
    setEditingSerial(null);
    setFormData(initialFormState);
  };

  const editCylinder = (item) => {
    setEditingSerial(item.serial_number || item.serial);
    setFormData({
      serial_number: item.serial_number || item.serial || "",
      brand: item.brand || "",
      gas_type: item.gas_type || "",
      size: item.size || "",
      manufacture_date: item.manufacture_date || "",
      expiry_date: item.expiry_date || item.expire_date || "",
      last_check_date: item.last_check_date || "",
      next_check_date: item.next_check_date || item.next_inspection_date || "",
      delivered_date: item.delivered_date || "",
      current_location: item.current_location || "",
      status: item.status || "ในคลัง",
    });
    // ฟอร์มอยู่ด้านบน เลื่อนขึ้นไปให้เห็น
    formRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  // ---------- กรองรายการถัง ----------
  const term = searchTerm.toLowerCase().trim();

  const filteredCylinders = cylinders.filter((item) => {
    const status = (item.status || "").trim();
    if (statusFilter !== "all" && status !== statusFilter) return false;
    if (!term) return true;
    return [
      item.serial_number, item.brand, item.gas_type, item.size,
      item.manufacture_date, item.expiry_date, item.next_check_date,
      item.current_location, status,
    ].some((v) => String(v || "").toLowerCase().includes(term));
  });

  const getDeliveryStatus = (item) => {
    if (isOver3Years(item.created_at || item.delivered_date)) return { text: "สูญหาย", color: "#ef4444" };
    const raw = (item.status || "").trim().toLowerCase();
    if (raw === "success") return { text: "จัดส่งสำเร็จ", color: "#10b981" };
    if (raw === "pending") return { text: "กำลังจัดส่ง", color: "#f59e0b" };
    return { text: item.status || "-", color: "#6b7280" };
  };

  const filteredDeliveries = deliveries
    .filter((item) => {
      if (!term) return true;
      return [
        item.delivery_id, item.serial_number, item.customer_name, item.address,
        item.brand, item.gas_type, item.size, item.status, getDeliveryStatus(item).text,
        item.created_at,
      ].some((v) => String(v || "").toLowerCase().includes(term));
    })
    .sort((a, b) => {
      const aLost = isOver3Years(a.created_at || a.delivered_date);
      const bLost = isOver3Years(b.created_at || b.delivered_date);
      if (aLost !== bLost) return aLost ? 1 : -1;
      return 0;
    });

  const statusCounts = cylinders.reduce((acc, c) => {
    const s = (c.status || "").trim() || "-";
    acc[s] = (acc[s] || 0) + 1;
    return acc;
  }, {});

  // ---------- บันทึก / ลบ ----------
  const handleSave = async (e) => {
    if (e) e.preventDefault();

    if (!formData.serial_number.trim()) return showToast("error", "กรุณากรอก Serial Number");
    if (!formData.brand) return showToast("error", "กรุณาเลือก ยี่ห้อ");
    if (!formData.gas_type) return showToast("error", "กรุณาเลือก ชนิดแก๊ส");
    if (!formData.size) return showToast("error", "กรุณาเลือก ขนาดถัง");
    if (!formData.manufacture_date) return showToast("error", "กรุณาเลือก วันที่ผลิต");

    setIsSubmitting(true);
    const payload = { ...formData, serial_number: formData.serial_number.trim() };
    const url = editingSerial ? `${API_BASE}/update_cylinder.php` : `${API_BASE}/create_cylinder.php`;

    try {
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (data.success) {
        showToast("success", editingSerial ? `แก้ไข ${payload.serial_number} สำเร็จ` : `เพิ่ม ${payload.serial_number} สำเร็จ`);
        clearForm();
        await fetchCylinders();
      } else {
        showToast("error", data.message || "เกิดข้อผิดพลาดในการบันทึก");
      }
    } catch (err) {
      console.error(err);
      showToast("error", "เชื่อมต่อเซิร์ฟเวอร์ไม่ได้");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDeleteCylinder = async (serialNumber) => {
    if (!serialNumber) return;
    if (!window.confirm(`ต้องการลบถังแก๊ส ${serialNumber} ออกจากระบบใช่หรือไม่?`)) return;

    try {
      const res = await fetch(`${API_BASE}/delete_cylinder.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ serial_number: serialNumber }),
      });
      const data = await res.json();
      if (data.success) {
        showToast("success", `ลบ ${serialNumber} สำเร็จ`);
        setCylinders((prev) => prev.filter((item) => item.serial_number !== serialNumber));
        if (editingSerial === serialNumber) clearForm();
      } else {
        showToast("error", data.message || "ลบไม่สำเร็จ");
      }
    } catch (err) {
      console.error("Error deleting cylinder:", err);
      showToast("error", "เชื่อมต่อเซิร์ฟเวอร์ไม่ได้");
    }
  };

  const openDatePicker = (e) => {
    try {
      e.target.showPicker?.();
    } catch {
      /* บางเบราว์เซอร์ไม่รองรับ */
    }
  };

  // ---------- จัดการตัวเลือก ----------
  const openOptionModal = (type, title) => {
    setManageModal({ open: true, type, title });
    setNewItemText("");
  };

  const getModalItems = () => {
    switch (manageModal.type) {
      case "brand": return brandOptions;
      case "gas_type": return gasTypeOptions;
      case "size": return sizeOptions;
      case "location": return locationOptions;
      default: return [];
    }
  };

  const handleAddItem = async () => {
    const value = newItemText.trim();
    if (!value) return;
    try {
      const res = await fetch(`${API_BASE}/Gas/manage_options.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "add", target: manageModal.type, value }),
      });
      const result = await res.json();
      if (result.success) {
        await fetchOptions();
        setNewItemText("");
      } else {
        showToast("error", result.message || "เพิ่มรายการไม่สำเร็จ");
      }
    } catch (err) {
      console.error("Error adding option:", err);
    }
  };

  const handleRemoveItem = async (id) => {
    if (!window.confirm("ต้องการลบรายการนี้ใช่หรือไม่?")) return;
    try {
      const res = await fetch(`${API_BASE}/Gas/manage_options.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", target: manageModal.type, id }),
      });
      const result = await res.json();
      if (result.success) await fetchOptions();
      else showToast("error", result.message || "ลบรายการไม่สำเร็จ");
    } catch (err) {
      console.error("Error removing option:", err);
    }
  };

  // ---------- QR / รูปหลักฐาน ----------
  const handleShowQR = (item) => {
    const serial = item.serial_number || item.serial;
    const found = cylinders.find((c) => String(c.serial_number) === String(serial));
    setQrModal({ open: true, cylinder: found ? { ...found, ...item, serial_number: found.serial_number } : item });
  };

  // พิมพ์เฉพาะ QR (เดิม window.print() พิมพ์ทั้งหน้า)
  const handlePrintQR = () => {
    const svg = document.getElementById("qr-print-svg");
    const serial = qrModal.cylinder?.serial_number || "";
    const win = window.open("", "_blank", "width=420,height=520");
    if (!svg || !win) return window.print();
    win.document.write(`
      <html><head><title>QR ${serial}</title></head>
      <body style="font-family:sans-serif;text-align:center;padding:24px">
        ${svg.outerHTML}
        <h2 style="margin:12px 0 0">${serial}</h2>
        <script>window.onload=function(){window.print();window.close();}</script>
      </body></html>`);
    win.document.close();
  };

  const getProofImageUrl = (proofImg) => {
    if (!proofImg) return "";
    if (proofImg.startsWith("http://") || proofImg.startsWith("https://")) return proofImg;
    const cleanPath = proofImg.replace(/^\/+/, "");
    if (cleanPath.startsWith("Backend/")) return `/${cleanPath}`;
    return `/Backend/uploads/${cleanPath}`;
  };

  const handleShowImage = (rawPath, serialOrId) => {
    if (!rawPath) return;
    setImageModal({ open: true, imgSrc: getProofImageUrl(rawPath), rawPath, serial: serialOrId });
  };

  if (loading) {
    return (
      <Layout>
        <div style={{ color: "#9ca3af", textAlign: "center", padding: "50px" }}>กำลังโหลดข้อมูล...</div>
      </Layout>
    );
  }

  const currentModalItems = getModalItems();
  const selectedCylinder = qrModal.cylinder;
  const selectedQrUrl = getQrUrl(selectedCylinder);

  const renderOptionSelect = (field, label, type, title, options, getVal, required = true) => (
    <div style={fieldGroupStyle}>
      <div style={labelRowStyle}>
        <label style={labelStyle}>{label}{required && <span style={reqStyle}> *</span>}</label>
        <button type="button" onClick={() => openOptionModal(type, title)} style={manageBtnStyle}>
          ⚙️ จัดการ
        </button>
      </div>
      <select
        value={formData[field]}
        onChange={(e) => setFormData({ ...formData, [field]: e.target.value })}
        style={inputStyle}
      >
        <option value="">-- เลือก --</option>
        {/* ค่าปัจจุบันที่ไม่มีในรายการ (ข้อมูลเก่า) ให้ยังเลือกค้างไว้ได้ */}
        {formData[field] && !options.some((o) => getVal(o) === formData[field]) && (
          <option value={formData[field]}>{formData[field]}</option>
        )}
        {options.map((item) => {
          const val = getVal(item);
          return <option key={item.id || val} value={val}>{val}</option>;
        })}
      </select>
    </div>
  );

  return (
    <Layout>
      <div style={pageStyle}>
        <div style={headerRowStyle}>
          <div>
            <h1 style={titleStyle}>จัดการถังแก๊ส</h1>
            <div style={subtitleStyle}>ถังทั้งหมด {cylinders.length} ใบ · รายการจัดส่ง {deliveries.length} รายการ</div>
          </div>
        </div>

        {/* สรุปตามสถานะ (กดเพื่อกรอง) */}
        <div style={chipRowStyle}>
          <button type="button" onClick={() => setStatusFilter("all")} style={filterChipStyle(statusFilter === "all", "#64748b")}>
            ทั้งหมด <strong>{cylinders.length}</strong>
          </button>
          {Object.entries(statusCounts).map(([s, n]) => (
            <button key={s} type="button" onClick={() => setStatusFilter(s)} style={filterChipStyle(statusFilter === s, STATUS_COLORS[s] || "#6b7280")}>
              <span style={{ ...dotStyle, background: STATUS_COLORS[s] || "#6b7280" }} />
              {s} <strong>{n}</strong>
            </button>
          ))}
        </div>

        {/* ฟอร์มเพิ่ม/แก้ไข */}
        <form ref={formRef} onSubmit={handleSave} style={{ ...cardStyle, ...(editingSerial ? editingCardStyle : {}) }}>
          <div style={cardHeaderStyle}>
            <h2 style={cardTitleStyle}>
              {editingSerial ? <>✏️ แก้ไขถัง <span style={{ color: "#fbbf24" }}>{editingSerial}</span></> : "➕ เพิ่มถังแก๊สใหม่"}
            </h2>
          </div>

          <div style={formGridStyle}>
            <div style={fieldGroupStyle}>
              <label style={labelStyle}>Serial Number<span style={reqStyle}> *</span></label>
              <input
                type="text"
                value={formData.serial_number}
                onChange={(e) => setFormData({ ...formData, serial_number: e.target.value })}
                style={{ ...inputStyle, ...(editingSerial ? readOnlyStyle : {}) }}
                placeholder="เช่น SN-1001"
                disabled={!!editingSerial}
              />
            </div>

            {renderOptionSelect("brand", "ยี่ห้อ", "brand", "จัดการรายการยี่ห้อ", brandOptions,
              (o) => o.brand_name || o.name || o)}
            {renderOptionSelect("gas_type", "ชนิดแก๊ส", "gas_type", "จัดการรายการชนิดแก๊ส", gasTypeOptions,
              (o) => o.type_name || o.gas_type_name || o.name || o)}
            {renderOptionSelect("size", "ขนาดถัง", "size", "จัดการรายการขนาดถัง", sizeOptions,
              (o) => o.size_name || o.name || o)}

            <div style={fieldGroupStyle}>
              <label style={labelStyle}>วันที่ผลิต<span style={reqStyle}> *</span></label>
              <input type="date" value={formData.manufacture_date} onClick={openDatePicker} onChange={handleManufactureDateChange} style={inputStyle} />
            </div>

            <div style={fieldGroupStyle}>
              <label style={labelStyle}>วันหมดอายุ <span style={hintStyle}>(ผลิต + 10 ปี)</span></label>
              <input type="date" value={formData.expiry_date} style={{ ...inputStyle, ...readOnlyStyle }} readOnly tabIndex={-1} />
            </div>

            <div style={fieldGroupStyle}>
              <label style={labelStyle}>วันที่ตรวจล่าสุด</label>
              <input type="date" value={formData.last_check_date} onClick={openDatePicker} onChange={handleLastCheckDateChange} style={inputStyle} />
            </div>

            <div style={fieldGroupStyle}>
              <label style={labelStyle}>ตรวจครั้งถัดไป <span style={hintStyle}>(+ 5 ปี)</span></label>
              <input type="date" value={formData.next_check_date} style={{ ...inputStyle, ...readOnlyStyle }} readOnly tabIndex={-1} />
            </div>

            <div style={fieldGroupStyle}>
              <label style={labelStyle}>วันที่ส่งมอบให้ลูกค้า</label>
              <input
                type="date"
                value={formData.delivered_date}
                onClick={openDatePicker}
                onChange={(e) => setFormData({ ...formData, delivered_date: e.target.value })}
                style={inputStyle}
              />
            </div>

            {renderOptionSelect("current_location", "สถานที่ปัจจุบัน", "location", "จัดการรายการสถานที่", locationOptions,
              (o) => o.location_name || o.name || o, false)}

            <div style={fieldGroupStyle}>
              <label style={labelStyle}>สถานะ<span style={reqStyle}> *</span></label>
              <select value={formData.status} onChange={(e) => setFormData({ ...formData, status: e.target.value })} style={inputStyle}>
                {!STATUS_OPTIONS.includes(formData.status) && formData.status && (
                  <option value={formData.status}>{formData.status}</option>
                )}
                {STATUS_OPTIONS.map((s) => <option key={s} value={s}>{s}</option>)}
              </select>
            </div>
          </div>

          <div style={formActionsStyle}>
            <button type="submit" style={primaryButtonStyle} disabled={isSubmitting}>
              {isSubmitting ? "กำลังบันทึก..." : editingSerial ? "💾 บันทึกการแก้ไข" : "➕ เพิ่มถัง"}
            </button>
            {(editingSerial || formData.serial_number) && (
              <button type="button" onClick={clearForm} style={secondaryButtonStyle}>
                {editingSerial ? "ยกเลิก" : "ล้างฟอร์ม"}
              </button>
            )}
          </div>
        </form>

        {/* ค้นหา */}
        <input
          type="text"
          placeholder="🔍 ค้นหา Serial, ยี่ห้อ, ชนิด, ขนาด, ลูกค้า, สถานที่, Delivery ID..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          style={searchInputStyle}
        />

        {/* ตารางที่ 1: ถังแก๊ส */}
        <div style={cardStyle}>
          <div style={cardHeaderStyle}>
            <h2 style={cardTitleStyle}>
              รายการถังแก๊ส {statusFilter !== "all" && <span style={{ color: "#9ca3af", fontWeight: "normal" }}>· {statusFilter}</span>}
              <span style={countPillStyle}>{filteredCylinders.length}</span>
            </h2>
          </div>
          <div style={tableWrapStyle}>
            <table style={tableStyle}>
              <thead>
                <tr>
                  <th style={thStyle}>Serial Number</th>
                  <th style={thStyle}>ยี่ห้อ</th>
                  <th style={thStyle}>ชนิด</th>
                  <th style={thStyle}>ขนาด</th>
                  <th style={thStyle}>วันที่ผลิต</th>
                  <th style={thStyle}>หมดอายุ</th>
                  <th style={thStyle}>ตรวจถัดไป</th>
                  <th style={thStyle}>สถานะ</th>
                  <th style={thStyle}>สถานที่</th>
                  <th style={{ ...thStyle, textAlign: "right" }}>จัดการ</th>
                </tr>
              </thead>
              <tbody>
                {filteredCylinders.length > 0 ? (
                  filteredCylinders.map((item) => {
                    const expiry = item.expiry_date || item.expire_date;
                    const nextCheck = item.next_check_date || item.next_inspection_date;
                    const isEditing = editingSerial === item.serial_number;
                    return (
                      <tr key={item.serial_number} style={isEditing ? { background: "rgba(245,158,11,0.08)" } : undefined}>
                        <td style={{ ...tdStyle, color: "#60a5fa", fontWeight: "bold" }}>{item.serial_number}</td>
                        <td style={tdStyle}>{item.brand || "-"}</td>
                        <td style={tdStyle}>{item.gas_type || "-"}</td>
                        <td style={tdStyle}>{item.size || "-"}</td>
                        <td style={tdMutedStyle}>{formatDate(item.manufacture_date)}</td>
                        <td style={isPastDate(expiry) ? tdDangerStyle : tdStyle}>
                          {formatDate(expiry)}{isPastDate(expiry) && " ⚠️"}
                        </td>
                        <td style={isPastDate(nextCheck) ? tdDangerStyle : tdStyle}>
                          {formatDate(nextCheck)}{isPastDate(nextCheck) && " ⚠️"}
                        </td>
                        <td style={tdStyle}><StatusBadge text={item.status} color={STATUS_COLORS[item.status]} /></td>
                        <td style={tdMutedStyle}>{item.current_location || "-"}</td>
                        <td style={{ ...tdStyle, textAlign: "right" }}>
                          <div style={actionGroupStyle}>
                            <button type="button" onClick={() => handleShowQR(item)} style={iconBtnStyle("#10b981")} title="QR Code">▦ QR</button>
                            <button type="button" onClick={() => editCylinder(item)} style={iconBtnStyle("#f59e0b")} title="แก้ไข">✏️</button>
                            <button type="button" onClick={() => handleDeleteCylinder(item.serial_number)} style={iconBtnStyle("#ef4444")} title="ลบ">🗑️</button>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr><td colSpan="10" style={emptyCellStyle}>ไม่พบถังแก๊สที่ตรงเงื่อนไข</td></tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* ตารางที่ 2: รายการจัดส่ง */}
        <div style={cardStyle}>
          <div style={cardHeaderStyle}>
            <h2 style={cardTitleStyle}>
              รายการจัดส่ง <span style={countPillStyle}>{filteredDeliveries.length}</span>
            </h2>
          </div>
          <div style={tableWrapStyle}>
            <table style={tableStyle}>
              <thead>
                <tr>
                  <th style={thStyle}>Delivery ID</th>
                  <th style={thStyle}>Serial Number</th>
                  <th style={thStyle}>ลูกค้า / ที่อยู่</th>
                  <th style={thStyle}>ยี่ห้อ</th>
                  <th style={thStyle}>ชนิด</th>
                  <th style={thStyle}>ขนาด</th>
                  <th style={thStyle}>สถานะ</th>
                  <th style={thStyle}>วันที่สั่ง</th>
                  <th style={{ ...thStyle, textAlign: "center" }}>หลักฐาน</th>
                  <th style={{ ...thStyle, textAlign: "right" }}>QR</th>
                </tr>
              </thead>
              <tbody>
                {filteredDeliveries.length > 0 ? (
                  filteredDeliveries.map((item) => {
                    const proofImg = item.proof_image_path || item.proof_image || "";
                    const status = getDeliveryStatus(item);
                    const serial = item.serial_number || item.serial;
                    return (
                      <tr key={item.delivery_id || item.id} style={status.text === "สูญหาย" ? { background: "rgba(239,68,68,0.08)" } : undefined}>
                        <td style={{ ...tdStyle, color: "#fbbf24", fontWeight: "bold" }}>#{item.delivery_id || "-"}</td>
                        <td style={tdStyle}>
                          {hasValue(serial)
                            ? <span style={{ color: "#60a5fa", fontWeight: "bold" }}>{serial}</span>
                            : <span style={{ color: "#9ca3af", fontSize: "12px", fontStyle: "italic" }}>ยังไม่สแกนถัง</span>}
                        </td>
                        <td style={{ ...tdStyle, whiteSpace: "normal", minWidth: "180px" }}>
                          <div style={{ fontWeight: 600 }}>{item.customer_name || "-"}</div>
                          <div style={{ fontSize: "12px", color: "#9ca3af" }}>{item.address || "-"}</div>
                        </td>
                        <td style={tdStyle}>{item.brand || item.req_brand || "-"}</td>
                        <td style={tdStyle}>{item.gas_type || item.req_gas_type || "-"}</td>
                        <td style={tdStyle}>{item.size || item.req_size || "-"}</td>
                        <td style={tdStyle}><StatusBadge text={status.text} color={status.color} /></td>
                        <td style={tdMutedStyle}>{formatDate(item.created_at || item.delivered_date)}</td>
                        <td style={{ ...tdStyle, textAlign: "center" }}>
                          {proofImg ? (
                            <button type="button" onClick={() => handleShowImage(proofImg, item.delivery_id)} style={iconBtnStyle("#3b82f6")}>
                              📷 ดูรูป
                            </button>
                          ) : (
                            <span style={{ color: "#6b7280", fontSize: "12px" }}>—</span>
                          )}
                        </td>
                        <td style={{ ...tdStyle, textAlign: "right" }}>
                          {/* ไม่มี Serial = ยังไม่รู้ว่าเป็นถังไหน สร้าง QR ไม่ได้ */}
                          <button
                            type="button"
                            onClick={() => handleShowQR(item)}
                            style={{ ...iconBtnStyle("#10b981"), ...(hasValue(serial) ? {} : disabledBtnStyle) }}
                            disabled={!hasValue(serial)}
                            title={hasValue(serial) ? "QR Code" : "ยังไม่ได้สแกนถัง"}
                          >
                            ▦ QR
                          </button>
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr><td colSpan="10" style={emptyCellStyle}>ไม่พบรายการจัดส่ง</td></tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Toast แจ้งผล */}
      {toast && (
        <div style={{ ...toastStyle, borderColor: toast.type === "success" ? "#10b981" : "#ef4444" }}>
          <span>{toast.type === "success" ? "✅" : "⚠️"}</span>
          <span>{toast.text}</span>
        </div>
      )}

      {/* Modal จัดการตัวเลือก */}
      {manageModal.open && (
        <div style={modalOverlayStyle} onClick={() => setManageModal({ open: false, type: "", title: "" })}>
          <div style={modalContentStyle} onClick={(e) => e.stopPropagation()}>
            <div style={modalHeaderStyle}>
              <h3 style={{ margin: 0 }}>⚙️ {manageModal.title}</h3>
              <button type="button" onClick={() => setManageModal({ open: false, type: "", title: "" })} style={closeBtnStyle}>✕</button>
            </div>

            <div style={{ display: "flex", gap: "8px", marginBottom: "14px" }}>
              <input
                type="text"
                value={newItemText}
                onChange={(e) => setNewItemText(e.target.value)}
                onKeyDown={(e) => e.key === "Enter" && handleAddItem()}
                placeholder="กรอกรายการใหม่..."
                style={inputStyle}
              />
              <button type="button" onClick={handleAddItem} style={primaryButtonStyle}>เพิ่ม</button>
            </div>

            <div style={optionListStyle}>
              {currentModalItems.length > 0 ? (
                currentModalItems.map((item) => {
                  const name = item.brand_name || item.type_name || item.gas_type_name || item.size_name || item.location_name || item.name;
                  return (
                    <div key={item.id} style={optionItemStyle}>
                      <span>{name}</span>
                      <button type="button" onClick={() => handleRemoveItem(item.id)} style={iconBtnStyle("#ef4444")}>ลบ</button>
                    </div>
                  );
                })
              ) : (
                <div style={{ color: "#9ca3af", textAlign: "center", padding: "12px 0" }}>ยังไม่มีรายการ</div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Modal QR Code */}
      {qrModal.open && (
        <div style={modalOverlayStyle} onClick={() => setQrModal({ open: false, cylinder: null })}>
          <div style={{ ...modalContentStyle, textAlign: "center" }} onClick={(e) => e.stopPropagation()}>
            <div style={modalHeaderStyle}>
              <h3 style={{ margin: 0 }}>QR Code ถังแก๊ส</h3>
              <button type="button" onClick={() => setQrModal({ open: false, cylinder: null })} style={closeBtnStyle}>✕</button>
            </div>

            <div style={{ fontSize: "20px", fontWeight: "bold", color: "#60a5fa" }}>{selectedCylinder?.serial_number}</div>
            <div style={{ color: "#9ca3af", fontSize: "13px", marginTop: "4px" }}>
              {[selectedCylinder?.brand, selectedCylinder?.gas_type, selectedCylinder?.size].filter(Boolean).join(" · ")}
            </div>

            <div style={{ background: "white", padding: "14px", borderRadius: "12px", display: "inline-block", margin: "16px 0" }}>
              <QRCodeSVG id="qr-print-svg" value={selectedQrUrl} size={200} includeMargin={true} />
            </div>

            <p style={{ fontSize: "11px", color: "#64748b", wordBreak: "break-all", margin: "0 0 16px" }}>{selectedQrUrl}</p>

            <button type="button" onClick={handlePrintQR} style={{ ...primaryButtonStyle, width: "100%" }}>🖨️ พิมพ์ QR Code</button>
          </div>
        </div>
      )}

      {/* Modal รูปหลักฐาน */}
      {imageModal.open && (
        <div style={modalOverlayStyle} onClick={() => setImageModal({ open: false, imgSrc: "", rawPath: "", serial: "" })}>
          <div style={{ ...modalContentStyle, width: "auto", maxWidth: "90vw" }} onClick={(e) => e.stopPropagation()}>
            <div style={modalHeaderStyle}>
              <h3 style={{ margin: 0 }}>หลักฐานการจัดส่ง #{imageModal.serial}</h3>
              <button type="button" onClick={() => setImageModal({ open: false, imgSrc: "", rawPath: "", serial: "" })} style={closeBtnStyle}>✕</button>
            </div>
            <img
              src={imageModal.imgSrc}
              alt="หลักฐานการจัดส่ง"
              style={{ maxWidth: "100%", maxHeight: "70vh", borderRadius: "8px", objectFit: "contain", display: "block", margin: "0 auto" }}
              onError={(e) => {
                e.target.onerror = null;
                const fileName = imageModal.rawPath ? imageModal.rawPath.split("/").pop() : "";
                if (fileName) e.target.src = `/Backend/uploads/${fileName}`;
              }}
            />
          </div>
        </div>
      )}
    </Layout>
  );
}

const StatusBadge = ({ text, color = "#6b7280" }) => (
  <span style={{ ...badgeStyle, color, background: `${color}22`, border: `1px solid ${color}` }}>{text || "-"}</span>
);

// ========== Styles ==========
const pageStyle = { color: "white" };
const headerRowStyle = { display: "flex", justifyContent: "space-between", alignItems: "flex-end", marginBottom: "16px" };
const titleStyle = { margin: 0, fontSize: "30px" };
const subtitleStyle = { color: "#9ca3af", fontSize: "14px", marginTop: "6px" };

const chipRowStyle = { display: "flex", gap: "8px", flexWrap: "wrap", marginBottom: "20px" };
const filterChipStyle = (active, color) => ({
  display: "inline-flex",
  alignItems: "center",
  gap: "8px",
  padding: "7px 14px",
  borderRadius: "999px",
  border: `1px solid ${active ? color : "#374151"}`,
  background: active ? `${color}26` : "#1f2937",
  color: active ? "white" : "#cbd5e1",
  cursor: "pointer",
  fontSize: "13px",
});
const dotStyle = { width: "8px", height: "8px", borderRadius: "50%", display: "inline-block" };

const cardStyle = { background: "#1f2937", padding: "20px", borderRadius: "14px", marginBottom: "20px", boxShadow: "0 1px 2px rgba(0,0,0,0.3)" };
const editingCardStyle = { boxShadow: "0 0 0 2px #f59e0b" };
const cardHeaderStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "16px" };
const cardTitleStyle = { margin: 0, fontSize: "18px", display: "flex", alignItems: "center", gap: "10px" };
const countPillStyle = { background: "#374151", color: "#e5e7eb", fontSize: "13px", padding: "2px 10px", borderRadius: "999px", fontWeight: "normal" };

const formGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(200px, 1fr))", gap: "14px 16px" };
const fieldGroupStyle = { display: "flex", flexDirection: "column", gap: "6px" };
const labelRowStyle = { display: "flex", justifyContent: "space-between", alignItems: "center" };
const labelStyle = { fontSize: "13px", color: "#cbd5e1", fontWeight: 600 };
const reqStyle = { color: "#f87171" };
const hintStyle = { color: "#6b7280", fontWeight: "normal", fontSize: "12px" };
const inputStyle = {
  height: "40px",
  padding: "0 12px",
  borderRadius: "8px",
  border: "1px solid #374151",
  background: "#111827",
  color: "white",
  width: "100%",
  boxSizing: "border-box",
  fontSize: "14px",
  colorScheme: "dark",
};
const readOnlyStyle = { background: "#1a2230", color: "#9ca3af", cursor: "not-allowed" };
const manageBtnStyle = { background: "none", border: "none", color: "#60a5fa", fontSize: "12px", cursor: "pointer", padding: 0 };
const formActionsStyle = { display: "flex", gap: "10px", marginTop: "18px" };

const searchInputStyle = { ...inputStyle, height: "44px", marginBottom: "20px", border: "1px solid #3b82f6", padding: "0 16px" };

const primaryButtonStyle = { height: "40px", padding: "0 18px", border: "none", borderRadius: "8px", background: "#2563eb", color: "white", cursor: "pointer", fontWeight: "bold", fontSize: "14px" };
const secondaryButtonStyle = { height: "40px", padding: "0 18px", border: "1px solid #4b5563", borderRadius: "8px", background: "transparent", color: "#e5e7eb", cursor: "pointer", fontSize: "14px" };
const iconBtnStyle = (color) => ({
  padding: "5px 10px",
  border: `1px solid ${color}`,
  borderRadius: "6px",
  background: `${color}1f`,
  color: "white",
  cursor: "pointer",
  fontSize: "12px",
  whiteSpace: "nowrap",
});
const disabledBtnStyle = { opacity: 0.35, cursor: "not-allowed" };
const actionGroupStyle = { display: "inline-flex", gap: "6px" };

const badgeStyle = { display: "inline-block", padding: "3px 10px", borderRadius: "999px", fontSize: "12px", fontWeight: "bold", whiteSpace: "nowrap" };

const tableWrapStyle = { overflowX: "auto" };
const tableStyle = { width: "100%", borderCollapse: "collapse", fontSize: "13px", whiteSpace: "nowrap" };
const thStyle = { padding: "10px 12px", textAlign: "left", borderBottom: "1px solid #374151", background: "#273244", color: "#cbd5e1", fontWeight: 600 };
const tdStyle = { padding: "10px 12px", borderBottom: "1px solid #2b3647", verticalAlign: "middle" };
const tdMutedStyle = { ...tdStyle, color: "#9ca3af" };
const tdDangerStyle = { ...tdStyle, color: "#f87171", fontWeight: "bold" };
const emptyCellStyle = { padding: "28px", textAlign: "center", color: "#9ca3af" };

const toastStyle = {
  position: "fixed",
  right: "24px",
  bottom: "24px",
  zIndex: 1100,
  display: "flex",
  gap: "10px",
  alignItems: "center",
  padding: "12px 16px",
  background: "#111827",
  color: "white",
  border: "1px solid",
  borderRadius: "10px",
  boxShadow: "0 8px 24px rgba(0,0,0,0.4)",
  fontSize: "14px",
};

const modalOverlayStyle = { position: "fixed", inset: 0, backgroundColor: "rgba(0,0,0,0.7)", display: "flex", justifyContent: "center", alignItems: "center", zIndex: 1000, padding: "16px" };
const modalContentStyle = { background: "#1f2937", color: "white", padding: "20px", borderRadius: "14px", width: "380px", maxWidth: "100%", boxShadow: "0 8px 24px rgba(0,0,0,0.4)", border: "1px solid #374151" };
const modalHeaderStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "16px", textAlign: "left" };
const closeBtnStyle = { background: "none", border: "none", color: "#9ca3af", fontSize: "18px", cursor: "pointer" };
const optionListStyle = { maxHeight: "260px", overflowY: "auto", display: "flex", flexDirection: "column", gap: "6px" };
const optionItemStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", padding: "8px 12px", background: "#111827", borderRadius: "8px" };

export default GasPage;
