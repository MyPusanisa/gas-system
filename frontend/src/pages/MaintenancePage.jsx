import { useMemo, useState, useEffect } from "react";
import Layout from "../components/Layout";

function MaintenancePage({
  cylinders: propCylinders,
  setCylinders: propSetCylinders,
}) {
  const [allCylinders, setAllCylinders] = useState([]);
  const [maintenances, setMaintenances] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  // --- Dynamic Option Lists ---
  const [typeOptions, setTypeOptions] = useState(["ตรวจสภาพ", "บำรุงรักษา", "ซ่อมแซม"]);
  const [resultOptions, setResultOptions] = useState(["ผ่าน", "ไม่ผ่าน", "รอผลตรวจ"]);
  const [actionOptions, setActionOptions] = useState([
    "ใช้งานต่อได้ (ปกติ)",
    "สมควรบำรุงรักษาต่อ",
    "ส่งซ่อมแซมด่วน",
    "ส่งทดสอบ Hydrostatic",
    "ปลดตระกูล / จำหน่ายออก",
  ]);
  const [noteOptions, setNoteOptions] = useState([
    "สภาพสมบูรณ์ พร้อมใช้งาน",
    "วาล์วชำรุด สมควรเปลี่ยนวาล์ว",
    "ตัวถังมีรอยบุบ/สนิม ต้องบำรุงรักษา",
    "ส่งทดสอบแรงดันน้ำ (Hydrostatic Test)",
    "หมดอายุการใช้งาน สมควรคัดทิ้ง",
  ]);

  // --- Form States ---
  const [selectedSerialNumber, setSelectedSerialNumber] = useState("");
  const [maintenanceType, setMaintenanceType] = useState("");
  const [result, setResult] = useState("");
  const [nextAction, setNextAction] = useState("");
  const [selectedNote, setSelectedNote] = useState("");
  const [description, setDescription] = useState("");

  // --- Search States ---
  const [dueSearchTerm, setDueSearchTerm] = useState("");
  const [historySearchTerm, setHistorySearchTerm] = useState("");

  // --- Modal Management State ---
  const [activeModal, setActiveModal] = useState(null); // 'type' | 'result' | 'action' | 'note' | null
  const [newItemInput, setNewItemInput] = useState("");

  // --- Edit Modal State ---
  const [editingItem, setEditingItem] = useState(null);
  const [editSerial, setEditSerial] = useState("");
  const [editType, setEditType] = useState("");
  const [editResult, setEditResult] = useState("");
  const [editNextAction, setEditNextAction] = useState("");
  const [editDesc, setEditDesc] = useState("");

  const todayStr = new Date().toISOString().split("T")[0];
  const todayDate = new Date(todayStr);

  const parseLocalDate = (dateStr) => {
    if (!dateStr || typeof dateStr !== "string") return null;
    const parts = dateStr.split("-");
    if (parts.length !== 3) return null;
    const [year, month, day] = parts;
    return new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
  };

  const fetchMaintenances = async () => {
    try {
      const res = await fetch("http://localhost/Backend/models/get_maintenance.php");
      const data = await res.json();
      if (data.success && Array.isArray(data.data)) {
        setMaintenances(data.data);
      } else {
        setMaintenances([]);
      }
    } catch (err) {
      console.error("fetchMaintenances Error:", err);
      setMaintenances([]);
    }
  };

  const fetchCylinders = async () => {
    try {
      setLoading(true);
      const res = await fetch("http://localhost/Backend/models/get_due_cylinders.php");
      const data = await res.json();
      if (data.success && Array.isArray(data.data)) {
        const enrichedData = data.data.map((item) => ({
          ...item,
          serial_number: item.serial_number || item.cylinder_id || "-",
          gas_type: item.gas_type || "LPG",
          current_location: item.current_location || "คลัง",
          next_check_date: item.next_check_date || null,
          status: item.status || "ปกติ",
        }));
        setAllCylinders(enrichedData);
        if (propSetCylinders) propSetCylinders(enrichedData);
      } else {
        setAllCylinders([]);
      }
    } catch (err) {
      console.error("fetchCylinders Error:", err);
      setAllCylinders([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCylinders();
    fetchMaintenances();
  }, []);

  const dueCylinders = useMemo(() => {
    if (!Array.isArray(allCylinders)) return [];
    return allCylinders.filter((item) => {
      if (!item || !item.next_check_date) return false;
      const dueDate = parseLocalDate(item.next_check_date);
      return dueDate && dueDate <= todayDate;
    });
  }, [allCylinders, todayDate]);

  const getDueStatus = (nextCheckDateStr) => {
    if (!nextCheckDateStr) return "ไม่มีกำหนด";
    const dueDate = parseLocalDate(nextCheckDateStr);
    if (!dueDate) return "รูปแบบผิด";
    if (dueDate < todayDate) return "เลยกำหนด";
    if (dueDate.getTime() === todayDate.getTime()) return "ถึงกำหนดวันนี้";
    return "ใกล้ถึงกำหนด";
  };

  // --- Handlers จัดการ เพิ่ม/ลบ ตัวเลือก ---
  const getCurrentModalList = () => {
    if (activeModal === "type") return typeOptions;
    if (activeModal === "result") return resultOptions;
    if (activeModal === "action") return actionOptions;
    if (activeModal === "note") return noteOptions;
    return [];
  };

  const handleAddItem = () => {
    const text = newItemInput.trim();
    if (!text) return;
    const currentList = getCurrentModalList();
    if (currentList.includes(text)) {
      alert("มีตัวเลือกนี้อยู่แล้ว");
      return;
    }

    if (activeModal === "type") setTypeOptions([...typeOptions, text]);
    if (activeModal === "result") setResultOptions([...resultOptions, text]);
    if (activeModal === "action") setActionOptions([...actionOptions, text]);
    if (activeModal === "note") setNoteOptions([...noteOptions, text]);

    setNewItemInput("");
  };

  const handleRemoveItem = (indexToRemove) => {
    if (activeModal === "type") setTypeOptions(typeOptions.filter((_, i) => i !== indexToRemove));
    if (activeModal === "result") setResultOptions(resultOptions.filter((_, i) => i !== indexToRemove));
    if (activeModal === "action") setActionOptions(actionOptions.filter((_, i) => i !== indexToRemove));
    if (activeModal === "note") setNoteOptions(noteOptions.filter((_, i) => i !== indexToRemove));
  };

  const getModalTitle = () => {
    if (activeModal === "type") return "จัดการประเภทการตรวจ/บำรุง";
    if (activeModal === "result") return "จัดการผลการตรวจ";
    if (activeModal === "action") return "จัดการสิ่งที่ต้องทำต่อ";
    if (activeModal === "note") return "จัดการหมายเหตุสำเร็จรูป";
    return "";
  };

  // --- 🔍 Search Filters ---
  const filteredDueCylinders = useMemo(() => {
    if (!Array.isArray(dueCylinders)) return [];
    return dueCylinders.filter((item) => {
      if (!item) return false;
      const statusText = getDueStatus(item.next_check_date);
      const searchText = [
        item.serial_number,
        item.brand,
        item.size,
        item.next_check_date,
        statusText,
        item.status,
      ]
        .filter((val) => val !== null && val !== undefined)
        .map((val) => String(val))
        .join(" ")
        .toLowerCase();

      return searchText.includes((dueSearchTerm || "").toLowerCase());
    });
  }, [dueCylinders, dueSearchTerm]);

  const filteredMaintenances = useMemo(() => {
    if (!Array.isArray(maintenances)) return [];
    return maintenances.filter((item) => {
      if (!item) return false;
      const searchText = [
        item.maintenance_id,
        item.serial_number || item.cylinder_id,
        item.maintenance_date,
        item.maintenance_type,
        item.result,
        item.next_action,
        item.next_check_date || item.next_maintenance_date,
        item.description,
      ]
        .filter((val) => val !== null && val !== undefined)
        .map((val) => String(val))
        .join(" ")
        .toLowerCase();

      return searchText.includes((historySearchTerm || "").toLowerCase());
    });
  }, [maintenances, historySearchTerm]);

  const saveMaintenance = async () => {
    if (!selectedSerialNumber || !maintenanceType || !result) {
      alert("กรุณาเลือกถัง ประเภทการตรวจ และผลการตรวจให้ครบถ้วน");
      return;
    }

    setSaving(true);
    const fullDescription = [selectedNote, description].filter(Boolean).join(" | ");

    const payload = {
      serial_number: selectedSerialNumber,
      maintenance_type: maintenanceType,
      result: result,
      next_action: nextAction,
      description: fullDescription,
    };

    try {
      const res = await fetch("http://localhost/Backend/models/save_maintenance.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (data.success) {
        alert("บันทึกผลตรวจเรียบร้อยแล้ว");
        setSelectedSerialNumber("");
        setMaintenanceType("");
        setResult("");
        setNextAction("");
        setSelectedNote("");
        setDescription("");

        await fetchCylinders();
        await fetchMaintenances();
      } else {
        alert("บันทึกไม่สำเร็จ: " + data.message);
      }
    } catch (err) {
      console.error("Save Error:", err);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์");
    } finally {
      setSaving(false);
    }
  };

  const handleEditClick = (item) => {
    setEditingItem(item);
    setEditSerial(item.serial_number || item.cylinder_id || "");
    setEditType(item.maintenance_type || "");
    setEditResult(item.result || "");
    setEditNextAction(item.next_action || "");
    setEditDesc(item.description || "");
  };

  const handleUpdate = async () => {
    if (!editSerial || !editType || !editResult) {
      alert("กรุณากรอกข้อมูลให้ครบถ้วน");
      return;
    }

    try {
      const res = await fetch("http://localhost/Backend/models/update_maintenance.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          maintenance_id: editingItem.maintenance_id,
          serial_number: editSerial,
          maintenance_type: editType,
          result: editResult,
          next_action: editNextAction,
          description: editDesc,
        }),
      });

      const data = await res.json();
      if (data.success) {
        alert("แก้ไขประวัติสำเร็จ");
        setEditingItem(null);
        fetchMaintenances();
      } else {
        alert("แก้ไขไม่สำเร็จ: " + data.message);
      }
    } catch (err) {
      console.error("Update error:", err);
      alert("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
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
      <h1 style={{ marginBottom: "20px", color: "white" }}>
        ตรวจสภาพและบำรุงรักษา 🔧
      </h1>

      <div style={summaryRowStyle}>
        <div style={summaryCardStyle}>
          <h3>ถังที่ถึงกำหนดตรวจ</h3>
          <p style={summaryNumberStyle}>{dueCylinders.length}</p>
        </div>
        <div style={summaryCardStyle}>
          <h3>ประวัติการตรวจทั้งหมด</h3>
          <p style={summaryNumberStyle}>{maintenances.length}</p>
        </div>
      </div>

      <div style={formCardStyle}>
        <h2 style={{ marginTop: 0, color: "white" }}>บันทึกผลตรวจ</h2>
        <div style={formGridStyle}>
          
          {/* เลือกถังแก๊ส */}
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>เลือกถังที่ตรวจ (ถึงกำหนดตรวจ) *</label>
            <select
              value={selectedSerialNumber}
              onChange={(e) => setSelectedSerialNumber(e.target.value)}
              style={inputStyle}
            >
              <option value="">-- เลือกถังที่ถึงกำหนดตรวจ --</option>
              {dueCylinders.map((cyl) => (
                <option key={cyl.serial_number} value={cyl.serial_number}>
                  {cyl.serial_number} - {cyl.brand || "LPG"} ({cyl.size}) [กำหนดตรวจ: {cyl.next_check_date}]
                </option>
              ))}
            </select>
          </div>

          {/* ประเภทการตรวจ/บำรุง + ปุ่มเฟือง */}
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ประเภทการตรวจ/บำรุง *</label>
            <div style={inputWithBtnStyle}>
              <select
                value={maintenanceType}
                onChange={(e) => setMaintenanceType(e.target.value)}
                style={inputStyle}
              >
                <option value="">-- เลือกประเภท --</option>
                {typeOptions.map((opt, i) => (
                  <option key={i} value={opt}>{opt}</option>
                ))}
              </select>
              <button 
                type="button" 
                onClick={() => setActiveModal("type")} 
                style={iconButtonStyle}
                title="จัดการประเภท"
              >
                ⚙️
              </button>
            </div>
          </div>

          {/* ผลการตรวจ + ปุ่มเฟือง */}
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ผลการตรวจ *</label>
            <div style={inputWithBtnStyle}>
              <select
                value={result}
                onChange={(e) => setResult(e.target.value)}
                style={inputStyle}
              >
                <option value="">-- เลือกผลตรวจ --</option>
                {resultOptions.map((opt, i) => (
                  <option key={i} value={opt}>{opt}</option>
                ))}
              </select>
              <button 
                type="button" 
                onClick={() => setActiveModal("result")} 
                style={iconButtonStyle}
                title="จัดการผลตรวจ"
              >
                ⚙️
              </button>
            </div>
          </div>

          {/* สิ่งที่ต้องทำต่อ + ปุ่มเฟือง */}
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>สิ่งที่ต้องทำต่อ 🔄</label>
            <div style={inputWithBtnStyle}>
              <select
                value={nextAction}
                onChange={(e) => setNextAction(e.target.value)}
                style={inputStyle}
              >
                <option value="">-- เลือกสิ่งที่ต้องทำต่อ --</option>
                {actionOptions.map((opt, i) => (
                  <option key={i} value={opt}>{opt}</option>
                ))}
              </select>
              <button 
                type="button" 
                onClick={() => setActiveModal("action")} 
                style={iconButtonStyle}
                title="จัดการสิ่งที่ต้องทำต่อ"
              >
                ⚙️
              </button>
            </div>
          </div>

          {/* เลือกหมายเหตุสำเร็จรูป + ปุ่มเฟือง */}
          <div style={{ ...fieldGroupStyle, gridColumn: "1 / -1" }}>
            <label style={labelStyle}>เลือกหมายเหตุสำเร็จรูป 🏷️</label>
            <div style={inputWithBtnStyle}>
              <select
                value={selectedNote}
                onChange={(e) => setSelectedNote(e.target.value)}
                style={inputStyle}
              >
                <option value="">-- เลือกหมายเหตุประเมิน --</option>
                {noteOptions.map((opt, i) => (
                  <option key={i} value={opt}>{opt}</option>
                ))}
              </select>
              <button 
                type="button" 
                onClick={() => setActiveModal("note")} 
                style={iconButtonStyle}
                title="จัดการหมายเหตุ"
              >
                ⚙️
              </button>
            </div>
          </div>

          {/* รายละเอียดเพิ่มเติม */}
          <div style={fieldGroupStyleFull}>
            <label style={labelStyle}>รายละเอียดเพิ่มเติม</label>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              style={textAreaStyle}
              placeholder="รายละเอียดการตรวจหรือข้อสังเกตเพิ่มเติม (ถ้ามี)"
              rows="3"
            />
          </div>
        </div>
        <button onClick={saveMaintenance} style={primaryButtonStyle} disabled={saving}>
          {saving ? "กำลังบันทึก..." : "💾 บันทึกผลตรวจ"}
        </button>
      </div>

      {/* 🔍 ค้นหาตารางบน */}
      <div style={{ marginBottom: "16px" }}>
        <input
          type="text"
          placeholder="🔍 ค้นหาถังที่ถึงกำหนดตรวจ..."
          value={dueSearchTerm}
          onChange={(e) => setDueSearchTerm(e.target.value)}
          style={searchInputStyle}
        />
      </div>

      <div style={{ overflowX: "auto", marginBottom: "32px" }}>
        <table style={tableStyle}>
          <thead>
            <tr>
              <th style={thStyle}>Serial Number</th>
              <th style={thStyle}>ยี่ห้อ</th>
              <th style={thStyle}>ขนาด</th>
              <th style={thStyle}>วันตรวจครั้งถัดไป</th>
              <th style={thStyle}>สถานะกำหนด</th>
              <th style={thStyle}>สถานะปัจจุบัน</th>
            </tr>
          </thead>
          <tbody>
            {filteredDueCylinders.length > 0 ? (
              filteredDueCylinders.map((item) => (
                <tr key={item.serial_number}>
                  <td style={tdStyle}><strong>{item.serial_number}</strong></td>
                  <td style={tdStyle}>{item.brand || "-"}</td>
                  <td style={tdStyle}>{item.size || "-"}</td>
                  <td style={tdStyle}>{item.next_check_date || "-"}</td>
                  <td style={tdStyle}>
                    <span style={{
                      ...badgeStyle,
                      ...(getDueStatus(item.next_check_date) === "เลยกำหนด" ? overdueStyle :
                         getDueStatus(item.next_check_date) === "ถึงกำหนดวันนี้" ? dueTodayStyle : normalStyle),
                    }}>
                      {getDueStatus(item.next_check_date)}
                    </span>
                  </td>
                  <td style={tdStyle}>{item.status || "-"}</td>
                </tr>
              ))
            ) : (
              <tr><td style={tdStyle} colSpan="6" align="center">ไม่พบข้อมูลที่ค้นหาในถังที่ถึงกำหนดตรวจ</td></tr>
            )}
          </tbody>
        </table>
      </div>

      <h2 style={{ color: "white", marginBottom: "16px" }}>ประวัติการตรวจล่าสุด</h2>
      
      <div style={{ marginBottom: "16px" }}>
        <input
          type="text"
          placeholder="🔍 ค้นหาประวัติการตรวจ..."
          value={historySearchTerm}
          onChange={(e) => setHistorySearchTerm(e.target.value)}
          style={searchInputStyle}
        />
      </div>

      <div style={{ overflowX: "auto" }}>
        <table style={tableStyle}>
          <thead>
            <tr>
              <th style={thStyle}>ID</th>
              <th style={thStyle}>Serial Number</th>
              <th style={thStyle}>วันที่ตรวจ</th>
              <th style={thStyle}>ประเภท</th>
              <th style={thStyle}>ผลตรวจ</th>
              <th style={thStyle}>สิ่งที่ต้องทำต่อ</th>
              <th style={thStyle}>วันตรวจครั้งถัดไป</th>
              <th style={thStyle}>รายละเอียด/หมายเหตุ</th>
              <th style={thStyle}>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            {filteredMaintenances.length > 0 ? (
              filteredMaintenances.map((item, index) => (
                <tr key={item.maintenance_id ? `${item.maintenance_id}-${index}` : index}>
                  <td style={tdStyle}>{item.maintenance_id}</td>
                  <td style={tdStyle}><strong>{item.serial_number || item.cylinder_id || "-"}</strong></td>
                  <td style={tdStyle}>{item.maintenance_date || "-"}</td>
                  <td style={tdStyle}>{item.maintenance_type || "-"}</td>
                  <td style={tdStyle}>{item.result || "-"}</td>
                  <td style={tdStyle}><span style={actionBadgeStyle}>{item.next_action || "-"}</span></td>
                  <td style={tdStyle}>{item.next_check_date || item.next_maintenance_date || "-"}</td>
                  <td style={tdStyle}>{item.description || "-"}</td>
                  <td style={tdStyle}>
                    <button onClick={() => handleEditClick(item)} style={editButtonStyle}>
                      ✏️ แก้ไข
                    </button>
                  </td>
                </tr>
              ))
            ) : (
              <tr><td style={tdStyle} colSpan="9" align="center">ไม่พบข้อมูลที่ค้นหาในประวัติการตรวจ</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {/* ⚙️ Dynamic Modal สำหรับจัดการเพิ่ม/ลบ ตัวเลือก (ถอดแบบจากในรูป) */}
      {activeModal && (
        <div style={modalOverlayStyle}>
          <div style={darkModalStyle}>
            {/* Header Modal */}
            <div style={modalHeaderStyle}>
              <span style={{ fontWeight: "bold", fontSize: "16px" }}>⚙️ {getModalTitle()}</span>
              <button onClick={() => setActiveModal(null)} style={closeModalIconStyle}>✕</button>
            </div>

            {/* Input และ ปุ่มเพิ่ม */}
            <div style={{ display: "flex", gap: "8px", marginBottom: "16px" }}>
              <input
                type="text"
                placeholder="กรอกตัวเลือกใหม่..."
                value={newItemInput}
                onChange={(e) => setNewItemInput(e.target.value)}
                style={darkInputStyle}
              />
              <button onClick={handleAddItem} style={blueAddButtonStyle}>+ เพิ่ม</button>
            </div>

            {/* รายการตัวเลือกแบบการ์ดพร้อมปุ่มลบ */}
            <div style={itemListContainerStyle}>
              {getCurrentModalList().map((item, idx) => (
                <div key={idx} style={itemCardStyle}>
                  <span style={{ fontSize: "14px", color: "#e5e7eb" }}>{item}</span>
                  <button onClick={() => handleRemoveItem(idx)} style={redDeleteButtonStyle}>❌ ลบ</button>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Modal แก้ไขประวัติการตรวจ */}
      {editingItem && (
        <div style={modalOverlayStyle}>
          <div style={modalStyle}>
            <h3 style={{ marginTop: 0 }}>✏️ แก้ไขประวัติการตรวจ (ID: {editingItem.maintenance_id})</h3>
            
            <div style={fieldGroupStyle}>
              <label style={labelStyle}>Serial Number</label>
              <input
                type="text"
                value={editSerial}
                onChange={(e) => setEditSerial(e.target.value)}
                style={inputStyle}
              />
            </div>

            <div style={{ ...fieldGroupStyle, marginTop: "10px" }}>
              <label style={labelStyle}>ประเภทการตรวจ</label>
              <select
                value={editType}
                onChange={(e) => setEditType(e.target.value)}
                style={inputStyle}
              >
                {typeOptions.map((opt, i) => (
                  <option key={i} value={opt}>{opt}</option>
                ))}
              </select>
            </div>

            <div style={{ ...fieldGroupStyle, marginTop: "10px" }}>
              <label style={labelStyle}>ผลตรวจ</label>
              <select
                value={editResult}
                onChange={(e) => setEditResult(e.target.value)}
                style={inputStyle}
              >
                {resultOptions.map((opt, i) => (
                  <option key={i} value={opt}>{opt}</option>
                ))}
              </select>
            </div>

            <div style={{ ...fieldGroupStyle, marginTop: "10px" }}>
              <label style={labelStyle}>สิ่งที่ต้องทำต่อ</label>
              <select
                value={editNextAction}
                onChange={(e) => setEditNextAction(e.target.value)}
                style={inputStyle}
              >
                {actionOptions.map((opt, i) => (
                  <option key={i} value={opt}>{opt}</option>
                ))}
              </select>
            </div>

            <div style={{ ...fieldGroupStyle, marginTop: "10px" }}>
              <label style={labelStyle}>รายละเอียดเพิ่มเติม</label>
              <textarea
                value={editDesc}
                onChange={(e) => setEditDesc(e.target.value)}
                style={textAreaStyle}
                rows="3"
              />
            </div>

            <div style={{ display: "flex", gap: "10px", justifyContent: "flex-end", marginTop: "15px" }}>
              <button onClick={() => setEditingItem(null)} style={cancelButtonStyle}>ยกเลิก</button>
              <button onClick={handleUpdate} style={primaryButtonStyle}>บันทึกแก้ไข</button>
            </div>
          </div>
        </div>
      )}
    </Layout>
  );
}

// --- Dynamic Styles & Scaffolding ---
const summaryRowStyle = { display: "flex", gap: "16px", flexWrap: "wrap", marginBottom: "20px" };
const summaryCardStyle = { background: "#1f2937", color: "white", padding: "20px", borderRadius: "12px", minWidth: "220px", flex: "1" };
const summaryNumberStyle = { fontSize: "28px", fontWeight: "bold", marginTop: "10px" };
const formCardStyle = { background: "#111827", padding: "20px", borderRadius: "12px", marginBottom: "20px" };
const formGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))", gap: "12px", marginBottom: "16px" };
const fieldGroupStyle = { display: "flex", flexDirection: "column", gap: "6px" };
const fieldGroupStyleFull = { display: "flex", flexDirection: "column", gap: "6px", gridColumn: "1 / -1" };
const labelStyle = { fontSize: "13px", fontWeight: "bold", color: "#e5e7eb" };
const inputStyle = { padding: "10px", borderRadius: "8px", border: "1px solid #ccc", width: "100%", boxSizing: "border-box", backgroundColor: "#fff", color: "#000" };
const inputWithBtnStyle = { display: "flex", gap: "6px", alignItems: "center" };
const iconButtonStyle = { padding: "8px 12px", background: "#374151", border: "1px solid #4b5563", borderRadius: "8px", cursor: "pointer", fontSize: "14px", color: "#fff" };
const textAreaStyle = { minHeight: "80px", padding: "10px", borderRadius: "8px", border: "1px solid #ccc", width: "100%", boxSizing: "border-box", resize: "vertical", fontFamily: "inherit", backgroundColor: "#fff", color: "#000" };
const searchInputStyle = { padding: "10px 14px", borderRadius: "8px", border: "1px solid #374151", width: "100%", boxSizing: "border-box", backgroundColor: "#1f2937", color: "#fff", fontSize: "14px" };
const tableStyle = { width: "100%", borderCollapse: "collapse", background: "#1f2937", color: "white", borderRadius: "12px", overflow: "hidden" };
const thStyle = { padding: "12px 10px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px", fontWeight: "bold", whiteSpace: "nowrap", backgroundColor: "#2d3a4a" };
const tdStyle = { padding: "10px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px" };
const primaryButtonStyle = { padding: "10px 14px", border: "none", borderRadius: "8px", background: "#2563eb", color: "white", cursor: "pointer", fontWeight: "bold", whiteSpace: "nowrap" };
const editButtonStyle = { padding: "4px 8px", border: "none", borderRadius: "6px", background: "#f59e0b", color: "black", cursor: "pointer", fontWeight: "bold", fontSize: "12px" };
const cancelButtonStyle = { padding: "10px 14px", border: "none", borderRadius: "8px", background: "#6b7280", color: "white", cursor: "pointer", fontWeight: "bold" };
const badgeStyle = { padding: "4px 10px", borderRadius: "999px", fontSize: "12px", fontWeight: "500", whiteSpace: "nowrap", display: "inline-block", textAlign: "center" };
const actionBadgeStyle = { padding: "3px 8px", borderRadius: "6px", fontSize: "12px", background: "#374151", color: "#60a5fa", border: "1px solid #4b5563" };
const overdueStyle = { background: "#ef4444", color: "white" };
const dueTodayStyle = { background: "#f59e0b", color: "black" };
const normalStyle = { background: "#10b981", color: "white" };

// --- Dark UI Modal Styles (ตรงตามภาพอ้างอิง) ---
const modalOverlayStyle = { position: "fixed", top: 0, left: 0, right: 0, bottom: 0, backgroundColor: "rgba(0,0,0,0.75)", display: "flex", justifyContent: "center", alignItems: "center", zIndex: 1000 };
const darkModalStyle = { backgroundColor: "#1e293b", color: "white", padding: "20px", borderRadius: "12px", width: "90%", maxWidth: "420px", boxShadow: "0 10px 25px rgba(0,0,0,0.5)", border: "1px solid #334155" };
const modalHeaderStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "16px", borderBottom: "1px solid #334155", paddingBottom: "10px" };
const closeModalIconStyle = { background: "none", border: "none", color: "#94a3b8", cursor: "pointer", fontSize: "18px" };
const darkInputStyle = { padding: "10px 12px", borderRadius: "8px", border: "1px solid #475569", backgroundColor: "#0f172a", color: "#fff", flex: 1, fontSize: "14px" };
const blueAddButtonStyle = { padding: "10px 16px", borderRadius: "8px", border: "none", backgroundColor: "#2563eb", color: "#fff", fontWeight: "bold", cursor: "pointer" };
const itemListContainerStyle = { maxHeight: "250px", overflowY: "auto", display: "flex", flexDirection: "column", gap: "8px" };
const itemCardStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", padding: "10px 14px", backgroundColor: "#0f172a", borderRadius: "8px", border: "1px solid #334155" };
const redDeleteButtonStyle = { padding: "4px 8px", borderRadius: "6px", border: "none", backgroundColor: "#dc2626", color: "#fff", fontSize: "12px", fontWeight: "bold", cursor: "pointer" };
const modalStyle = { backgroundColor: "#1f2937", color: "white", padding: "20px", borderRadius: "12px", width: "90%", maxWidth: "480px" };

export default MaintenancePage;