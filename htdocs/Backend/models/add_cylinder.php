<?php

require_once '../config/db.php';
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// ฟังก์ชันคำนวณค่าเริ่มต้น
function calculateExpiryDate($manufactureDate) {
    if (!$manufactureDate) return null;
    $date = new DateTime($manufactureDate);
    $date->modify('+10 years');
    return $date->format('Y-m-d');
}

function calculateNextCheckDate($manufactureDate) {
    if (!$manufactureDate) return null;
    $date = new DateTime($manufactureDate);
    $date->modify('+3 years');
    return $date->format('Y-m-d');
}

// รับค่าจาก React Frontend
$serial_number    = $data['serial_number'] ?? '';
// หากไม่ได้ส่ง cylinder_id มา ให้ใช้ serial_number แทน
$cylinder_id      = $data['cylinder_id'] ?? $serial_number; 
$brand            = $data['brand'] ?? '';
$gas_type         = $data['gas_type'] ?? 'LPG';
$size             = $data['size'] ?? '';
$manufacture_date = $data['manufacture_date'] ?? '';
$expiry_date      = $data['expiry_date'] ?? ($manufacture_date ? calculateExpiryDate($manufacture_date) : null);

// สร้าง URL สำหรับ QR Code หากไม่ได้ส่งมา
$qr_code          = $data['qr_code'] ?? ($serial_number ? "http://192.168.1.176:5173/cylinder/{$serial_number}" : '');

$last_check_date  = !empty($data['last_check_date']) ? $data['last_check_date'] : null;
$next_check_date  = $data['next_check_date'] ?? ($manufacture_date ? calculateNextCheckDate($manufacture_date) : null);
$delivered_date   = !empty($data['delivered_date']) ? $data['delivered_date'] : null;
$current_location = $data['current_location'] ?? '';
$status           = $data['status'] ?? 'ในคลัง';

// ตรวจสอบเฉพาะฟิลด์ที่จำเป็นจริง ๆ (ตัดการเช็ค $cylinder_id แบบบังคับออก)
if (!$serial_number || !$brand || !$gas_type || !$size || !$manufacture_date) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// ตรวจสอบรหัสถังซ้ำ (เช็คจาก cylinder_id หรือ serial_number)
$check = $conn->prepare("SELECT cylinder_id FROM gas_cylinder WHERE cylinder_id = ? OR serial_number = ?");
$check->bind_param("ss", $cylinder_id, $serial_number);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Serial Number หรือ Cylinder ID นี้มีในระบบแล้ว']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

$sql = "INSERT INTO gas_cylinder (serial_number, brand, gas_type, size, status, expiry_date, next_check_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt->bind_param("sssssssssssss", 
    $cylinder_id, $serial_number, $brand, $gas_type, $size, $manufacture_date, 
    $expiry_date, $qr_code, $last_check_date, $next_check_date, $delivered_date, 
    $current_location, $status
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'เพิ่มถังแก๊สสำเร็จ', 'cylinder_id' => $cylinder_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>