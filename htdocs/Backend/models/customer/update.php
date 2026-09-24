<?php
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true) ?: [];

$old_phone = trim($data['old_phone'] ?? '');
$new_phone = trim($data['new_phone'] ?? '');
$name = trim($data['name'] ?? '');
$address = trim($data['address'] ?? '');
$map_pin = trim($data['map_pin'] ?? '');

if (empty($old_phone) || empty($new_phone)) {
    echo json_encode(["success" => false, "message" => "กรุณาระบุเบอร์โทรศัพท์"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($old_phone !== $new_phone) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE phone = ?");
        $checkStmt->execute([$new_phone]);
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(["success" => false, "message" => "เบอร์โทรศัพท์ใหม่นี้มีในระบบแล้ว"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $pdo->beginTransaction();

    $exists = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE phone = ?");
    $exists->execute([$old_phone]);

    if ($exists->fetchColumn() > 0) {
        $stmt = $pdo->prepare("UPDATE customers SET phone = ?, name = ?, address = ?, map_pin = ? WHERE phone = ?");
        $stmt->execute([$new_phone, $name, $address, $map_pin, $old_phone]);
    } else {
        // ลูกค้าจากงานจัดส่งที่ยังไม่มีในตาราง customers — สร้างใหม่ (เดิม UPDATE ไม่โดนแถวไหนเลย)
        // customer_id ไม่มี auto_increment จึงกำหนดเป็นค่าสูงสุด + 1
        $stmt = $pdo->prepare(
            "INSERT INTO customers (customer_id, phone, name, address, map_pin)
             SELECT COALESCE(MAX(customer_id), 0) + 1, ?, ?, ?, ? FROM customers"
        );
        $stmt->execute([$new_phone, $name, $address, $map_pin]);
    }

    // การ์ดงานจัดส่งอ่านพิกัดจาก deliveries.map_pin — อัปเดตงานของลูกค้าคนนี้ด้วย
    $stmtDelivery = $pdo->prepare("UPDATE deliveries SET phone = ?, map_pin = ? WHERE phone = ?");
    $stmtDelivery->execute([$new_phone, $map_pin, $old_phone]);

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "แก้ไขข้อมูลลูกค้าเรียบร้อยแล้ว"], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
