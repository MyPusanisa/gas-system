<?php
// 1. เคลียร์ Output ทั้งหมดป้องกัน HTML/Whitespace หลุดออกมา
while (ob_get_level()) {
    ob_end_clean();
}

// 2. จัดการ CORS Header ให้รองรับ credentials และ Origin
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// 3. ตอบกลับ OPTIONS Request สำหรับ Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 4. เชื่อมต่อ DB (ดึงค่าตั้งค่าจาก db.php หรือต่อตรงเพื่อลดความซ้ำซ้อน)
$conn = new mysqli("localhost", "root", "", "gas_system", 3307);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB Connection Failed"]);
    exit();
}
$conn->set_charset("utf8mb4");

try {
    // 5. ดึงข้อมูลถังที่ถึงกำหนดตรวจ
    $sql = "SELECT * FROM gas_cylinder WHERE next_check_date <= CURRENT_DATE() OR next_check_date IS NULL ORDER BY next_check_date ASC";
    $result = $conn->query($sql);

    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "data" => []
    ]);
}

$conn->close();
exit();
?>