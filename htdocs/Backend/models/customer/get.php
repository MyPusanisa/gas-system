<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/database.php';

$phone = trim($_GET['phone'] ?? '');

if (empty($phone)) {
    echo json_encode(["success" => false, "message" => "ไม่ระบุเบอร์โทร"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = ? LIMIT 1");
$stmt->execute([$phone]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if ($customer) {
    echo json_encode(["success" => true, "data" => $customer]);
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบข้อมูล"]);
}
?>