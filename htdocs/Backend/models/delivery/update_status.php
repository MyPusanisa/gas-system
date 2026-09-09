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

// รับข้อมูล JSON จาก Frontend
$input = json_decode(file_get_contents('php://input'), true);

$delivery_id = isset($input['delivery_id']) ? intval($input['delivery_id']) : 0;
$action = isset($input['action']) ? $input['action'] : '';

if ($delivery_id <= 0) {
    echo json_encode(["success" => false, "message" => "ไม่พบรหัสการจัดส่ง (delivery_id)"]);
    exit();
}

try {
    if ($action === 'approve') {
        // 1. ค้นหา staff_id ของงานนี้ก่อน
        $getStaffSql = "SELECT staff_id FROM deliveries WHERE delivery_id = ?";
        $stmtStaff = $conn->prepare($getStaffSql);
        $stmtStaff->bind_param("i", $delivery_id);
        $stmtStaff->execute();
        $staffResult = $stmtStaff->get_result()->fetch_assoc();
        $staff_id = $staffResult ? $staffResult['staff_id'] : null;

        // 2. อัปเดตสถานะงานจัดส่งเป็น success
        $updateSql = "UPDATE deliveries SET status = 'success' WHERE delivery_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("i", $delivery_id);
        
        if ($stmt->execute()) {
            // 3. บวกจำนวนงานจัดส่งของพนักงานเพิ่ม 1 (ถ้ามี staff_id)
            if ($staff_id) {
                $countSql = "UPDATE delivery_staff SET delivery_count = delivery_count + 1 WHERE staff_id = ?";
                $stmtCount = $conn->prepare($countSql);
                $stmtCount->bind_param("i", $staff_id);
                $stmtCount->execute();
            }

            echo json_encode(["success" => true, "message" => "อนุมัติการจัดส่งเรียบร้อยแล้ว"]);
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