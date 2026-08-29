<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || $conn->connect_error) {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "Database Connection Failed", "data" => []]);
    exit();
}

// ค้นหารายชื่อตารางที่ถูกต้องใน Database เพื่อกันปัญหาชื่อตารางพัง
$tables = ['cylinders', 'cylinder', 'gas_cylinders'];
$target_table = '';

foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        $target_table = $table;
        break;
    }
}

if (empty($target_table)) {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "Table not found", "data" => []]);
    exit();
}

// ดึงถังที่ถึงกำหนดตรวจ (น้อยกว่าหรือเท่ากับวันปัจจุบัน)
$sql = "SELECT * FROM $target_table WHERE next_check_date <= CURDATE() ORDER BY next_check_date ASC";
$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

ob_end_clean();
http_response_code(200);
echo json_encode([
    "success" => true,
    "data" => $data
]);
exit();
?>