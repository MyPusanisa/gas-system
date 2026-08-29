<?php
// 1. เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล (ถอย 2 โฟลเดอร์ไปหา config/db.php)
require_once __DIR__ . '/../../config/db.php';

// 2. ดึงข้อมูลงานจัดส่งทั้งหมดเรียงจากใหม่ไปเก่า
$sql = "SELECT * FROM deliveries ORDER BY delivery_id DESC";
$result = $conn->query($sql);

$deliveries = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $deliveries[] = $row;
    }
}

// 3. ส่งข้อมูลกลับเป็น JSON
echo json_encode([
    "success" => true,
    "data" => $deliveries
]);
?>