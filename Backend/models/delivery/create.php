<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "../../config/db.php";

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ถูกต้อง"]);
    exit;
}

$customerData = $input['customer'] ?? null;
$deliveryData = $input['delivery'] ?? null;

if (!$customerData || !$deliveryData) {
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่สมบูรณ์"]);
    exit;
}

// 1. จัดการข้อมูลลูกค้า
$name    = trim($customerData['name'] ?? '');
$phone   = trim($customerData['phone'] ?? '');
$address = trim($customerData['address'] ?? '');
$mapPin  = trim($customerData['mapPin'] ?? '');

if (empty($name) || empty($phone) || empty($address)) {
    echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลลูกค้าให้ครบถ้วน"]);
    exit;
}

$conn->begin_transaction();

try {
    // ค้นหาหรือเพิ่มลูกค้า
    $findCust = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ?");
    $findCust->bind_param("s", $phone);
    $findCust->execute();
    $resCust = $findCust->get_result()->fetch_assoc();
    $findCust->close();

    if ($resCust) {
        $customerId = $resCust['customer_id'];
        $upCust = $conn->prepare("UPDATE customers SET name = ?, address = ?, map_pin = ? WHERE customer_id = ?");
        $upCust->bind_param("sssi", $name, $address, $mapPin, $customerId);
        $upCust->execute();
        $upCust->close();
    } else {
        $inCust = $conn->prepare("INSERT INTO customers (name, phone, address, map_pin) VALUES (?, ?, ?, ?)");
        $inCust->bind_param("ssss", $name, $phone, $address, $mapPin);
        $inCust->execute();
        $customerId = $inCust->insert_id;
        $inCust->close();
    }

    // 2. จัดการสร้างงานจัดส่ง (บันทึกสเปก req_brand, req_gas_type, req_size)
    $brand   = $deliveryData['brand'] ?? '';
    $gasType = $deliveryData['gas_type'] ?? 'LPG';
    $size    = $deliveryData['size'] ?? '';
    $staffId = $deliveryData['staff_id'] ?? null;
    $status  = 'pending';

    if (empty($brand) || empty($size) || empty($staffId)) {
        throw new Exception("กรุณาระบุยี่ห้อ ขนาดถัง และพนักงานส่งให้ครบถ้วน");
    }

    $stmt = $conn->prepare("
        INSERT INTO delivery 
        (customer_id, staff_id, req_brand, req_gas_type, req_size, brand, gas_type, size, status, delivery_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iisssssss", $customerId, $staffId, $brand, $gasType, $size, $brand, $gasType, $size, $status);
    
    if (!$stmt->execute()) {
        throw new Exception("สร้างงานส่งไม่สำเร็จ: " . $stmt->error);
    }
    
    $conn->commit();
    echo json_encode(["success" => true, "message" => "สร้างงานจัดส่งเรียบร้อยแล้ว"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>