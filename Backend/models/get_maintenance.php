<?php
// 1. ปลดล็อก CORS ให้ React ดึงข้อมูลได้
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// ดักจับ preflight request ของเบราว์เซอร์
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../config/db.php";

mysqli_set_charset($conn, "utf8mb4");

// 2. ปรับ Query แก้ตรง JOIN ให้เช็คเฉพาะ c.serial_number
$sql = "SELECT 
    m.maintenance_id,
    m.maintenance_date,
    m.description,
    m.maintenance_type,
    m.result,
    m.next_maintenance_date,
    m.cylinder_id,
    m.admin_id,
    COALESCE(c.serial_number, NULLIF(m.cylinder_id, '0'), '-') AS serial_number
FROM maintenance m
LEFT JOIN cylinders c ON m.cylinder_id = c.serial_number
ORDER BY m.maintenance_id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = array();

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
], JSON_UNESCAPED_UNICODE);

mysqli_close($conn);
?>