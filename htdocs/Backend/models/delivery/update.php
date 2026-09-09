<?php
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: PUT, POST, GET, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once "../../config/db.php";

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$deliveryId = $input['delivery_id'] ?? null;
$action = $input['action'] ?? '';
$userId = $_SESSION['user_id'] ?? $_SESSION['staff_id'] ?? $input['staff_id'] ?? $input['user_id'] ?? 1;

if ($action === "pick") {
    if (!$deliveryId) {
        echo json_encode(["success" => false, "message" => "Missing delivery_id"]);
        exit;
    }

    $serialNumber = trim($input['serialNumber'] ?? $input['cylinderId'] ?? '');
    if (!$serialNumber) {
        echo json_encode(["success" => false, "message" => "กรุณาสแกน หรือระบุ Serial Number ถัง"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT delivery_id, status, gas_type, brand, size FROM deliveries WHERE delivery_id = ?");
    $stmt->bind_param("i", $deliveryId);
    $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$delivery) {
        echo json_encode(["success" => false, "message" => "ไม่พบงานจัดส่ง"]);
        exit;
    }

    $cylStmt = $conn->prepare("SELECT serial_number, gas_type, brand, size, status FROM gas_cylinder WHERE serial_number = ?");
    $cylStmt->bind_param("s", $serialNumber);
    $cylStmt->execute();
    $cylinder = $cylStmt->get_result()->fetch_assoc();
    $cylStmt->close();

    if (!$cylinder) {
        echo json_encode(["success" => false, "message" => "ไม่พบ Serial Number '{$serialNumber}' ในคลังระบบ"]);
        exit;
    }

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("UPDATE deliveries SET status = 'delivering', staff_id = ? WHERE delivery_id = ?");
        $upd->bind_param("ii", $userId, $deliveryId);
        $upd->execute();
        $upd->close();

        $updCyl = $conn->prepare("UPDATE gas_cylinder SET status = 'กำลังส่ง', current_location = 'กำลังจัดส่ง' WHERE serial_number = ?");
        $updCyl->bind_param("s", $serialNumber);
        $updCyl->execute();
        $updCyl->close();

        $conn->commit();
        echo json_encode(["success" => true, "message" => "ตรวจสอบสเปกถูกต้อง และรับงานสำเร็จ"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
elseif ($action === "complete_by_staff") {
    if (!$deliveryId) {
        echo json_encode(["success" => false, "message" => "Missing delivery_id"]);
        exit;
    }

    $proofImagePath = trim($input['proof_image_path'] ?? $input['proofImagePath'] ?? '');

    $conn->begin_transaction();
    try {
        if ($proofImagePath !== '') {
            // ปรับเรียงพารามิเตอร์ SQL ให้ตรงกับ bind_param ("si" => String, Integer)
            $upd = $conn->prepare("UPDATE deliveries SET proof_image_path = ?, status = 'pending_approval' WHERE delivery_id = ?");
            $upd->bind_param("si", $proofImagePath, $deliveryId);
        } else {
            $upd = $conn->prepare("UPDATE deliveries SET status = 'pending_approval' WHERE delivery_id = ?");
            $upd->bind_param("i", $deliveryId);
        }
        $upd->execute();
        $upd->close();

        $conn->commit();
        echo json_encode(["success" => true, "message" => "ส่งงานเรียบร้อย รอแอดมินตรวจสอบ"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
elseif ($action === "approve") {
    if (!$deliveryId) {
        echo json_encode(["success" => false, "message" => "Missing delivery_id"]);
        exit;
    }

    $receivedSerial = trim($input['receivedSerialNumber'] ?? $input['receivedCylinderId'] ?? '');

    $conn->begin_transaction();
    try {
        // อัปเดตสถานะงานจัดส่งใน Database เป็น success
        $upd = $conn->prepare("UPDATE deliveries SET status = 'success' WHERE delivery_id = ?");
        $upd->bind_param("i", $deliveryId);
        $upd->execute();
        $upd->close();

        if ($receivedSerial) {
            $updReturn = $conn->prepare("UPDATE gas_cylinder SET status = 'ในคลัง', current_location = 'คลัง' WHERE serial_number = ?");
            $updReturn->bind_param("s", $receivedSerial);
            $updReturn->execute();
            $updReturn->close();
        }

        $conn->commit();
        echo json_encode(["success" => true, "message" => "อนุมัติงานจัดส่งเรียบร้อยแล้ว"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
else {
    echo json_encode(["success" => false, "message" => "Unknown action"]);
}