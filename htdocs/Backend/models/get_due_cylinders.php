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
    // เชื่อมต่อ MySQL โดยเน้นพอร์ต 3308 ก่อน
    $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3308);
    if ($conn->connect_error) {
        $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3307);
    }
    if ($conn->connect_error) {
        $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3306);
    }

    if ($conn->connect_error) {
        throw new Exception("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    // ดึงข้อมูลถังที่ถึงกำหนดตรวจ (next_check_date น้อยกว่าหรือเท่ากับวันปัจจุบัน)
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
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit();
?>