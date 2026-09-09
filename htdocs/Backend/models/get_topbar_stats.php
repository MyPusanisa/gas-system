<?php
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

// 1. ดึงค่าแก๊สในคลังล่าสุด
$gasLevel = 0;
$sqlGas = "SELECT gas_value FROM gas_sensor_logs ORDER BY id DESC LIMIT 1";
$resGas = $conn->query($sqlGas);
if ($resGas && $row = $resGas->fetch_assoc()) {
    $gasLevel = (int)$row['gas_value'];
}

// 2. ดึงจำนวนรายการที่รอ Admin อนุมัติ (ใช้เงื่อนไขเดียวกับ get_pending.php)
$successCount = 0;
$sqlSucc = "SELECT COUNT(*) as cnt FROM deliveries WHERE status = 'pending_approval' OR status = 'pending'";
$resSucc = $conn->query($sqlSucc);
if ($resSucc && $row = $resSucc->fetch_assoc()) {
    $successCount = (int)$row['cnt'];
}

echo json_encode([
    "success" => true,
    "gasLevel" => $gasLevel,
    "successCount" => $successCount
]);

$conn->close();
?>