<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
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

if (empty($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

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

    $stmt = $conn->prepare("
        SELECT delivery_id, status, req_gas_type, req_brand, req_size
        FROM delivery
        WHERE delivery_id = ?
    ");
    $stmt->bind_param("i", $deliveryId);
    $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$delivery) {
        echo json_encode(["success" => false, "message" => "ไม่พบงานจัดส่ง"]);
        exit;
    }
    if ($delivery['status'] !== 'pending') {
        echo json_encode(["success" => false, "message" => "งานนี้ถูกดำเนินการไปแล้ว"]);
        exit;
    }

    $cylStmt = $conn->prepare("
        SELECT serial_number, gas_type, brand, size, status
        FROM gas_cylinder
        WHERE serial_number = ?
    ");
    $cylStmt->bind_param("s", $serialNumber);
    $cylStmt->execute();
    $cylinder = $cylStmt->get_result()->fetch_assoc();
    $cylStmt->close();

    if (!$cylinder) {
        echo json_encode(["success" => false, "message" => "ไม่พบ Serial Number '{$serialNumber}' ในคลังระบบ"]);
        exit;
    }

    if ($cylinder['status'] !== 'ในคลัง') {
        echo json_encode(["success" => false, "message" => "ถังแก๊ส '{$serialNumber}' ไม่อยู่ในคลัง"]);
        exit;
    }

    if ($cylinder['brand'] !== $delivery['req_brand'] || 
        $cylinder['gas_type'] !== $delivery['req_gas_type'] || 
        $cylinder['size'] !== $delivery['req_size']) {
        echo json_encode([
            "success" => false,
            "message" => "สเปกถังไม่ตรงกับที่ระบุไว้ในงาน (ต้องการ: {$delivery['req_brand']} | {$delivery['req_gas_type']} | {$delivery['req_size']})"
        ]);
        exit;
    }

    $checkExisting = $conn->prepare("
        SELECT delivery_id FROM delivery 
        WHERE serial_number = ? AND status NOT IN ('success', 'cancelled') AND delivery_id != ?
    ");
    $checkExisting->bind_param("si", $serialNumber, $deliveryId);
    $checkExisting->execute();
    $existing = $checkExisting->get_result()->fetch_assoc();
    $checkExisting->close();

    if ($existing) {
        echo json_encode(["success" => false, "message" => "ถังนี้กำลังถูกใช้งานในงานอื่น"]);
        exit;
    }

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("
            UPDATE delivery
            SET serial_number = ?, status = 'delivering', accepted_at = NOW(), started_at = NOW()
            WHERE delivery_id = ?
        ");
        $upd->bind_param("si", $serialNumber, $deliveryId);
        $upd->execute();
        $upd->close();

        $updCyl = $conn->prepare("
            UPDATE gas_cylinder
            SET status = 'กำลังส่ง', current_location = 'กำลังจัดส่ง'
            WHERE serial_number = ?
        ");
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

    $check = $conn->prepare("
        SELECT serial_number, proof_image_path
        FROM delivery
        WHERE delivery_id = ? AND status = 'delivering'
    ");
    $check->bind_param("i", $deliveryId);
    $check->execute();
    $delivery = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$delivery) {
        echo json_encode(["success" => false, "message" => "ไม่พบงานที่อยู่ระหว่างจัดส่ง"]);
        exit;
    }
    if (empty($delivery['proof_image_path'])) {
        echo json_encode(["success" => false, "message" => "กรุณาอัปโหลดรูปถ่ายหลักฐานก่อน"]);
        exit;
    }

    $sentSerial = $delivery['serial_number'];

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("
            UPDATE delivery
            SET status = 'pending_approval', completed_at = NOW()
            WHERE delivery_id = ?
        ");
        $upd->bind_param("i", $deliveryId);
        $upd->execute();
        $upd->close();

        if ($sentSerial) {
            $updSent = $conn->prepare("
                UPDATE gas_cylinder
                SET status = 'อยู่กับลูกค้า', current_location = 'ลูกค้า'
                WHERE serial_number = ?
            ");
            $updSent->bind_param("s", $sentSerial);
            $updSent->execute();
            $updSent->close();
        }

        $conn->commit();
        echo json_encode(["success" => true, "message" => "ส่งงานเรียบร้อย รอแอดมินตรวจสอบ"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
elseif ($action === "approve") {
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(["success" => false, "message" => "เฉพาะแอดมินเท่านั้น"]);
        exit;
    }
    if (!$deliveryId) {
        echo json_encode(["success" => false, "message" => "Missing delivery_id"]);
        exit;
    }

    $receivedSerial = trim($input['receivedSerialNumber'] ?? $input['receivedCylinderId'] ?? '');
    if (!$receivedSerial) {
        echo json_encode(["success" => false, "message" => "กรุณาระบุ Serial Number ถังที่รับคืน"]);
        exit;
    }

    $checkCyl = $conn->prepare("SELECT serial_number FROM gas_cylinder WHERE serial_number = ?");
    $checkCyl->bind_param("s", $receivedSerial);
    $checkCyl->execute();
    $cylExists = $checkCyl->get_result()->fetch_assoc();
    $checkCyl->close();

    if (!$cylExists && empty($input['new_cylinder'])) {
        echo json_encode([
            "success" => false,
            "need_create_cylinder" => true,
            "serial_number" => $receivedSerial,
            "message" => "ไม่พบถังคืนนี้ในระบบ ต้องการลงทะเบียนถังใหม่หรือไม่?"
        ]);
        exit;
    }

    $conn->begin_transaction();
    try {
        if (!$cylExists && !empty($input['new_cylinder'])) {
            $newCyl = $input['new_cylinder'];
            $insCyl = $conn->prepare("
                INSERT INTO gas_cylinder 
                (serial_number, gas_type, brand, size, manufacture_date, expiry_date, qr_code, last_check_date, next_check_date, current_location, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'คลัง', 'ในคลัง')
            ");
            $insCyl->bind_param(
                "sssssssss",
                $newCyl['serial_number'],
                $newCyl['gas_type'],
                $newCyl['brand'],
                $newCyl['size'],
                $newCyl['manufacture_date'],
                $newCyl['expiry_date'],
                $newCyl['qr_code'],
                $newCyl['last_check_date'],
                $newCyl['next_check_date']
            );
            $insCyl->execute();
            $insCyl->close();
        }

        $upd = $conn->prepare("
            UPDATE delivery
            SET received_serial_number = ?, status = 'success', delivered_at = NOW()
            WHERE delivery_id = ?
        ");
        $upd->bind_param("si", $receivedSerial, $deliveryId);
        $upd->execute();
        $upd->close();

        $updReturn = $conn->prepare("
            UPDATE gas_cylinder
            SET status = 'ในคลัง', current_location = 'คลัง'
            WHERE serial_number = ?
        ");
        $updReturn->bind_param("s", $receivedSerial);
        $updReturn->execute();
        $updReturn->close();

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
?>