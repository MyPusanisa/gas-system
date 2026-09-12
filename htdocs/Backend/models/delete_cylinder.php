<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/db.php";

// รับค่า serial_number (แทน cylinder_id เดิม)
$serial_number = $_GET['serial_number'] ?? $_POST['serial_number'] ?? '';

if (empty($serial_number)) {
    echo json_encode(["success" => false, "message" => "Missing serial_number"]);
    exit;
}

// แก้ไขคำสั่ง DELETE ให้ลบตาม serial_number
$sql = "DELETE FROM gas_cylinder WHERE serial_number = '$serial_number'";

if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => true, "message" => "Deleted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
}

mysqli_close($conn);
?>