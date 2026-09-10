<?php
while (ob_get_level()) {
    ob_end_clean();
}

error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // 1. เชื่อมต่อฐานข้อมูลโดยลองพอร์ต 3308 ก่อน
    $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3308);
    if ($conn->connect_error) {
        $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3307);
    }
    if ($conn->connect_error) {
        $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3306);
    }

    if ($conn->connect_error) {
        throw new Exception("DB Connection Failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        throw new Exception("ไม่พบข้อมูลที่ส่งมาจาก Frontend");
    }

    // รองรับทั้งแบบเลือกหลายถัง (serial_numbers) และเลือกถังเดียว (serial_number)
    $serial_numbers = [];
    if (isset($input['serial_numbers']) && is_array($input['serial_numbers'])) {
        $serial_numbers = $input['serial_numbers'];
    } elseif (!empty($input['serial_number'])) {
        $serial_numbers = [$input['serial_number']];
    }

    if (empty($serial_numbers)) {
        throw new Exception("กรุณาเลือกถังแก๊สอย่างน้อย 1 รายการ");
    }

    $type        = $input['maintenance_type'] ?? 'ตรวจสภาพ';
    $result      = $input['result'] ?? 'ผ่าน';
    $description = $input['description'] ?? '';
    $today       = date("Y-m-d");

    $conn->begin_transaction();

    // 2. บันทึกประวัติเข้าตาราง maintenance
    $stmt_insert = $conn->prepare("
        INSERT INTO maintenance (serial_number, maintenance_date, maintenance_type, result, description) 
        VALUES (?, ?, ?, ?, ?)
    ");

    // 3. เลื่อนวันตรวจครั้งถัดไปในตาราง gas_cylinder ออกไป 1 ปี
    $stmt_update = $conn->prepare("
        UPDATE gas_cylinder 
        SET next_check_date = DATE_ADD(CURRENT_DATE(), INTERVAL 1 YEAR) 
        WHERE serial_number = ?
    ");

    foreach ($serial_numbers as $sn) {
        $stmt_insert->bind_param("sssss", $sn, $today, $type, $result, $description);
        $stmt_insert->execute();

        $stmt_update->bind_param("s", $sn);
        $stmt_update->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "บันทึกผลการตรวจเรียบร้อยแล้ว (" . count($serial_numbers) . " รายการ)"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit();
?>