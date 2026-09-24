<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];

$serial_number = trim($data['serial_number'] ?? '');
if ($serial_number === '') {
    echo json_encode(['success' => false, 'message' => 'ไม่พบ Serial Number'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ค่าว่างให้เป็น NULL (คอลัมน์วันที่รับ '' ไม่ได้)
$v = function ($key) use ($data) {
    $val = isset($data[$key]) ? trim((string)$data[$key]) : '';
    return $val === '' ? null : $val;
};

$brand            = $v('brand');
$gas_type         = $v('gas_type');
$size             = $v('size');
$manufacture_date = $v('manufacture_date');
$expiry_date      = $v('expiry_date');
$last_check_date  = $v('last_check_date');
$next_check_date  = $v('next_check_date');
$delivered_date   = $v('delivered_date');
$current_location = $v('current_location');
$status           = $v('status') ?? 'ในคลัง';

// qr_code ไม่ได้ส่งมาจากฟอร์ม จึงไม่แตะ (เดิมถูกล้างเป็นค่าว่างทุกครั้งที่แก้ไข)
$stmt = $conn->prepare("UPDATE gas_cylinder SET
    brand=?, gas_type=?, size=?, manufacture_date=?, expiry_date=?,
    last_check_date=?, next_check_date=?, delivered_date=?, current_location=?, status=?
    WHERE serial_number=?");
$stmt->bind_param("sssssssssss",
    $brand, $gas_type, $size, $manufacture_date, $expiry_date,
    $last_check_date, $next_check_date, $delivered_date, $current_location, $status,
    $serial_number
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
} else {
    $exists = $conn->prepare("SELECT 1 FROM gas_cylinder WHERE serial_number = ?");
    $exists->bind_param("s", $serial_number);
    $exists->execute();
    if ($exists->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => "ไม่พบถัง $serial_number ในระบบ"], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => true, 'message' => 'แก้ไขข้อมูลสำเร็จ'], JSON_UNESCAPED_UNICODE);
    }
}
$conn->close();
