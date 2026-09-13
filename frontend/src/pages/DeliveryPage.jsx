import { useEffect, useMemo, useState, useCallback } from "react";
import Layout from "../components/Layout";
import { Html5QrcodeScanner } from "html5-qrcode";
import API_BASE_URL from "../config";

const UPLOADS_BASE_URL = API_BASE_URL.replace(/\/models\/?$/, "/uploads");

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

  const [role] = useState(storedRole);
  const [username] = useState(storedUsername);
  const [staffId] = useState(storedStaffId);

  const [deliveries, setDeliveries] = useState([]);
  const [cylinders, setCylinders] = useState([]);
  const [staffs, setStaffs] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [gasLevel, setGasLevel] = useState(0);
  const [loading, setLoading] = useState(true);
  const [gettingLocationId, setGettingLocationId] = useState(null);

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
      throw new Error("Server responded with non-JSON");
    }
  };

  const brandOptions = useMemo(() => ["ปตท.", "World Gas", "สยามแก๊ส", "ยูนิคแก๊ส", "PT Gas", "พีเอพี"], []);
  const gasTypeOptions = useMemo(() => ["LPG"], []);
  const sizeOptions = useMemo(() => ["4 กก.", "7 กก.", "11.5 กก.", "13.5 กก.", "15 กก.", "48 กก."], []);

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

  const handleSaveCurrentLocation = (item) => {
    if (!navigator.geolocation) {
      alert("อุปกรณ์หรือเบราว์เซอร์นี้ไม่รองรับการดึงพิกัด GPS");
      return;
    }

    setGettingLocationId(item.id);

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const mapPinString = `${lat},${lng}`;

        try {
          const res = await apiFetch(`${API_BASE_URL}/customer/update.php`, {
            method: "PUT",
            body: JSON.stringify({
              old_phone: item.phone,
              new_phone: item.phone,
              name: item.customerName,
              address: item.address,
              map_pin: mapPinString,
            }),
          });

          if (res && res.success) {
            alert(`บันทึกพิกัด GPS สำเร็จ: ${mapPinString}`);
            await loadData();
          } else {
            alert(res.message || "บันทึกพิกัด GPS ไม่สำเร็จ");
          }
        } catch (err) {
          console.error("Error saving GPS location:", err);
          alert("เกิดข้อผิดพลาดในการเชื่อมต่อเพื่อบันทึกพิกัด GPS");
        } finally {
          setGettingLocationId(null);
        }
      },
      (error) => {
        setGettingLocationId(null);
        console.error("Geolocation error:", error);
        switch (error.code) {
          case error.PERMISSION_DENIED:
            alert("กรุณาเปิดสิทธิ์การเข้าถึงตำแหน่ง GPS บนอุปกรณ์/เบราว์เซอร์");
            break;
          case error.POSITION_UNAVAILABLE:
            alert("ไม่สามารถระบุตำแหน่งพิกัด GPS ในขณะนี้ได้");
            break;
          case error.TIMEOUT:
            alert("หมดเวลาในการดึงตำแหน่ง GPS กรุณาลองใหม่อีกครั้ง");
            break;
          default:
            alert("เกิดข้อผิดพลาดในการรับพิกัด GPS");
            break;
        }
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      }
    );
  };

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
      alert("พบข้อมูลลูกค้าในระบบ");
    } else {
      try {
        const res = await apiFetch(`${API_BASE_URL}/customer/get.php?phone=${encodeURIComponent(newPhone.trim())}`);
        if (res && res.success && res.data) {
          setNewCustomerName(res.data.name || res.data.customer_name || "");
          setNewAddress(res.data.address || res.data.customer_address || "");
          setNewMapPin(res.data.map_pin || res.data.mapPin || "");
          setIsCustomerFound(true);
          alert("พบข้อมูลลูกค้าในระบบ");
        } else {
          setIsCustomerFound(false);
          alert("ไม่พบข้อมูลลูกค้า เบอร์นี้สามารถกรอกเป็นลูกค้าใหม่ได้ทันที");
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
      alert("กรุณากรอกข้อมูลลูกค้าและพนักงานส่งให้ครบ");
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
          alert("ส่งรูปหลักฐานสำเร็จ รออนุมัติงาน");
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
        manufacture_date: new Date().toISOString().split("T")[0],
        expiry_date: calculateExpiry(new Date().toISOString().split("T")[0]),
        last_check_date: new Date().toISOString().split("T")[0],
        next_check_date: calculateExpiry(new Date().toISOString().split("T")[0]),
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
    if (!deliveryId) return alert("ไม่พบรหัสงานจัดส่ง");
    if (!window.confirm(`คุณต้องการลบงานรหัส #${deliveryId} ใช่หรือไม่?`)) return;

    try {
      const res = await apiFetch(`${API_BASE_URL}/cylinder/delete_delivery.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ delivery_id: deliveryId }),
      });

      alert(res.message || (res.success ? "ลบข้อมูลสำเร็จ" : "ลบข้อมูลไม่สำเร็จ"));
      if (res.success) {
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
    let currentUsername = (
      localStorage.getItem("username") ||
      localStorage.getItem("userName") ||
      localStorage.getItem("name") ||
      username
    ).toLowerCase();
    let currentStaffId = String(localStorage.getItem("staff_id") || staffId || "").trim();

    if (!currentStaffId) {
      try {
        const userObj = JSON.parse(localStorage.getItem("user") || "{}");
        currentStaffId = String(userObj.id || userObj.staff_id || userObj.user_id || "").trim();
      } catch (e) {}
    }

    if (currentRole === "admin" || currentRole === "ผู้ดูแลระบบ") {
      return deliveries.filter((d) => d.status !== "success");
    }

    return deliveries.filter((d) => {
      if (d.status === "pending_approval" || d.status === "success") return false;

      const dStaffId = String(d.staff_id ?? d.staffId ?? "").trim();
      const dStaffName = String(d.assignedStaff ?? d.staff_name ?? "").toLowerCase();

      const isMyJob =
        (currentStaffId !== "" && dStaffId === currentStaffId) ||
        (currentUsername !== "" && (dStaffName.includes(currentUsername) || currentUsername.includes(dStaffName)));

      const isUnassigned = !dStaffId || dStaffId === "0" || dStaffId === "null" || dStaffId === "";

      if (d.status === "pending") {
        return isMyJob || isUnassigned;
      }
      if (d.status === "delivering") {
        return true;
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
        return { background: "rgba(234, 179, 8, 0.2)", color: "#facc15", border: "1px solid #ca8a04" };
      case "delivering":
        return { background: "rgba(249, 115, 22, 0.2)", color: "#fb923c", border: "1px solid #ea580c" };
      case "pending_approval":
        return { background: "rgba(168, 85, 247, 0.2)", color: "#c084fc", border: "1px solid #9333ea" };
      case "success":
        return { background: "rgba(34, 197, 94, 0.2)", color: "#4ade80", border: "1px solid #16a34a" };
      default:
        return { background: "rgba(107, 114, 128, 0.2)", color: "#9ca3af", border: "1px solid #4b5563" };
    }
  };

  const getStatusText = (status) => {
    switch (status) {
      case "pending_approval":
        return "รออนุมัติ";
      case "pending":
        return "กำลังจัดส่ง";
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
        <div style={{ color: "white", textAlign: "center", padding: "60px 20px" }}>
          ⏳ กำลังโหลดข้อมูลงานจัดส่ง...
        </div>
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
      <div style={{ maxWidth: "800px", margin: "0 auto", padding: "0 12px 40px 12px" }}>
        
        {/* Card สร้างงานจัดส่ง (Admin) */}
        {role === "admin" && (
          <div style={styles.cardContainer}>
            <div style={styles.cardHeader}>
              <h2 style={{ margin: 0, fontSize: "1rem", fontWeight: "600", color: "#f8fafc" }}>
                สร้างงานจัดส่งใหม่
              </h2>
            </div>

            <div style={{ padding: "16px" }}>
              <div style={{ marginBottom: "16px" }}>
                <label style={styles.label}>ค้นหาเบอร์โทรศัพท์ลูกค้า *</label>
                <div style={{ display: "flex", gap: "8px", flexWrap: "wrap" }}>
                  <input
                    type="text"
                    placeholder="กรอกเบอร์โทรศัพท์..."
                    value={newPhone}
                    onChange={(e) => {
                      setNewPhone(e.target.value);
                      setIsCustomerFound(false);
                    }}
                    style={{ ...styles.input, flex: 1, minWidth: "180px" }}
                  />
                  <button type="button" onClick={handleSearchCustomer} style={styles.primaryBtn}>
                    🔍 ค้นหา
                  </button>
                  {isCustomerFound && (
                    <button type="button" onClick={handleOpenEditCustomer} style={styles.warningBtn}>
                      ✏️ แก้ไข
                    </button>
                  )}
                </div>
              </div>

              <div style={styles.formGrid}>
                <div>
                  <label style={styles.label}>ชื่อลูกค้า *</label>
                  <input
                    type="text"
                    placeholder="ระบุชื่อลูกค้า"
                    value={newCustomerName}
                    onChange={(e) => setNewCustomerName(e.target.value)}
                    style={styles.input}
                  />
                </div>

                <div>
                  <label style={styles.label}>Map Pin / พิกัด GPS</label>
                  <input
                    type="text"
                    placeholder="เช่น 13.818, 100.514"
                    value={newMapPin}
                    onChange={(e) => setNewMapPin(e.target.value)}
                    style={styles.input}
                  />
                </div>

                <div style={{ gridColumn: "1 / -1" }}>
                  <label style={styles.label}>ที่อยู่จัดส่ง *</label>
                  <textarea
                    rows={2}
                    placeholder="ระบุที่อยู่จัดส่งโดยละเอียด"
                    value={newAddress}
                    onChange={(e) => setNewAddress(e.target.value)}
                    style={{ ...styles.input, resize: "vertical" }}
                  />
                </div>

                <div>
                  <label style={styles.label}>ยี่ห้อถังแก๊ส *</label>
                  <select
                    value={newBrand}
                    onChange={(e) => setNewBrand(e.target.value)}
                    style={styles.select}
                  >
                    <option value="">-- เลือกยี่ห้อ --</option>
                    {brandOptions.map((b) => (
                      <option key={b} value={b}>{b}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label style={styles.label}>ชนิดแก๊ส *</label>
                  <select
                    value={newGasType}
                    onChange={(e) => setNewGasType(e.target.value)}
                    style={styles.select}
                  >
                    {gasTypeOptions.map((g) => (
                      <option key={g} value={g}>{g}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label style={styles.label}>ขนาดถังแก๊ส *</label>
                  <select
                    value={newSize}
                    onChange={(e) => setNewSize(e.target.value)}
                    style={styles.select}
                  >
                    <option value="">-- เลือกขนาด --</option>
                    {sizeOptions.map((s) => (
                      <option key={s} value={s}>{s}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label style={styles.label}>พนักงานจัดส่ง *</label>
                  <select
                    value={newStaffId}
                    onChange={(e) => setNewStaffId(e.target.value)}
                    style={styles.select}
                  >
                    <option value="">-- เลือกพนักงานส่ง --</option>
                    {availableStaffs.map((staff) => (
                      <option key={staff.staff_id} value={staff.staff_id}>
                        {staff.staff_id} - {staff.staff_name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div style={{ marginTop: "16px" }}>
                <button onClick={createDeliveryJob} style={{ ...styles.primaryBtn, width: "100%", padding: "10px", fontSize: "14px" }}>
                  สร้างงานจัดส่ง 
                </button>
              </div>
            </div>
          </div>
        )}

        {role === "staff" && currentStaffActiveJob && (
          <div style={styles.activeJobBanner}>
            คุณกำลังจัดส่งงาน #{currentStaffActiveJob.id} กรุณาส่งรูปหลักฐานเพื่อปิดงาน
          </div>
        )}

        {/* รายการงานจัดส่ง */}
        <h2 style={{ fontSize: "1.1rem", color: "#f8fafc", marginBottom: "14px", fontWeight: "600" }}>
          รายการงานจัดส่งทั้งหมด ({visibleDeliveries.length})
        </h2>

        <div style={{ display: "flex", flexDirection: "column", gap: "14px" }}>
          {visibleDeliveries.length > 0 ? (
            visibleDeliveries.map((item) => (
              <div key={item.id} style={styles.jobCard}>
                
                {/* Header */}
                <div style={styles.jobCardHeader}>
                  <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                    <span style={styles.jobTag}>งาน #{item.id}</span>
                    <span style={{ ...styles.badge, ...getStatusStyle(item.status) }}>
                      {getStatusText(item.status)}
                    </span>
                  </div>

                  {role === "admin" && (item.status === "pending" || item.status === "delivering") && (
                    <button onClick={() => handleDelete(item)} style={styles.dangerBtn}>
                      🗑️ ลบ
                    </button>
                  )}
                </div>

                {/* Body Details */}
                <div style={styles.jobCardBody}>
                  
                  {/* ลูกค้า */}
                  <div style={styles.infoRow}>
                    <span style={styles.iconSpan}> </span>
                    <div>
                      <div style={{ color: "#ffffff", fontWeight: "600", fontSize: "15px" }}>
                        {item.customerName}
                      </div>
                      <div style={{ color: "#94a3b8", fontSize: "13px" }}>{item.phone}</div>
                    </div>
                  </div>

                  {/* ที่อยู่ */}
                  <div style={styles.infoRow}>
                    <span style={styles.iconSpan}> </span>
                    <div style={{ color: "#cbd5e1", fontSize: "13px", lineHeight: "1.4" }}>
                      {item.address}
                    </div>
                  </div>

                  {/* สเปกแก๊ส & Serial */}
                  <div style={styles.specBox}>
                    <div style={{ fontSize: "12px", color: "#94a3b8", marginBottom: "4px" }}>สเปกแก๊ส:</div>
                    <div style={{ display: "flex", gap: "6px", flexWrap: "wrap", alignItems: "center" }}>
                      <span style={styles.specBadge}>
                        {(item.req_brand || item.brand || "-")}
                      </span>
                      <span style={styles.specBadge}>
                        {(item.req_gas_type || item.gasType || item.gas_type || "LPG")}
                      </span>
                      <span style={{ ...styles.specBadge, background: "#0284c7", color: "#fff" }}>
                        {(item.req_size || item.size || "-")}
                      </span>
                    </div>

                    {(item.serial_number || item.deliveryCylinderId) && (
                      <div style={{ marginTop: "8px", paddingTop: "8px", borderTop: "1px dashed #334155", fontSize: "13px" }}>
                        <span style={{ color: "#94a3b8" }}>Serial ถังที่ส่ง: </span>
                        <strong style={{ color: "#4ade80" }}>{item.serial_number || item.deliveryCylinderId}</strong>
                      </div>
                    )}
                  </div>

                  {/* พนักงานผู้รับผิดชอบ */}
                  <div style={{ fontSize: "13px", color: "#94a3b8", display: "flex", alignItems: "center", gap: "6px" }}>
                    
                    <span>ผู้รับผิดชอบ: <strong style={{ color: "#e2e8f0" }}>{item.assignedStaff || "ไม่ระบุชื่อ"}</strong></span>
                  </div>

                  {/* Action Bar ปุ่มดำเนินการ */}
                  <div style={styles.jobCardActions}>
                    
                    {/* ปุ่มปักหมุด GPS */}
                    {(!item.mapPin || item.mapPin === "-") ? (
                      <button
                        type="button"
                        onClick={() => handleSaveCurrentLocation(item)}
                        disabled={gettingLocationId === item.id}
                        style={styles.gpsPinBtn}
                      >
                       {gettingLocationId === item.id ? "กำลังบันทึกพิกัด..." : "ปักหมุดตำแหน่งปัจจุบัน"}
                      </button>
                    ) : (
                      <a
                        href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(item.mapPin)}`}
                        target="_blank"
                        rel="noreferrer"
                        style={styles.gpsLinkBtn}
                      >
                        เปิด Google Maps ({item.mapPin})
                      </a>
                    )}

                    {/* Staff: รับงาน */}
                    {role === "staff" && item.status === "pending" && (
                      <div style={{ width: "100%", marginTop: "6px" }}>
                        {scanningJobId === item.id ? (
                          <div style={{ textAlign: "center" }}>
                            <div id="qr-reader-container" style={{ width: "100%", background: "#fff", borderRadius: "8px", overflow: "hidden" }}></div>
                            <button
                              onClick={() => setScanningJobId(null)}
                              style={{ ...styles.secondaryBtn, width: "100%", marginTop: "8px" }}
                            >
                              ยกเลิกการสแกน
                            </button>
                          </div>
                        ) : (
                          <div style={{ display: "flex", flexDirection: "column", gap: "8px" }}>
                            <button onClick={() => setScanningJobId(item.id)} style={{ ...styles.secondaryBtn, background: "#0284c7" }}>
                             สแกน QR Code ถัง
                            </button>

                            {confirmCylinderInputs[item.id] && (
                              <div style={{ background: "#0f172a", padding: "8px", borderRadius: "6px", fontSize: "12px", textAlign: "center" }}>
                                Serial ที่สแกน: <strong style={{ color: "#4ade80" }}>{confirmCylinderInputs[item.id]}</strong>
                              </div>
                            )}

                            <button
                              onClick={() => confirmReceiveJob(item)}
                              disabled={!confirmCylinderInputs[item.id]}
                              style={{
                                ...styles.primaryBtn,
                                opacity: confirmCylinderInputs[item.id] ? 1 : 0.5,
                                cursor: confirmCylinderInputs[item.id] ? "pointer" : "not-allowed",
                              }}
                            >
                            ยืนยันรับงานส่ง
                            </button>
                          </div>
                        )}
                      </div>
                    )}

                    {/* Staff: ถ่ายรูปถังคืน */}
                    {role === "staff" && item.status === "delivering" && (
                      <div style={{ width: "100%", marginTop: "6px" }}>
                        <button
                          onClick={() => setSelectedProofId(selectedProofId === item.id ? null : item.id)}
                          style={styles.cameraBtn}
                        >
                        ถ่ายรูปถังที่รับคืน
                        </button>
                        {selectedProofId === item.id && (
                          <div style={{ marginTop: "8px" }}>
                            <input
                              type="file"
                              accept="image/*"
                              capture="environment"
                              onChange={(e) => handleProofUpload(item.id, e.target.files?.[0])}
                              style={{ color: "white", fontSize: "12px", width: "100%" }}
                            />
                          </div>
                        )}
                      </div>
                    )}

                    {/* Admin: อนุมัติงาน */}
                    {role === "admin" && item.status === "pending_approval" && (
                      <div style={{ width: "100%", display: "flex", flexDirection: "column", gap: "8px", marginTop: "6px" }}>
                        {item.proofImagePath && (
                          <img
                            src={`${UPLOADS_BASE_URL}/${item.proofImagePath}`}
                            alt="proof"
                            style={styles.proofImgPreview}
                          />
                        )}
                        <input
                          type="text"
                          placeholder="กรอก Serial ถังที่รับคืน"
                          value={adminApproveInputs[item.id] ?? ""}
                          onChange={(e) =>
                            setAdminApproveInputs((prev) => ({
                              ...prev,
                              [item.id]: e.target.value,
                            }))
                          }
                          style={styles.input}
                        />
                        <button onClick={() => approveDelivery(item.id)} style={{ ...styles.primaryBtn, background: "#16a34a" }}>
                          อนุมัติงานส่ง
                        </button>
                      </div>
                    )}

                  </div>
                </div>
              </div>
            ))
          ) : (
            <div style={styles.emptyCard}>ไม่มีรายการงานจัดส่งในขณะนี้</div>
          )}
        </div>

        {/* Modal แก้ไขลูกค้า */}
        {isEditingCustomer && (
          <div style={styles.modalOverlay}>
            <div style={styles.modalBody}>
              <h3 style={{ marginTop: 0, color: "#fff", fontSize: "16px" }}>แก้ไขข้อมูลลูกค้า</h3>
              <div style={{ display: "flex", flexDirection: "column", gap: "10px", marginTop: "14px" }}>
                <div>
                  <label style={styles.label}>เบอร์โทรศัพท์ *</label>
                  <input
                    value={editCustomerData.new_phone}
                    onChange={(e) => setEditCustomerData({ ...editCustomerData, new_phone: e.target.value })}
                    style={styles.input}
                  />
                </div>
                <div>
                  <label style={styles.label}>ชื่อลูกค้า *</label>
                  <input
                    value={editCustomerData.name}
                    onChange={(e) => setEditCustomerData({ ...editCustomerData, name: e.target.value })}
                    style={styles.input}
                  />
                </div>
                <div>
                  <label style={styles.label}>ที่อยู่ *</label>
                  <textarea
                    rows={2}
                    value={editCustomerData.address}
                    onChange={(e) => setEditCustomerData({ ...editCustomerData, address: e.target.value })}
                    style={styles.input}
                  />
                </div>
                <div>
                  <label style={styles.label}>Map Pin / พิกัด GPS</label>
                  <input
                    value={editCustomerData.map_pin}
                    onChange={(e) => setEditCustomerData({ ...editCustomerData, map_pin: e.target.value })}
                    style={styles.input}
                  />
                </div>
              </div>

              <div style={{ display: "flex", gap: "8px", marginTop: "16px" }}>
                <button onClick={() => setIsEditingCustomer(false)} style={{ ...styles.secondaryBtn, flex: 1 }}>
                  ยกเลิก
                </button>
                <button onClick={handleSaveCustomerEdit} style={{ ...styles.warningBtn, flex: 1 }}>
                  บันทึก
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Modal ลงทะเบียนถังใหม่ */}
        {showCylinderModal && (
          <div style={styles.modalOverlay}>
            <div style={styles.modalBody}>
              <h3 style={{ marginTop: 0, color: "#fff", fontSize: "16px" }}>➕ เพิ่มถังใหม่เข้าระบบ</h3>
              <p style={{ fontSize: "13px", color: "#94a3b8" }}>
                ไม่พบ Serial Number <strong style={{ color: "#facc15" }}>{newCylinderForApproval.serial_number}</strong> ในฐานข้อมูล
              </p>

              <div style={{ display: "flex", flexDirection: "column", gap: "10px", marginTop: "12px" }}>
                <div>
                  <label style={styles.label}>Serial Number *</label>
                  <input
                    value={newCylinderForApproval.serial_number}
                    onChange={(e) => setNewCylinderForApproval({ ...newCylinderForApproval, serial_number: e.target.value })}
                    style={styles.input}
                  />
                </div>
                <div>
                  <label style={styles.label}>ยี่ห้อ *</label>
                  <select
                    value={newCylinderForApproval.brand}
                    onChange={(e) => setNewCylinderForApproval({ ...newCylinderForApproval, brand: e.target.value })}
                    style={styles.select}
                  >
                    <option value="">-- เลือกยี่ห้อ --</option>
                    {brandOptions.map((b) => (
                      <option key={b} value={b}>{b}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label style={styles.label}>ชนิดแก๊ส</label>
                  <select
                    value={newCylinderForApproval.gas_type}
                    onChange={(e) => setNewCylinderForApproval({ ...newCylinderForApproval, gas_type: e.target.value })}
                    style={styles.select}
                  >
                    <option value="LPG">LPG</option>
                  </select>
                </div>
                <div>
                  <label style={styles.label}>ขนาดถัง *</label>
                  <select
                    value={newCylinderForApproval.size}
                    onChange={(e) => setNewCylinderForApproval({ ...newCylinderForApproval, size: e.target.value })}
                    style={styles.select}
                  >
                    <option value="">-- เลือกขนาด --</option>
                    {sizeOptions.map((s) => (
                      <option key={s} value={s}>{s}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label style={styles.label}>วันที่ผลิต *</label>
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
              </div>

              <div style={{ display: "flex", gap: "8px", marginTop: "16px" }}>
                <button onClick={() => setShowCylinderModal(false)} style={{ ...styles.secondaryBtn, flex: 1 }}>
                  ยกเลิก
                </button>
                <button onClick={handleCreateAndApprove} style={{ ...styles.primaryBtn, flex: 1 }}>
                  ยืนยันและอนุมัติ
                </button>
              </div>
            </div>
          </div>
        )}

      </div>
    </Layout>
  );
}

const styles = {
  cardContainer: {
    background: "#1e293b",
    border: "1px solid #334155",
    borderRadius: "12px",
    overflow: "hidden",
    marginBottom: "20px",
    boxShadow: "0 4px 12px rgba(0, 0, 0, 0.2)",
  },
  cardHeader: {
    padding: "12px 16px",
    background: "#0f172a",
    borderBottom: "1px solid #334155",
  },
  formGrid: {
    display: "grid",
    gridTemplateColumns: "repeat(auto-fit, minmax(180px, 1fr))",
    gap: "12px",
  },
  label: {
    display: "block",
    fontSize: "12px",
    fontWeight: "500",
    color: "#94a3b8",
    marginBottom: "4px",
  },
  input: {
    width: "100%",
    padding: "8px 12px",
    borderRadius: "6px",
    border: "1px solid #334155",
    background: "#0f172a",
    color: "#f8fafc",
    fontSize: "13px",
    boxSizing: "border-box",
    outline: "none",
  },
  select: {
    width: "100%",
    padding: "8px 12px",
    borderRadius: "6px",
    border: "1px solid #334155",
    background: "#0f172a",
    color: "#f8fafc",
    fontSize: "13px",
    boxSizing: "border-box",
    cursor: "pointer",
  },
  primaryBtn: {
    padding: "8px 14px",
    background: "#0284c7",
    color: "#ffffff",
    border: "none",
    borderRadius: "6px",
    fontSize: "13px",
    fontWeight: "600",
    cursor: "pointer",
    whiteSpace: "nowrap",
  },
  secondaryBtn: {
    padding: "8px 14px",
    background: "#475569",
    color: "#ffffff",
    border: "none",
    borderRadius: "6px",
    fontSize: "13px",
    fontWeight: "600",
    cursor: "pointer",
    whiteSpace: "nowrap",
  },
  warningBtn: {
    padding: "8px 14px",
    background: "#d97706",
    color: "#ffffff",
    border: "none",
    borderRadius: "6px",
    fontSize: "13px",
    fontWeight: "600",
    cursor: "pointer",
    whiteSpace: "nowrap",
  },
  dangerBtn: {
    padding: "4px 8px",
    background: "rgba(239, 68, 68, 0.2)",
    color: "#f87171",
    border: "1px solid #ef4444",
    borderRadius: "6px",
    fontSize: "12px",
    fontWeight: "600",
    cursor: "pointer",
  },
  badge: {
    fontFamily: "'Prompt', sans-serif",
    padding: "2px 8px",
    borderRadius: "12px",
    fontSize: "11px",
    fontWeight: "bold",
  },
  jobCard: {
    background: "#1e293b",
    border: "1px solid #334155",
    borderRadius: "12px",
    overflow: "hidden",
    boxShadow: "0 2px 8px rgba(0,0,0,0.15)",
  },
  jobCardHeader: {
    padding: "10px 14px",
    background: "#0f172a",
    borderBottom: "1px solid #334155",
    display: "flex",
    justifyContent: "space-between",
    alignItems: "center",
  },
  jobTag: {
    fontSize: "13px",
    fontWeight: "bold",
    color: "#38bdf8",
    background: "rgba(56, 189, 248, 0.1)",
    padding: "2px 8px",
    borderRadius: "4px",
  },
  jobCardBody: {
    padding: "14px",
    display: "flex",
    flexDirection: "column",
    gap: "12px",
  },
  infoRow: {
    display: "flex",
    gap: "10px",
    alignItems: "flex-start",
  },
  iconSpan: {
    fontSize: "16px",
    lineHeight: "1.2",
  },
  specBox: {
    background: "#0f172a",
    padding: "10px",
    borderRadius: "8px",
    border: "1px solid #1e293b",
  },
  specBadge: {
    background: "#334155",
    color: "#f8fafc",
    padding: "3px 8px",
    borderRadius: "4px",
    fontSize: "12px",
    fontWeight: "500",
  },
  jobCardActions: {
    marginTop: "4px",
    display: "flex",
    flexDirection: "column",
    gap: "8px",
  },
  gpsPinBtn: {
    width: "100%",
    padding: "8px 12px",
    background: "#0284c7",
    color: "#ffffff",
    border: "none",
    borderRadius: "6px",
    fontSize: "13px",
    fontWeight: "600",
    cursor: "pointer",
    textAlign: "center",
  },
  gpsLinkBtn: {
    display: "block",
    textAlign: "center",
    padding: "6px 10px",
    background: "rgba(56, 189, 248, 0.1)",
    color: "#38bdf8",
    border: "1px solid #0284c7",
    borderRadius: "6px",
    fontSize: "12px",
    textDecoration: "none",
    fontWeight: "500",
  },
  cameraBtn: {
    width: "100%",
    padding: "10px",
    background: "#d97706",
    color: "#ffffff",
    border: "none",
    borderRadius: "6px",
    fontSize: "13px",
    fontWeight: "600",
    cursor: "pointer",
  },
  emptyCard: {
    background: "#1e293b",
    padding: "24px",
    borderRadius: "10px",
    textAlign: "center",
    color: "#94a3b8",
    fontSize: "13px",
    border: "1px solid #334155",
  },
  activeJobBanner: {
    background: "#9a3412",
    color: "#ffedd5",
    padding: "10px 14px",
    borderRadius: "8px",
    marginBottom: "14px",
    fontSize: "13px",
    fontWeight: "bold",
    textAlign: "center",
  },
  proofImgPreview: {
    width: "100%",
    maxHeight: "140px",
    objectFit: "cover",
    borderRadius: "6px",
    border: "1px solid #334155",
  },
  modalOverlay: {
    position: "fixed",
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    background: "rgba(0, 0, 0, 0.75)",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    zIndex: 9999,
    padding: "16px",
  },
  modalBody: {
    background: "#1e293b",
    border: "1px solid #334155",
    borderRadius: "12px",
    padding: "18px",
    width: "100%",
    maxWidth: "400px",
    maxHeight: "90vh",
    overflowY: "auto",
    boxSizing: "border-box",
  },
};

export default DeliveryPage;