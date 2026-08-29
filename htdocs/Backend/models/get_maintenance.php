<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/db.php';

// ดึงประวัติการตรวจ เรียงจาก ID ล่าสุดลงไป
$sql = "SELECT * FROM maintenance ORDER BY maintenance_id DESC";
$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

ob_end_clean();
http_response_code(200);
echo json_encode(["success" => true, "data" => $data]);
exit();
?>