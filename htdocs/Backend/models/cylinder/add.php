<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once "../../config/db.php";

$input = json_decode(file_get_contents('php://input'), true);

$serial = $input['serial_number'] ?? '';
$brand = $input['brand'] ?? '';
$gas_type = $input['gas_type'] ?? $input['gasType'] ?? '';
$size = $input['size'] ?? '';
$status = $input['status'] ?? 'normal';
$location = $input['current_location'] ?? $input['currentLocation'] ?? 'คลังสินค้า';

if (empty($serial)) {
    echo json_encode(["success" => false, "message" => "กรุณาระบุ Serial Number"]);
    exit();
}

try {
    $sql = "INSERT INTO gas_cylinder (serial_number, brand, gas_type, size, status, current_location) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $serial, $brand, $gas_type, $size, $status, $location);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "เพิ่มถังแก๊สสำเร็จ"]);
    } else {
        echo json_encode(["success" => false, "message" => "Serial Number นี้มีในระบบแล้ว"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>