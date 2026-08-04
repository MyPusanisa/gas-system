<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "../../config/db.php";

// ตรวจสอบการล็อกอินและ role admin
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized: เฉพาะแอดมินเท่านั้น"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$customerData = $input['customer'] ?? null;
$deliveryData = $input['delivery'] ?? null;

if (!$customerData || !$deliveryData) {
    echo json_encode(["success" => false, "message" => "Missing customer or delivery data"]);
    exit;
}

// ---- 1. จัดการลูกค้า (ใช้เบอร์โทรเป็นหลัก) ----
$name   = trim($customerData['name'] ?? '');
$phone  = trim($customerData['phone'] ?? '');
$address= trim($customerData['address'] ?? '');
$mapPin = trim($customerData['mapPin'] ?? '');

if (empty($name) || empty($phone) || empty($address)) {
    echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลลูกค้าให้ครบ"]);
    exit;
}

// ค้นหาลูกค้าจากเบอร์โทร
$findCustomer = $conn->prepare("SELECT customer_id, name, phone, address, map_pin FROM customer WHERE phone = ?");
$findCustomer->bind_param("s", $phone);
$findCustomer->execute();
$existingCustomer = $findCustomer->get_result()->fetch_assoc();
$findCustomer->close();

if ($existingCustomer) {
    // มีเบอร์นี้อยู่แล้ว -> อัปเดตข้อมูล
    $customerId = $existingCustomer['customer_id'];
    $updateCustomer = $conn->prepare("UPDATE customer SET name = ?, address = ?, map_pin = ? WHERE customer_id = ?");
    $updateCustomer->bind_param("sssi", $name, $address, $mapPin, $customerId);
    $updateCustomer->execute();
    $updateCustomer->close();
} else {
    // ลูกค้าใหม่ -> เพิ่มเข้าไป
    $insertCustomer = $conn->prepare("INSERT INTO customer (name, phone, address, map_pin) VALUES (?, ?, ?, ?)");
    $insertCustomer->bind_param("ssss", $name, $phone, $address, $mapPin);
    $insertCustomer->execute();
    $customerId = $insertCustomer->insert_id;
    $insertCustomer->close();
}

// ---- 2. ข้อมูลงานส่ง (รับ serial_number แทน cylinder_id) ----
$serialNumber = $deliveryData['serial_number'] ?? $deliveryData['cylinder_id'] ?? null; 
$staffId      = $deliveryData['staff_id'] ?? null;
$adminId      = $_SESSION['user_id'];

if (!$serialNumber) {
    echo json_encode(["success" => false, "message" => "กรุณาเลือกถังแก๊ส (Serial Number)"]);
    exit;
}
if (!$staffId) {
    echo json_encode(["success" => false, "message" => "กรุณาเลือกพนักงานส่ง"]);
    exit;
}

// ---- 3. ตรวจสอบ serial_number จากตาราง gas_cylinder ----
// 3.1 ถังมีอยู่จริง และ status ต้อง 'ในคลัง' หรือ 'ปกติ'
$checkCyl = $conn->prepare("SELECT serial_number, gas_type, brand, size, status FROM gas_cylinder WHERE serial_number = ?");
$checkCyl->bind_param("s", $serialNumber);
$checkCyl->execute();
$cylinder = $checkCyl->get_result()->fetch_assoc();
$checkCyl->close();

if (!$cylinder) {
    echo json_encode(["success" => false, "message" => "ไม่พบ Serial Number ถังแก๊สนี้ในระบบ"]);
    exit;
}
if (!in_array($cylinder['status'], ['ในคลัง', 'ปกติ'])) {
    echo json_encode(["success" => false, "message" => "ถังนี้ไม่พร้อมใช้งาน (สถานะปัจจุบัน: {$cylinder['status']})"]);
    exit;
}

// 3.2 ตรวจสอบว่า serial_number นี้มีงาน delivery ที่ยังไม่เสร็จหรือไม่
$checkDuplicate = $conn->prepare("
    SELECT delivery_id FROM delivery 
    WHERE serial_number = ? AND status NOT IN ('success', 'cancelled')
");
$checkDuplicate->bind_param("s", $serialNumber);
$checkDuplicate->execute();
$existingDelivery = $checkDuplicate->get_result()->fetch_assoc();
$checkDuplicate->close();

if ($existingDelivery) {
    echo json_encode(["success" => false, "message" => "ถังนี้มีงานส่งค้างอยู่ (รหัสงาน: {$existingDelivery['delivery_id']}) ไม่สามารถสร้างงานใหม่ได้"]);
    exit;
}

// ---- 4. ตรวจสอบ staff_id ----
$checkStaff = $conn->prepare("SELECT staff_id FROM delivery_staff WHERE staff_id = ?");
$checkStaff->bind_param("i", $staffId);
$checkStaff->execute();
$staffExist = $checkStaff->get_result()->num_rows > 0;
$checkStaff->close();

if (!$staffExist) {
    echo json_encode(["success" => false, "message" => "ไม่พบรหัสพนักงานส่ง"]);
    exit;
}

// ---- 5. เตรียมข้อมูลและทำ Transaction ----
$gasType = $cylinder['gas_type'];
$brand   = $cylinder['brand'];
$size    = $cylinder['size'];
$status  = 'pending';
$deliveryDate = date('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    // 5.1 บันทึกงานส่งลงตาราง delivery โดยอ้างอิง serial_number
    $stmt = $conn->prepare("
        INSERT INTO delivery 
        (customer_id, admin_id, staff_id, serial_number, gas_type, brand, size, status, delivery_date, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiissssss", $customerId, $adminId, $staffId, $serialNumber, $gasType, $brand, $size, $status, $deliveryDate);
    
    if (!$stmt->execute()) {
        throw new Exception("ไม่สามารถสร้างงานส่งได้: " . $stmt->error);
    }
    $deliveryId = $stmt->insert_id;
    $stmt->close();

    // 5.2 อัปเดตสถานะถังในตาราง gas_cylinder ผ่าน serial_number
    $updateCyl = $conn->prepare("UPDATE gas_cylinder SET status = 'กำลังจัดส่ง', current_location = 'รอจัดส่ง' WHERE serial_number = ?");
    $updateCyl->bind_param("s", $serialNumber);
    if (!$updateCyl->execute()) {
        throw new Exception("อัปเดตสถานะถังไม่สำเร็จ: " . $updateCyl->error);
    }
    $updateCyl->close();

    $conn->commit();
    echo json_encode(["success" => true, "delivery_id" => $deliveryId, "message" => "สร้างงานและอัปเดตสถานะถังเรียบร้อย"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>