<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once "../../config/db.php";

// เลือกเฉพาะคอลัมน์ที่จำเป็นสำหรับ DeliveryPage โดยใช้ serial_number แทน cylinder_id
$sql = "SELECT 
            serial_number AS id,
            serial_number,
            status,
            size,
            brand,
            gas_type AS gasType,
            next_check_date AS nextCheckDate,
            expiry_date,
            current_location AS currentLocation
        FROM gas_cylinder
        ORDER BY serial_number ASC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["success" => false, "message" => $conn->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["success" => true, "data" => $data]);
?>