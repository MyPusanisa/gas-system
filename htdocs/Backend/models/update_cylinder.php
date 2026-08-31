<?php
require_once '../config/db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$serial_number = isset($data['serial_number']) ? trim($data['serial_number']) : null;

// เปลี่ยนมาเช็ค serial_number เป็นหลัก
if (!$serial_number) {
    echo json_encode(['success' => false, 'message' => 'Invalid data: ไม่พบ Serial Number']);
    exit;
}

$brand            = $data['brand'] ?? $data['brand_id'] ?? '';
$gas_type         = $data['gas_type'] ?? $data['gas_type_id'] ?? 'LPG';
$size             = $data['size'] ?? $data['size_id'] ?? '';
$manufacture_date = !empty($data['manufacture_date']) ? $data['manufacture_date'] : null;
$expiry_date      = !empty($data['expiry_date']) ? $data['expiry_date'] : (!empty($data['expire_date']) ? $data['expire_date'] : null);
$qr_code          = $data['qr_code'] ?? '';
$last_check_date  = !empty($data['last_check_date']) ? $data['last_check_date'] : (!empty($data['last_checked']) ? $data['last_checked'] : null);
$next_check_date  = !empty($data['next_check_date']) ? $data['next_check_date'] : (!empty($data['next_check']) ? $data['next_check'] : null);
$delivered_date   = !empty($data['delivered_date']) ? $data['delivered_date'] : (!empty($data['delivery_date']) ? $data['delivery_date'] : null);
$current_location = $data['current_location'] ?? $data['location_id'] ?? '';
$status           = $data['status'] ?? 'ในคลัง';

// อัปเดตข้อมูลโดยระบุ WHERE serial_number = ?
$stmt = $conn->prepare("UPDATE gas_cylinder SET 
    brand=?, gas_type=?, size=?, manufacture_date=?, expiry_date=?, 
    qr_code=?, last_check_date=?, next_check_date=?, delivered_date=?, current_location=?, status=?
    WHERE serial_number=?");

// ใช้ "ssssssssssss" (String ทั้งหมด 12 ตัว)
$stmt->bind_param("ssssssssssss", 
    $brand, $gas_type, $size, $manufacture_date, $expiry_date, 
    $qr_code, $last_check_date, $next_check_date, $delivered_date, $current_location, $status,
    $serial_number
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>