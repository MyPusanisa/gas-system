<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// ค้นหาไฟล์ db.php จากหลายๆ Path ที่เป็นไปได้
$configPath = __DIR__ . "/../../config/db.php";
if (!file_exists($configPath)) $configPath = __DIR__ . "/../config/db.php";
if (!file_exists($configPath)) $configPath = __DIR__ . "/../../db.php";

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบไฟล์ db.php"]);
    exit();
}

try {
    // 1. ถังทั้งหมด
    $res1 = $conn->query("SELECT COUNT(*) AS total FROM gas_cylinder");
    $total = $res1 ? ($res1->fetch_assoc()['total'] ?? 0) : 0;

    // 2. ในคลัง
    $res2 = $conn->query("SELECT COUNT(*) AS in_stock FROM gas_cylinder WHERE status = 'ในคลัง'");
    $in_stock = $res2 ? ($res2->fetch_assoc()['in_stock'] ?? 0) : 0;

    // 3. พร้อมใช้งาน
    $res3 = $conn->query("SELECT COUNT(*) AS ready FROM gas_cylinder WHERE status IN ('ปกติ', 'ในคลัง')");
    $ready = $res3 ? ($res3->fetch_assoc()['ready'] ?? 0) : 0;

    echo json_encode([
        "success" => true,
        "total_cylinders" => (int)$total,
        "in_stock" => (int)$in_stock,
        "ready_to_use" => (int)$ready
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
$conn->close();
?>