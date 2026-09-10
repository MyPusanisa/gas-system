<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../config/database.php'; // ปรับ path ตามไฟล์เชื่อมต่อ DB ของคุณ

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->old_phone) && !empty($data->new_phone)) {
    $old_phone = trim($data->old_phone);
    $new_phone = trim($data->new_phone);

    // 1. ตรวจสอบว่าเบอร์ใหม่มีในระบบแล้วหรือยัง
    $check_stmt = $conn->prepare("SELECT phone FROM customers WHERE phone = ?");
    $check_stmt->bind_param("s", $new_phone);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "เบอร์โทรศัพท์ใหม่นี้มีในระบบแล้ว"]);
        exit();
    }

    // 2. อัปเดตเบอร์โทรศัพท์ในตาราง customers
    $stmt = $conn->prepare("UPDATE customers SET phone = ? WHERE phone = ?");
    $stmt->bind_param("ss", $new_phone, $old_phone);

    if ($stmt->execute()) {
        // 3. อัปเดตเบอร์ในประวัติการจัดส่ง (ถ้ามี)
        $update_delivery = $conn->prepare("UPDATE deliveries SET phone = ? WHERE phone = ?");
        $update_delivery->bind_param("ss", $new_phone, $old_phone);
        $update_delivery->execute();

        echo json_encode(["success" => true, "message" => "อัปเดตเบอร์โทรศัพท์เรียบร้อยแล้ว"]);
    } else {
        echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการอัปเดต"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "กรุณาระบุเบอร์เก่าและเบอร์ใหม่"]);
}
?>