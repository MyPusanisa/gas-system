<?php
// 1. เคลียร์ Output Buffer ทั้งหมด
while (ob_get_level()) {
    ob_end_clean();
}

// 2. ตั้งค่า CORS Headers ให้รองรับ Frontend
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// 3. จัดการ Preflight Options Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 4. เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "gas_system", 3307);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB Connection Failed"]);
    exit();
}
$conn->set_charset("utf8mb4");

// 5. รับข้อมูลแบบ JSON จาก React
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลที่ส่งมา"]);
    exit();
}

// รับค่าตัวแปร (ปรับชื่อให้ตรงกับ Payload ที่ React ส่งมา)
$cylinder_id = $input['cylinder_id'] ?? null;
$type = $input['type'] ?? null;
$result = $input['result'] ?? null;
$action = $input['action'] ?? null;
$remark = $input['remark'] ?? '';
$details = $input['details'] ?? '';

if (!$cylinder_id) {
    echo json_encode(["success" => false, "message" => "กรุณาเลือกถังแก๊ส"]);
    exit();
}

try {
    // 6. เพิ่มข้อมูลลงในตารางประวัติการตรวจ (ปรับชื่อตารางและคอลัมน์ตาม DB ของคุณ)
    $sql = "INSERT INTO maintenance_history (cylinder_id, type, result, action, remark, details, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $cylinder_id, $type, $result, $action, $remark, $details);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "บันทึกผลตรวจเรียบร้อยแล้ว"
        ]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
exit();
?>