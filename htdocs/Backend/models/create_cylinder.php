<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../config/database.php'; // ปรับ Path ตามไฟล์ของคุณ ($conn)

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['serial_number'])) {
    try {
        // 1. เจนรหัส cylinder_id แบบสุ่มไม่ให้ซ้ำ เช่น CYL-172597200088
        $cylinder_id = 'CYL-' . time() . rand(10, 99);

        // 2. รับค่าจาก Frontend
        $serial_number = $data['serial_number'];
        $brand         = $data['brand'] ?? null;
        $gas_type      = $data['gas_type'] ?? null;
        $size          = $data['size'] ?? null;
        $status        = $data['status'] ?? 'in_stock';
        $expiry_date   = $data['expiry_date'] ?? null;
        $next_check_date = $data['next_check_date'] ?? null;

        // 3. เตรียม SQL INSERT ลงตาราง gas_cylinder
        $sql = "INSERT INTO gas_cylinder (cylinder_id, serial_number, brand, gas_type, size, status, expiry_date, next_check_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssssssss", $cylinder_id, $serial_number, $brand, $gas_type, $size, $status, $expiry_date, $next_check_date);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "สร้างถังแก๊สสำเร็จ",
                "cylinder_id" => $cylinder_id
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