<?php
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['cylinder_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$cylinder_id = $data['cylinder_id'];
$serial_number = $data['serial_number'] ?? '';
$brand = $data['brand'] ?? '';
$gas_type = $data['gas_type'] ?? 'LPG';
$size = $data['size'] ?? '';
$manufacture_date = $data['manufacture_date'] ?? '';
$expiry_date = $data['expiry_date'] ?? null;
$qr_code = $data['qr_code'] ?? '';
$last_check_date = $data['last_check_date'] ?? null;
$next_check_date = $data['next_check_date'] ?? null;
$delivered_date = $data['delivered_date'] ?? null;
$current_location = $data['current_location'] ?? '';
$status = $data['status'] ?? 'ในคลัง';

$stmt = $conn->prepare("UPDATE gas_cylinder SET 
    serial_number=?, brand=?, gas_type=?, size=?, manufacture_date=?, expiry_date=?, 
    qr_code=?, last_check_date=?, next_check_date=?, delivered_date=?, current_location=?, status=?
    WHERE cylinder_id=?");

$stmt->bind_param("sssssssssssss", 
    $serial_number, $brand, $gas_type, $size, $manufacture_date, $expiry_date, 
    $qr_code, $last_check_date, $next_check_date, $delivered_date, $current_location, $status,
    $cylinder_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>