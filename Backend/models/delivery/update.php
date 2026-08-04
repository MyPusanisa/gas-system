<?php
session_start();
include_once "../../config/db.php";

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid input"
    ]);
    exit;
}

$deliveryId = $input['delivery_id'] ?? null;
$action = $input['action'] ?? '';

// ======================================================
// ตรวจสอบการล็อกอิน
// ======================================================

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

elseif ($action === "pick") {
    if (!$deliveryId) {
        echo json_encode(["success" => false, "message" => "Missing delivery_id"]);
        exit;
    }

    $serialNumber = trim($input['serialNumber'] ?? $input['cylinderId'] ?? '');
    if (!$serialNumber) {
        echo json_encode(["success" => false, "message" => "กรุณาเลือก Serial Number ถัง"]);
        exit;
    }

    // ตรวจสอบงาน
    $stmt = $conn->prepare("
        SELECT delivery_id, status, gas_type, brand, size
        FROM delivery
        WHERE delivery_id = ?
    ");
    $stmt->bind_param("i", $deliveryId);
    $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$delivery) {
        echo json_encode(["success" => false, "message" => "ไม่พบงาน"]);
        exit;
    }
    if ($delivery['status'] !== 'pending') {
        echo json_encode(["success" => false, "message" => "งานนี้ถูกดำเนินการแล้ว"]);
        exit;
    }

    // ตรวจสอบ cylinder
    $cylStmt = $conn->prepare("
        SELECT serial_number, gas_type, brand, size
        FROM gas_cylinder
        WHERE serial_number = ?
    ");
    $cylStmt->bind_param("s", $serialNumber);
    $cylStmt->execute();
    $cylinder = $cylStmt->get_result()->fetch_assoc();
    $cylStmt->close();

    if (!$cylinder) {
        echo json_encode([
            "success" => false,
            "message" => "ไม่พบ Serial Number {$serialNumber} ในระบบ กรุณาตรวจสอบ"
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
        echo json_encode([
            "success" => false,
            "message" => "ถังนี้กำลังถูกใช้ในงานอื่น (รหัส {$existing['delivery_id']}) ไม่สามารถรับงานนี้ได้"
        ]);
        exit;
    }

    // ทำ transaction
    $conn->begin_transaction();
    try {
        // อัปเดต delivery
        $upd = $conn->prepare("
            UPDATE delivery
            SET serial_number = ?, status = 'delivering', accepted_at = NOW(), started_at = NOW()
            WHERE delivery_id = ?
        ");
        $upd->bind_param("si", $serialNumber, $deliveryId);
        $upd->execute();
        $upd->close();

        // อัปเดตสถานะถังเป็น 'กำลังส่ง'
        $updCyl = $conn->prepare("
            UPDATE gas_cylinder
            SET status = 'กำลังส่ง', current_location = 'กำลังจัดส่ง'
            WHERE serial_number = ?
        ");
        $updCyl->bind_param("s", $serialNumber);
        $updCyl->execute();
        $updCyl->close();

        $conn->commit();
        echo json_encode(["success" => true, "message" => "รับงานสำเร็จ"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

elseif ($action === "create_cylinder") {
    $cylinderData = $input['cylinder_data'] ?? null;
    $serial = trim($cylinderData['serial_number'] ?? '');
    
    if (!$cylinderData || empty($serial)) {
        echo json_encode([
            "success" => false,
            "message" => "กรุณากรอก Serial Number ให้ถูกต้อง"
        ]);
        exit;
    }

    $check = $conn->prepare("SELECT serial_number FROM gas_cylinder WHERE serial_number = ?");
    $check->bind_param("s", $serial);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();

    if ($exists) {
        echo json_encode([
            "success" => false,
            "message" => "Serial Number นี้มีอยู่แล้วในระบบ"
        ]);
        exit;
    }

    $gasType = $cylinderData['gas_type'] ?? '';
    $brand = $cylinderData['brand'] ?? '';
    $size = $cylinderData['size'] ?? '';
    $manufactureDate = $cylinderData['manufacture_date'] ?? null;
    $expiryDate = $cylinderData['expiry_date'] ?? null;
    $qrCode = $cylinderData['qr_code'] ?? '';
    $lastCheckDate = $cylinderData['last_check_date'] ?? null;
    $nextCheckDate = $cylinderData['next_check_date'] ?? null;
    $deliveredDate = $cylinderData['delivered_date'] ?? null;
    $currentLocation = $cylinderData['current_location'] ?? 'คลัง';
    $status = 'ในคลัง';

    $insert = $conn->prepare("
        INSERT INTO gas_cylinder 
        (serial_number, gas_type, brand, size, manufacture_date, expiry_date, 
         qr_code, last_check_date, next_check_date, delivered_date, current_location, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param(
        "ssssssssssss",
        $serial,
        $gasType,
        $brand,
        $size,
        $manufactureDate,
        $expiryDate,
        $qrCode,
        $lastCheckDate,
        $nextCheckDate,
        $deliveredDate,
        $currentLocation,
        $status
    );

    if ($insert->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "สร้างถังใหม่สำเร็จ"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "สร้างถังไม่สำเร็จ: " . $conn->error
        ]);
    }
    $insert->close();
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
        echo json_encode(["success" => false, "message" => "กรุณากรอก Serial Number ถังรับคืน"]);
        exit;
    }

    // ตรวจสอบงาน
    $del = $conn->prepare("
        SELECT serial_number
        FROM delivery
        WHERE delivery_id = ? AND status = 'pending_approval'
    ");
    $del->bind_param("i", $deliveryId);
    $del->execute();
    $delivery = $del->get_result()->fetch_assoc();
    $del->close();

    if (!$delivery) {
        echo json_encode(["success" => false, "message" => "ไม่พบงานรออนุมัติ"]);
        exit;
    }

    $sentSerial = $delivery['serial_number'];

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("
            UPDATE delivery
            SET received_serial_number = ?, status = 'success', completed_at = NOW(), delivered_at = NOW()
            WHERE delivery_id = ?
        ");
        $upd->bind_param("si", $receivedSerial, $deliveryId);
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

        $updReturn = $conn->prepare("
            UPDATE gas_cylinder
            SET status = 'ในคลัง', current_location = 'คลัง'
            WHERE serial_number = ?
        ");
        $updReturn->bind_param("s", $receivedSerial);
        $updReturn->execute();
        $updReturn->close();

        $conn->commit();
        echo json_encode(["success" => true, "message" => "อนุมัติงานสำเร็จ"]);
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
        echo json_encode(["success" => false, "message" => "ไม่พบงานที่กำลังจัดส่ง"]);
        exit;
    }
    if (empty($delivery['proof_image_path'])) {
        echo json_encode(["success" => false, "message" => "กรุณาอัปโหลดรูปถังที่รับคืนก่อน"]);
        exit;
    }

    $sentSerial = $delivery['serial_number'];

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("
            UPDATE delivery
            SET status = 'success', completed_at = NOW(), delivered_at = NOW()
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
        echo json_encode(["success" => true, "message" => "จบงานสำเร็จ"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else {
    echo json_encode(["success" => false, "message" => "Unknown action"]);
}
?>