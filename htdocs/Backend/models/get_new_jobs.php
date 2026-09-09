<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once "../config/db.php";

// ดึงข้อมูลโดยตรงจากตาราง deliveries โดยไม่ต้อง JOIN ตาราง customers
$query = "
    SELECT 
        delivery_id,
        customer_name,
        phone,
        address,
        map_pin,
        brand AS req_brand,
        gas_type AS req_gas_type,
        size AS req_size,
        staff_id,
        status,
        created_at
    FROM deliveries 
    WHERE status = 'pending'
    ORDER BY delivery_id DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => mysqli_error($conn)]);
    exit;
}

$data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
?>