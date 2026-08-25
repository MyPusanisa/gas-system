import { useState, useEffect } from "react";
import Layout from "../components/Layout";
import { QRCodeSVG } from "qrcode.react";
import { fetchAPI } from "../services/api"; // 👈 นำ fetchAPI มาใช้งาน

function GasPage({ cylinders = [], setCylinders, deliveries }) {
  const [newSerialNumber, setNewSerialNumber] = useState("");
  const [newBrand, setNewBrand] = useState("");
  const [newGasType, setNewGasType] = useState("");
  const [newSize, setNewSize] = useState("");
  const [newManufactureDate, setNewManufactureDate] = useState("");
  const [newExpiryDate, setNewExpiryDate] = useState("");
  const [newLastCheckDate, setNewLastCheckDate] = useState("");
  const [newNextCheckDate, setNewNextCheckDate] = useState("");
  const [newDeliveredDate, setNewDeliveredDate] = useState("");
  const [newCurrentLocation, setNewCurrentLocation] = useState("");
  const [newStatus, setNewStatus] = useState("ในคลัง");
  const [editingSerial, setEditingSerial] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");

  // Dynamic Options State
  const [gasBrands, setGasBrands] = useState([]);
  const [gasTypes, setGasTypes] = useState([]);
  const [gasSizes, setGasSizes] = useState([]);
  const [gasLocations, setGasLocations] = useState([]);
  const [gasStatuses, setGasStatuses] = useState([]);

  // Modal State
  const [showOptionModal, setShowOptionModal] = useState(false);
  const [modalTarget, setModalTarget] = useState("brand"); 
  const [newOptionValue, setNewOptionValue] = useState("");
  const [selectedQR, setSelectedQR] = useState(null);

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

  // ดึงข้อมูลถังแก๊สทั้งหมด
  const fetchCylinders = async () => {
    const json = await fetchAPI("/get_cylinders.php");
    if (json.success) {
      setCylinders(json.data);
    } else {
      alert(json.message || "โหลดข้อมูลไม่สำเร็จ");
    }
  };

  // ดึงข้อมูลตัวเลือกทั้งหมดจาก Database
  const fetchOptions = async () => {
    const json = await fetchAPI("/gas/manage_options.php");
    if (json.success) {
      setGasBrands(json.data.brands || []);
      setGasTypes(json.data.types || []);
      setGasSizes(json.data.sizes || []);
      setGasLocations(json.data.locations || []);
      setGasStatuses(json.data.statuses || []);
    }
  };

  useEffect(() => {
    fetchCylinders();
    fetchOptions();
  }, []);

  const clearForm = () => {
    setNewSerialNumber("");
    setNewBrand("");
    setNewGasType("");
    setNewSize("");
    setNewManufactureDate("");
    setNewExpiryDate("");
    setNewLastCheckDate("");
    setNewNextCheckDate("");
    setNewDeliveredDate("");
    setNewCurrentLocation("");
    setNewStatus("ในคลัง");
    setEditingSerial(null);
  };

  const addOrUpdateCylinder = async () => {
    if (!newSerialNumber || !newBrand || !newGasType || !newSize || !newManufactureDate) {
      alert("กรุณากรอกข้อมูลที่จำเป็นให้ครบ (Serial Number, ยี่ห้อ, ชนิด, ขนาด, วันที่ผลิต)");
      return;
    }

    const expiryDate = newExpiryDate || calculateExpiryDate(newManufactureDate);
    const nextCheckDate = newNextCheckDate || calculateNextCheckDate(newManufactureDate);
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

    const endpoint = editingSerial ? "/update_cylinder.php" : "/add_cylinder.php";
    const result = await fetchAPI(endpoint, {
      method: "POST",
      body: JSON.stringify(cylinderData),
    });

    if (result.success) {
      await fetchCylinders();
      clearForm();
      alert(result.message || "บันทึกข้อมูลเรียบร้อย");
    } else {
      alert(result.message || "เกิดข้อผิดพลาดในการบันทึก");
    }
  };

  const deleteCylinder = async (serialNumber) => {
    if (!window.confirm(`ยืนยันลบถัง Serial: ${serialNumber}?`)) return;

    const result = await fetchAPI("/delete_cylinder.php", {
      method: "POST",
      body: JSON.stringify({ serial_number: serialNumber }),
    });

    if (result.success) {
      await fetchCylinders();
      if (editingSerial === serialNumber) clearForm();
      alert(result.message || "ลบข้อมูลเรียบร้อย");
    } else {
      alert(result.message || "ลบไม่สำเร็จ");
    }
  };

  const editCylinder = (item) => {
    setNewSerialNumber(item.serial_number);
    setNewBrand(item.brand || "");
    setNewGasType(item.gas_type || "");
    setNewSize(item.size || "");
    setNewManufactureDate(item.manufacture_date || "");
    setNewExpiryDate(item.expiry_date || "");
    setNewLastCheckDate(item.last_check_date || "");
    setNewNextCheckDate(item.next_check_date || "");
    setNewDeliveredDate(item.delivered_date || "");
    setNewCurrentLocation(item.current_location || "");
    setNewStatus(item.status || "ในคลัง");
    setEditingSerial(item.serial_number);
  };

  const openOptionModal = (target) => {
    setModalTarget(target);
    setNewOptionValue("");
    setShowOptionModal(true);
  };

  const handleAddOption = async () => {
    if (!newOptionValue.trim()) return alert("กรุณากรอกข้อมูล");

    const data = await fetchAPI("/gas/manage_options.php", {
      method: "POST",
      body: JSON.stringify({
        action: "add",
        target: modalTarget,
        value: newOptionValue.trim(),
      }),
    });

    if (data.success) {
      setNewOptionValue("");
      await fetchOptions();
    } else {
      alert(data.message || "เกิดข้อผิดพลาดในการเพิ่มข้อมูล");
    }
  };

  const handleDeleteOption = async (id, name) => {
    if (!window.confirm(`ยืนยันลบ "${name}" ออกจากรายการตัวเลือก?`)) return;

    const data = await fetchAPI("/gas/manage_options.php", {
      method: "POST",
      body: JSON.stringify({
        action: "delete",
        target: modalTarget,
        id: id,
      }),
    });

    if (data.success) {
      await fetchOptions();
    } else {
      alert(data.message || "เกิดข้อผิดพลาด");
    }
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

  const getModalTitle = () => {
    if (modalTarget === "brand") return "ยี่ห้อ";
    if (modalTarget === "type") return "ชนิดแก๊ส";
    if (modalTarget === "size") return "ขนาดถัง";
    if (modalTarget === "status") return "สถานะ";
    return "สถานที่";
  };

  const getCurrentOptionsList = () => {
    if (modalTarget === "brand") return gasBrands.map(i => ({ id: i.id, name: i.brand_name || i.name }));
    if (modalTarget === "type") return gasTypes.map(i => ({ id: i.id, name: i.type_name || i.name }));
    if (modalTarget === "size") return gasSizes.map(i => ({ id: i.id, name: i.size_name || i.name }));
    if (modalTarget === "status") return gasStatuses.map(i => ({ id: i.id, name: i.status_name || i.name }));
    return gasLocations.map(i => ({ id: i.id, name: i.location_name || i.name }));
  };

  return (
    <Layout
      gasLevel={610}
      maintenanceDueItems={dueForMaintenance}
      expiredCylinderItems={expiredCylinders}
      deliverySuccessItems={[]}
      newAssignedJobItems={[]}
    >
      <h1 style={{ marginBottom: "20px", color: "white" }}>จัดการถังแก๊ส 🛢</h1>

      {/* ฟอร์มเพิ่ม/แก้ไข */}
      <div style={formGridStyle}>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>Serial Number *</label>
          <input 
            value={newSerialNumber} 
            onChange={(e) => setNewSerialNumber(e.target.value)} 
            style={inputStyle} 
            placeholder="SN-001" 
            disabled={!!editingSerial}
          />
        </div>

        {/* Select ยี่ห้อ */}
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>ยี่ห้อ *</label>
          <div style={{ display: "flex", gap: "6px" }}>
            <select value={newBrand} onChange={(e) => setNewBrand(e.target.value)} style={inputStyle}>
              <option value="">-- เลือกยี่ห้อ --</option>
              {gasBrands.map((item) => {
                const name = item.brand_name || item.name;
                return (
                  <option key={item.id} value={name}>
                    {name}
                  </option>
                );
              })}
            </select>
            <button type="button" onClick={() => openOptionModal("brand")} style={gearBtnStyle} title="ตั้งค่ายี่ห้อ">⚙️</button>
          </div>
        </div>

        {/* Select ชนิดแก๊ส */}
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>ชนิดแก๊ส *</label>
          <div style={{ display: "flex", gap: "6px" }}>
            <select value={newGasType} onChange={(e) => setNewGasType(e.target.value)} style={inputStyle}>
              <option value="">-- เลือกชนิด --</option>
              {gasTypes.map((item) => {
                const name = item.type_name || item.name;
                return (
                  <option key={item.id} value={name}>
                    {name}
                  </option>
                );
              })}
            </select>
            <button type="button" onClick={() => openOptionModal("type")} style={gearBtnStyle} title="ตั้งค่าชนิดแก๊ส">⚙️</button>
          </div>
        </div>

        {/* Select ขนาดถัง */}
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>ขนาดถัง *</label>
          <div style={{ display: "flex", gap: "6px" }}>
            <select value={newSize} onChange={(e) => setNewSize(e.target.value)} style={inputStyle}>
              <option value="">-- เลือกขนาด --</option>
              {gasSizes.map((item) => {
                const name = item.size_name || item.name;
                return (
                  <option key={item.id} value={name}>
                    {name}
                  </option>
                );
              })}
            </select>
            <button type="button" onClick={() => openOptionModal("size")} style={gearBtnStyle} title="ตั้งค่าขนาดถัง">⚙️</button>
          </div>
        </div>

        <div style={fieldGroupStyle}>
          <label style={labelStyle}>วันที่ผลิต *</label>
          <input type="date" value={newManufactureDate} onChange={(e) => setNewManufactureDate(e.target.value)} style={inputStyle} />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>วันหมดอายุ (คำนวณอัตโนมัติ)</label>
          <input type="date" value={newExpiryDate || calculateExpiryDate(newManufactureDate)} readOnly style={{ ...inputStyle, background: "#e5e7eb", color: "#333" }} />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>วันที่ตรวจล่าสุด</label>
          <input type="date" value={newLastCheckDate} onChange={(e) => setNewLastCheckDate(e.target.value)} style={inputStyle} />
        </div>
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>วันที่ตรวจครั้งถัดไป (คำนวณ)</label>
          <input type="date" value={newNextCheckDate || calculateNextCheckDate(newManufactureDate)} readOnly style={{ ...inputStyle, background: "#e5e7eb", color: "#333" }} />
        </div>

        {editingSerial && (
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>วันที่ส่งมอบให้ลูกค้า</label>
            <input type="date" value={newDeliveredDate} onChange={(e) => setNewDeliveredDate(e.target.value)} style={inputStyle} />
          </div>
        )}

        {/* Select สถานที่ปัจจุบัน */}
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>สถานที่ปัจจุบัน</label>
          <div style={{ display: "flex", gap: "6px" }}>
            <select value={newCurrentLocation} onChange={(e) => setNewCurrentLocation(e.target.value)} style={inputStyle}>
              <option value="">-- เลือกสถานที่ --</option>
              {gasLocations.map((item) => {
                const name = item.location_name || item.name;
                return (
                  <option key={item.id} value={name}>
                    {name}
                  </option>
                );
              })}
            </select>
            <button type="button" onClick={() => openOptionModal("location")} style={gearBtnStyle} title="ตั้งค่าสถานที่">⚙️</button>
          </div>
        </div>

        {/* Select สถานะ */}
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>{editingSerial ? "สถานะ" : "สถานะเริ่มต้น"}</label>
          <div style={{ display: "flex", gap: "6px" }}>
            <select value={newStatus} onChange={(e) => setNewStatus(e.target.value)} style={inputStyle}>
              <option value="">-- เลือกสถานะ --</option>
              {gasStatuses.map((item) => {
                const name = item.status_name || item.name;
                return (
                  <option key={item.id} value={name}>
                    {name}
                  </option>
                );
              })}
            </select>
            <button type="button" onClick={() => openOptionModal("status")} style={gearBtnStyle} title="ตั้งค่าสถานะ">⚙️</button>
          </div>
        </div>
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

      {/* ตารางแสดงผล */}
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

      {/* Pop-up QR Code */}
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
              <button onClick={() => window.print()} style={printButtonStyle}>🖨️ สั่งพิมพ์</button>
              <button onClick={() => setSelectedQR(null)} style={closeButtonStyle}>ปิด</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal จัดการตัวเลือก */}
      {showOptionModal && (
        <div style={modalOverlayStyle}>
          <div style={{ ...modalContentStyle, width: "360px", textAlign: "left" }}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "15px" }}>
              <h3 style={{ margin: 0, color: "white", fontSize: "16px" }}>
                ⚙️ จัดการ{getModalTitle()}
              </h3>
              <button onClick={() => setShowOptionModal(false)} style={closeButtonStyle}>✕</button>
            </div>

            <div style={{ display: "flex", gap: "8px", marginBottom: "15px" }}>
              <input
                type="text"
                placeholder={`กรอก${getModalTitle()}ใหม่...`}
                value={newOptionValue}
                onChange={(e) => setNewOptionValue(e.target.value)}
                style={inputStyle}
              />
              <button onClick={handleAddOption} style={primaryButtonStyle}>➕ เพิ่ม</button>
            </div>

            <div style={{ maxHeight: "200px", overflowY: "auto" }}>
              <ul style={{ listStyle: "none", padding: 0, margin: 0 }}>
                {getCurrentOptionsList().map((item) => (
                  <li
                    key={item.id}
                    style={{
                      display: "flex",
                      justifyContent: "space-between",
                      alignItems: "center",
                      padding: "8px 10px",
                      background: "#111827",
                      borderRadius: "6px",
                      marginBottom: "6px",
                      color: "white",
                    }}
                  >
                    <span>{item.name}</span>
                    <button
                      onClick={() => handleDeleteOption(item.id, item.name)}
                      style={deleteButtonStyle}
                    >
                      ❌ ลบ
                    </button>
                  </li>
                ))}
              </ul>
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
const inputStyle = { padding: "10px", borderRadius: "8px", border: "1px solid #4b5563", background: "#111827", color: "white", width: "100%", boxSizing: "border-box" };
const searchInputStyle = { padding: "10px", borderRadius: "8px", border: "1px solid #4b5563", background: "#111827", color: "white", width: "100%", boxSizing: "border-box" };
const tableStyle = { width: "100%", borderCollapse: "collapse", background: "#1f2937", color: "white", borderRadius: "12px", overflow: "hidden" };
const thStyle = { padding: "10px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px", whiteSpace: "nowrap" };
const tdStyle = { padding: "10px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px", wordBreak: "break-word" };
const primaryButtonStyle = { marginRight: "10px", padding: "10px 14px", border: "none", borderRadius: "8px", background: "#2563eb", color: "white", cursor: "pointer", fontWeight: "bold" };
const secondaryButtonStyle = { padding: "10px 14px", border: "none", borderRadius: "8px", background: "#6b7280", color: "white", cursor: "pointer" };
const editButtonStyle = { marginRight: "8px", padding: "6px 10px", border: "none", borderRadius: "6px", background: "#f59e0b", color: "white", cursor: "pointer" };
const deleteButtonStyle = { padding: "6px 10px", border: "none", borderRadius: "6px", background: "#dc2626", color: "white", cursor: "pointer" };

const gearBtnStyle = { padding: "8px 12px", borderRadius: "8px", border: "none", background: "#374151", color: "white", cursor: "pointer", fontSize: "14px" };
const qrButtonStyle = { marginRight: "8px", padding: "6px 10px", border: "none", borderRadius: "6px", background: "#059669", color: "white", cursor: "pointer", fontWeight: "bold" };
const printButtonStyle = { padding: "8px 14px", border: "none", borderRadius: "8px", background: "#2563eb", color: "white", cursor: "pointer", fontWeight: "bold" };
const closeButtonStyle = { padding: "8px 14px", border: "none", borderRadius: "8px", background: "#4b5563", color: "white", cursor: "pointer" };
const modalOverlayStyle = { position: "fixed", top: 0, left: 0, right: 0, bottom: 0, backgroundColor: "rgba(0, 0, 0, 0.6)", display: "flex", justifyContent: "center", alignItems: "center", zIndex: 999 };
const modalContentStyle = { background: "#1f2937", padding: "24px", borderRadius: "16px", textAlign: "center", boxShadow: "0 20px 25px -5px rgba(0, 0, 0, 0.5)", border: "1px solid #374151", width: "300px" };

export default GasPage;