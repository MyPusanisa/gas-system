<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$old_phone = trim($data['old_phone'] ?? '');
$new_phone = trim($data['new_phone'] ?? '');
$name = trim($data['name'] ?? '');
$address = trim($data['address'] ?? '');
$map_pin = trim($data['map_pin'] ?? '');

if (empty($old_phone) || empty($new_phone)) {
    echo json_encode(["success" => false, "message" => "กรุณาระบุเบอร์โทรศัพท์"]);
    exit;
}

try {
    if ($old_phone !== $new_phone) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE phone = ?");
        $checkStmt->execute([$new_phone]);
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(["success" => false, "message" => "เบอร์โทรศัพท์ใหม่นี้มีในระบบแล้ว"]);
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE customers SET phone = ?, name = ?, address = ?, map_pin = ? WHERE phone = ?");
    $stmt->execute([$new_phone, $name, $address, $map_pin, $old_phone]);

    $stmtDelivery = $pdo->prepare("UPDATE deliveries SET phone = ? WHERE phone = ?");
    $stmtDelivery->execute([$new_phone, $old_phone]);

    echo json_encode(["success" => true, "message" => "แก้ไขข้อมูลลูกค้าเรียบร้อยแล้ว"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}
?>