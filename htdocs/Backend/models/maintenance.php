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
  const [maintenanceType, setMaintenanceType] = useState("");
  const [description, setDescription] = useState("");
  const [result, setResult] = useState("");
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedSerialNumber, setSelectedSerialNumber] = useState("");
  
  const todayStr = new Date().toISOString().split("T")[0];
  const todayDate = new Date(todayStr);

  const parseLocalDate = (dateStr) => {
    if (!dateStr) return null;
    const [year, month, day] = dateStr.split("-");
    return new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
  };

  const fetchMaintenances = async () => {
  try {
    const res = await fetch("http://localhost:8080/Backend/models/get_maintenance.php");
    const data = await res.json();
    if (data.success) {
      const sorted = data.data.sort((a, b) => 
        new Date(b.maintenance_date) - new Date(a.maintenance_date)
      );
      setMaintenances(sorted);
    }
  } catch (err) {
    console.error(err);
  }
};

  const fetchCylinders = async () => {
  try {
    setLoading(true);
    const res = await fetch("http://localhost:8080/Backend/models/get_due_cylinders.php");
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
    return allCylinders.filter((item) => {
      if (!item.next_check_date) return false;
      const dueDate = parseLocalDate(item.next_check_date);
      return dueDate && dueDate <= todayDate;
    });
  }, [allCylinders, todayDate]);

  const filteredDueCylinders = dueCylinders.filter((item) => {
    const searchText = [
      item.serial_number, item.brand, item.size, 
      item.next_check_date, item.status
    ].filter(Boolean).join(" ").toLowerCase();
    return searchText.includes(searchTerm.toLowerCase());
  });

  const getDueStatus = (nextCheckDateStr) => {
    if (!nextCheckDateStr) return "ไม่มีกำหนด";
    const dueDate = parseLocalDate(nextCheckDateStr);
    if (!dueDate) return "รูปแบบผิด";
    if (dueDate < todayDate) return "เลยกำหนด";
    if (dueDate.getTime() === todayDate.getTime()) return "ถึงกำหนดวันนี้";
    return "ใกล้ถึงกำหนด";
  };

  const res = await fetch("http://localhost:8080/Backend/models/save_maintenance.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify(payload),
});

    const adminId = localStorage.getItem("admin_id") || "1";

    const payload = {
      serial_number: selectedSerialNumber,
      maintenance_type: maintenanceType,
      result: result,
      description: description,
      admin_id: adminId,
    };

    setSaving(true);
    try {
      const res = await fetch("http://localhost/Backend/models/save_maintenance.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json();

      if (data.success) {
        alert("บันทึกสำเร็จ");
        setSelectedSerialNumber("");
        setMaintenanceType("");
        setDescription("");
        setResult("");
        await fetchCylinders();
        await fetchMaintenances();
      } else {
        alert(data.message || "บันทึกไม่สำเร็จ");
      }
    } catch (err) {
      console.error(err);
      alert("เชื่อมต่อ server ไม่ได้");
    } finally {
      setSaving(false);
    }
  };

  const latestRecords = maintenances.slice(0, 10);

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
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>เลือกถังที่ตรวจ *</label>
            <select
              value={selectedSerialNumber}
              onChange={(e) => setSelectedSerialNumber(e.target.value)}
              style={inputStyle}
            >
              <option value="">-- เลือกถัง --</option>
              {dueCylinders.map((item) => (
                <option key={item.serial_number} value={item.serial_number}>
                  {item.serial_number} - {item.brand || "ไม่ระบุ"} - {item.size}
                </option>
              ))}
            </select>
          </div>
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ประเภทการตรวจ/บำรุง *</label>
            <select
              value={maintenanceType}
              onChange={(e) => setMaintenanceType(e.target.value)}
              style={inputStyle}
            >
              <option value="">-- เลือกประเภท --</option>
              <option value="ตรวจสภาพ">ตรวจสภาพ</option>
              <option value="บำรุงรักษา">บำรุงรักษา</option>
              <option value="ซ่อมแซม">ซ่อมแซม</option>
            </select>
          </div>
          <div style={fieldGroupStyle}>
            <label style={labelStyle}>ผลการตรวจ *</label>
            <select
              value={result}
              onChange={(e) => setResult(e.target.value)}
              style={inputStyle}
            >
              <option value="">-- เลือกผลตรวจ --</option>
              <option value="ผ่าน">ผ่าน</option>
              <option value="ไม่ผ่าน">ไม่ผ่าน</option>
            </select>
          </div>
          <div style={fieldGroupStyleFull}>
            <label style={labelStyle}>รายละเอียด</label>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              style={textAreaStyle}
              placeholder="รายละเอียดการตรวจหรือบำรุงรักษา (ถ้ามี)"
              rows="3"
            />
          </div>
        </div>
        <button onClick={saveMaintenance} style={primaryButtonStyle} disabled={saving}>
          {saving ? "กำลังบันทึก..." : "💾 บันทึกผลตรวจ"}
        </button>
      </div>

      <div style={{ marginBottom: "16px" }}>
        <input
          type="text"
          placeholder="ค้นหาถังที่ถึงกำหนดตรวจ..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          style={searchInputStyle}
        />
      </div>

      <div style={{ overflowX: "auto", marginBottom: "24px" }}>
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
                  <td style={tdStyle}>{item.size}</td>
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
                  <td style={tdStyle}>{item.status}</td>
                </tr>
              ))
            ) : (
              <tr><td style={tdStyle} colSpan="6" align="center">ไม่พบถังที่ถึงกำหนดตรวจ</td></tr>
            )}
          </tbody>
        </table>
      </div>

      <h2 style={{ color: "white" }}>ประวัติการตรวจล่าสุด</h2>
      <div style={{ overflowX: "auto" }}>
        <table style={tableStyle}>
          <thead>
            <tr>
              <th style={thStyle}>Maintenance ID</th>
              <th style={thStyle}>Serial Number</th>
              <th style={thStyle}>วันที่ตรวจ</th>
              <th style={thStyle}>ประเภท</th>
              <th style={thStyle}>ผลตรวจ</th>
              <th style={thStyle}>วันตรวจครั้งถัดไป</th>
              <th style={thStyle}>รายละเอียด</th>
              <th style={thStyle}>ผู้บันทึก</th>
            </tr>
          </thead>
          <tbody>
            {latestRecords.length > 0 ? (
              latestRecords.map((item) => {
                // ดึง Serial Number ถ้าเป็น "0" หรือไม่มีให้แสดง "-"
                const displaySerial = (item.serial_number && item.serial_number !== "0") 
                  ? item.serial_number 
                  : ((item.cylinder_id && item.cylinder_id !== "0") ? item.cylinder_id : "-");

                return (
                  <tr key={item.maintenance_id}>
                    <td style={tdStyle}>{item.maintenance_id}</td>
                    <td style={tdStyle}><strong>{displaySerial}</strong></td>
                    <td style={tdStyle}>{item.maintenance_date}</td>
                    <td style={tdStyle}>{item.maintenance_type}</td>
                    <td style={tdStyle}>{item.result}</td>
                    <td style={tdStyle}>{item.next_maintenance_date || "-"}</td>
                    <td style={tdStyle}>{item.description || "-"}</td>
                    <td style={tdStyle}>{item.admin_id || "-"}</td>
                  </tr>
                );
              })
            ) : (
              <tr><td style={tdStyle} colSpan="8" align="center">ยังไม่มีประวัติการตรวจ</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </Layout>
  );
}

