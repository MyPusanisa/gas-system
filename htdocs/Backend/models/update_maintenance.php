<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/db.php";

$input = json_decode(file_get_contents("php://input"), true) ?: [];

$id          = (int)($input['maintenance_id'] ?? 0);
$serial      = trim($input['serial_number'] ?? '');
$type        = trim($input['maintenance_type'] ?? '');
$result      = trim($input['result'] ?? '');
$next_action = trim($input['next_action'] ?? '');
$description = trim($input['description'] ?? '');

if ($id <= 0 || $serial === '' || $type === '' || $result === '') {
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ครบ"], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt = $conn->prepare(
    "UPDATE maintenance SET serial_number = ?, maintenance_type = ?, result = ?, next_action = ?, description = ?
     WHERE maintenance_id = ?"
);
$stmt->bind_param("sssssi", $serial, $type, $result, $next_action, $description, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "แก้ไขประวัติสำเร็จ"], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error], JSON_UNESCAPED_UNICODE);
}
$conn->close();
