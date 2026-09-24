<?php
// ไม่แสดง PHP Error ออกหน้าเว็บ (กันข้อมูลระบบรั่ว)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// ค้นหาไฟล์ db.php แบบยืดหยุ่นเพื่อป้องกัน Path ผิด
$configPath = __DIR__ . "/../../config/db.php";
if (!file_exists($configPath)) {
    $configPath = __DIR__ . "/../config/db.php";
}
if (!file_exists($configPath)) {
    $configPath = __DIR__ . "/../../db.php";
}

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบไฟล์ db.php ในตำแหน่งที่กำหนด"]);
    exit();
}

// เช็กการเชื่อมต่อฐานข้อมูล
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(["success" => false, "message" => "เชื่อมต่อฐานข้อมูลล้มเหลว: " . ($conn->connect_error ?? "ไม่พบตัวแปร \$conn")]);
    exit();
}

try {
    $sql = "SELECT 
                s.staff_id,
                s.staff_name,
                s.staff_phone,
                s.username,
                s.address,
                s.status,
                COUNT(CASE WHEN d.status = 'pending' THEN 1 END) AS pending_jobs
            FROM delivery_staff s
            LEFT JOIN deliveries d ON s.staff_id = d.staff_id
            GROUP BY s.staff_id
            ORDER BY s.staff_id ASC";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $staffs = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $staffs
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>