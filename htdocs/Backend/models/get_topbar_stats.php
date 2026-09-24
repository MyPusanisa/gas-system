<?php
// เปิด Debug Error ชั่วคราว
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$configPath = __DIR__ . "/../config/db.php";
if (!file_exists($configPath)) {
    $configPath = __DIR__ . "/../db.php";
}

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบไฟล์ db.php"]);
    exit();
}

// ตรวจสอบว่ามีตัวแปร $conn และเชื่อมต่อสำเร็จหรือไม่
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(["success" => false, "message" => "เชื่อมต่อฐานข้อมูลล้มเหลว: " . ($conn->connect_error ?? "ไม่พบตัวแปร \$conn")]);
    exit();
}

$gasLevel = 0;
$successCount = 0;

// 1. ดึงค่าแก๊สในคลังล่าสุด
$sqlGas = "SELECT gas_value FROM gas_sensor_logs ORDER BY id DESC LIMIT 1";
$resGas = $conn->query($sqlGas);
if ($resGas && $row = $resGas->fetch_assoc()) {
    $gasLevel = (int)$row['gas_value'];
}

// 2. ดึงจำนวนรายการที่รอ Admin อนุมัติ
$sqlSucc = "SELECT COUNT(*) as cnt FROM deliveries WHERE status = 'pending_approval' OR status = 'pending'";
$resSucc = $conn->query($sqlSucc);
if ($resSucc && $row = $resSucc->fetch_assoc()) {
    $successCount = (int)$row['cnt'];
}

echo json_encode([
    "success" => true,
    "gasLevel" => $gasLevel,
    "successCount" => $successCount
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>