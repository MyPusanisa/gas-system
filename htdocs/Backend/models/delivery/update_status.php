<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$configPath = __DIR__ . "/../../config/db.php";
if (!file_exists($configPath)) {
    $configPath = __DIR__ . "/../../db.php";
}

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบไฟล์ db.php"]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$delivery_id = isset($input['delivery_id']) ? intval($input['delivery_id']) : 0;
$action = isset($input['action']) ? $input['action'] : '';

if ($delivery_id <= 0) {
    echo json_encode(["success" => false, "message" => "ไม่พบรหัสการจัดส่ง (delivery_id)"]);
    exit();
}

try {
    if ($action === 'approve') {
        // 1. ดึงข้อมูล staff_id และ serial_number จากการจัดส่ง
        $getDeliverySql = "SELECT staff_id, serial_number FROM deliveries WHERE delivery_id = ?";
        $stmtDelivery = $conn->prepare($getDeliverySql);
        $stmtDelivery->bind_param("i", $delivery_id);
        $stmtDelivery->execute();
        $deliveryResult = $stmtDelivery->get_result()->fetch_assoc();
        
        $staff_id = $deliveryResult ? $deliveryResult['staff_id'] : null;
        $serial_number = $deliveryResult ? $deliveryResult['serial_number'] : null;

        // 2. อัปเดตตาราง deliveries
        $updateSql = "UPDATE deliveries SET status = 'success' WHERE delivery_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("i", $delivery_id);
        
        if ($stmt->execute()) {
            // 3. อัปเดตจำนวนงานของพนักงาน
            if ($staff_id) {
                $countSql = "UPDATE delivery_staff SET delivery_count = delivery_count + 1 WHERE staff_id = ?";
                $stmtCount = $conn->prepare($countSql);
                $stmtCount->bind_param("i", $staff_id);
                $stmtCount->execute();
            }

            // 4. อัปเดตตาราง gas_cylinder ทันที
            if ($serial_number) {
                $today = date("Y-m-d");
                $cylinderSql = "UPDATE gas_cylinder SET status = 'จัดส่งสำเร็จ', delivered_date = ? WHERE serial_number = ?";
                $stmtCylinder = $conn->prepare($cylinderSql);
                $stmtCylinder->bind_param("ss", $today, $serial_number);
                $stmtCylinder->execute();
            }

            echo json_encode(["success" => true, "message" => "อนุมัติการจัดส่งและอัปเดตสถานะถังแก๊สสำเร็จ"]);
        } else {
            echo json_encode(["success" => false, "message" => "ไม่สามารถอัปเดตสถานะได้"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Action ไม่ถูกต้อง"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Exception: " . $e->getMessage()]);
}
?>