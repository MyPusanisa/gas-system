<?php
require_once "../../config/db.php";

// ตรวจสอบ method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}



$input = json_decode(file_get_contents("php://input"), true);
$deliveryId = $input['delivery_id'] ?? null;

if (empty($deliveryId)) {
    echo json_encode(["success" => false, "message" => "Missing delivery_id"]);
    exit;
}

try {
    // ตรวจสอบ delivery
    $check = $conn->prepare("SELECT delivery_id, status, cylinder_id FROM delivery WHERE delivery_id = ?");
    $check->bind_param("i", $deliveryId);
    $check->execute();
    $result = $check->get_result();
    $delivery = $result->fetch_assoc();
    $check->close();

    if (!$delivery) {
        echo json_encode(["success" => false, "message" => "ไม่พบงาน"]);
        exit;
    }

    // อนุญาตเฉพาะ pending
    if ($delivery['status'] !== 'pending') {
        echo json_encode(["success" => false, "message" => "ลบได้เฉพาะงานที่รอรับงาน (pending)"]);
        exit;
    }

    $conn->begin_transaction();

    // คืนสถานะถัง (ถ้ามี cylinder_id)
    if (!empty($delivery['cylinder_id'])) {
        $resetCylinder = $conn->prepare("UPDATE gas_cylinder SET status = 'ในคลัง', current_location = 'คลัง' WHERE cylinder_id = ?");
        $resetCylinder->bind_param("s", $delivery['cylinder_id']);
        if (!$resetCylinder->execute()) {
            throw new Exception($resetCylinder->error);
        }
        $resetCylinder->close();
    }

    // ลบ delivery
    $deleteStmt = $conn->prepare("DELETE FROM delivery WHERE delivery_id = ?");
    $deleteStmt->bind_param("i", $deliveryId);
    if (!$deleteStmt->execute()) {
        throw new Exception($deleteStmt->error);
    }
    if ($deleteStmt->affected_rows <= 0) {
        throw new Exception("ไม่สามารถลบข้อมูลได้");
    }
    $deleteStmt->close();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "ลบงานสำเร็จ"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>