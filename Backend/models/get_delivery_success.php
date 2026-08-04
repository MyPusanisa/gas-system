<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../config/db.php";

if (!$conn) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// แก้ไขจาก JOIN customers เป็น JOIN customer
$sql = "SELECT 
            d.delivery_id AS id,
            d.delivery_date,
            d.return_date,
            d.status,
            d.created_at,
            c.customer_id,
            c.name AS customer_name
        FROM delivery d
        JOIN customers c ON d.customer_id = c.customer_id
        WHERE d.status IN ('สำเร็จ', 'success')
        ORDER BY d.delivery_id DESC
        LIMIT 50";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query error: " . $conn->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "count" => count($data),
    "items" => $data
], JSON_UNESCAPED_UNICODE);
?>