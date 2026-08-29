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

// แก้ไขจาก cylinders เป็น gas_cylinder
$sql = "SELECT * FROM gas_cylinder WHERE expiry_date <= CURDATE() ORDER BY expiry_date ASC";
$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "items" => $data
], JSON_UNESCAPED_UNICODE);
?>