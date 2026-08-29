<?php
while (ob_get_level()) {
    ob_end_clean();
}

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

$conn = new mysqli("localhost", "root", "", "gas_system", 3307);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB Connection Failed: " . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลที่ส่งมาจาก Frontend"]);
    exit();
}

try {
    $serial_numbers = [];
    if (isset($input['serial_numbers']) && is_array($input['serial_numbers'])) {
        $serial_numbers = $input['serial_numbers'];
    } elseif (!empty($input['serial_number'])) {
        $serial_numbers = [$input['serial_number']];
    }

    $type        = $input['maintenance_type'] ?? 'ตรวจสภาพ';
    $result      = $input['result'] ?? 'ผ่าน';
    $next_action = $input['next_action'] ?? '';
    $description = $input['description'] ?? '';
    $today       = date("Y-m-d");

    if (empty($serial_numbers)) {
        throw new Exception("ไม่ได้ระบุ Serial Number ของถังแก๊ส");
    }

    $conn->begin_transaction();

    // บันทึกเข้าตาราง maintenance
    $stmt_insert = $conn->prepare("
        INSERT INTO maintenance (serial_number, maintenance_date, maintenance_type, result, next_action, description) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    // อัปเดตวันตรวจถัดไปในตาราง gas_cylinder
    $stmt_update = $conn->prepare("
        UPDATE gas_cylinder 
        SET next_check_date = DATE_ADD(CURRENT_DATE(), INTERVAL 1 YEAR) 
        WHERE serial_number = ?
    ");

    // ตรวจสอบข้อมูลในตาราง gas_cylinder
    $stmt_check = $conn->prepare("SELECT serial_number FROM gas_cylinder WHERE serial_number = ?");

    foreach ($serial_numbers as $sn) {
        $stmt_check->bind_param("s", $sn);
        $stmt_check->execute();
        $check_res = $stmt_check->get_result();

        if ($check_res->num_rows === 0) {
            throw new Exception("ไม่พบ Serial Number '{$sn}' ในระบบฐานข้อมูล (gas_cylinder)");
        }

        $stmt_insert->bind_param("ssssss", $sn, $today, $type, $result, $next_action, $description);
        $stmt_insert->execute();

        $stmt_update->bind_param("s", $sn);
        $stmt_update->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "บันทึกผลการตรวจเรียบร้อยแล้ว (" . count($serial_numbers) . " รายการ)"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $e->getMessage()
    ]);
}

$conn->close();
exit();
?>