<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

// เชื่อม DB
require_once '../config/db.php';

// SQL นับจำนวน
$sql = "SELECT COUNT(maintenance_id) AS total FROM maintenance";

// Query
$result = $conn->query($sql);

// เช็ค query
if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Query failed",
        "error" => $conn->error
    ]);

    exit();
}

// ดึงข้อมูล
$row = $result->fetch_assoc();

// ส่ง JSON
echo json_encode([
    "success" => true,
    "total" => (int)$row['total']
]);

$conn->close();

?>