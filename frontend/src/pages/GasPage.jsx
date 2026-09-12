import React, { useState, useEffect } from "react";
import Layout from "../components/Layout";
import { QRCodeSVG } from "qrcode.react";

const API_BASE = "/Backend/models";

function GasPage() {
  const [cylinders, setCylinders] = useState([]);
  const [deliveries, setDeliveries] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [editingSerial, setEditingSerial] = useState(null);

  // State สำหรับค้นหาถังแก๊ส
  const [searchTerm, setSearchTerm] = useState("");

  // ตัวเลือกดร็อปดาวน์
  const [brandOptions, setBrandOptions] = useState([]);
  const [gasTypeOptions, setGasTypeOptions] = useState([]);
  const [sizeOptions, setSizeOptions] = useState([]);
  const [locationOptions, setLocationOptions] = useState([]);

  // State สำหรับ Modal เพิ่ม/ลด ตัวเลือก
  const [manageModal, setManageModal] = useState({ open: false, type: "", title: "" });
  const [newItemText, setNewItemText] = useState("");

  // State สำหรับ Modal แสดง QR Code
  const [qrModal, setQrModal] = useState({ open: false, cylinder: null });

  // State สำหรับ Modal แสดงรูป Proof Image ขนาดใหญ่
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

  // ----------------------------------------------------
  // ฟังก์ชันคำนวณ URL สำหรับ QR Code
  // ----------------------------------------------------
  const getQrUrl = (item) => {
    if (!item) return "#";

    const cylinderIdentifier =
      item.serial_number ||
      item.serial ||
      item.delivery_id ||
      item.id;

    const cleanId = String(cylinderIdentifier || "").trim();

    if (cleanId && cleanId !== "0" && cleanId !== "undefined" && cleanId !== "null") {
      return `http://${window.location.hostname}:5173/cylinder/${encodeURIComponent(cleanId)}`;
    }

    return "#";
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

  // 2. ดึงรายการถังแก๊สในคลัง
  const fetchCylinders = async () => {
    try {
      const res = await fetch(`${API_BASE}/get_cylinders.php`);
      const data = await res.json();
      if (data.success) {
        setCylinders(data.data || []);
      }
    } catch (err) {
      console.error("Error fetching cylinders:", err);
    }
  };

  // 3. ดึงรายการจัดส่งทั้งหมด
  const fetchDeliveries = async () => {
    try {
      const res = await fetch(`${API_BASE}/get_delivery_success.php`);
      const data = await res.json();
      if (Array.isArray(data)) {
        setDeliveries(data);
      } else if (data.success && Array.isArray(data.data)) {
        setDeliveries(data.data);
      }
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

  // คำนวณวันหมดอายุและวันที่ตรวจครั้งถัดไป
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
    setFormData((prev) => ({
      ...prev,
      manufacture_date: mfgDate,
      expiry_date: expiry,
      next_check_date: nextCheck,
    }));
  };

  const handleLastCheckDateChange = (e) => {
    const lastCheck = e.target.value;
    const { nextCheck } = calculateDates(formData.manufacture_date, lastCheck);
    setFormData((prev) => ({
      ...prev,
      last_check_date: lastCheck,
      next_check_date: nextCheck,
    }));
  };

  const clearForm = () => {
    setEditingSerial(null);
    setFormData(initialFormState);
  };

  const editCylinder = (item) => {
    setEditingSerial(item.serial_number || item.serial);
    setFormData({
      serial_number: item.serial_number || item.serial || "",
      brand: item.brand || item.brand_name || item.req_brand || "",
      gas_type: item.gas_type || item.gas_type_name || item.req_gas_type || "",
      size: item.size || item.size_name || item.req_size || "",
      manufacture_date: item.manufacture_date || "",
      expiry_date: item.expiry_date || item.expire_date || "",
      last_check_date: item.last_check_date || item.last_checked || "",
      next_check_date: item.next_check_date || item.next_check || "",
      delivered_date: item.delivered_date || item.delivery_date || "",
      current_location: item.current_location || item.location_name || item.address || "",
      status: item.status || "ในคลัง",
    });
  };

  // ฟังก์ชันเช็คว่าจัดส่งเกิน 3 ปีหรือไม่ (365 * 3 = 1,095 วัน)
  const isOver3Years = (dateString) => {
    if (!dateString) return false;
    const deliveryDate = new Date(dateString);
    if (isNaN(deliveryDate.getTime())) return false;
    
    const today = new Date();
    const diffTime = today - deliveryDate;
    const diffDays = diffTime / (1000 * 60 * 60 * 24);
    return diffDays > (365 * 3);
  };

  // การกรองรายการถังแก๊สในคลัง (ค้นหาได้ทุกฟิลด์)
  const filteredCylinders = cylinders.filter((item) => {
    const term = searchTerm.toLowerCase().trim();

    const serial = (item.serial_number || item.serial || "").toLowerCase();
    const brand = (item.brand || item.brand_name || "").toLowerCase();
    const gasType = (item.gas_type || item.gas_type_name || "").toLowerCase();
    const size = (item.size || item.size_name || "").toLowerCase();
    const mfgDate = (item.manufacture_date || "").toLowerCase();
    const expiryDate = (item.expiry_date || item.expire_date || "").toLowerCase();
    const nextCheckDate = (item.next_check_date || item.next_check || "").toLowerCase();
    const location = (item.current_location || item.location_name || "").toLowerCase();
    const status = (item.status || "").trim().toLowerCase();

    const isInStock = status === "ในคลัง";

    const matchesSearch =
      serial.includes(term) ||
      brand.includes(term) ||
      gasType.includes(term) ||
      size.includes(term) ||
      mfgDate.includes(term) ||
      expiryDate.includes(term) ||
      nextCheckDate.includes(term) ||
      location.includes(term) ||
      status.includes(term);

    return isInStock && matchesSearch;
  });

  // การกรองรายการจัดส่ง (ค้นหาได้ทุกฟิลด์ + ค้นหาภาษาไทย)
  const filteredDeliveries = deliveries
    .filter((item) => {
      const term = searchTerm.toLowerCase().trim();

      const deliveryId = (item.delivery_id || "").toString().toLowerCase();
      const serialNumber = (item.serial_number || item.serial || "").toString().toLowerCase();
      const customer = (item.customer_name || "").toLowerCase();
      const address = (item.address || "").toLowerCase();
      const brand = (item.brand || item.req_brand || "").toLowerCase();
      const gasType = (item.gas_type || item.req_gas_type || "").toLowerCase();
      const size = (item.size || item.req_size || "").toLowerCase();
      const rawStatus = (item.status || "").toLowerCase();
      const deliveryDate = (item.created_at || item.delivered_date || "").toLowerCase();

      const isLost = isOver3Years(item.created_at || item.delivered_date);

      let thStatus = "";
      if (isLost) {
        thStatus = "สูญหาย";
      } else if (rawStatus === "success") {
        thStatus = "จัดส่งสำเร็จ";
      } else if (rawStatus === "pending") {
        thStatus = "กำลังจัดส่ง";
      }

      const matchesSearch =
        deliveryId.includes(term) ||
        serialNumber.includes(term) ||
        customer.includes(term) ||
        address.includes(term) ||
        brand.includes(term) ||
        gasType.includes(term) ||
        size.includes(term) ||
        rawStatus.includes(term) ||
        thStatus.includes(term) ||
        deliveryDate.includes(term);

      return matchesSearch;
    })
    .sort((a, b) => {
      const aIsLost = isOver3Years(a.created_at || a.delivered_date);
      const bIsLost = isOver3Years(b.created_at || b.delivered_date);

      if (aIsLost && !bIsLost) return 1;
      if (!aIsLost && bIsLost) return -1;
      
      return 0;
    });

  const handleSave = async (e) => {
    if (e) e.preventDefault();

    if (!formData.serial_number.trim()) return alert("กรุณากรอก Serial Number");
    if (!formData.brand) return alert("กรุณาเลือก ยี่ห้อ");
    if (!formData.gas_type) return alert("กรุณาเลือก ชนิดแก๊ส");
    if (!formData.size) return alert("กรุณาเลือก ขนาดถัง");
    if (!formData.manufacture_date) return alert("กรุณาเลือก วันที่ผลิต");
    if (!formData.status) return alert("กรุณาเลือก สถานะเริ่มต้น");

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
        loadAllData();
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

  const handleDeleteCylinder = async (serialNumber) => {
    if (!serialNumber) return alert("ไม่พบรหัส Serial Number");
    if (!window.confirm(`คุณต้องการลบถังแก๊ส ${serialNumber} ออกจากฐานข้อมูลใช่หรือไม่?`)) return;

    try {
      const res = await fetch(`${API_BASE}/delete_cylinder.php?serial_number=${encodeURIComponent(serialNumber)}`);
      const data = await res.json();

      if (data.success) {
        alert("ลบข้อมูลออกจากฐานข้อมูลสำเร็จ");
        setCylinders((prev) => prev.filter((item) => (item.serial_number || item.serial) !== serialNumber));
      } else {
        alert(data.message || "เกิดข้อผิดพลาดในการลบข้อมูล");
      }
    } catch (err) {
      console.error("Error deleting cylinder:", err);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์");
    }
  };

  const handleDeleteDelivery = async (deliveryId) => {
    if (!deliveryId) return;
    if (!window.confirm(`คุณต้องการลบรายการจัดส่ง Delivery ID: #${deliveryId} ใช่หรือไม่?`)) return;

    try {
      const res = await fetch(`${API_BASE}/delete_delivery.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ delivery_id: deliveryId }),
      });
      const data = await res.json();
      if (data.success) {
        alert("ลบรายการจัดส่งเรียบร้อยแล้ว");
        fetchDeliveries();
      } else {
        alert(data.message || "ไม่สามารถลบรายการจัดส่งได้");
      }
    } catch (err) {
      console.error("Error deleting delivery:", err);
      alert("เกิดข้อผิดพลาดในการลบรายการจัดส่ง");
    }
  };

  const openDatePicker = (e) => {
    if (e.target.showPicker) {
      try {
        e.target.showPicker();
      } catch (error) {
        console.error("DatePicker showPicker error:", error);
      }
    }
  };

  const openOptionModal = (type, title) => {
    setManageModal({ open: true, type, title });
    setNewItemText("");
  };

  const getModalItems = () => {
    switch (manageModal.type) {
      case "brand":
        return brandOptions;
      case "gas_type":
      case "gasType":
        return gasTypeOptions;
      case "size":
        return sizeOptions;
      case "location":
        return locationOptions;
      default:
        return [];
    }
  };

  const handleAddItem = async () => {
    if (!newItemText.trim()) return;
    const value = newItemText.trim();

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
        alert(result.message || "ไม่สามารถเพิ่มข้อมูลได้");
      }
    } catch (err) {
      console.error("Error adding option:", err);
    }
  };

  const handleRemoveItem = async (id) => {
    if (!window.confirm("คุณต้องการลบรายการนี้ใช่หรือไม่?")) return;

    try {
      const res = await fetch(`${API_BASE}/Gas/manage_options.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", target: manageModal.type, id }),
      });
      const result = await res.json();
      if (result.success) {
        await fetchOptions();
      } else {
        alert(result.message || "ไม่สามารถลบข้อมูลได้");
      }
    } catch (err) {
      console.error("Error removing option:", err);
    }
  };

  const handleShowQR = (item) => {
    let fullData = { ...item };
    const targetSerial = item.serial_number || item.serial;

    if (targetSerial && Array.isArray(cylinders)) {
      const found = cylinders.find(
        (c) => String(c.serial_number || c.serial) === String(targetSerial)
      );

      if (found) {
        fullData = {
          ...found,
          ...item,
          serial_number: found.serial_number || found.serial
        };
      }
    }

    setQrModal({ open: true, cylinder: fullData });
  };

  const handlePrintQR = () => {
    window.print();
  };

  const getProofImageUrl = (proofImg) => {
    if (!proofImg) return "";
    if (proofImg.startsWith("http://") || proofImg.startsWith("https://")) {
      return proofImg;
    }
    const cleanPath = proofImg.replace(/^\/+/, "");
    if (cleanPath.startsWith("Backend/")) {
      return `/${cleanPath}`;
    }
    return `/Backend/uploads/${cleanPath}`;
  };

  const handleShowImage = (rawPath, serialOrId) => {
    if (!rawPath) return;
    const fullImgUrl = getProofImageUrl(rawPath);
    setImageModal({ open: true, imgSrc: fullImgUrl, rawPath, serial: serialOrId });
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

  const currentModalItems = getModalItems();
  const selectedCylinder = qrModal.cylinder;

  return (
    <Layout>
      <h1 style={{ marginBottom: "20px", color: "white" }}>จัดการถังแก๊ส</h1>

      {/* ฟอร์มจัดการถังแก๊ส */}
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
              required
            />
          </div>

          <div style={fieldGroupStyle}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <label style={labelStyle}>ยี่ห้อ *</label>
              <button
                type="button"
                onClick={() => openOptionModal("brand", "จัดการรายการยี่ห้อ")}
                style={manageBtnStyle}
              >
                ⚙️ จัดการ
              </button>
            </div>
            <select
              value={formData.brand}
              onChange={(e) => setFormData({ ...formData, brand: e.target.value })}
              style={inputStyle}
              required
            >
              <option value="">-- เลือกยี่ห้อ --</option>
              {brandOptions.map((item) => {
                const val = item.brand_name || item.brand || item.name || item;
                return (
                  <option key={item.id || val} value={val}>
                    {val}
                  </option>
                );
              })}
            </select>
          </div>

          <div style={fieldGroupStyle}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <label style={labelStyle}>ชนิดแก๊ส *</label>
              <button
                type="button"
                onClick={() => openOptionModal("gas_type", "จัดการรายการชนิดแก๊ส")}
                style={manageBtnStyle}
              >
                ⚙️ จัดการ
              </button>
            </div>
            <select
              value={formData.gas_type}
              onChange={(e) => setFormData({ ...formData, gas_type: e.target.value })}
              style={inputStyle}
              required
            >
              <option value="">-- เลือกชนิด --</option>
              {gasTypeOptions.map((item) => {
                const val = item.type_name || item.gas_type || item.gas_type_name || item.name || item;
                return (
                  <option key={item.id || val} value={val}>
                    {val}
                  </option>
                );
              })}
            </select>
          </div>

          <div style={fieldGroupStyle}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <label style={labelStyle}>ขนาดถัง *</label>
              <button
                type="button"
                onClick={() => openOptionModal("size", "จัดการรายการขนาดถัง")}
                style={manageBtnStyle}
              >
                ⚙️ จัดการ
              </button>
            </div>
            <select
              value={formData.size}
              onChange={(e) => setFormData({ ...formData, size: e.target.value })}
              style={inputStyle}
              required
            >
              <option value="">-- เลือกขนาด --</option>
              {sizeOptions.map((item) => {
                const val = item.size_name || item.size || item.name || item;
                return (
                  <option key={item.id || val} value={val}>
                    {val}
                  </option>
                );
              })}
            </select>
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันที่ผลิต *</label>
            <input
              type="date"
              value={formData.manufacture_date}
              onClick={openDatePicker}
              onChange={handleManufactureDateChange}
              style={inputStyle}
              required
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันหมดอายุ (คำนวณอัตโนมัติ)</label>
            <input
              type="date"
              value={formData.expiry_date}
              style={{ ...inputStyle, backgroundColor: "#1f2937", cursor: "not-allowed" }}
              readOnly
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันที่ตรวจล่าสุด</label>
            <input
              type="date"
              value={formData.last_check_date}
              onClick={openDatePicker}
              onChange={handleLastCheckDateChange}
              style={inputStyle}
            />
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันที่ตรวจครั้งถัดไป (คำนวณ)</label>
            <input
              type="date"
              value={formData.next_check_date}
              style={{ ...inputStyle, backgroundColor: "#1f2937", cursor: "not-allowed" }}
              readOnly
            />
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

          <div style={fieldGroupStyle}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <label style={labelStyle}>สถานที่ปัจจุบัน</label>
              <button
                type="button"
                onClick={() => openOptionModal("location", "จัดการรายการสถานที่")}
                style={manageBtnStyle}
              >
                ⚙️ จัดการ
              </button>
            </div>
            <select
              value={formData.current_location}
              onChange={(e) => setFormData({ ...formData, current_location: e.target.value })}
              style={inputStyle}
            >
              <option value="">-- เลือกสถานที่ --</option>
              {locationOptions.map((item) => {
                const val = item.location_name || item.location || item.current_location || item.name || item;
                return (
                  <option key={item.id || val} value={val}>
                    {val}
                  </option>
                );
              })}
            </select>
          </div>

          <div style={fieldGroupStyle}>
            <label style={labelStyle}>สถานะเริ่มต้น *</label>
            <select
              value={formData.status}
              onChange={(e) => setFormData({ ...formData, status: e.target.value })}
              style={inputStyle}
              required
            >
              <option value="ในคลัง">ในคลัง</option>
              <option value="ปกติ">ปกติ</option>
              <option value="pending">pending (กำลังจัดส่ง)</option>
              <option value="success">success (จัดส่งสำเร็จ)</option>
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

      {/* Modal จัดการตัวเลือก */}
      {manageModal.open && (
        <div style={modalOverlayStyle}>
          <div style={modalContentStyle}>
            <h3 style={{ marginTop: 0, color: "white" }}>{manageModal.title}</h3>

            <div style={{ display: "flex", gap: "8px", marginBottom: "15px" }}>
              <input
                type="text"
                value={newItemText}
                onChange={(e) => setNewItemText(e.target.value)}
                placeholder="กรอกรายการใหม่..."
                style={inputStyle}
              />
              <button type="button" onClick={handleAddItem} style={primaryButtonStyle}>
                เพิ่ม
              </button>
            </div>

            <ul style={{ listStyle: "none", padding: 0, margin: "0 0 15px 0", maxHeight: "200px", overflowY: "auto" }}>
              {currentModalItems.length > 0 ? (
                currentModalItems.map((item) => {
                  const name =
                    item.brand_name ||
                    item.type_name ||
                    item.gas_type_name ||
                    item.size_name ||
                    item.location_name ||
                    item.name;
                  return (
                    <li key={item.id} style={modalListItemStyle}>
                      <span style={{ color: "white" }}>{name}</span>
                      <button type="button" onClick={() => handleRemoveItem(item.id)} style={deleteButtonStyle}>
                        ลบ
                      </button>
                    </li>
                  );
                })
              ) : (
                <li style={{ color: "#9ca3af", textAlign: "center", padding: "10px 0" }}>
                  ยังไม่มีรายการ
                </li>
              )}
            </ul>

            <div style={{ textAlign: "right" }}>
              <button
                type="button"
                onClick={() => setManageModal({ open: false, type: "", title: "" })}
                style={secondaryButtonStyle}
              >
                ปิด
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal QR Code */}
      {qrModal.open && (
        <div style={modalOverlayStyle} onClick={() => setQrModal({ open: false, cylinder: null })}>
          <div
            style={{ ...modalContentStyle, textAlign: "center" }}
            onClick={(e) => e.stopPropagation()}
          >
            <h3 style={{ marginTop: 0, color: "white" }}>QR Code สำหรับสแกนด้วยมือถือ</h3>

            {(() => {
              const serialVal = selectedCylinder?.serial_number || selectedCylinder?.serial;
              if (serialVal && serialVal !== "-") {
                return (
                  <p style={{ margin: "4px 0", color: "#94a3b8", fontWeight: "bold" }}>
                    Serial: {serialVal}
                  </p>
                );
              }
              return null;
            })()}

            <div style={{ background: "white", padding: "16px", borderRadius: "12px", display: "inline-block", margin: "16px 0" }}>
              <QRCodeSVG 
                value={getQrUrl(selectedCylinder)} 
                size={200}
                includeMargin={true}
              />
            </div>

            <p style={{ fontSize: "12px", color: "#64748b", wordBreak: "break-all", margin: "4px 0" }}>
              URL: {getQrUrl(selectedCylinder)}
            </p>

            <div style={{ display: "flex", gap: "10px", justifyContent: "center", marginTop: "16px" }}>
              <button type="button" onClick={handlePrintQR} style={primaryButtonStyle}>
                🖨️ พิมพ์ QR Code
              </button>
              <button
                type="button"
                onClick={() => setQrModal({ open: false, cylinder: null })}
                style={secondaryButtonStyle}
              >
                ปิด
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal รูปภาพหลักฐาน */}
      {imageModal.open && (
        <div 
          style={modalOverlayStyle} 
          onClick={() => setImageModal({ open: false, imgSrc: "", rawPath: "", serial: "" })}
        >
          <div 
            style={{ ...modalContentStyle, width: "auto", maxWidth: "90vw", textAlign: "center", background: "#111827" }} 
            onClick={(e) => e.stopPropagation()}
          >
            <h3 style={{ marginTop: 0, color: "white" }}>
              หลักฐานการจัดส่ง ({imageModal.serial})
            </h3>
            
            <img
              src={imageModal.imgSrc}
              alt="Proof of delivery"
              style={{ maxWidth: "100%", maxHeight: "70vh", borderRadius: "8px", objectFit: "contain" }}
              onError={(e) => {
                e.target.onerror = null; 
                const fileName = imageModal.rawPath ? imageModal.rawPath.split('/').pop() : "";
                if (fileName) {
                  e.target.src = `/Backend/uploads/${fileName}`;
                }
              }}
            />
            
            <div style={{ marginTop: "15px", textAlign: "center" }}>
              <button
                type="button"
                onClick={() => setImageModal({ open: false, imgSrc: "", rawPath: "", serial: "" })}
                style={secondaryButtonStyle}
              >
                ปิด
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ช่องค้นหา */}
      <div style={{ marginBottom: "20px", width: "100%" }}>
        <input
          type="text"
          placeholder="ค้นหา Serial Number, Delivery ID, ยี่ห้อ, ลูกค้า, สถานที่..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          style={{
            ...inputStyle,
            width: "100%",
            padding: "10px 14px",
            borderColor: "#3b82f6",
          }}
        />
      </div>

      {/* ตารางที่ 1: ในคลัง / ทั่วไป */}
      <div style={{ marginBottom: "30px", overflowX: "auto" }}>
        <h3 style={{ color: "#38bdf8", marginBottom: "12px", display: "flex", alignItems: "center", gap: "8px" }}>
          รายการถังแก๊สในคลัง / ทั่วไป
        </h3>
        <table style={tableStyle}>
          <thead>
            <tr>
              <th style={thStyle}>Serial Number</th>
              <th style={thStyle}>ยี่ห้อ</th>
              <th style={thStyle}>ชนิด</th>
              <th style={thStyle}>ขนาด</th>
              <th style={thStyle}>วันที่ผลิต</th>
              <th style={thStyle}>วันหมดอายุ</th>
              <th style={thStyle}>วันตรวจถัดไป</th>
              <th style={thStyle}>สถานะ</th>
              <th style={thStyle}>สถานที่ปัจจุบัน</th>
              <th style={{ ...thStyle, textAlign: "center" }}>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            {filteredCylinders.length > 0 ? (
              filteredCylinders.map((item) => (
                <tr key={item.serial_number || item.serial || item.id}>
                  <td style={{ ...tdStyle, color: "#38bdf8", fontWeight: "bold" }}>
                    {item.serial_number || item.serial || "-"}
                  </td>
                  <td style={tdStyle}>{item.brand || item.brand_name || "-"}</td>
                  <td style={tdStyle}>{item.gas_type || item.gas_type_name || "-"}</td>
                  <td style={tdStyle}>{item.size || item.size_name || "-"}</td>
                  <td style={tdStyle}>{item.manufacture_date || "-"}</td>
                  <td style={tdStyle}>{item.expiry_date || item.expire_date || "-"}</td>
                  <td style={tdStyle}>{item.next_check_date || item.next_check || "-"}</td>
                  <td style={tdStyle}>
                    <span style={badgeStyle("#3b82f6")}>{item.status || "-"}</span>
                  </td>
                  <td style={tdStyle}>{item.current_location || item.location_name || "-"}</td>
                  <td style={{ ...tdStyle, textAlign: "center" }}>
                    <div style={{ display: "inline-flex", gap: "4px" }}>
                      <button type="button" onClick={() => handleShowQR(item)} style={qrButtonStyle}>
                        QR
                      </button>
                      <button type="button" onClick={() => editCylinder(item)} style={editButtonStyle}>
                        แก้ไข
                      </button>
                      <button 
                        type="button" 
                        onClick={() => handleDeleteCylinder(item.serial_number || item.serial)} 
                        style={deleteButtonStyle}
                      >
                        ลบ
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan="10" style={{ ...tdStyle, textAlign: "center", color: "#9ca3af", padding: "20px" }}>
                  ไม่พบข้อมูลถังแก๊สในคลัง
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {/* ตารางที่ 2: จัดส่งสู่บ้านลูกค้า (deliveries) */}
      <div style={{ overflowX: "auto" }}>
        <h3 style={{ color: "#22c55e", marginBottom: "12px", display: "flex", alignItems: "center", gap: "8px" }}>
          รายการถังแก๊สที่กำลังจัดส่ง/จัดส่งสำเร็จ
        </h3>
        <table style={tableStyle}>
          <thead>
            <tr style={{ backgroundColor: "#064e3b" }}>
              <th style={{ ...thStyle, backgroundColor: "#064e3b" }}>Delivery ID</th>
              <th style={{ ...thStyle, backgroundColor: "#064e3b" }}>Serial Number</th>
              <th style={{ ...thStyle, backgroundColor: "#064e3b" }}>ลูกค้า / สถานที่</th>
              <th style={{ ...thStyle, backgroundColor: "#064e3b" }}>ยี่ห้อ</th>
              <th style={{ ...thStyle, backgroundColor: "#064e3b" }}>ชนิด</th>
              <th style={{ ...thStyle, backgroundColor: "#064e3b" }}>ขนาด</th>
              <th style={{ ...thStyle, backgroundColor: "#064e3b" }}>สถานะ</th>
              <th style={{ ...thStyle, backgroundColor: "#064e3b" }}>วันที่จัดส่ง</th>
              <th style={{ ...thStyle, backgroundColor: "#064e3b", textAlign: "center" }}>หลักฐาน</th>
              <th style={{ ...thStyle, backgroundColor: "#064e3b", textAlign: "center" }}>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            {filteredDeliveries.length > 0 ? (
              filteredDeliveries.map((item) => {
                const proofImg = item.proof_image_path || item.proof_image || "";
                
                const deliveryDate = item.created_at || item.delivered_date;
                const isLost = isOver3Years(deliveryDate);

                return (
                  <tr 
                    key={item.delivery_id || item.id}
                    style={isLost ? { backgroundColor: "rgba(239, 68, 68, 0.15)" } : {}}
                  >
                    <td style={{ ...tdStyle, color: "#f59e0b", fontWeight: "bold" }}>
                      #{item.delivery_id || "-"}
                    </td>

                    <td style={tdStyle}>
                      {(() => {
                        const serialVal = item.serial_number || item.serial;
                        if (
                          serialVal &&
                          String(serialVal).trim() !== "" &&
                          String(serialVal) !== "undefined" &&
                          String(serialVal) !== "null"
                        ) {
                          return (
                            <span style={{ color: "#38bdf8", fontWeight: "bold" }}>
                              {serialVal}
                            </span>
                          );
                        }
                        return (
                          <span style={{ color: "#f59e0b", fontSize: "12px", fontStyle: "italic" }}>
                            รอสแกนถัง
                          </span>
                        );
                      })()}
                    </td>

                    <td style={{ ...tdStyle, color: "#4ade80" }}>
                      <div>{item.customer_name || "-"}</div>
                      <div style={{ fontSize: "11px", color: "#9ca3af" }}>{item.address || "-"}</div>
                    </td>
                    <td style={tdStyle}>{item.brand || item.req_brand || "-"}</td>
                    <td style={tdStyle}>{item.gas_type || item.req_gas_type || "-"}</td>
                    <td style={tdStyle}>{item.size || item.req_size || "-"}</td>
                    
                    <td style={tdStyle}>
                      {(() => {
                        if (isLost) {
                          return <span style={badgeStyle("#ef4444")}>สูญหาย</span>;
                        }

                        const rawStatus = (item.status || "").trim().toLowerCase();
                        if (rawStatus === "success") {
                          return <span style={badgeStyle("#22c55e")}>จัดส่งสำเร็จ</span>;
                        }
                        if (rawStatus === "pending") {
                          return <span style={badgeStyle("#f59e0b")}>กำลังจัดส่ง</span>;
                        }
                        return <span style={badgeStyle("#6b7280")}>{item.status || "-"}</span>;
                      })()}
                    </td>

                    <td style={{ ...tdStyle, color: "#38bdf8", fontSize: "12px" }}>
                      {item.created_at || item.delivered_date || "-"}
                    </td>

                    <td style={{ ...tdStyle, textAlign: "center" }}>
                      {proofImg ? (
                        <button
                          type="button"
                          onClick={() => handleShowImage(proofImg, item.delivery_id)}
                          style={viewImageButtonStyle}
                        >
                          📷 ดูรูป
                        </button>
                      ) : (
                        <span style={{ color: "#6b7280", fontSize: "12px" }}>ไม่มีรูป</span>
                      )}
                    </td>

                    <td style={{ ...tdStyle, textAlign: "center" }}>
                      <div style={{ display: "inline-flex", gap: "4px" }}>
                        <button type="button" onClick={() => handleShowQR(item)} style={qrButtonStyle}>
                          QR
                        </button>
                        <button type="button" onClick={() => editCylinder(item)} style={editButtonStyle}>
                          แก้ไข
                        </button>
                        <button 
                          type="button" 
                          onClick={() => handleDeleteDelivery(item.delivery_id)} 
                          style={deleteButtonStyle}
                        >
                          ลบ
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan="10" style={{ ...tdStyle, textAlign: "center", color: "#9ca3af", padding: "20px" }}>
                  ยังไม่มีรายการจัดส่ง
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </Layout>
  );
}

// Styles
const formCardStyle = {
  background: "#1f2937",
  padding: "20px",
  borderRadius: "12px",
  marginBottom: "20px",
};

const formGridStyle = {
  display: "grid",
  gridTemplateColumns: "repeat(auto-fit, minmax(180px, 1fr))",
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
  fontSize: "13px",
};

const manageBtnStyle = {
  background: "none",
  border: "none",
  color: "#60a5fa",
  fontSize: "11px",
  cursor: "pointer",
  padding: 0,
};

const primaryButtonStyle = {
  padding: "8px 16px",
  border: "none",
  borderRadius: "8px",
  background: "#2563eb",
  color: "white",
  cursor: "pointer",
  fontWeight: "bold",
};

const secondaryButtonStyle = {
  padding: "8px 16px",
  border: "none",
  borderRadius: "8px",
  background: "#6b7280",
  color: "white",
  cursor: "pointer",
};

const viewImageButtonStyle = {
  padding: "4px 10px",
  border: "1px solid #3b82f6",
  borderRadius: "6px",
  background: "#1e3a8a",
  color: "#60a5fa",
  cursor: "pointer",
  fontSize: "12px",
  fontWeight: "500",
};

const qrButtonStyle = {
  padding: "4px 8px",
  border: "none",
  borderRadius: "4px",
  background: "#10b981",
  color: "white",
  cursor: "pointer",
  fontSize: "12px",
};

const editButtonStyle = {
  padding: "4px 8px",
  border: "none",
  borderRadius: "4px",
  background: "#f59e0b",
  color: "white",
  cursor: "pointer",
  fontSize: "12px",
};

const deleteButtonStyle = {
  padding: "4px 8px",
  border: "none",
  borderRadius: "4px",
  background: "#ef4444",
  color: "white",
  cursor: "pointer",
  fontSize: "12px",
};

const badgeStyle = (bgColor) => ({
  display: "inline-block",
  padding: "2px 8px",
  borderRadius: "12px",
  backgroundColor: bgColor,
  color: "white",
  fontSize: "11px",
  fontWeight: "bold",
});

const modalOverlayStyle = {
  position: "fixed",
  top: 0,
  left: 0,
  right: 0,
  bottom: 0,
  backgroundColor: "rgba(0, 0, 0, 0.7)",
  display: "flex",
  justifyContent: "center",
  alignItems: "center",
  zIndex: 1000,
};

const modalContentStyle = {
  background: "#1f2937",
  padding: "20px",
  borderRadius: "12px",
  width: "350px",
  boxShadow: "0 4px 6px rgba(0,0,0,0.3)",
};

const modalListItemStyle = {
  display: "flex",
  justifyContent: "space-between",
  alignItems: "center",
  padding: "8px 0",
  borderBottom: "1px solid #374151",
};

const tableStyle = {
  width: "100%",
  borderCollapse: "collapse",
  background: "#1f2937",
  color: "white",
  borderRadius: "12px",
  fontSize: "13px",
  whiteSpace: "nowrap",
};

const thStyle = {
  padding: "10px 12px",
  textAlign: "left",
  borderBottom: "1px solid #374151",
  backgroundColor: "#2d3a4a",
  fontWeight: "600",
  color: "#e5e7eb",
};

const tdStyle = {
  padding: "8px 12px",
  borderBottom: "1px solid #374151",
  verticalAlign: "middle",
};

export default GasPage;