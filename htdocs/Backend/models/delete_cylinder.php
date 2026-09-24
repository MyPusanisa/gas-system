<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/db.php";

// รับได้ทั้ง JSON body, POST และ GET (หน้าเว็บรุ่นเก่าส่งทาง query string)
$input = json_decode(file_get_contents("php://input"), true) ?: [];
$serial_number = trim($input['serial_number'] ?? $_POST['serial_number'] ?? $_GET['serial_number'] ?? '');

if ($serial_number === '') {
    echo json_encode(["success" => false, "message" => "ไม่พบ Serial Number"], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("DELETE FROM gas_cylinder WHERE serial_number = ?");
$stmt->bind_param("s", $serial_number);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "ลบไม่สำเร็จ: " . $stmt->error], JSON_UNESCAPED_UNICODE);
} elseif ($stmt->affected_rows === 0) {
    echo json_encode(["success" => false, "message" => "ไม่พบถัง $serial_number ในระบบ"], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["success" => true, "message" => "ลบถังแก๊สสำเร็จ"], JSON_UNESCAPED_UNICODE);
}
$conn->close();
