<?php
// คัดลอก 6 บรรทัดนี้ไปวางไว้บนสุดของไฟล์ get_cylinder_by_id.php ครับ
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}


include_once "../config/db.php";

$id = $_GET['id'] ?? "";
if (!$id) {
    echo json_encode(["success" => false, "message" => "Missing id"]);
    exit;
}

// คิวรีดึงข้อมูลจากตาราง gas_cylinder ตามรหัส id ที่ส่งมา
$stmt = $conn->prepare("SELECT * FROM gas_cylinder WHERE cylinder_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$cylinder = $result->fetch_assoc();

if ($cylinder) {
    echo json_encode(["success" => true, "data" => $cylinder]);
} else {
    echo json_encode(["success" => false, "message" => "Not found"]);
}
?>