<?php
session_start();
include_once "../../config/db.php";

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$deliveryId = $input['delivery_id'] ?? null;
$mapPinFromStaff = trim($input['mapPin'] ?? '');

if (!$deliveryId || !$mapPinFromStaff) {
    echo json_encode(["success" => false, "message" => "Missing delivery_id or mapPin"]);
    exit;
}

// 1. ดึงข้อมูล delivery + customer map_pin
$query = "
    SELECT d.delivery_id, d.status, d.proof_image_path, c.map_pin AS customer_map_pin
    FROM delivery d
    JOIN customer c ON d.customer_id = c.customer_id
    WHERE d.delivery_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $deliveryId);
$stmt->execute();
$result = $stmt->get_result();
$delivery = $result->fetch_assoc();
$stmt->close();

if (!$delivery) {
    echo json_encode(["success" => false, "message" => "ไม่พบงานส่ง"]);
    exit;
}

if ($delivery['status'] !== 'delivering') {
    echo json_encode(["success" => false, "message" => "งานนี้ไม่ได้อยู่ในสถานะกำลังจัดส่ง"]);
    exit;
}

if (empty($delivery['proof_image_path'])) {
    echo json_encode(["success" => false, "message" => "กรุณาอัปโหลดรูปถังที่รับคืนก่อน"]);
    exit;
}

// 2. ตรวจสอบ mapPin ว่าตรงกับ customer.map_pin หรือไม่
$customerMapPin = trim($delivery['customer_map_pin'] ?? '');
if ($customerMapPin === '') {
    echo json_encode(["success" => false, "message" => "ลูกค้ารายนี้ไม่มี map_pin ในระบบ กรุณาติดต่อแอดมิน"]);
    exit;
}

// เปรียบเทียบแบบไม่สน case และ whitespace
if (strcasecmp(trim($mapPinFromStaff), trim($customerMapPin)) !== 0) {
    echo json_encode([
        "success" => false,
        "message" => "Map pin ไม่ตรงกับที่อยู่ของลูกค้า กรุณาตรวจสอบอีกครั้ง"
    ]);
    exit;
}

// 3. อัปเดต delivery เป็น pending_approval และบันทึก delivered_map_pin
$update = $conn->prepare("
    UPDATE delivery
    SET delivered_map_pin = ?, status = 'pending_approval'
    WHERE delivery_id = ?
");
$update->bind_param("si", $mapPinFromStaff, $deliveryId);
$update->execute();
$updated = $update->affected_rows > 0;
$update->close();

if ($updated) {
    echo json_encode(["success" => true, "message" => "ส่งขออนุมัติแล้ว รอแอดมินตรวจสอบ"]);
} else {
    echo json_encode(["success" => false, "message" => "ไม่สามารถอัปเดตสถานะได้"]);
}