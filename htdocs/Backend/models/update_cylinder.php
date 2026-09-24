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

// อัปเดตเฉพาะฟิลด์ที่ส่งมา — ฟิลด์ที่ไม่ได้ส่งคงค่าเดิมในฐานข้อมูล
$allowed = [
    'brand', 'gas_type', 'size', 'manufacture_date', 'expiry_date',
    'last_check_date', 'next_check_date', 'delivered_date', 'current_location', 'status',
];

$sets = [];
$values = [];
foreach ($allowed as $col) {
    if (!array_key_exists($col, $data)) continue;
    $val = trim((string)($data[$col] ?? ''));
    $sets[] = "`$col` = ?";
    $values[] = $val === '' ? null : $val; // ค่าว่างให้เป็น NULL (คอลัมน์วันที่รับ '' ไม่ได้)
}

if (empty($sets)) {
    echo json_encode(['success' => true, 'message' => 'ไม่มีข้อมูลที่เปลี่ยนแปลง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$values[] = $serial_number;
$stmt = $conn->prepare("UPDATE gas_cylinder SET " . implode(', ', $sets) . " WHERE serial_number = ?");
$stmt->bind_param(str_repeat('s', count($values)), ...$values);

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
