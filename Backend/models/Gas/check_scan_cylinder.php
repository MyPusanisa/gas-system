<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include_once "../../config/db.php"; // เปลี่ยน path ตามไฟล์เชื่อมต่อ DB ของคุณ

$data = json_decode(file_get_contents("php://input"), true);

$delivery_id = $data['delivery_id'] ?? null;
$scanned_serial = $data['serial_number'] ?? null;

if (!$delivery_id || !$scanned_serial) {
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ครบถ้วน"]);
    exit;
}

// 1. ดึงข้อมูลงานจัดส่ง
$stmt = $conn->prepare("SELECT * FROM deliveries WHERE id = ?");
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$delivery = $stmt->get_result()->fetch_assoc();

if (!$delivery) {
    echo json_encode(["success" => false, "message" => "ไม่พบใบงานจัดส่งนี้"]);
    exit;
}

// 2. ดึงข้อมูลถังแก๊สที่สแกน
$stmt = $conn->prepare("SELECT * FROM cylinders WHERE serial_number = ?");
$stmt->bind_param("s", $scanned_serial);
$stmt->execute();
$cylinder = $stmt->get_result()->fetch_assoc();

if (!$cylinder) {
    echo json_encode(["success" => false, "message" => "❌ ไม่พบถังแก๊ส Serial นี้ในระบบ"]);
    exit;
}

// 3. ตรวจสอบสถานะของถังแก๊ส
if ($cylinder['status'] !== 'ในคลัง') {
    echo json_encode(["success" => false, "message" => "❌ ถังนี้ไม่อยู่ในคลัง (สถานะปัจจุบัน: " . $cylinder['status'] . ")"]);
    exit;
}

// 4. ตรวจสอบเงื่อนไข สเปกยี่ห้อ ชนิดแก๊ส และขนาด
$mismatch = [];
if ($cylinder['brand'] !== $delivery['req_brand']) {
    $mismatch[] = "ยี่ห้อ (ต้องการ: {$delivery['req_brand']}, ถังที่สแกน: {$cylinder['brand']})";
}
if ($cylinder['gas_type'] !== $delivery['req_gas_type']) {
    $mismatch[] = "ชนิดแก๊ส (ต้องการ: {$delivery['req_gas_type']}, ถังที่สแกน: {$cylinder['gas_type']})";
}
if ($cylinder['size'] !== $delivery['req_size']) {
    $mismatch[] = "ขนาด (ต้องการ: {$delivery['req_size']}, ถังที่สแกน: {$cylinder['size']})";
}

if (count($mismatch) > 0) {
    echo json_encode([
        "success" => false,
        "message" => "❌ ถังแก๊สไม่ตรงตามสเปก:\n- " . implode("\n- ", $mismatch)
    ]);
    exit;
}

// 5. เงื่อนไขผ่านทั้งหมด -> บันทึกข้อมูลผูก Serial และเปลี่ยนสถานะ
$conn->begin_transaction();
try {
    // อัปเดตใบงานจัดส่ง
    $stmt1 = $conn->prepare("UPDATE deliveries SET serial_number = ?, status = 'อยู่ระหว่างจัดส่ง' WHERE id = ?");
    $stmt1->bind_param("si", $scanned_serial, $delivery_id);
    $stmt1->execute();

    // อัปเดตสถานะถังแก๊ส
    $stmt2 = $conn->prepare("UPDATE cylinders SET status = 'กำลังส่ง' WHERE serial_number = ?");
    $stmt2->bind_param("s", $scanned_serial);
    $stmt2->execute();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "✅ ตรวจสอบสำเร็จ! สเปกถูกต้อง ผูกถังแก๊สเข้ากับใบงานแล้ว"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการบันทึกข้อมูล"]);
}