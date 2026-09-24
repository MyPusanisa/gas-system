<?php
// ซ่อน PHP HTML Error เพื่อป้องกันการหลุดไปรวมกับ JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true) ?: [];

$serial_number = trim($data['serial_number'] ?? '');
if ($serial_number === '') {
    echo json_encode(["success" => false, "message" => "กรุณาระบุ Serial Number"], JSON_UNESCAPED_UNICODE);
    exit();
}

// ค่าว่างให้เป็น NULL (คอลัมน์วันที่รับ '' ไม่ได้)
$v = function ($key) use ($data) {
    $val = isset($data[$key]) ? trim((string)$data[$key]) : '';
    return $val === '' ? null : $val;
};

// ตรวจ Serial ซ้ำก่อน เพื่อให้ข้อความผิดพลาดอ่านเข้าใจ
$check = $conn->prepare("SELECT 1 FROM gas_cylinder WHERE serial_number = ?");
$check->bind_param("s", $serial_number);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Serial Number \"$serial_number\" มีอยู่ในระบบแล้ว"], JSON_UNESCAPED_UNICODE);
    exit();
}

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

$stmt = $conn->prepare(
    "INSERT INTO gas_cylinder
        (serial_number, brand, gas_type, size, manufacture_date, expiry_date,
         last_check_date, next_check_date, delivered_date, current_location, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssssssssss",
    $serial_number, $brand, $gas_type, $size, $manufacture_date, $expiry_date,
    $last_check_date, $next_check_date, $delivered_date, $current_location, $status
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "เพิ่มถังแก๊สสำเร็จ"], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["success" => false, "message" => "บันทึกไม่สำเร็จ: " . $stmt->error], JSON_UNESCAPED_UNICODE);
}
$conn->close();
