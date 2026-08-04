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

$cylinder_id = $data['cylinder_id'] ?? '';
$serial_number = $data['serial_number'] ?? '';
$brand = $data['brand'] ?? '';
$gas_type = $data['gas_type'] ?? 'LPG';
$size = $data['size'] ?? '';
$manufacture_date = $data['manufacture_date'] ?? '';
$expiry_date = $data['expiry_date'] ?? ($manufacture_date ? calculateExpiryDate($manufacture_date) : null);
$qr_code = $data['qr_code'] ?? ($cylinder_id ? "https://yourdomain.com/cylinder/{$cylinder_id}" : '');
$last_check_date = $data['last_check_date'] ?? null;
$next_check_date = $data['next_check_date'] ?? ($manufacture_date ? calculateNextCheckDate($manufacture_date) : null);
$delivered_date = $data['delivered_date'] ?? null;
$current_location = $data['current_location'] ?? '';
$status = $data['status'] ?? 'ในคลัง';

// ตรวจสอบข้อมูลจำเป็น
if (!$cylinder_id || !$serial_number || !$brand || !$gas_type || !$size || !$manufacture_date) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// ตรวจสอบรหัสถังซ้ำ
$check = $conn->prepare("SELECT cylinder_id FROM gas_cylinder WHERE cylinder_id = ?");
$check->bind_param("s", $cylinder_id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Cylinder ID already exists']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

$stmt = $conn->prepare("INSERT INTO gas_cylinder 
    (cylinder_id, serial_number, brand, gas_type, size, manufacture_date, expiry_date, qr_code, last_check_date, next_check_date, delivered_date, current_location, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sssssssssssss", 
    $cylinder_id, $serial_number, $brand, $gas_type, $size, $manufacture_date, 
    $expiry_date, $qr_code, $last_check_date, $next_check_date, $delivered_date, 
    $current_location, $status
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cylinder added successfully', 'cylinder_id' => $cylinder_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?> 