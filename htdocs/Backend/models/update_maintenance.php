<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';

$input = json_decode(file_get_contents("php://input"), true);

$maintenance_id   = isset($input['maintenance_id']) ? intval($input['maintenance_id']) : 0;
$serial_number    = isset($input['serial_number']) ? $conn->real_escape_string(trim($input['serial_number'])) : '';
$maintenance_type = isset($input['maintenance_type']) ? $conn->real_escape_string(trim($input['maintenance_type'])) : '';
$result           = isset($input['result']) ? $conn->real_escape_string(trim($input['result'])) : '';
$description      = isset($input['description']) ? $conn->real_escape_string(trim($input['description'])) : '';

if ($maintenance_id <= 0 || empty($serial_number) || empty($maintenance_type) || empty($result)) {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ครบถ้วน"]);
    exit();
}

$sql = "UPDATE maintenance 
        SET serial_number = '$serial_number',
            maintenance_type = '$maintenance_type', 
            result = '$result', 
            description = '$description' 
        WHERE maintenance_id = $maintenance_id";

if ($conn->query($sql)) {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "แก้ไขข้อมูลสำเร็จ"]);
} else {
    $err = $conn->error;
    ob_end_clean();
    http_response_code(200);
    echo json_encode(["success" => false, "message" => "MySQL Error: " . $err]);
}
exit();
?>