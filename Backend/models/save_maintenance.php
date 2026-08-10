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
    $serial_number = $input['serial_number'] ?? null;
    $type          = $input['maintenance_type'] ?? 'ตรวจสภาพ';
    $result        = $input['result'] ?? 'ผ่าน';
    $next_action   = $input['next_action'] ?? '';
    $description   = $input['description'] ?? '';

    if (!$serial_number) {
        throw new Exception("ไม่ได้ระบุ Serial Number ของถังแก๊ส");
    }

    // 1. บันทึกข้อมูลเข้าตาราง maintenance (ตัด created_at ออก)
    $sql = "INSERT INTO maintenance (serial_number, maintenance_type, result, next_action, description) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL Prepare Failed: " . $conn->error);
    }

    $stmt->bind_param("sssss", $serial_number, $type, $result, $next_action, $description);
    
    if (!$stmt->execute()) {
        throw new Exception("SQL Execute Failed: " . $stmt->error);
    }

    // 2. อัปเดตวันตรวจครั้งถัดไปในตาราง gas_cylinder (บวกไป 1 ปี)
    $update_sql = "UPDATE gas_cylinder SET next_check_date = DATE_ADD(CURRENT_DATE(), INTERVAL 1 YEAR) WHERE serial_number = ?";
    $up_stmt = $conn->prepare($update_sql);
    if ($up_stmt) {
        $up_stmt->bind_param("s", $serial_number);
        $up_stmt->execute();
    }

    echo json_encode([
        "success" => true,
        "message" => "บันทึกผลการตรวจเรียบร้อยแล้ว"
    ]);

} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $e->getMessage()
    ]);
}

$conn->close();
exit();
?>