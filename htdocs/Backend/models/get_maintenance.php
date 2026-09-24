<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/db.php";

// ประวัติการตรวจจริงจากตาราง maintenance (ล่าสุดขึ้นก่อน)
$sql = "SELECT maintenance_id, serial_number, maintenance_date, maintenance_type,
               result, next_action, next_maintenance_date, description
        FROM maintenance
        ORDER BY maintenance_date DESC, maintenance_id DESC";

$res = $conn->query($sql);
if (!$res) {
    echo json_encode(["success" => false, "message" => $conn->error, "data" => []], JSON_UNESCAPED_UNICODE);
    exit();
}

$data = [];
while ($row = $res->fetch_assoc()) {
    // รายการเก่าเก็บ "สิ่งที่ต้องทำต่อ" ไว้ใน description
    if (empty($row['next_action']) && preg_match('/\[สิ่งที่ต้องทำต่อ: ([^\]]+)\]/u', (string)$row['description'], $m)) {
        $row['next_action'] = $m[1];
    }
    $row['description'] = trim(preg_replace('/\[สิ่งที่ต้องทำต่อ: [^\]]+\]/u', '', (string)$row['description']));
    $data[] = $row;
}

echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
$conn->close();
