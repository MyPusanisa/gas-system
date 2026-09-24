<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/db.php";

// ถังทุกใบ (หน้าเว็บกรองตามสถานะเอง) — เดิมดึงเฉพาะ 'ในคลัง'
// ทำให้ถังที่แก้สถานะเป็น ชำรุด/ปกติ หายไปจากหน้าจอ
$sql = "SELECT * FROM gas_cylinder ORDER BY created_at DESC, serial_number DESC";
$result = $conn->query($sql);

if ($result) {
    echo json_encode(["success" => true, "data" => $result->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["success" => false, "message" => $conn->error], JSON_UNESCAPED_UNICODE);
}
$conn->close();
