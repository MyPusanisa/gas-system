import { useEffect, useMemo, useState } from "react";
import Layout from "../components/Layout";
import { Html5QrcodeScanner } from "html5-qrcode";
import API_BASE_URL from "../config"; // 👈 Import API_BASE_URL จาก config กลาง

// สร้าง UPLOADS_BASE_URL อ้างอิงจากโฟลเดอร์ root ของ Backend
const UPLOADS_BASE_URL = API_BASE_URL.replace(/\/models\/?$/, "/uploads");

function DeliveryPage() {
  // อ่านค่าจาก localStorage ให้แน่ใจว่าได้ค่าล่าสุดเสมอ
  const [role, setRole] = useState(() => localStorage.getItem("role") || "");
  const [username, setUsername] = useState(() => localStorage.getItem("username") || "");
  const [staffId, setStaffId] = useState(() => localStorage.getItem("staff_id") || "");

  const [deliveries, setDeliveries] = useState([]);
  const [cylinders, setCylinders] = useState([]);
  const [staffs, setStaffs] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);

  const [selectedProofId, setSelectedProofId] = useState(null);
  const [confirmCylinderInputs, setConfirmCylinderInputs] = useState({});
  const [adminApproveInputs, setAdminApproveInputs] = useState({});

  // State สำหรับควบคุมกล้องสแกน QR Code
  const [scanningJobId, setScanningJobId] = useState(null);

  const [showCylinderModal, setShowCylinderModal] = useState(false);
  const [pendingApproveId, setPendingApproveId] = useState(null);
  const [newCylinderForApproval, setNewCylinderForApproval] = useState({
    serial_number: "",
    gas_type: "LPG",
    brand: "",
    size: "",
    manufacture_date: "",
    expiry_date: "",
    qr_code: "",
    last_check_date: "",
    next_check_date: "",
    delivered_date: "",
    current_location: "คลัง",
  });

  const [newCustomerName, setNewCustomerName] = useState("");
  const [newPhone, setNewPhone] = useState("");
  const [newAddress, setNewAddress] = useState("");
  const [newMapPin, setNewMapPin] = useState("");
  
  // ปรับสำหรับ Admin: ระบุสเปกแก๊สแทนการเลือก Serial Number ถัง
  const [newBrand, setNewBrand] = useState("");
  const [newGasType, setNewGasType] = useState("LPG");
  const [newSize, setNewSize] = useState("");
  const [newStaffId, setNewStaffId] = useState("");

  const apiFetch = async (url, options = {}) => {
    const res = await fetch(url, { ...options, credentials: "include" });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error("Invalid JSON from server:", text.slice(0, 200));
      throw new Error("Server responded with non-JSON (likely PHP error)");
    }
  };

  // ดึงตัวเลือก ยี่ห้อ/ชนิดแก๊ส/ขนาดถัง จากตารางถังแก๊สในฐานข้อมูลแบบ Dynamic
  const brandOptions = useMemo(() => {
    if (!Array.isArray(cylinders)) return [];
    const brands = cylinders.map((c) => c.brand || c.brand_name).filter(Boolean);
    return Array.from(new Set(brands));
  }, [cylinders]);

  const gasTypeOptions = useMemo(() => {
    if (!Array.isArray(cylinders)) return [];
    const types = cylinders.map((c) => c.gas_type || c.gasType || c.type).filter(Boolean);
    return Array.from(new Set(types));
  }, [cylinders]);

  const sizeOptions = useMemo(() => {
    if (!Array.isArray(cylinders)) return [];
    const sizes = cylinders.map((c) => c.size || c.cylinder_size).filter(Boolean);
    return Array.from(new Set(sizes));
  }, [cylinders]);

  const findCustomerByPhone = (phone) =>
    customers.find((c) => c.phone?.trim() === phone.trim());

  const findCustomerByAddress = (address) =>
    customers.find(
      (c) => c.address?.trim().toLowerCase() === address.trim().toLowerCase()
    );

  const calculateExpiry = (manuDate) => {
    if (!manuDate) return "";
    const d = new Date(manuDate);
    d.setFullYear(d.getFullYear() + 5);
    return d.toISOString().split("T")[0];
  };

  const loadData = async () => {
    try {
      setLoading(true);

      const [deliveriesRes, cylindersRes, staffsRes, customersRes] = await Promise.allSettled([
        apiFetch(`${API_BASE_URL}/delivery/list.php`),
        apiFetch(`${API_BASE_URL}/cylinder/list.php`),
        apiFetch(`${API_BASE_URL}/staff/list.php`),
        apiFetch(`${API_BASE_URL}/customer/list.php`),
      ]);

      if (deliveriesRes.status === "fulfilled" && deliveriesRes.value.success) {
        const rawData = deliveriesRes.value.data || [];
        
        const formattedDeliveries = rawData.map((d) => ({
          ...d,
          id: d.id || d.delivery_id,
          customerName: d.customerName || d.customer_name || d.name || "ไม่ระบุชื่อลูกค้า",
          phone: d.phone || d.customer_phone || "-",
          address: d.address || d.customer_address || "-",
          mapPin: d.mapPin || d.map_pin || "-",
          brand: d.brand || d.req_brand || "-",
          gasType: d.gasType || d.gas_type || d.req_gas_type || "LPG",
          size: d.size || d.req_size || "-",
          staff_id: String(d.staff_id ?? d.staffId ?? ""),
          status: d.status || "pending",
        }));

        setDeliveries(formattedDeliveries);
      }

      if (cylindersRes.status === "fulfilled" && cylindersRes.value.success) {
        setCylinders(cylindersRes.value.data);
      } else {
        console.error("โหลด cylinders ล้มเหลว", cylindersRes);
      }

      if (staffsRes.status === "fulfilled" && staffsRes.value.success) {
        setStaffs(staffsRes.value.data);
      } else {
        console.error("โหลด staffs ล้มเหลว", staffsRes);
      }

      if (customersRes.status === "fulfilled" && customersRes.value.success) {
        setCustomers(customersRes.value.data);
      } else {
        console.error("โหลด customers ล้มเหลว", customersRes);
      }

      if (deliveriesRes.status === "rejected" && cylindersRes.status === "rejected") {
        alert("ไม่สามารถโหลดข้อมูลหลักได้ กรุณาตรวจสอบเครือข่ายหรือติดต่อผู้ดูแล");
      }
    } catch (error) {
      console.error("โหลดข้อมูลล้มเหลวขั้นรุนแรง", error);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์ กรุณารีเฟรชหน้าเว็บ");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  // 📷 useEffect สำหรับสแกน QR Code
  useEffect(() => {
    let scanner = null;
    if (scanningJobId) {
      scanner = new Html5QrcodeScanner(
        "qr-reader-container",
        { fps: 10, qrbox: { width: 220, height: 220 } },
        false
      );

      scanner.render(
        (decodedText) => {
          const extractedSerial = decodedText.split("/").pop().trim();
          
          setConfirmCylinderInputs((prev) => ({
            ...prev,
            [scanningJobId]: extractedSerial,
          }));

          scanner.clear().catch((err) => console.error(err));
          setScanningJobId(null);
        },
        (error) => {}
      );
    }

    return () => {
      if (scanner) {
        scanner.clear().catch((err) => console.error(err));
      }
    };
  }, [scanningJobId]);

  useEffect(() => {
    if (!newPhone.trim()) return;
    const exist = findCustomerByPhone(newPhone);
    if (exist) {
      setNewCustomerName(exist.name || "");
      setNewAddress(exist.address || "");
      setNewMapPin(exist.mapPin || "");
    }
  }, [newPhone, customers]);

  useEffect(() => {
    if (!newAddress.trim()) return;
    const exist = findCustomerByAddress(newAddress);
    if (exist && exist.mapPin) setNewMapPin(exist.mapPin);
  }, [newAddress, customers]);

  const createDeliveryJob = async () => {
    if (!newCustomerName || !newPhone || !newAddress || !newStaffId) {
      alert("กรุณากรอกข้อมูลลูกค้าและพนักงานให้ครบ");
      return;
    }
    if (!newBrand || !newGasType || !newSize) {
      alert("กรุณาระบุยี่ห้อ ชนิดแก๊ส และขนาดถังให้ครบถ้วน");
      return;
    }

    const payload = {
      customer_name: newCustomerName.trim(),
      phone: newPhone.trim(),
      address: newAddress.trim(),
      map_pin: newMapPin.trim(),
      brand: newBrand,
      gas_type: newGasType,
      size: newSize,
      staff_id: newStaffId,
      status: "pending"
    };

    try {
      const res = await apiFetch(`${API_BASE_URL}/delivery/create.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      if (res && (res.success || res.status === "success" || res.delivery_id)) {
        alert("สร้างงานจัดส่งเรียบร้อยแล้ว");
        
        setNewCustomerName("");
        setNewPhone("");
        setNewAddress("");
        setNewMapPin("");
        setNewBrand("");
        setNewSize("");
        setNewStaffId("");

        await loadData();
      } else {
        alert(res.message || "สร้างงานไม่สำเร็จ กรุณาตรวจสอบไฟล์ create.php");
      }
    } catch (error) {
      console.error("Create Delivery Error:", error);
      alert("เกิดข้อผิดพลาดในการสร้างงาน");
    }
  };

  // พนักงานสแกน QR ยืนยันการรับงาน
  const confirmReceiveJob = async (jobItem) => {
    const serialNumber = confirmCylinderInputs[jobItem.id]?.trim();
    if (!serialNumber) return alert("กรุณาสแกน QR Code ถังแก๊สเพื่อยืนยัน");

    const cylinder = cylinders.find((c) => c.serial_number === serialNumber);
    if (!cylinder) {
      alert(`❌ ไม่พบถังแก๊ส Serial Number "${serialNumber}" ในระบบ`);
      return;
    }
    if (cylinder.status !== "ในคลัง") {
      alert(`❌ ถังแก๊ส "${serialNumber}" ไม่อยู่ในคลัง (สถานะปัจจุบัน: ${cylinder.status})`);
      return;
    }

    const targetBrand = jobItem.req_brand || jobItem.brand || "";
    const targetGasType = jobItem.req_gas_type || jobItem.gasType || jobItem.gas_type || "";
    const targetSize = jobItem.req_size || jobItem.size || "";

    const cylBrand = cylinder.brand || "";
    const cylGasType = cylinder.gas_type || cylinder.gasType || "";
    const cylSize = cylinder.size || "";

    if (
      cylBrand !== targetBrand ||
      cylGasType !== targetGasType ||
      cylSize !== targetSize
    ) {
      alert(
        `❌ สเปกถังแก๊สไม่ตรงตามเงื่อนไข!\n\n` +
        `ความต้องการงาน: ${targetBrand} | ${targetGasType} | ${targetSize}\n` +
        `ถังที่สแกนได้: ${cylBrand} | ${cylGasType} | ${cylSize}`
      );
      return;
    }

    try {
      const res = await apiFetch(`${API_BASE_URL}/delivery/update.php`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          delivery_id: jobItem.id,
          action: "pick",
          serialNumber: serialNumber,
        }),
      });

      if (res.success) {
        alert("ตรวจสอบสเปกถูกต้อง! รับงานและผูกถังแก๊สเรียบร้อยแล้ว");
        await loadData();
        setConfirmCylinderInputs((prev) => ({ ...prev, [jobItem.id]: "" }));
      } else {
        alert(res.message || "ไม่สามารถรับงานได้");
      }
    } catch (error) {
      console.error(error);
      alert("เกิดข้อผิดพลาดในการรับงาน");
    }
  };

  const handleProofUpload = async (id, file) => {
    if (!file) return;
    const formData = new FormData();
    formData.append("delivery_id", id);
    formData.append("proof", file);

    try {
      const res = await fetch(`${API_BASE_URL}/delivery/upload_proof.php`, {
        method: "POST",
        body: formData,
      });
      const data = await res.json();
      if (data.success) {
        const completeRes = await apiFetch(`${API_BASE_URL}/delivery/update.php`, {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            delivery_id: id,
            action: "complete_by_staff",
          }),
        });
        if (completeRes.success) {
          alert("ส่งรูปและจบงานสำเร็จ");
          await loadData();
          setSelectedProofId(null);
        } else {
          alert(completeRes.message || "จบงานไม่สำเร็จ แต่รูปถูกบันทึกแล้ว");
          await loadData();
        }
      } else {
        alert(data.message || "อัปโหลดรูปไม่สำเร็จ");
      }
    } catch (error) {
      console.error(error);
      alert("เกิดข้อผิดพลาดในการอัปโหลดรูป");
    }
  };

  const sendApproveRequest = async (id, receivedSerialNumber, newCylinderData = null) => {
    const payload = {
      delivery_id: id,
      action: "approve",
      receivedSerialNumber: receivedSerialNumber,
    };
    if (newCylinderData) payload.new_cylinder = newCylinderData;

    const res = await apiFetch(`${API_BASE_URL}/delivery/update.php`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    if (res.success) {
      alert("อนุมัติงานสำเร็จ");
      await loadData();
      setAdminApproveInputs((prev) => ({ ...prev, [id]: "" }));
      setShowCylinderModal(false);
    } else if (res.need_create_cylinder) {
      setPendingApproveId(id);
      setNewCylinderForApproval((prev) => ({ ...prev, serial_number: res.serial_number }));
      setShowCylinderModal(true);
    } else {
      alert(res.message || "อนุมัติไม่สำเร็จ");
    }
  };

  const approveDelivery = async (id) => {
    const receivedSerialNumber = adminApproveInputs[id]?.trim();
    if (!receivedSerialNumber) return alert("กรุณากรอก Serial Number ถังที่รับคืน");

    const exists = cylinders.some((c) => c.serial_number === receivedSerialNumber);
    if (!exists) {
      setPendingApproveId(id);
      setNewCylinderForApproval({
        ...newCylinderForApproval,
        serial_number: receivedSerialNumber,
        gas_type: "LPG",
        brand: "",
        size: "",
        manufacture_date: "",
        expiry_date: "",
        last_check_date: "",
        next_check_date: "",
      });
      setShowCylinderModal(true);
      return;
    }
    await sendApproveRequest(id, receivedSerialNumber);
  };

  const handleCreateAndApprove = () => {
    const { serial_number, brand, size, manufacture_date } = newCylinderForApproval;
    if (!serial_number || !brand || !size || !manufacture_date) {
      alert("กรุณากรอกข้อมูลให้ครบ (Serial Number, ยี่ห้อ, ขนาด, วันที่ผลิต)");
      return;
    }
    let dataToSend = { ...newCylinderForApproval };
    if (!dataToSend.expiry_date) dataToSend.expiry_date = calculateExpiry(manufacture_date);
    if (!dataToSend.next_check_date) dataToSend.next_check_date = calculateExpiry(manufacture_date);
    if (!dataToSend.last_check_date) dataToSend.last_check_date = manufacture_date;

    sendApproveRequest(pendingApproveId, dataToSend.serial_number, dataToSend);
  };

  const handleDeleteDelivery = async (deliveryId) => {
    if (!deliveryId) {
      alert("ไม่พบรหัสงานที่จะลบ");
      return;
    }

    if (!window.confirm("คุณแน่ใจที่จะลบงานนี้? การดำเนินการนี้ไม่สามารถกู้คืนได้")) return;
    try {
      const res = await apiFetch(`${API_BASE_URL}/delivery/delete_delivery.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ delivery_id: deliveryId }),
      });
      if (res.success) {
        alert("ลบงานเรียบร้อย");
        await loadData();
      } else {
        alert(res.message || "ลบไม่สำเร็จ");
      }
    } catch (error) {
      console.error(error);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อ");
    }
  };

  const availableStaffs = useMemo(() => staffs, [staffs]);

  const visibleDeliveries = useMemo(() => {
    const currentRole = localStorage.getItem("role") || role;
    const currentStaffId = localStorage.getItem("staff_id") || staffId;
    const currentUsername = localStorage.getItem("username") || username;

    if (currentRole === "admin" || currentRole === "ผู้ดูแลระบบ") {
      return deliveries;
    }

    return deliveries.filter((d) => {
      const dStaffId = String(d.staff_id ?? d.staffId ?? "");
      const myStaffId = String(currentStaffId ?? "");
      const dStaffName = String(d.assignedStaff ?? d.staff_name ?? "").toLowerCase();
      const myUsername = String(currentUsername ?? "").toLowerCase();

      const isMyJob =
        (myStaffId && dStaffId === myStaffId) ||
        (myUsername && dStaffName.includes(myUsername));

      const isUnassigned = !dStaffId || dStaffId === "0" || dStaffId === "null";

      return isMyJob || isUnassigned;
    });
  }, [deliveries, role, username, staffId]);

  const currentStaffActiveJob = useMemo(
    () => deliveries.find((d) => d.assignedStaff === username && d.status === "delivering"),
    [deliveries, username]
  );

  const maintenanceDueItems = cylinders.filter((c) => {
    if (!c.nextCheckDate) return false;
    return new Date(c.nextCheckDate) <= new Date();
  });

  const deliverySuccessItems = deliveries.filter((d) => d.status === "success");

  const currentRole = localStorage.getItem("role") || role;
  const currentUsername = localStorage.getItem("username") || username;
  const currentStaffId = localStorage.getItem("staff_id") || staffId;

  const newAssignedJobItems =
    currentRole === "staff"
      ? deliveries.filter((d) => {
          const isMyJob =
            String(d.staff_id) === String(currentStaffId) ||
            d.assignedStaff === currentUsername ||
            d.staffUsername === currentUsername;
          return isMyJob && d.status === "pending";
        })
      : [];

  const getStatusStyle = (status) => {
    switch (status) {
      case "pending":
        return { background: "#facc15", color: "black" };
      case "delivering":
        return { background: "#f59e0b", color: "white" };
      case "pending_approval":
        return { background: "#8b5cf6", color: "white" };
      case "success":
        return { background: "#22c55e", color: "white" };
      default:
        return { background: "#6b7280", color: "white" };
    }
  };

  const getStatusText = (status) => {
    switch (status) {
      case "pending_approval":
        return "รออนุมัติ";
      case "pending":
        return "รอรับงาน";
      case "delivering":
        return "กำลังจัดส่ง";
      case "success":
        return "สำเร็จ";
      default:
        return status;
    }
  };

  if (loading) {
    return (
      <Layout>
        <div style={{ color: "white" }}>กำลังโหลดข้อมูล...</div>
      </Layout>
    );
  }

  return (
    <Layout
      gasLevel={610}
      maintenanceDueItems={maintenanceDueItems}
      expiredCylinderItems={[]}
      deliverySuccessItems={deliverySuccessItems}
      newAssignedJobItems={newAssignedJobItems}
    >
      <h1 style={{ marginBottom: "20px" }}>🚚 Delivery Management</h1>

      {role === "admin" && (
        <div style={styles.formCard}>
          <h2 style={{ marginTop: 0 }}>สร้างงานจัดส่ง</h2>
          <div style={styles.formGrid}>
            <input
              placeholder="ชื่อลูกค้า"
              value={newCustomerName}
              onChange={(e) => setNewCustomerName(e.target.value)}
              style={styles.input}
            />
            <input
              placeholder="เบอร์โทร"
              value={newPhone}
              onChange={(e) => setNewPhone(e.target.value)}
              style={styles.input}
            />
            <input
              placeholder="ที่อยู่"
              value={newAddress}
              onChange={(e) => setNewAddress(e.target.value)}
              style={styles.input}
            />
            <input
              placeholder="Map Pin / พิกัด"
              value={newMapPin}
              onChange={(e) => setNewMapPin(e.target.value)}
              style={styles.input}
            />

            <select
              value={newBrand}
              onChange={(e) => setNewBrand(e.target.value)}
              style={styles.input}
            >
              <option value="">-- เลือกยี่ห้อ --</option>
              {brandOptions.map((b) => (
                <option key={b} value={b}>{b}</option>
              ))}
            </select>

            <select
              value={newGasType}
              onChange={(e) => setNewGasType(e.target.value)}
              style={styles.input}
            >
              <option value="">-- เลือกชนิดแก๊ส --</option>
              {gasTypeOptions.map((g) => (
                <option key={g} value={g}>{g}</option>
              ))}
            </select>

            <select
              value={newSize}
              onChange={(e) => setNewSize(e.target.value)}
              style={styles.input}
            >
              <option value="">-- เลือกขนาดถัง --</option>
              {sizeOptions.map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>

            <select
              value={newStaffId}
              onChange={(e) => setNewStaffId(e.target.value)}
              style={styles.input}
            >
              <option value="">-- เลือกคนส่ง --</option>
              {availableStaffs.map((staff) => (
                <option key={staff.staff_id} value={staff.staff_id}>
                  {staff.staff_id} - {staff.staff_name}
                </option>
              ))}
            </select>
          </div>
          <button onClick={createDeliveryJob} style={styles.blueBtn}>
            สร้างงานส่ง
          </button>
        </div>
      )}

      {role === "staff" && currentStaffActiveJob && (
        <div style={styles.warningBox}>
          คุณกำลังจัดส่งงาน {currentStaffActiveJob.id} อยู่ กรุณาส่งงานนี้ให้เสร็จก่อน
        </div>
      )}

      <div style={{ display: "flex", flexDirection: "column", gap: "20px" }}>
        {visibleDeliveries.length > 0 ? (
          visibleDeliveries.map((item) => (
            <div key={item.id} style={styles.card}>
              <div style={styles.headerBar}>
                <div>
                  <h2 style={{ margin: 0 }}>ข้อมูลงานจัดส่ง</h2>
                  <p style={{ margin: "8px 0 0", opacity: 0.9 }}>รหัสงาน: {item.id}</p>
                </div>
                <div style={{ display: "flex", gap: "12px", alignItems: "center" }}>
                  <span style={{ ...styles.statusBadge, ...getStatusStyle(item.status) }}>
                    {getStatusText(item.status)}
                  </span>
                  {role === "admin" && (item.status === "pending" || item.status === "delivering") && (
                    <button
                      onClick={() => handleDeleteDelivery(item.delivery_id || item.id)}
                      style={styles.deleteBtn}
                    >
                      🗑️ ลบ
                    </button>
                  )}
                </div>
              </div>

              <div style={styles.bodyBox}>
                <div style={styles.row}>
                  <strong>ลูกค้า:</strong>
                  <span>{item.customerName}</span>
                </div>
                <div style={styles.row}>
                  <strong>เบอร์:</strong>
                  <span>{item.phone}</span>
                </div>
                <div style={styles.row}>
                  <strong>ที่อยู่:</strong>
                  <span>{item.address}</span>
                </div>
                <div style={styles.row}>
                  <strong>Map Pin:</strong>
                  <span>{item.mapPin || "-"}</span>
                </div>
                <div style={styles.row}>
                  <strong>สเปกถังแก๊สที่ต้องส่ง:</strong>
                  <span style={{ color: "#facc15", fontWeight: "bold" }}>
                    {(item.req_brand || item.brand || "-")} | {(item.req_gas_type || item.gasType || item.gas_type || "LPG")} | {(item.req_size || item.size || "-")}
                  </span>
                </div>
                <div style={styles.row}>
                  <strong>ผู้รับผิดชอบ:</strong>
                  <span>{item.assignedStaff || "ไม่ระบุชื่อ"}</span>
                </div>

                {(item.serial_number || item.deliveryCylinderId) && (
                  <div style={styles.row}>
                    <strong>Serial Number ถังที่ส่ง (สแกนรับแล้ว):</strong>
                    <span style={{ fontWeight: "bold", color: "#22c55e" }}>
                      {item.serial_number || item.deliveryCylinderId}
                    </span>
                  </div>
                )}
                {(item.received_serial_number || item.receivedCylinderId) && (
                  <div style={styles.row}>
                    <strong>Serial Number ถังที่รับคืน:</strong>
                    <span>{item.received_serial_number || item.receivedCylinderId}</span>
                  </div>
                )}
                {item.deliveredAt && (
                  <div style={styles.row}>
                    <strong>เวลาส่งสำเร็จ:</strong>
                    <span>{item.deliveredAt}</span>
                  </div>
                )}
              </div>

              {role === "staff" && item.status === "pending" && (
                <div style={styles.sectionBox}>
                  <h3>📷 ยืนยันรับงาน (สแกน QR Code ถังแก๊ส)</h3>

                  {scanningJobId === item.id ? (
                    <div style={{ textAlign: "center" }}>
                      <div id="qr-reader-container" style={{ maxWidth: "320px", margin: "0 auto", background: "white", borderRadius: "12px", overflow: "hidden" }}></div>
                      <button
                        onClick={() => setScanningJobId(null)}
                        style={{ ...styles.blueBtn, background: "#6b7280", marginTop: "10px" }}
                      >
                        ยกเลิกการสแกน
                      </button>
                    </div>
                  ) : (
                    <div>
                      <button
                        onClick={() => setScanningJobId(item.id)}
                        style={{ ...styles.greenBtn, marginBottom: "12px", width: "100%", display: "flex", justifyContent: "center", alignItems: "center", gap: "8px" }}
                      >
                        📷 เปิดกล้องสแกน QR Code
                      </button>

                      {confirmCylinderInputs[item.id] && (
                        <div style={{ background: "#1f2937", padding: "10px 14px", borderRadius: "8px", border: "1px solid #22c55e", marginBottom: "12px" }}>
                          <span style={{ fontSize: "12px", color: "#9ca3af" }}>Serial Number ที่สแกนได้: </span>
                          <strong style={{ fontSize: "16px", color: "#22c55e" }}>{confirmCylinderInputs[item.id]}</strong>
                        </div>
                      )}

                      <button
                        onClick={() => confirmReceiveJob(item)}
                        disabled={!confirmCylinderInputs[item.id]}
                        style={{
                          ...styles.blueBtn,
                          width: "100%",
                          opacity: confirmCylinderInputs[item.id] ? 1 : 0.5,
                          cursor: confirmCylinderInputs[item.id] ? "pointer" : "not-allowed",
                        }}
                      >
                        ✅ ตรวจสอบสเปกและรับงาน
                      </button>
                    </div>
                  )}
                </div>
              )}

              {role === "staff" && item.status === "delivering" && (
                <div style={styles.sectionBox}>
                  <h3>📸 ถ่ายรูปถังที่รับคืน (ต้องเห็น Serial Number ชัดเจน)</h3>
                  {item.proofImagePath ? (
                    <div>
                      <img
                        src={`${UPLOADS_BASE_URL}/${item.proofImagePath}`}
                        alt="proof"
                        style={styles.proofImage}
                      />
                      <p style={{ color: "#22c55e", fontWeight: "bold" }}>✅ อัปโหลดรูปแล้ว งานเสร็จสมบูรณ์</p>
                    </div>
                  ) : (
                    <>
                      <p style={{ color: "#f87171" }}>⚠️ ยังไม่มีรูปถังคืน</p>
                      <button
                        onClick={() => setSelectedProofId(selectedProofId === item.id ? null : item.id)}
                        style={styles.orangeBtn}
                      >
                        📸 ถ่ายรูปถังที่รับคืน
                      </button>
                      {selectedProofId === item.id && (
                        <input
                          type="file"
                          accept="image/*"
                          capture="environment"
                          onChange={(e) => handleProofUpload(item.id, e.target.files?.[0])}
                          style={{ color: "white", marginTop: "10px" }}
                        />
                      )}
                    </>
                  )}
                </div>
              )}

              {role === "admin" && item.status === "pending_approval" && (
                <div style={styles.sectionBox}>
                  <h3>🔍 อนุมัติงานส่ง</h3>
                  {item.proofImagePath && (
                    <div>
                      <p>
                        <strong>รูปถังที่รับคืน (โปรดดู Serial Number จากรูป):</strong>
                      </p>
                      <img
                        src={`${UPLOADS_BASE_URL}/${item.proofImagePath}`}
                        alt="return cylinder"
                        style={styles.proofImage}
                      />
                    </div>
                  )}
                  <input
                    type="text"
                    placeholder="กรอก Serial Number ถังที่รับคืน"
                    value={adminApproveInputs[item.id] ?? ""}
                    onChange={(e) =>
                      setAdminApproveInputs((prev) => ({
                        ...prev,
                        [item.id]: e.target.value,
                      }))
                    }
                    style={styles.input}
                  />
                  <button onClick={() => approveDelivery(item.id)} style={styles.greenBtn}>
                    ✅ อนุมัติงาน
                  </button>
                </div>
              )}

              {role === "admin" && item.proofImagePath && item.status !== "pending_approval" && (
                <div style={styles.sectionBox}>
                  <h3>หลักฐานการส่ง</h3>
                  <img
                    src={`${UPLOADS_BASE_URL}/${item.proofImagePath}`}
                    alt="proof"
                    style={styles.proofImage}
                  />
                </div>
              )}
            </div>
          ))
        ) : (
          <div style={styles.emptyBox}>ไม่พบข้อมูลงานจัดส่ง</div>
        )}
      </div>

      {showCylinderModal && (
        <div style={styles.modalOverlay}>
          <div style={styles.modalContent}>
            <h3>➕ เพิ่มถังใหม่ในระบบ</h3>
            <p>
              ไม่พบ Serial Number <strong>{newCylinderForApproval.serial_number}</strong> กรุณากรอกข้อมูลถังใหม่
            </p>
            <div style={styles.formGrid}>
              <div>
                <label>Serial Number *</label>
                <input
                  value={newCylinderForApproval.serial_number}
                  onChange={(e) =>
                    setNewCylinderForApproval({ ...newCylinderForApproval, serial_number: e.target.value })
                  }
                  style={styles.input}
                />
              </div>
              <div>
                <label>ยี่ห้อ *</label>
                <select
                  value={newCylinderForApproval.brand}
                  onChange={(e) =>
                    setNewCylinderForApproval({ ...newCylinderForApproval, brand: e.target.value })
                  }
                  style={styles.input}
                >
                  <option value="">-- เลือก --</option>
                  <option>ปตท.</option>
                  <option>World Gas</option>
                  <option>สยามแก๊ส</option>
                  <option>ยูนิคแก๊ส</option>
                  <option>PT Gas</option>
                  <option>พีเอพี</option>
                </select>
              </div>
              <div>
                <label>ชนิดแก๊ส</label>
                <select
                  value={newCylinderForApproval.gas_type}
                  onChange={(e) =>
                    setNewCylinderForApproval({ ...newCylinderForApproval, gas_type: e.target.value })
                  }
                  style={styles.input}
                >
                  <option>LPG</option>
                </select>
              </div>
              <div>
                <label>ขนาดถัง *</label>
                <select
                  value={newCylinderForApproval.size}
                  onChange={(e) =>
                    setNewCylinderForApproval({ ...newCylinderForApproval, size: e.target.value })
                  }
                  style={styles.input}
                >
                  <option value="">-- เลือก --</option>
                  <option>4 กก.</option>
                  <option>7 กก.</option>
                  <option>11.5 กก.</option>
                  <option>13.5 กก.</option>
                  <option>15 กก.</option>
                  <option>48 กก.</option>
                </select>
              </div>
              <div>
                <label>วันที่ผลิต *</label>
                <input
                  type="date"
                  value={newCylinderForApproval.manufacture_date}
                  onChange={(e) => {
                    const manu = e.target.value;
                    setNewCylinderForApproval({
                      ...newCylinderForApproval,
                      manufacture_date: manu,
                      expiry_date: calculateExpiry(manu),
                      next_check_date: calculateExpiry(manu),
                      last_check_date: manu,
                    });
                  }}
                  style={styles.input}
                />
              </div>
              <div>
                <label>วันหมดอายุ</label>
                <input
                  value={newCylinderForApproval.expiry_date}
                  disabled
                  style={{ ...styles.input, background: "#e5e7eb" }}
                />
              </div>
              <div>
                <label>QR Code</label>
                <input
                  value={newCylinderForApproval.qr_code}
                  onChange={(e) =>
                    setNewCylinderForApproval({ ...newCylinderForApproval, qr_code: e.target.value })
                  }
                  style={styles.input}
                />
              </div>
              <div>
                <label>สถานที่ปัจจุบัน</label>
                <input
                  value={newCylinderForApproval.current_location}
                  onChange={(e) =>
                    setNewCylinderForApproval({ ...newCylinderForApproval, current_location: e.target.value })
                  }
                  style={styles.input}
                />
              </div>
            </div>
            <div style={{ display: "flex", gap: "12px", justifyContent: "flex-end", marginTop: "20px" }}>
              <button onClick={() => setShowCylinderModal(false)} style={{ ...styles.blueBtn, background: "#6b7280" }}>
                ยกเลิก
              </button>
              <button onClick={handleCreateAndApprove} style={styles.blueBtn}>
                ✅ ยืนยันและอนุมัติ
              </button>
            </div>
          </div>
        </div>
      )}
    </Layout>
  );
}

