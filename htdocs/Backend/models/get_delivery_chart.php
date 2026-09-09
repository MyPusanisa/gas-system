<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once "../config/db.php";

// ✅ แก้ไขจาก delivery เป็น deliveries (เติม s)
$sql = "SELECT 
            status,
            COUNT(delivery_id) AS total
        FROM deliveries
        GROUP BY status";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => $conn->error
    ]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);

$conn->close();
?>