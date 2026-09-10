import { useEffect, useMemo, useState, useCallback } from "react";
import Layout from "../components/Layout";
import { Html5QrcodeScanner } from "html5-qrcode";
import API_BASE_URL from "../config";

const UPLOADS_BASE_URL = API_BASE_URL.replace(/\/models\/?$/, "/uploads");

// ฟังก์ชันดึงข้อมูลผู้ใช้จาก LocalStorage แบบรองรับหลายคีย์
const getInitialUserData = () => {
  const storedRole = localStorage.getItem("role") || "";
  const storedUsername =
    localStorage.getItem("username") ||
    localStorage.getItem("userName") ||
    localStorage.getItem("name") ||
    "";

  let storedStaffId = localStorage.getItem("staff_id") || "";
  if (!storedStaffId) {
    try {
      const userJson = JSON.parse(localStorage.getItem("user") || "{}");
      storedStaffId = userJson.id || userJson.staff_id || "";
    } catch (e) {
      storedStaffId = "";
    }
  }

  return { storedRole, storedUsername, storedStaffId };
};

function DeliveryPage() {
  const { storedRole, storedUsername, storedStaffId } = getInitialUserData();

  const [role, setRole] = useState(storedRole);
  const [username, setUsername] = useState(storedUsername);
  const [staffId, setStaffId] = useState(storedStaffId);

  const [deliveries, setDeliveries] = useState([]);
  const [cylinders, setCylinders] = useState([]);
  const [staffs, setStaffs] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [gasLevel, setGasLevel] = useState(0);
  const [loading, setLoading] = useState(true);

  const [selectedProofId, setSelectedProofId] = useState(null);
  const [confirmCylinderInputs, setConfirmCylinderInputs] = useState({});
  const [adminApproveInputs, setAdminApproveInputs] = useState({});

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

  const [isCustomerFound, setIsCustomerFound] = useState(false);
  const [isEditingCustomer, setIsEditingCustomer] = useState(false);
  const [editCustomerData, setEditCustomerData] = useState({
    old_phone: "",
    new_phone: "",
    name: "",
    address: "",
    map_pin: "",
  });

  const [newBrand, setNewBrand] = useState("");
  const [newGasType, setNewGasType] = useState("LPG");
  const [newSize, setNewSize] = useState("");
  const [newStaffId, setNewStaffId] = useState("");

  const apiFetch = async (url, options = {}) => {
    const token = localStorage.getItem("token") || "";

    const defaultHeaders = {
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };

    const res = await fetch(url, {
      ...options,
      credentials: "include",
      headers: {
        ...defaultHeaders,
        ...options.headers,
      },
    });

    if (!res.ok) {
      if (res.status === 401) {
        alert("เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่อีกครั้ง");
      }
      throw new Error(`HTTP ${res.status}`);
    }

    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error("Invalid JSON from server:", text.slice(0, 200));
      throw new Error("Server responded with non-JSON (likely PHP error)");
    }
  };

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
    customers.find((c) => (c.phone || c.customer_phone || "").trim() === phone.trim());

  const calculateExpiry = (manuDate) => {
    if (!manuDate) return "";
    const d = new Date(manuDate);
    d.setFullYear(d.getFullYear() + 5);
    return d.toISOString().split("T")[0];
  };

  const loadData = useCallback(async (isBackground = false) => {
    try {
      if (!isBackground) setLoading(true);

      apiFetch(`${API_BASE_URL}/gas/latest.php`)
        .then((res) => {
          if (res && res.success && res.data) {
            setGasLevel(Number(res.data.gas_value));
          }
        })
        .catch(() => {});

      const [deliveriesRes, cylindersRes, staffsRes, customersRes] = await Promise.allSettled([
        apiFetch(`${API_BASE_URL}/delivery/list.php`),
        apiFetch(`${API_BASE_URL}/cylinder/list.php`),
        apiFetch(`${API_BASE_URL}/staff/list.php`),
        apiFetch(`${API_BASE_URL}/customer/list.php`),
      ]);

      const staffList = staffsRes.status === "fulfilled" && staffsRes.value.success ? staffsRes.value.data : [];
      if (staffsRes.status === "fulfilled" && staffsRes.value.success) {
        setStaffs(staffList);
      }

      if (deliveriesRes.status === "fulfilled" && deliveriesRes.value.success) {
        const rawData = deliveriesRes.value.data || deliveriesRes.value.items || [];

        const formattedDeliveries = rawData.map((d) => {
          const realId = d.delivery_id || d.id;
          const currentStaffId = String(d.staff_id ?? d.staffId ?? "").trim();

          const matchedStaff = staffList.find(
            (s) =>
              (currentStaffId && String(s.staff_id) === currentStaffId) ||
              (d.staff_name && String(s.staff_name).includes(d.staff_name)) ||
              (d.assignedStaff && String(s.staff_name).includes(d.assignedStaff))
          );

          const staffName =
            d.assignedStaff ||
            d.staff_name ||
            (matchedStaff ? matchedStaff.staff_name : "") ||
            "ไม่ระบุชื่อ";

          return {
            ...d,
            id: realId,
            customerName: d.customerName || d.customer_name || d.name || "ไม่ระบุชื่อลูกค้า",
            phone: d.phone || d.customer_phone || "-",
            address: d.address || d.customer_address || "-",
            mapPin: d.mapPin || d.map_pin || "-",
            brand: d.brand || d.req_brand || "-",
            gasType: d.gasType || d.gas_type || d.req_gas_type || "LPG",
            size: d.size || d.req_size || "-",
            staff_id: currentStaffId || (matchedStaff ? String(matchedStaff.staff_id) : ""),
            assignedStaff: staffName,
            status: d.status || "pending",
            proofImagePath: d.proofImagePath || d.proof_image_path || "",
          };
        });

        setDeliveries(formattedDeliveries);
      }

      if (cylindersRes.status === "fulfilled" && cylindersRes.value.success) {
        setCylinders(cylindersRes.value.data);
      }

      if (customersRes.status === "fulfilled" && customersRes.value.success) {
        setCustomers(customersRes.value.data);
      }
    } catch (error) {
      console.error("โหลดข้อมูลล้มเหลว", error);
    } finally {
      if (!isBackground) setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadData();
    const interval = setInterval(() => {
      loadData(true);
    }, 10000);

    return () => clearInterval(interval);
  }, [loadData]);

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
          let extractedSerial = decodedText.split("/").pop().trim();

          try {
            const parsed = JSON.parse(extractedSerial);
            if (parsed && parsed.serial_number) {
              extractedSerial = String(parsed.serial_number).trim();
            }
          } catch (e) {}

          setConfirmCylinderInputs((prev) => ({
            ...prev,
            [scanningJobId]: extractedSerial,
          }));

          scanner.clear().catch((err) => console.error(err));
          setScanningJobId(null);
        },
        () => {}
      );
    }

    return () => {
      if (scanner) {
        scanner.clear().catch((err) => console.error(err));
      }
    };
  }, [scanningJobId]);

  const handleSearchCustomer = async () => {
    if (!newPhone.trim()) {
      alert("กรุณากรอกเบอร์โทรศัพท์เพื่อค้นหา");
      return;
    }

    const exist = findCustomerByPhone(newPhone);
    if (exist) {
      setNewCustomerName(exist.name || exist.customer_name || "");
      setNewAddress(exist.address || exist.customer_address || "");
      setNewMapPin(exist.mapPin || exist.map_pin || "");
      setIsCustomerFound(true);
      alert("พบข้อมูลลูกค้า");
    } else {
      try {
        const res = await apiFetch(`${API_BASE_URL}/customer/get.php?phone=${encodeURIComponent(newPhone.trim())}`);
        if (res && res.success && res.data) {
          setNewCustomerName(res.data.name || res.data.customer_name || "");
          setNewAddress(res.data.address || res.data.customer_address || "");
          setNewMapPin(res.data.map_pin || res.data.mapPin || "");
          setIsCustomerFound(true);
          alert("พบข้อมูลลูกค้า");
        } else {
          setIsCustomerFound(false);
          alert("ไม่พบข้อมูลลูกค้า เบอร์นี้สามารถกรอกข้อมูลลูกค้าใหม่ได้ทันที");
        }
      } catch (err) {
        console.error("Search customer error:", err);
        setIsCustomerFound(false);
        alert("ไม่พบข้อมูลลูกค้าในระบบ สามารถกรอกข้อมูลใหม่ได้ทันที");
      }
    }
  };

  const handleOpenEditCustomer = () => {
    setEditCustomerData({
      old_phone: newPhone,
      new_phone: newPhone,
      name: newCustomerName,
      address: newAddress,
      map_pin: newMapPin,
    });
    setIsEditingCustomer(true);
  };

  const handleSaveCustomerEdit = async () => {
    if (!editCustomerData.new_phone.trim()) {
      alert("กรุณากรอกเบอร์โทรศัพท์ใหม่");
      return;
    }

    try {
      const res = await apiFetch(`${API_BASE_URL}/customer/update.php`, {
        method: "PUT",
        body: JSON.stringify({
          old_phone: editCustomerData.old_phone,
          new_phone: editCustomerData.new_phone,
          name: editCustomerData.name,
          address: editCustomerData.address,
          map_pin: editCustomerData.map_pin,
        }),
      });

      if (res.success) {
        alert("อัปเดตข้อมูลลูกค้าเรียบร้อยแล้ว");
        setNewPhone(editCustomerData.new_phone);
        setNewCustomerName(editCustomerData.name);
        setNewAddress(editCustomerData.address);
        setNewMapPin(editCustomerData.map_pin);
        setIsEditingCustomer(false);
        await loadData();
      } else {
        alert(res.message || "แก้ไขข้อมูลไม่สำเร็จ");
      }
    } catch (error) {
      console.error("Update Customer Error:", error);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อ");
    }
  };

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
      status: "pending",
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
        setIsCustomerFound(false);

        await loadData();
      } else {
        alert(res.message || "สร้างงานไม่สำเร็จ");
      }
    } catch (error) {
      console.error("Create Delivery Error:", error);
      alert("เกิดข้อผิดพลาดในการสร้างงาน");
    }
  };

  const confirmReceiveJob = async (jobItem) => {
    const serialNumber = confirmCylinderInputs[jobItem.id]?.trim();
    if (!serialNumber) return alert("กรุณาสแกน QR Code ถังแก๊สเพื่อยืนยัน");

    const cylinder = cylinders.find((c) => c.serial_number === serialNumber);
    if (!cylinder) {
      alert(`ไม่พบถังแก๊ส Serial Number "${serialNumber}" ในระบบ`);
      return;
    }
    if (cylinder.status !== "ในคลัง") {
      alert(`ถังแก๊ส "${serialNumber}" ไม่อยู่ในคลัง (สถานะปัจจุบัน: ${cylinder.status})`);
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
        `สเปกถังแก๊สไม่ตรงตามเงื่อนไข!\n\n` +
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

        setDeliveries((prev) =>
          prev.map((d) =>
            d.id === jobItem.id ? { ...d, status: "delivering", serial_number: serialNumber } : d
          )
        );

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
        const uploadedPath = data.filePath || data.filename || data.path || "";

        const completeRes = await apiFetch(`${API_BASE_URL}/delivery/update.php`, {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            delivery_id: id,
            action: "complete_by_staff",
            proof_image_path: uploadedPath,
          }),
        });

        if (completeRes.success) {
          alert("ส่งรูปหลักฐานและส่งงานเรียบร้อยแล้ว (รอแอดมินอนุมัติ)");
          setSelectedProofId(null);

          setDeliveries((prevDeliveries) =>
            prevDeliveries.map((d) => {
              if (String(d.id) === String(id) || String(d.delivery_id) === String(id)) {
                return {
                  ...d,
                  status: "pending_approval",
                  proofImagePath: uploadedPath,
                  proof_image_path: uploadedPath,
                };
              }
              return d;
            })
          );

          await loadData();
        } else {
          alert(completeRes.message || "อัปเดตสถานะงานไม่สำเร็จ");
        }
      } else {
        alert(data.message || "อัปโหลดรูปภาพไม่สำเร็จ");
      }
    } catch (error) {
      console.error("Upload error:", error);
      alert("เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ");
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

  const handleDelete = async (item) => {
    const deliveryId = item.id || item.delivery_id;
    if (!deliveryId) {
      alert("ไม่พบรหัสงานจัดส่ง");
      return;
    }

    if (!window.confirm(`คุณต้องการลบงานรหัส ${deliveryId} ใช่หรือไม่?`)) return;

    try {
      const res = await fetch(`${API_BASE_URL}/cylinder/delete_delivery.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ delivery_id: deliveryId }),
      });

      const textData = await res.text();
      let data;

      try {
        data = JSON.parse(textData);
      } catch (e) {
        console.error("PHP Response ไม่ใช่ JSON:", textData);
        alert("เซิร์ฟเวอร์ตอบกลับมาไม่ถูกต้อง: " + textData.substring(0, 100));
        return;
      }

      alert(data.message || (data.success ? "ลบข้อมูลสำเร็จ" : "ลบข้อมูลไม่สำเร็จ"));

      if (data.success) {
        setDeliveries((prev) => prev.filter((d) => (d.id || d.delivery_id) !== deliveryId));
      }
    } catch (err) {
      console.error("Delete Error:", err);
      alert("เกิดข้อผิดพลาดในการเชื่อมต่อ");
    }
  };

  const availableStaffs = useMemo(() => staffs, [staffs]);

  const visibleDeliveries = useMemo(() => {
    let currentRole = localStorage.getItem("role") || role;
    let currentUsername =
      localStorage.getItem("username") ||
      localStorage.getItem("userName") ||
      localStorage.getItem("name") ||
      username;
    let currentStaffId = localStorage.getItem("staff_id") || staffId;

    if (!currentStaffId) {
      try {
        const userObj = JSON.parse(localStorage.getItem("user") || "{}");
        currentStaffId = userObj.id || userObj.staff_id || userObj.user_id || "";
      } catch (e) {}
    }

    if (currentRole === "admin" || currentRole === "ผู้ดูแลระบบ") {
      return deliveries.filter((d) => d.status !== "success");
    }

    return deliveries.filter((d) => {
      if (d.status === "pending_approval" || d.status === "success") {
        return false;
      }

      const dStaffId = String(d.staff_id ?? d.staffId ?? "").trim();
      const myStaffId = String(currentStaffId ?? "").trim();
      const dStaffName = String(d.assignedStaff ?? d.staff_name ?? "").toLowerCase();
      const myUsername = String(currentUsername ?? "").toLowerCase();

      const isMyJob =
        (myStaffId && dStaffId && dStaffId === myStaffId) ||
        (myUsername && myUsername !== "" && (dStaffName.includes(myUsername) || myUsername.includes(dStaffName)));

      const isUnassigned = !dStaffId || dStaffId === "0" || dStaffId === "null" || dStaffId === "";

      if (d.status === "pending") {
        return isMyJob || isUnassigned;
      }

      if (d.status === "delivering") {
        return isMyJob || isUnassigned || !myStaffId;
      }

      return false;
    });
  }, [deliveries, role, username, staffId]);

  const currentStaffActiveJob = useMemo(() => {
    let currentUsername =
      localStorage.getItem("username") ||
      localStorage.getItem("userName") ||
      localStorage.getItem("name") ||
      username;
    let currentStaffId = localStorage.getItem("staff_id") || staffId;

    if (!currentStaffId) {
      try {
        const userObj = JSON.parse(localStorage.getItem("user") || "{}");
        currentStaffId = userObj.id || userObj.staff_id || "";
      } catch (e) {}
    }

    return deliveries.find(
      (d) =>
        d.status === "delivering" &&
        ((currentUsername && (d.assignedStaff || "").includes(currentUsername)) ||
          (currentStaffId && String(d.staff_id) === String(currentStaffId)))
    );
  }, [deliveries, username, staffId]);

  const maintenanceDueItems = cylinders.filter((c) => {
    if (!c.nextCheckDate) return false;
    return new Date(c.nextCheckDate) <= new Date();
  });

  const deliverySuccessItems = deliveries.filter((d) => d.status === "success");

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
        <div style={{ color: "white", textAlign: "center", padding: "40px" }}>⏳ กำลังโหลดข้อมูล...</div>
      </Layout>
    );
  }

  return (
    <Layout
      gasLevel={gasLevel}
      maintenanceDueItems={maintenanceDueItems}
      expiredCylinderItems={[]}
      deliverySuccessItems={deliverySuccessItems}
      newAssignedJobItems={[]}
    >
      <h1 style={{ marginBottom: "20px" }}>Delivery Management</h1>

      {role === "admin" && (
        <div style={styles.formCard}>
          <h2 style={{ marginTop: 0 }}>สร้างงานจัดส่ง</h2>

          <div style={{ marginBottom: "16px" }}>
            <label style={{ display: "block", marginBottom: "6px", fontSize: "14px", fontWeight: "bold" }}>
              ค้นหาเบอร์โทรศัพท์ลูกค้า *
            </label>
            <div style={{ display: "flex", gap: "8px", flexWrap: "wrap" }}>
              <input
                placeholder="กรอกเบอร์โทรศัพท์เพื่อค้นหา"
                value={newPhone}
                onChange={(e) => {
                  setNewPhone(e.target.value);
                  setIsCustomerFound(false);
                }}
                style={{ ...styles.input, flex: 1, minWidth: "200px" }}
              />
              <button
                type="button"
                onClick={handleSearchCustomer}
                style={styles.blueBtn}
              >
                🔍 ค้นหา
              </button>
              {isCustomerFound && (
                <button
                  type="button"
                  onClick={handleOpenEditCustomer}
                  style={styles.yellowBtn}
                >
                  ✏️ แก้ไขข้อมูลลูกค้า
                </button>
              )}
            </div>
          </div>

          <div style={styles.formGrid}>
            <div>
              <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                ชื่อลูกค้า *
              </label>
              <input
                placeholder="ชื่อลูกค้า *"
                value={newCustomerName}
                onChange={(e) => setNewCustomerName(e.target.value)}
                style={styles.input}
              />
            </div>

            <div>
              <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                ที่อยู่ *
              </label>
              <input
                placeholder="ที่อยู่ *"
                value={newAddress}
                onChange={(e) => setNewAddress(e.target.value)}
                style={styles.input}
              />
            </div>

            <div>
              <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                Map Pin / พิกัด
              </label>
              <input
                placeholder="Map Pin / พิกัด"
                value={newMapPin}
                onChange={(e) => setNewMapPin(e.target.value)}
                style={styles.input}
              />
            </div>

            <div>
              <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                ยี่ห้อ *
              </label>
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
            </div>

            <div>
              <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                ชนิดแก๊ส *
              </label>
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
            </div>

            <div>
              <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                ขนาดถัง *
              </label>
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
            </div>

            <div>
              <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                พนักงานส่ง *
              </label>
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
          </div>

          <button onClick={createDeliveryJob} style={{ ...styles.blueBtn, marginTop: "12px" }}>
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
            <div key={item.id} id={`job-card-${item.id}`} style={styles.card}>
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
                      onClick={() => handleDelete(item)}
                      style={styles.deleteBtn}
                    >
                      ลบ
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
                  <strong>Map Pin / พิกัดบ้าน:</strong>
                  <span>
                    {item.mapPin && item.mapPin !== "-" ? (
                      <a
                        href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(item.mapPin)}`}
                        target="_blank"
                        rel="noreferrer"
                        style={{ color: "#60a5fa", textDecoration: "underline", fontWeight: "bold" }}
                      >
                        📍 {item.mapPin} (กดนำทาง Google Maps)
                      </a>
                    ) : (
                      "-"
                    )}
                  </span>
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
                  <button onClick={() => approveDelivery(item.id)} style={{ ...styles.greenBtn, marginTop: "10px" }}>
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

      {isEditingCustomer && (
        <div style={styles.modalOverlay}>
          <div style={{ ...styles.modalContent, maxWidth: "500px" }}>
            <h3 style={{ marginTop: 0 }}>✏️ แก้ไขข้อมูลลูกค้า</h3>
            <div style={{ display: "flex", flexDirection: "column", gap: "12px", marginTop: "16px" }}>
              <div>
                <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                  เบอร์โทรศัพท์ (Primary Key) *
                </label>
                <input
                  value={editCustomerData.new_phone}
                  onChange={(e) => setEditCustomerData({ ...editCustomerData, new_phone: e.target.value })}
                  style={styles.input}
                />
              </div>

              <div>
                <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                  ชื่อลูกค้า *
                </label>
                <input
                  value={editCustomerData.name}
                  onChange={(e) => setEditCustomerData({ ...editCustomerData, name: e.target.value })}
                  style={styles.input}
                />
              </div>

              <div>
                <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                  ที่อยู่ *
                </label>
                <textarea
                  value={editCustomerData.address}
                  onChange={(e) => setEditCustomerData({ ...editCustomerData, address: e.target.value })}
                  style={{ ...styles.input, height: "80px", resize: "vertical" }}
                />
              </div>

              <div>
                <label style={{ display: "block", marginBottom: "4px", fontSize: "12px", color: "#9ca3af" }}>
                  Map Pin / พิกัด
                </label>
                <input
                  value={editCustomerData.map_pin}
                  onChange={(e) => setEditCustomerData({ ...editCustomerData, map_pin: e.target.value })}
                  style={styles.input}
                />
              </div>
            </div>

            <div style={{ display: "flex", gap: "12px", justifyContent: "flex-end", marginTop: "20px" }}>
              <button onClick={() => setIsEditingCustomer(false)} style={{ ...styles.blueBtn, background: "#6b7280" }}>
                ยกเลิก
              </button>
              <button onClick={handleSaveCustomerEdit} style={styles.yellowBtn}>
                บันทึกการแก้ไข
              </button>
            </div>
          </div>
        </div>
      )}

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
  yellowBtn: {
    padding: "10px 16px",
    border: "none",
    borderRadius: "10px",
    background: "#f59e0b",
    color: "#000000",
    cursor: "pointer",
    fontWeight: "bold",
    whiteSpace: "nowrap",
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
    padding: "8px 16px",
    border: "none",
    borderRadius: "999px",
    background: "#dc2626",
    color: "white",
    cursor: "pointer",
    fontWeight: "bold",
    fontSize: "14px",
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