<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/db.php";

// ถังทั้งหมดพร้อมวันตรวจครั้งถัดไปจริง (หน้าเว็บกรองเฉพาะที่เลยกำหนด)
$sql = "SELECT cylinder_id, serial_number, brand, size, gas_type, status, current_location,
               COALESCE(next_check_date, next_inspection_date) AS next_check_date,
               DATEDIFF(COALESCE(next_check_date, next_inspection_date), CURDATE()) AS days_left
        FROM gas_cylinder
        ORDER BY days_left IS NULL, days_left ASC";

$res = $conn->query($sql);
if (!$res) {
    echo json_encode(["success" => false, "message" => $conn->error, "data" => []], JSON_UNESCAPED_UNICODE);
    exit();
}

$data = [];
while ($row = $res->fetch_assoc()) {
    $row['days_left'] = $row['days_left'] === null ? null : (int)$row['days_left'];
    $data[] = $row;
}

echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
$conn->close();
