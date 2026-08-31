<?php
// บังคับ Clear Output Buffer ไม่ให้มี Whitespace หรือ Error หลุดออกไปก่อน JSON
ob_clean();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ซ่อน Error Text ของ PHP เพื่อป้องกันไม่ให้ไปทำลายโครงสร้าง JSON
error_reporting(0);
ini_set('display_errors', 0);

// ค้นหาตำแหน่งไฟล์ db.php
$dbPaths = [
    __DIR__ . "/../../config/db.php",
    __DIR__ . "/../config/db.php",
    __DIR__ . "/../../../config/db.php",
    $_SERVER['DOCUMENT_ROOT'] . "/Backend/config/db.php"
];

$conn = null;
foreach ($dbPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

if (!$conn) {
    echo json_encode(["success" => false, "message" => "ไม่สามารถเชื่อมต่อฐานข้อมูลได้ (หาไฟล์ db.php ไม่พบ)"]);
    exit();
}

// รับค่า payload จาก React
$input = json_decode(file_get_contents("php://input"), true);
$deliveryId = $input['delivery_id'] ?? $input['id'] ?? $_POST['delivery_id'] ?? $_POST['id'] ?? $_GET['delivery_id'] ?? $_GET['id'] ?? null;

if (empty($deliveryId)) {
    echo json_encode(["success" => false, "message" => "ไม่ได้รับค่า ID งานจัดส่ง"]);
    exit();
}

// ลบรายการในตาราง deliveries
$stmt = $conn->prepare("DELETE FROM deliveries WHERE delivery_id = ?");

if ($stmt) {
    $stmt->bind_param("i", $deliveryId);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "ลบงานสำเร็จ"]);
        } else {
            echo json_encode(["success" => false, "message" => "ไม่พบรหัสงานนี้ในฐานข้อมูล"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการ execute: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "SQL Error: " . $conn->error]);
}

$conn->close();
?>