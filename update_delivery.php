<?php
ob_clean();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

error_reporting(0);
ini_set("display_errors", 0);

$dbPaths = [
    __DIR__ . "/../../config/db.php",
    __DIR__ . "/../config/db.php",
    __DIR__ . "/../../../config/db.php",
    $_SERVER["DOCUMENT_ROOT"] . "/Backend/config/db.php"
];

$conn = null;
foreach ($dbPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

if (!$conn) {
    echo json_encode(["success" => false, "message" => "ไม่สามารถเชื่อมต่อฐานข้อมูลได้"]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$deliveryId = $input["delivery_id"] ?? $input["id"] ?? $_POST["delivery_id"] ?? $_POST["id"] ?? null;

if (!$deliveryId) {
    echo json_encode(["success" => false, "message" => "ไม่พบ delivery_id"]);
    exit();
}

$clean_id = intval(preg_replace("/[^0-9]/", "", $deliveryId));
$status = $input["status"] ?? "จัดส่งสำเร็จ";
$customer = $input["customer_name"] ?? $input["customer"] ?? null;

if ($customer) {
    $stmt = $conn->prepare("UPDATE deliveries SET status = ?, customer_name = ? WHERE delivery_id = ? OR id = ?");
    $stmt->bind_param("ssii", $status, $customer, $clean_id, $clean_id);
} else {
    $stmt = $conn->prepare("UPDATE deliveries SET status = ? WHERE delivery_id = ? OR id = ?");
    $stmt->bind_param("sii", $status, $clean_id, $clean_id);
}

if ($stmt && $stmt->execute()) {
    echo json_encode(["success" => true, "message" => "อัปเดตรายการจัดส่งเรียบร้อย"]);
} else {
    echo json_encode(["success" => false, "message" => "ไม่สามารถอัปเดตข้อมูลได้: " . $conn->error]);
}

$conn->close();
?>
