<?php
// 1. ซ่อน PHP HTML Error เพื่อป้องกันการหลุดไปรวมกับ JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php'; // ตรวจสอบชื่อไฟล์ให้ถูกต้อง เช่น db.php หรือ database.php

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['serial_number'])) {
    try {
        $serial_number   = $data['serial_number'];
        $brand           = $data['brand'] ?? null;
        $gas_type        = $data['gas_type'] ?? null;
        $size            = $data['size'] ?? null;
        $status          = $data['status'] ?? 'in_stock';
        $expiry_date     = $data['expiry_date'] ?? null;
        $next_check_date = $data['next_check_date'] ?? null;

        $sql = "INSERT INTO gas_cylinder (serial_number, brand, gas_type, size, status, expiry_date, next_check_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sssssss", $serial_number, $brand, $gas_type, $size, $status, $expiry_date, $next_check_date);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "สร้างถังแก๊สสำเร็จ"
            ]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "กรุณาระบุ Serial Number"
    ]);
}
?>