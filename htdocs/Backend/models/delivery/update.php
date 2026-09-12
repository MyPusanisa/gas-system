<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูลของคุณ
require_once "../config/database.php"; 

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลที่ส่งมา"]);
    exit();
}

$action = $data['action'] ?? '';
$delivery_id = $data['delivery_id'] ?? null;

if (!$delivery_id) {
    echo json_encode(["success" => false, "message" => "ไม่ได้ระบุ delivery_id"]);
    exit();
}

try {
    // ------------------------------------------------------------------
    // ACTION 1: พนักงานสแกนรับงาน (Pick)
    // ------------------------------------------------------------------
    if ($action === 'pick') {
        $serialNumber = $data['serialNumber'] ?? '';

        if (!$serialNumber) {
            echo json_encode(["success" => false, "message" => "ไม่ได้ระบุ Serial Number ถังแก๊ส"]);
            exit();
        }

        $pdo->beginTransaction();

        // อัปเดตงานจัดส่ง
        $stmt = $pdo->prepare("UPDATE deliveries SET status = 'delivering', serial_number = :sn WHERE id = :id");
        $stmt->execute([':sn' => $serialNumber, ':id' => $delivery_id]);

        // อัปเดตสถานะถังแก๊สในคลังให้เป็นกำลังจัดส่ง
        $stmtCyl = $pdo->prepare("UPDATE gas_cylinder SET status = 'กำลังส่ง' WHERE serial_number = :sn");
        $stmtCyl->execute([':sn' => $serialNumber]);

        $pdo->commit();

        echo json_encode(["success" => true, "message" => "รับงานสำเร็จ"]);
        exit();
    }

    // ------------------------------------------------------------------
    // ACTION 2: พนักงานส่งรูปหลักฐานปิดงาน (Complete by Staff)
    // ------------------------------------------------------------------
    if ($action === 'complete_by_staff') {
        $proof_image_path = $data['proof_image_path'] ?? '';

        $stmt = $pdo->prepare("UPDATE deliveries SET status = 'pending_approval', proof_image_path = :proof WHERE id = :id");
        $stmt->execute([':proof' => $proof_image_path, ':id' => $delivery_id]);

        echo json_encode(["success" => true, "message" => "อัปเดตสถานะรออนุมัติสำเร็จ"]);
        exit();
    }

    // ------------------------------------------------------------------
    // ACTION 3: Admin กดอนุมัติงาน (Approve) -> [จุดสำคัญที่แก้ไข]
    // ------------------------------------------------------------------
    if ($action === 'approve') {
        $receivedSerialNumber = trim($data['receivedSerialNumber'] ?? '');

        // ดึงข้อมูลงานจัดส่งเดิมเพื่อเอา Serial ถังที่ไปส่ง และ ชื่อลูกค้า
        $stmtGet = $pdo->prepare("SELECT serial_number, customer_name FROM deliveries WHERE id = :id");
        $stmtGet->execute([':id' => $delivery_id]);
        $delivery = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if (!$delivery) {
            echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลงานจัดส่งนี้"]);
            exit();
        }

        $deliveredSerial = $delivery['serial_number']; // เช่น SN-3004
        $customerName = $delivery['customer_name'];

        $pdo->beginTransaction();

        // 1. อัปเดตตาราง deliveries เป็น success
        $stmtDelivery = $pdo->prepare("UPDATE deliveries SET status = 'success' WHERE id = :id");
        $stmtDelivery->execute([':id' => $delivery_id]);

        // 2. อัปเดตถังแก๊สใบใหม่ที่ส่งให้ลูกค้า (เช่น SN-3004) ในตาราง gas_cylinder
        if ($deliveredSerial) {
            $stmtDeliveredCyl = $pdo->prepare("
                UPDATE gas_cylinder 
                SET status = 'จัดส่งสำเร็จ', 
                    delivered_date = CURRENT_DATE(),
                    current_location = :customer_name,
                    updated_at = CURRENT_TIMESTAMP()
                WHERE serial_number = :sn
            ");
            $stmtDeliveredCyl->execute([
                ':customer_name' => $customerName,
                ':sn' => $deliveredSerial
            ]);
        }

        // 3. อัปเดตถังแก๊สใบเก่าที่รับคืนเข้าคลัง ในตาราง gas_cylinder
        if ($receivedSerialNumber) {
            // เช็คว่ามีถังเดิมในตารางไหม
            $chkStmt = $pdo->prepare("SELECT serial_number FROM gas_cylinder WHERE serial_number = :sn");
            $chkStmt->execute([':sn' => $receivedSerialNumber]);
            
            if ($chkStmt->fetch()) {
                // อัปเดตถังเก่าให้กลับเข้าคลัง
                $stmtReturnedCyl = $pdo->prepare("
                    UPDATE gas_cylinder 
                    SET status = 'ในคลัง', 
                        current_location = 'คลัง',
                        delivered_date = NULL,
                        import_date = CURRENT_DATE(),
                        updated_at = CURRENT_TIMESTAMP()
                    WHERE serial_number = :sn
                ");
                $stmtReturnedCyl->execute([':sn' => $receivedSerialNumber]);
            } else if (isset($data['new_cylinder'])) {
                // หากเป็นถังนอกระบบที่เพิ่งกรอกสร้างใหม่ ให้แทรกข้อมูลลงตาราง
                $nc = $data['new_cylinder'];
                $stmtInsert = $pdo->prepare("
                    INSERT INTO gas_cylinder 
                    (serial_number, brand, gas_type, size, manufacture_date, expiry_date, last_check_date, next_check_date, status, current_location, import_date) 
                    VALUES (:sn, :brand, :gas_type, :size, :mfg, :exp, :last_chk, :next_chk, 'ในคลัง', 'คลัง', CURRENT_DATE())
                ");
                $stmtInsert->execute([
                    ':sn' => $nc['serial_number'],
                    ':brand' => $nc['brand'],
                    ':gas_type' => $nc['gas_type'] ?? 'LPG',
                    ':size' => $nc['size'],
                    ':mfg' => $nc['manufacture_date'],
                    ':exp' => $nc['expiry_date'],
                    ':last_chk' => $nc['last_check_date'],
                    ':next_chk' => $nc['next_check_date']
                ]);
            }
        }

        $pdo->commit();

        echo json_encode(["success" => true, "message" => "อนุมัติงานและอัปเดตสถานะถังแก๊สสำเร็จ"]);
        exit();
    }

    echo json_encode(["success" => false, "message" => "ไม่พบ Action ที่ระบุ"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}
?>