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
    // สถานะที่นับว่า "อยู่ในคลัง"
    $inStockStatus = "status IN ('ในคลัง', 'ทั่วไป', 'ปกติ')";
    // ตารางมีคอลัมน์วันที่ซ้ำ 2 ชุด ใช้ชุดที่มีค่า
    $isDue     = "COALESCE(next_check_date, next_inspection_date) <= CURDATE()";
    $isExpired = "COALESCE(expiry_date, expire_date) <= CURDATE()";

    // ถังที่ยังอยู่กับร้าน (ในคลัง / นอกระบบ) = ทุกสถานะที่ไม่ใช่การจัดส่ง
    $notDelivered = "COALESCE(status, '') NOT IN ('กำลังจัดส่ง', 'จัดส่งสำเร็จ')";

    $sql = "SELECT
                SUM($notDelivered) AS not_delivered,
                SUM($inStockStatus) AS in_stock,
                SUM($inStockStatus AND $isDue) AS maintenance_due,
                SUM($inStockStatus AND $isExpired) AS expired
            FROM gas_cylinder";

    $res = $conn->query($sql);
    if (!$res) throw new Exception($conn->error);
    $row = $res->fetch_assoc();

    // ถังที่กำลังจัดส่ง / จัดส่งสำเร็จ นับจากตารางออเดอร์
    $resDel = $conn->query("SELECT COUNT(*) AS n FROM deliveries WHERE status IN ('pending', 'success')");
    if (!$resDel) throw new Exception($conn->error);
    $delivered = (int)($resDel->fetch_assoc()['n'] ?? 0);

    $total     = (int)($row['not_delivered'] ?? 0) + $delivered;
    $in_stock  = (int)($row['in_stock'] ?? 0);
    $due       = (int)($row['maintenance_due'] ?? 0);

    echo json_encode([
        "success" => true,
        // ถังทั้งหมด = ในคลัง/นอกระบบ + กำลังจัดส่ง/จัดส่งสำเร็จ (ทุกใบในระบบ)
        "total_cylinders" => $total,
        "delivering_or_delivered" => $delivered,
        // ในคลัง = สถานะในคลัง/ทั่วไป
        "in_stock" => $in_stock,
        // พร้อมใช้งาน = ในคลัง - ถังที่ถึงกำหนดตรวจบำรุง
        "ready_to_use" => max(0, $in_stock - $due),
        "maintenance_due" => $due,
        "expired" => (int)($row['expired'] ?? 0)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
$conn->close();
?>
