<?php
ob_start();
error_reporting(0);
ini_set("display_errors", 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    ob_end_clean();
    http_response_code(200);
    exit();
}

// เชื่อมต่อตรงโดยไม่ใช้ require
$conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3306);
if ($conn->connect_error) {
    $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3308);
}

$gasLevel = 0;
$successCount = 0;

if ($conn && !$conn->connect_error) {
    $resGas = @$conn->query("SELECT gas_value FROM gas_sensor_logs ORDER BY id DESC LIMIT 1");
    if ($resGas && $row = $resGas->fetch_assoc()) {
        $gasLevel = (int)$row["gas_value"];
    } else {
        $resStock = @$conn->query("SELECT COUNT(*) as cnt FROM gas_cylinder WHERE current_status = 'ในคลัง' OR status = 'ในคลัง'");
        if ($resStock && $row = $resStock->fetch_assoc()) {
            $gasLevel = (int)$row["cnt"];
        }
    }

    $resSucc = @$conn->query("SELECT COUNT(*) as cnt FROM deliveries WHERE status = 'pending_approval'");
    if (!$resSucc) {
        $resSucc = @$conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending_approval'");
    }
    if ($resSucc && $row = $resSucc->fetch_assoc()) {
        $successCount = (int)$row["cnt"];
    }
    $conn->close();
}

ob_end_clean();
http_response_code(200);
echo json_encode([
    "success" => true,
    "gasLevel" => $gasLevel,
    "successCount" => $successCount
], JSON_UNESCAPED_UNICODE);
exit();
