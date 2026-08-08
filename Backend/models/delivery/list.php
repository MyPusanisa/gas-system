<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
include_once "../../config/db.php";

if (!isset($conn) || $conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . ($conn->connect_error ?? "unknown error")
    ]);
    exit;
}

// ปรับคำสั่ง SQL ดึงฟิลด์ req_brand, req_gas_type, req_size และ serial_number
$sql = "SELECT 
            d.delivery_id AS id,
            d.delivery_id,
            d.customer_id,
            c.name AS customerName,
            c.phone,
            c.address,
            c.map_pin AS mapPin,
            
            /* ดึงค่าสเปกที่ระบุไว้ในใบงาน */
            COALESCE(d.req_brand, d.brand) AS brand,
            COALESCE(d.req_brand, d.brand) AS req_brand,
            COALESCE(d.req_gas_type, d.gas_type) AS gasType,
            COALESCE(d.req_gas_type, d.gas_type) AS req_gas_type,
            COALESCE(d.req_size, d.size) AS size,
            COALESCE(d.req_size, d.size) AS req_size,
            
            /* ดึง Serial Number ของถังแก๊ส */
            COALESCE(d.serial_number, d.cylinder_id) AS serial_number,
            COALESCE(d.serial_number, d.cylinder_id) AS deliveryCylinderId,
            COALESCE(d.received_serial_number, d.received_cylinder_id) AS received_serial_number,
            COALESCE(d.received_serial_number, d.received_cylinder_id) AS receivedCylinderId,
            
            d.status,
            d.delivered_map_pin AS deliveredMapPin,
            d.proof_image_path AS proofImagePath,
            d.delivered_at AS deliveredAt,
            d.delivery_date AS deliveryDate,
            d.accepted_at,
            d.started_at,
            d.completed_at,
            s.staff_id,
            COALESCE(s.staff_name, 'ไม่ระบุชื่อ') AS assignedStaff,
            s.username AS staffUsername
        FROM delivery d
        LEFT JOIN customers c ON d.customer_id = c.customer_id
        LEFT JOIN delivery_staff s ON d.staff_id = s.staff_id
        ORDER BY d.delivery_id DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Query failed: " . $conn->error
    ]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $row['proofImagePath'] = $row['proofImagePath'] ?? '';
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>