const styles = {
  formCard: {
    background: "#1f2937",
    color: "white",
    padding: "20px",
    borderRadius: "12px",
    marginBottom: "20px",
  },
  formGrid: {
    display: "grid",
    gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))",
    gap: "12px",
    marginBottom: "16px",
  },
  card: {
    background: "#1f2937",
    color: "white",
    borderRadius: "16px",
    overflow: "hidden",
    boxShadow: "0 10px 25px rgba(0,0,0,0.2)",
  },
  headerBar: {
    background: "linear-gradient(90deg, #1d4ed8, #2563eb)",
    padding: "20px",
    display: "flex",
    justifyContent: "space-between",
    alignItems: "center",
    gap: "12px",
    flexWrap: "wrap",
  },
  statusBadge: {
    padding: "8px 14px",
    borderRadius: "999px",
    fontWeight: "bold",
    fontSize: "14px",
  },
  bodyBox: { padding: "20px" },
  row: {
    display: "flex",
    justifyContent: "space-between",
    gap: "12px",
    padding: "12px 0",
    borderBottom: "1px solid #374151",
    flexWrap: "wrap",
  },
  sectionBox: {
    margin: "0 20px 20px",
    padding: "20px",
    background: "#111827",
    borderRadius: "12px",
    position: "relative",
    zIndex: 20,
    pointerEvents: "auto",
  },
  input: {
    width: "100%",
    padding: "12px 14px",
    borderRadius: "8px",
    border: "1px solid #9ca3af",
    background: "#ffffff",
    color: "#111827",
    fontSize: "16px",
    boxSizing: "border-box",
    outline: "none",
    position: "relative",
    zIndex: 21,
    pointerEvents: "auto",
  },
  blueBtn: {
    padding: "10px 16px",
    border: "none",
    borderRadius: "10px",
    background: "#2563eb",
    color: "white",
    cursor: "pointer",
    fontWeight: "bold",
  },
  orangeBtn: {
    padding: "10px 16px",
    border: "none",
    borderRadius: "10px",
    background: "#f59e0b",
    color: "white",
    cursor: "pointer",
    fontWeight: "bold",
  },
  greenBtn: {
    padding: "10px 16px",
    border: "none",
    borderRadius: "10px",
    background: "#16a34a",
    color: "white",
    cursor: "pointer",
    fontWeight: "bold",
  },
  proofImage: {
    width: "180px",
    maxWidth: "100%",
    borderRadius: "12px",
    border: "2px solid #374151",
    marginBottom: "10px",
  },
  warningBox: {
    background: "#7c2d12",
    color: "white",
    padding: "14px 16px",
    borderRadius: "10px",
    marginBottom: "20px",
    fontWeight: "bold",
  },
  emptyBox: {
    background: "#1f2937",
    color: "white",
    padding: "20px",
    borderRadius: "12px",
  },
  deleteBtn: {
    padding: "6px 12px",
    border: "none",
    borderRadius: "8px",
    background: "#dc2626",
    color: "white",
    cursor: "pointer",
    fontWeight: "bold",
    fontSize: "12px",
  },
  modalOverlay: {
    position: "fixed",
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    background: "rgba(0,0,0,0.7)",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    zIndex: 1000,
  },
  modalContent: {
    background: "#1f2937",
    color: "white",
    padding: "20px",
    borderRadius: "16px",
    maxWidth: "800px",
    width: "90%",
    maxHeight: "90%",
    overflow: "auto",
  },
};

export default DeliveryPage;