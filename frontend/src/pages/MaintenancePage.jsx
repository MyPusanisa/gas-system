import { useMemo, useState, useEffect } from "react";
import Layout from "../components/Layout";
import {
  Settings, Pencil, Trash2, CircleCheck, X, Save, Plus, Check, Search,
  TriangleAlert, CalendarClock, ClipboardList, ClipboardCheck, History,
} from "lucide-react";
import API_BASE_URL from "../config"; 

function MaintenancePage({
  cylinders: propCylinders,
  setCylinders: propSetCylinders,
}) {
  const [allCylinders, setAllCylinders] = useState([]);
  const [maintenances, setMaintenances] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  // --- Bulk Selection State ---
  const [selectedSerialNumbers, setSelectedSerialNumbers] = useState([]);

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
  const [activeModal, setActiveModal] = useState(null);
  const [newItemInput, setNewItemInput] = useState("");

  // --- Edit Modal State ---
  const [editingItem, setEditingItem] = useState(null);
  const [editSerial, setEditSerial] = useState("");
  const [editType, setEditType] = useState("");
  const [editResult, setEditResult] = useState("");
  const [editNextAction, setEditNextAction] = useState("");
  const [editDesc, setEditDesc] = useState("");

  const todayStr = new Date().toISOString().split("T")[0];
  const todayDate = useMemo(() => new Date(todayStr), [todayStr]);

  const parseLocalDate = (dateStr) => {
    if (!dateStr || typeof dateStr !== "string") return null;
    const parts = dateStr.split("-");
    if (parts.length !== 3) return null;
    const [year, month, day] = parts;
    return new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
  };

  const fetchMaintenances = async () => {
    try {
      const res = await fetch(`${API_BASE_URL}/get_maintenance.php`);
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

  const calculateNextCheckDate = (item) => {
    if (item.next_check_date) return item.next_check_date;
    if (item.next_maintenance_date) return item.next_maintenance_date;

    if (item.maintenance_date) {
      const d = new Date(item.maintenance_date);
      if (!isNaN(d.getTime())) {
        d.setFullYear(d.getFullYear() + 1);
        return d.toISOString().split("T")[0];
      }
    }
    return "-";
  };

  const fetchCylinders = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE_URL}/get_due_cylinders.php`);
      const data = await res.json();
      if (data.success) {
        const enrichedData = data.data.map(item => ({
          ...item,
          serial_number: item.serial_number || item.cylinder_id || "-",
          gas_type: item.gas_type || "LPG",
          current_location: item.current_location || "คลัง",
          next_check_date: item.next_check_date || null,
          status: item.status || "ปกติ"
        }));
        setAllCylinders(enrichedData);
        if (propSetCylinders) propSetCylinders(enrichedData);
      }
    } catch (err) {
      console.error(err);
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
  // เฉพาะถังที่เลยกำหนดตรวจแล้ว (ใช้ทั้งการ์ดสรุป, dropdown และตาราง)
  return allCylinders.filter((item) => {
    const dueDate = item && parseLocalDate(item.next_check_date);
    return dueDate && dueDate < todayDate;
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

  const handleToggleSelect = (serialNumber) => {
    setSelectedSerialNumbers((prev) =>
      prev.includes(serialNumber)
        ? prev.filter((sn) => sn !== serialNumber)
        : [...prev, serialNumber]
    );
  };

  const handleSelectAll = (e) => {
    if (e.target.checked) {
      const allFilteredSerials = filteredDueCylinders.map((c) => c.serial_number);
      setSelectedSerialNumbers(allFilteredSerials);
    } else {
      setSelectedSerialNumbers([]);
    }
  };

  const saveMaintenance = async () => {
    const targetSerials = selectedSerialNumbers.length > 0 
      ? selectedSerialNumbers 
      : (selectedSerialNumber ? [selectedSerialNumber] : []);

    if (targetSerials.length === 0) {
      alert("กรุณาเลือกถังแก๊สอย่างน้อย 1 รายการ");
      return;
    }

    if (!maintenanceType || !result) {
      alert("กรุณาเลือกประเภทการตรวจ และผลการตรวจ");
      return;
    }

    const fullDescription = [
      selectedNote ? `[หมายเหตุ: ${selectedNote}]` : "",
      description
    ].filter(Boolean).join(" ");

    const payload = {
      serial_numbers: targetSerials,
      maintenance_type: maintenanceType,
      result: result,
      next_action: nextAction,
      description: fullDescription,
    };

    setSaving(true);
    try {
      const res = await fetch(`${API_BASE_URL}/save_maintenance.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json();

      if (data.success) {
        alert(data.message || "บันทึกสำเร็จ");
        setSelectedSerialNumber("");
        setSelectedSerialNumbers([]);
        setMaintenanceType("");
        setResult("");
        setNextAction("");
        setSelectedNote("");
        setDescription("");
        await fetchCylinders();
        await fetchMaintenances();
      } else {
        alert(data.message || "บันทึกไม่สำเร็จ");
      }
    } catch (err) {
      console.error(err);
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
      const res = await fetch(`${API_BASE_URL}/update_maintenance.php`, {
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

  // ถังที่จะถึงกำหนดตรวจภายใน 30 วัน (ยังไม่เลย)
  const dueSoonCount = (Array.isArray(allCylinders) ? allCylinders : []).filter((c) => {
    const d = c && parseLocalDate(c.next_check_date);
    if (!d) return false;
    const days = Math.round((d - todayDate) / 86400000);
    return days >= 0 && days <= 30;
  }).length;

  const targetCount = selectedSerialNumbers.length || (selectedSerialNumber ? 1 : 0);

  if (loading) {
    return (
      <Layout>
        <div style={{ color: "#9ca3af", textAlign: "center", padding: "50px" }}>กำลังโหลดข้อมูล...</div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div style={{ marginBottom: "20px" }}>
        <h1 style={titleStyle}>ตรวจสภาพและบำรุงรักษา</h1>
        <div style={subtitleStyle}>ติดตามกำหนดตรวจถังและบันทึกผลการตรวจ</div>
      </div>

      {/* ===== การ์ดสรุป ===== */}
      <div style={statGridStyle}>
        <StatCard icon={TriangleAlert} accent="#ef4444" title="เลยกำหนดตรวจ" value={dueCylinders.length} unit="ถัง"
          valueColor={dueCylinders.length > 0 ? "#f87171" : undefined} />
        <StatCard icon={CalendarClock} accent="#f59e0b" title="ถึงกำหนดใน 30 วัน" value={dueSoonCount} unit="ถัง"
          valueColor={dueSoonCount > 0 ? "#fbbf24" : undefined} />
        <StatCard icon={ClipboardList} accent="#3b82f6" title="ประวัติการตรวจ" value={maintenances.length} unit="รายการ" />
      </div>

      {/* ===== ฟอร์มบันทึกผลตรวจ ===== */}
      <div style={cardStyle}>
        <div style={cardHeaderStyle}>
          <h2 style={cardTitleStyle}><ClipboardCheck size={18} color="#60a5fa" /> บันทึกผลตรวจ</h2>
          {targetCount > 0 && <span style={countPillStyle}>{targetCount} ถัง</span>}
        </div>

        {/* ถังที่จะบันทึก */}
        <div style={fieldGroupStyle}>
          <label style={labelStyle}>ถังที่ตรวจ<span style={reqStyle}> *</span></label>
          {selectedSerialNumbers.length > 0 ? (
            <div style={chipWrapStyle}>
              {selectedSerialNumbers.map((sn) => (
                <span key={sn} style={serialChipStyle}>
                  {sn}
                  <button type="button" onClick={() => handleToggleSelect(sn)} style={chipCloseStyle} title="เอาออก">
                    <X size={12} />
                  </button>
                </span>
              ))}
              <button type="button" onClick={() => setSelectedSerialNumbers([])} style={linkBtnStyle}>ล้างทั้งหมด</button>
            </div>
          ) : (
            <select
              value={selectedSerialNumber}
              onChange={(e) => setSelectedSerialNumber(e.target.value)}
              style={inputStyle}
            >
              <option value="">{dueCylinders.length ? "-- เลือกถังที่เลยกำหนดตรวจ --" : "ไม่มีถังที่เลยกำหนดตรวจ"}</option>
              {dueCylinders.map((cyl) => (
                <option key={cyl.serial_number} value={cyl.serial_number}>
                  {cyl.serial_number} · {cyl.brand || "-"} {cyl.size || ""} · กำหนดตรวจ {formatDate(cyl.next_check_date)}
                </option>
              ))}
            </select>
          )}
          <span style={hintStyle}>หรือติ๊กเลือกหลายถังจากตาราง "เลยกำหนดตรวจ" ด้านล่าง</span>
        </div>

        {/* ประเภท / ผลตรวจ แบบปุ่มเลือก */}
        <div style={twoColStyle}>
          <ChipPicker
            label="ประเภทการตรวจ"
            required
            options={typeOptions}
            value={maintenanceType}
            onChange={setMaintenanceType}
            onManage={() => setActiveModal("type")}
          />
          <ChipPicker
            label="ผลการตรวจ"
            required
            options={resultOptions}
            value={result}
            onChange={setResult}
            onManage={() => setActiveModal("result")}
            colorFor={(opt) => resultBadgeStyle(opt).color}
          />
        </div>

        <div style={twoColStyle}>
          <SelectWithManage
            label="สิ่งที่ต้องทำต่อ"
            placeholder="-- เลือกสิ่งที่ต้องทำต่อ --"
            options={actionOptions}
            value={nextAction}
            onChange={setNextAction}
            onManage={() => setActiveModal("action")}
          />
          <SelectWithManage
            label="หมายเหตุสำเร็จรูป"
            placeholder="-- เลือกหมายเหตุ --"
            options={noteOptions}
            value={selectedNote}
            onChange={setSelectedNote}
            onManage={() => setActiveModal("note")}
          />
        </div>

        <div style={{ ...fieldGroupStyle, marginTop: "14px" }}>
          <label style={labelStyle}>รายละเอียดเพิ่มเติม</label>
          <textarea
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            style={textAreaStyle}
            placeholder="รายละเอียดการตรวจหรือข้อสังเกตเพิ่มเติม (ถ้ามี)"
            rows="3"
          />
        </div>

        <div style={formFooterStyle}>
          <span style={hintStyle}>ผล "ผ่าน" จะเลื่อนวันตรวจครั้งถัดไปของถังออกไป 1 ปี</span>
          <button onClick={saveMaintenance} style={primaryButtonStyle} disabled={saving}>
            <Save size={16} />
            {saving ? "กำลังบันทึก..." : `บันทึกผลตรวจ${targetCount > 1 ? ` (${targetCount} ถัง)` : ""}`}
          </button>
        </div>
      </div>

      {/* ===== ถังที่เลยกำหนดตรวจ ===== */}
      <div style={cardStyle}>
        <div style={cardHeaderStyle}>
          <h2 style={cardTitleStyle}>
            <TriangleAlert size={18} color="#f87171" /> ถังที่เลยกำหนดตรวจ <span style={countPillStyle}>{filteredDueCylinders.length}</span>
          </h2>
          <SearchBox value={dueSearchTerm} onChange={setDueSearchTerm} placeholder="ค้นหา Serial, ยี่ห้อ, ขนาด..." />
        </div>

        <div style={{ overflowX: "auto" }}>
          <table style={tableStyle}>
            <thead>
              <tr>
                <th style={{ ...thStyle, width: "40px" }}>
                  <input
                    type="checkbox"
                    onChange={handleSelectAll}
                    checked={filteredDueCylinders.length > 0 && selectedSerialNumbers.length === filteredDueCylinders.length}
                    title="เลือกทั้งหมด"
                  />
                </th>
                <th style={thStyle}>Serial Number</th>
                <th style={thStyle}>ยี่ห้อ / ขนาด</th>
                <th style={thStyle}>กำหนดตรวจ</th>
                <th style={thStyle}>เลยกำหนด</th>
                <th style={thStyle}>สถานะถัง</th>
              </tr>
            </thead>
            <tbody>
              {filteredDueCylinders.length > 0 ? (
                filteredDueCylinders.map((item) => {
                  const selected = selectedSerialNumbers.includes(item.serial_number);
                  return (
                    <tr key={item.serial_number} style={selected ? selectedRowStyle : undefined}>
                      <td style={tdStyle}>
                        <input type="checkbox" checked={selected} onChange={() => handleToggleSelect(item.serial_number)} />
                      </td>
                      <td style={{ ...tdStyle, color: "#60a5fa", fontWeight: 700 }}>{item.serial_number}</td>
                      <td style={tdMutedStyle}>{[item.brand, item.size].filter(Boolean).join(" · ") || "-"}</td>
                      <td style={tdStyle}>{formatDate(item.next_check_date)}</td>
                      <td style={tdStyle}>
                        <span style={{ ...badgeStyle, ...badgeRed }}>
                          {item.days_left != null ? `เลย ${Math.abs(item.days_left)} วัน` : getDueStatus(item.next_check_date)}
                        </span>
                      </td>
                      <td style={tdMutedStyle}>{item.status || "-"}</td>
                    </tr>
                  );
                })
              ) : (
                <tr>
                  <td style={emptyCellStyle} colSpan="6">
                    {dueSearchTerm ? "ไม่พบถังที่ค้นหา" : (
                      <span style={{ display: "inline-flex", alignItems: "center", gap: "8px" }}>
                        <CircleCheck size={18} color="#10b981" /> ไม่มีถังที่เลยกำหนดตรวจ
                      </span>
                    )}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* ===== ประวัติการตรวจ ===== */}
      <div style={cardStyle}>
        <div style={cardHeaderStyle}>
          <h2 style={cardTitleStyle}>
            <History size={18} color="#60a5fa" /> ประวัติการตรวจล่าสุด <span style={countPillStyle}>{filteredMaintenances.length}</span>
          </h2>
          <SearchBox value={historySearchTerm} onChange={setHistorySearchTerm} placeholder="ค้นหาประวัติการตรวจ..." />
        </div>

        <div style={{ overflowX: "auto" }}>
          <table style={tableStyle}>
            <thead>
              <tr>
                <th style={thStyle}>วันที่ตรวจ</th>
                <th style={thStyle}>Serial Number</th>
                <th style={thStyle}>ประเภท</th>
                <th style={thStyle}>ผลตรวจ</th>
                <th style={thStyle}>สิ่งที่ต้องทำต่อ</th>
                <th style={thStyle}>ตรวจครั้งถัดไป</th>
                <th style={thStyle}>หมายเหตุ</th>
                <th style={{ ...thStyle, textAlign: "right" }}></th>
              </tr>
            </thead>
            <tbody>
              {filteredMaintenances.length > 0 ? (
                filteredMaintenances.map((item, index) => (
                  <tr key={item.maintenance_id ? `${item.maintenance_id}-${index}` : index}>
                    <td style={tdStyle}>{formatDate(item.maintenance_date)}</td>
                    <td style={{ ...tdStyle, color: "#60a5fa", fontWeight: 700 }}>{item.serial_number || item.cylinder_id || "-"}</td>
                    <td style={tdStyle}>{item.maintenance_type || "-"}</td>
                    <td style={tdStyle}>
                      {item.result ? <span style={{ ...badgeStyle, ...resultBadgeStyle(item.result) }}>{item.result}</span> : "-"}
                    </td>
                    <td style={tdStyle}>
                      {item.next_action ? <span style={actionBadgeStyle}>{item.next_action}</span> : "-"}
                    </td>
                    <td style={tdStyle}>{formatDate(calculateNextCheckDate(item))}</td>
                    <td style={{ ...tdMutedStyle, whiteSpace: "normal", minWidth: "180px" }}>{item.description || "-"}</td>
                    <td style={{ ...tdStyle, textAlign: "right" }}>
                      <button onClick={() => handleEditClick(item)} style={iconBtnStyle("#f59e0b")} title="แก้ไข">
                        <Pencil size={14} />
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td style={emptyCellStyle} colSpan="8">
                    {historySearchTerm ? "ไม่พบประวัติที่ค้นหา" : "ยังไม่มีประวัติการตรวจ"}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* ===== Modal จัดการตัวเลือก ===== */}
      {activeModal && (
        <div style={modalOverlayStyle} onClick={() => setActiveModal(null)}>
          <div style={modalStyle} onClick={(e) => e.stopPropagation()}>
            <div style={modalHeaderStyle}>
              <h3 style={modalTitleStyle}><Settings size={18} color="#9ca3af" /> {getModalTitle()}</h3>
              <button onClick={() => setActiveModal(null)} style={closeBtnStyle}><X size={18} /></button>
            </div>

            <div style={{ display: "flex", gap: "8px", marginBottom: "14px" }}>
              <input
                type="text"
                placeholder="กรอกตัวเลือกใหม่..."
                value={newItemInput}
                onChange={(e) => setNewItemInput(e.target.value)}
                onKeyDown={(e) => e.key === "Enter" && handleAddItem()}
                style={inputStyle}
              />
              <button onClick={handleAddItem} style={primaryButtonStyle}><Plus size={16} /> เพิ่ม</button>
            </div>

            <div style={itemListStyle}>
              {getCurrentModalList().map((item, idx) => (
                <div key={idx} style={itemRowStyle}>
                  <span>{item}</span>
                  <button onClick={() => handleRemoveItem(idx)} style={iconBtnStyle("#ef4444")} title="ลบ">
                    <Trash2 size={14} />
                  </button>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* ===== Modal แก้ไขประวัติ ===== */}
      {editingItem && (
        <div style={modalOverlayStyle} onClick={() => setEditingItem(null)}>
          <div style={{ ...modalStyle, maxWidth: "520px" }} onClick={(e) => e.stopPropagation()}>
            <div style={modalHeaderStyle}>
              <h3 style={modalTitleStyle}>
                <Pencil size={18} color="#fbbf24" /> แก้ไขประวัติการตรวจ
                <span style={{ color: "#9ca3af", fontWeight: "normal" }}>#{editingItem.maintenance_id}</span>
              </h3>
              <button onClick={() => setEditingItem(null)} style={closeBtnStyle}><X size={18} /></button>
            </div>

            <div style={{ display: "flex", flexDirection: "column", gap: "14px" }}>
              <div style={fieldGroupStyle}>
                <label style={labelStyle}>Serial Number</label>
                <input type="text" value={editSerial} onChange={(e) => setEditSerial(e.target.value)} style={inputStyle} />
              </div>
              <ChipPicker label="ประเภทการตรวจ" options={typeOptions} value={editType} onChange={setEditType} />
              <ChipPicker label="ผลการตรวจ" options={resultOptions} value={editResult} onChange={setEditResult}
                colorFor={(opt) => resultBadgeStyle(opt).color} />
              <SelectWithManage label="สิ่งที่ต้องทำต่อ" placeholder="-- ไม่ระบุ --" options={actionOptions}
                value={editNextAction} onChange={setEditNextAction} />
              <div style={fieldGroupStyle}>
                <label style={labelStyle}>รายละเอียดเพิ่มเติม</label>
                <textarea value={editDesc} onChange={(e) => setEditDesc(e.target.value)} style={textAreaStyle} rows="3" />
              </div>
            </div>

            <div style={{ display: "flex", gap: "10px", justifyContent: "flex-end", marginTop: "18px" }}>
              <button onClick={() => setEditingItem(null)} style={secondaryButtonStyle}>ยกเลิก</button>
              <button onClick={handleUpdate} style={primaryButtonStyle}><Save size={16} /> บันทึก</button>
            </div>
          </div>
        </div>
      )}
    </Layout>
  );
}

// ========== Sub components ==========
const StatCard = ({ icon: Icon, accent, title, value, unit, valueColor }) => (
  <div style={{ ...statCardStyle, borderTop: `3px solid ${accent}` }}>
    <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
      <span style={{ color: "#cbd5e1", fontSize: "14px", fontWeight: 600 }}>{title}</span>
      <span style={{ ...statIconStyle, background: `${accent}26`, color: accent }}><Icon size={17} /></span>
    </div>
    <div style={{ marginTop: "10px", fontSize: "32px", fontWeight: 700, color: valueColor || "white", lineHeight: 1.1 }}>
      {value} <span style={{ fontSize: "14px", color: "#9ca3af", fontWeight: 400 }}>{unit}</span>
    </div>
  </div>
);

const ChipPicker = ({ label, required, options, value, onChange, onManage, colorFor }) => (
  <div style={fieldGroupStyle}>
    <div style={labelRowStyle}>
      <label style={labelStyle}>{label}{required && <span style={reqStyle}> *</span>}</label>
      {onManage && (
        <button type="button" onClick={onManage} style={linkBtnStyle}><Settings size={12} /> จัดการ</button>
      )}
    </div>
    <div style={chipWrapStyle}>
      {options.map((opt) => {
        const active = value === opt;
        const color = (colorFor && colorFor(opt)) || "#60a5fa";
        return (
          <button
            key={opt}
            type="button"
            onClick={() => onChange(active ? "" : opt)}
            style={{
              ...pickChipStyle,
              ...(active ? { borderColor: color, color, background: `${color}1f`, fontWeight: 600 } : {}),
            }}
          >
            {active && <Check size={14} />} {opt}
          </button>
        );
      })}
    </div>
  </div>
);

const SelectWithManage = ({ label, placeholder, options, value, onChange, onManage }) => (
  <div style={fieldGroupStyle}>
    <div style={labelRowStyle}>
      <label style={labelStyle}>{label}</label>
      {onManage && (
        <button type="button" onClick={onManage} style={linkBtnStyle}><Settings size={12} /> จัดการ</button>
      )}
    </div>
    <select value={value} onChange={(e) => onChange(e.target.value)} style={inputStyle}>
      <option value="">{placeholder}</option>
      {value && !options.includes(value) && <option value={value}>{value}</option>}
      {options.map((opt) => <option key={opt} value={opt}>{opt}</option>)}
    </select>
  </div>
);

const SearchBox = ({ value, onChange, placeholder }) => (
  <div style={{ position: "relative", width: "280px", maxWidth: "100%" }}>
    <Search size={15} style={{ position: "absolute", left: "12px", top: "50%", transform: "translateY(-50%)", color: "#6b7280" }} />
    <input type="text" value={value} onChange={(e) => onChange(e.target.value)} placeholder={placeholder}
      style={{ ...inputStyle, paddingLeft: "36px" }} />
  </div>
);

// "2026-08-01" -> "01/08/2026"
const formatDate = (dateStr) => {
  if (!dateStr || typeof dateStr !== "string") return "-";
  const [y, m, d] = dateStr.slice(0, 10).split("-");
  return y && m && d ? `${d}/${m}/${y}` : dateStr;
};

const resultBadgeStyle = (result) => {
  if (result === "ผ่าน") return { background: "rgba(16,185,129,0.15)", color: "#34d399", border: "1px solid #10b981" };
  if (result === "ไม่ผ่าน") return { background: "rgba(239,68,68,0.15)", color: "#f87171", border: "1px solid #ef4444" };
  return { background: "rgba(245,158,11,0.15)", color: "#fbbf24", border: "1px solid #f59e0b" };
};

// ========== Styles ==========
const titleStyle = { margin: 0, fontSize: "30px", color: "white" };
const subtitleStyle = { color: "#9ca3af", fontSize: "14px", marginTop: "6px" };

const statGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(170px, 1fr))", gap: "14px", marginBottom: "20px" };
const statCardStyle = { background: "#1f2937", padding: "16px 18px", borderRadius: "14px", boxShadow: "0 1px 2px rgba(0,0,0,0.3)" };
const statIconStyle = { width: "34px", height: "34px", borderRadius: "10px", display: "flex", alignItems: "center", justifyContent: "center" };

const cardStyle = { background: "#1f2937", color: "white", padding: "20px", borderRadius: "14px", marginBottom: "20px", boxShadow: "0 1px 2px rgba(0,0,0,0.3)" };
const cardHeaderStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", gap: "12px", flexWrap: "wrap", marginBottom: "16px" };
const cardTitleStyle = { margin: 0, fontSize: "18px", color: "white", display: "flex", alignItems: "center", gap: "10px" };
const countPillStyle = { background: "#374151", color: "#e5e7eb", fontSize: "13px", padding: "2px 10px", borderRadius: "999px", fontWeight: "normal" };

const twoColStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(min(280px, 100%), 1fr))", gap: "14px 20px", marginTop: "16px" };
const fieldGroupStyle = { display: "flex", flexDirection: "column", gap: "8px" };
const labelRowStyle = { display: "flex", justifyContent: "space-between", alignItems: "center" };
const labelStyle = { fontSize: "13px", fontWeight: 600, color: "#cbd5e1" };
const reqStyle = { color: "#f87171" };
const hintStyle = { color: "#6b7280", fontSize: "12px" };
const inputStyle = { height: "40px", padding: "0 12px", borderRadius: "8px", border: "1px solid #374151", background: "#111827", color: "white", width: "100%", boxSizing: "border-box", fontSize: "14px" };
const textAreaStyle = { minHeight: "80px", padding: "10px 12px", borderRadius: "8px", border: "1px solid #374151", width: "100%", boxSizing: "border-box", resize: "vertical", backgroundColor: "#111827", color: "white", fontSize: "14px" };
const formFooterStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", gap: "12px", flexWrap: "wrap", marginTop: "18px", paddingTop: "16px", borderTop: "1px solid #2b3647" };

const chipWrapStyle = { display: "flex", flexWrap: "wrap", gap: "8px", alignItems: "center" };
const pickChipStyle = { display: "inline-flex", alignItems: "center", gap: "4px", padding: "8px 14px", borderRadius: "999px", border: "1px solid #374151", background: "#111827", color: "#cbd5e1", fontSize: "13px", cursor: "pointer" };
const serialChipStyle = { display: "inline-flex", alignItems: "center", gap: "6px", padding: "5px 6px 5px 12px", borderRadius: "999px", background: "rgba(59,130,246,0.15)", border: "1px solid #3b82f6", color: "#93c5fd", fontSize: "13px", fontWeight: 600 };
const chipCloseStyle = { display: "inline-flex", padding: "3px", borderRadius: "50%", border: "none", background: "rgba(255,255,255,0.08)", color: "#93c5fd", cursor: "pointer" };
const linkBtnStyle = { display: "inline-flex", alignItems: "center", gap: "4px", background: "none", border: "none", color: "#60a5fa", fontSize: "12px", cursor: "pointer", padding: 0 };

const primaryButtonStyle = { display: "inline-flex", alignItems: "center", justifyContent: "center", gap: "6px", height: "40px", padding: "0 18px", border: "none", borderRadius: "8px", background: "#2563eb", color: "white", cursor: "pointer", fontWeight: 600, fontSize: "14px", whiteSpace: "nowrap" };
const secondaryButtonStyle = { height: "40px", padding: "0 18px", border: "1px solid #4b5563", borderRadius: "8px", background: "transparent", color: "#e5e7eb", cursor: "pointer", fontSize: "14px" };
const iconBtnStyle = (color) => ({ display: "inline-flex", alignItems: "center", padding: "6px 9px", border: `1px solid ${color}`, borderRadius: "6px", background: `${color}1f`, color: "white", cursor: "pointer" });

const tableStyle = { width: "100%", borderCollapse: "collapse", color: "white", fontSize: "13px" };
const thStyle = { padding: "10px 12px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px", fontWeight: 600, whiteSpace: "nowrap", background: "#273244", color: "#cbd5e1" };
const tdStyle = { padding: "11px 12px", textAlign: "left", borderBottom: "1px solid #2b3647", verticalAlign: "middle", whiteSpace: "nowrap" };
const tdMutedStyle = { ...tdStyle, color: "#9ca3af" };
const emptyCellStyle = { padding: "28px", textAlign: "center", color: "#9ca3af" };
const selectedRowStyle = { background: "rgba(37,99,235,0.12)" };

const badgeStyle = { padding: "3px 10px", borderRadius: "999px", fontSize: "12px", fontWeight: 600, whiteSpace: "nowrap", display: "inline-block" };
const badgeRed = { background: "rgba(239,68,68,0.15)", color: "#f87171", border: "1px solid #ef4444" };
const actionBadgeStyle = { ...badgeStyle, fontWeight: 500, background: "rgba(59,130,246,0.12)", color: "#93c5fd", border: "1px solid #3b82f6" };

const modalOverlayStyle = { position: "fixed", inset: 0, backgroundColor: "rgba(0,0,0,0.7)", display: "flex", justifyContent: "center", alignItems: "center", zIndex: 1000, padding: "16px" };
const modalStyle = { background: "#1f2937", color: "white", padding: "20px", borderRadius: "14px", width: "100%", maxWidth: "420px", maxHeight: "90vh", overflowY: "auto", border: "1px solid #374151", boxShadow: "0 8px 24px rgba(0,0,0,0.4)", boxSizing: "border-box" };
const modalHeaderStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "16px" };
const modalTitleStyle = { margin: 0, fontSize: "17px", display: "flex", alignItems: "center", gap: "8px" };
const closeBtnStyle = { display: "flex", background: "none", border: "none", color: "#9ca3af", cursor: "pointer", padding: "4px" };
const itemListStyle = { maxHeight: "260px", overflowY: "auto", display: "flex", flexDirection: "column", gap: "6px" };
const itemRowStyle = { display: "flex", justifyContent: "space-between", alignItems: "center", padding: "8px 12px", background: "#111827", borderRadius: "8px", fontSize: "14px", color: "#e5e7eb" };

export default MaintenancePage;
