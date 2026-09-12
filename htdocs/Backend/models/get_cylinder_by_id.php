<?php
// ปิดการแสดง Error HTML เพื่อไม่ให้ JSON พัง
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "127.0.0.1";
$user = "root";
$password = "";
$dbname = "gas_system";
$port = 3308;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database Connection Error: " . $e->getMessage()]);
    exit();
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($id)) {
    echo json_encode(["success" => false, "message" => "ไม่ได้ระบุ Serial Number หรือ ID"]);
    exit;
}

try {
    // 1. ค้นหาในตาราง gas_cylinder ตาม serial_number
    $stmt = $pdo->prepare("SELECT gc.*, d.status AS delivery_status, d.created_at AS delivered_date 
                           FROM gas_cylinder gc 
                           LEFT JOIN deliveries d ON gc.serial_number = d.serial_number
                           WHERE gc.serial_number = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $cylinder = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cylinder) {
        $status = $cylinder['status'];
        $delStatus = strtolower(trim($cylinder['delivery_status'] ?? ''));
        $isSuccess = ($status === 'success' || $status === 'จัดส่งสำเร็จ' || $delStatus === 'success' || $delStatus === 'จัดส่งสำเร็จ');

        echo json_encode([
            "success" => true,
            "data" => [
                "serial_number" => $cylinder['serial_number'] ?? "-",
                "brand" => $cylinder['brand'] ?? "-",
                "gas_type" => $cylinder['gas_type'] ?? "-",
                "size" => $cylinder['size'] ?? "-",
                "status" => $cylinder['status'] ?? "-",
                "manufacture_date" => $cylinder['manufacture_date'] ?? "-",
                "expiry_date" => $cylinder['expiry_date'] ?? "-",
                "last_check_date" => $cylinder['last_check_date'] ?? "-",
                "next_check_date" => $cylinder['next_check_date'] ?? "-",
                "delivered_date" => $isSuccess ? ($cylinder['delivered_date'] ?? "-") : null
            ]
        ]);
        exit;
    }

    // 2. ค้นหาเพิ่มเติมในตาราง deliveries หากหาใน gas_cylinder ไม่เจอ
    $cleanDeliveryId = str_replace('delivery-', '', $id);
    $stmt2 = $pdo->prepare("SELECT * FROM deliveries WHERE delivery_id = :did OR serial_number = :id LIMIT 1");
    $stmt2->execute([':did' => $cleanDeliveryId, ':id' => $id]);
    $delivery = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($delivery) {
        $delStatus = strtolower(trim($delivery['status'] ?? ''));
        $isSuccess = ($delStatus === 'success' || $delStatus === 'จัดส่งสำเร็จ');

        echo json_encode([
            "success" => true,
            "data" => [
                "serial_number" => $delivery['serial_number'] ?? "-",
                "brand" => $delivery['brand'] ?? $delivery['req_brand'] ?? "-",
                "gas_type" => $delivery['gas_type'] ?? $delivery['req_gas_type'] ?? "-",
                "size" => $delivery['size'] ?? $delivery['req_size'] ?? "-",
                "status" => $delivery['status'] ?? "-",
                "manufacture_date" => "-",
                "expiry_date" => "-",
                "delivered_date" => $isSuccess ? ($delivery['created_at'] ?? $delivery['delivered_date'] ?? "-") : null
            ]
        ]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลถังแก๊สรหัส: " . $id]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Query Error: " . $e->getMessage()]);
}
?>