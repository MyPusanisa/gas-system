<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$serial = isset($data['serial_number']) ? trim($data['serial_number']) : '';

if (empty($serial)) {
    echo json_encode(["success" => false, "message" => "ไม่ได้ระบุ Serial Number"]);
    exit;
}

$sql = "DELETE FROM gas_cylinder WHERE serial_number = '$serial'";
if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => true, "message" => "ลบถังแก๊สสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
}
mysqli_close($conn);
?>