const summaryRowStyle = { display: "flex", gap: "16px", flexWrap: "wrap", marginBottom: "20px" };
const summaryCardStyle = { background: "#1f2937", color: "white", padding: "20px", borderRadius: "12px", minWidth: "220px", flex: "1" };
const summaryNumberStyle = { fontSize: "28px", fontWeight: "bold", marginTop: "10px" };
const formCardStyle = { background: "#111827", padding: "20px", borderRadius: "12px", marginBottom: "20px" };
const formGridStyle = { display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))", gap: "12px", marginBottom: "16px" };
const fieldGroupStyle = { display: "flex", flexDirection: "column", gap: "6px" };
const fieldGroupStyleFull = { display: "flex", flexDirection: "column", gap: "6px", gridColumn: "1 / -1" };
const labelStyle = { fontSize: "13px", fontWeight: "bold", color: "#e5e7eb" };
const inputStyle = { padding: "10px", borderRadius: "8px", border: "1px solid #ccc", width: "100%", boxSizing: "border-box", backgroundColor: "#fff" };
const textAreaStyle = { minHeight: "80px", padding: "10px", borderRadius: "8px", border: "1px solid #ccc", width: "100%", boxSizing: "border-box", resize: "vertical", fontFamily: "inherit" };
const searchInputStyle = { padding: "10px", borderRadius: "8px", border: "1px solid #ccc", width: "100%", boxSizing: "border-box" };
const tableStyle = { width: "100%", borderCollapse: "collapse", background: "#1f2937", color: "white", borderRadius: "12px", overflow: "hidden" };
const thStyle = { padding: "12px 10px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px", fontWeight: "bold", whiteSpace: "nowrap", backgroundColor: "#2d3a4a" };
const tdStyle = { padding: "10px", textAlign: "left", borderBottom: "1px solid #374151", fontSize: "13px" };
const primaryButtonStyle = { padding: "10px 14px", border: "none", borderRadius: "8px", background: "#2563eb", color: "white", cursor: "pointer", fontWeight: "bold" };
const badgeStyle = { padding: "4px 10px", borderRadius: "999px", fontSize: "12px", fontWeight: "500", whiteSpace: "nowrap", display: "inline-block", textAlign: "center" };
const overdueStyle = { background: "#ef4444", color: "white" };
const dueTodayStyle = { background: "#f59e0b", color: "black" };
const normalStyle = { background: "#10b981", color: "white" };

export default MaintenancePage;