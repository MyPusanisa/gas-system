<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    $staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
    $period = isset($_GET['period']) ? trim($_GET['period']) : 'all';

    // เชื่อมต่อฐานข้อมูล gas_system บนพอร์ต 3308 (หรือ 3306)
    $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3308);
    if ($conn->connect_error) {
        $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3306);
    }

    if ($conn->connect_error) {
        ob_end_clean();
        echo json_encode([
            "success" => false, 
            "message" => "เชื่อมต่อฐานข้อมูลล้มเหลว", 
            "data" => [], 
            "total_completed" => 0
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $conn->set_charset("utf8mb4");

    // ดึงข้อมูลประวัติการจัดส่งของพนักงาน
    $sql = "SELECT * FROM deliveries WHERE staff_id = ?";
    
    if ($period === 'day' || $period === 'today') {
        $sql .= " AND DATE(created_at) = CURDATE()";
    } else if ($period === 'month') {
        $sql .= " AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    }
    
    $sql .= " ORDER BY delivery_id DESC";

    $history = [];
    $total_completed = 0;

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
            if (isset($row['status']) && ($row['status'] === 'completed' || $row['status'] === 'สำเร็จ')) {
                $total_completed++;
            }
        }
        $stmt->close();
    }

    if ($total_completed === 0 && !empty($history)) {
        $total_completed = count($history);
    }

    ob_end_clean();
    echo json_encode([
        "success" => true,
        "data" => $history,
        "total_completed" => $total_completed
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "data" => [],
        "total_completed" => 0
    ], JSON_UNESCAPED_UNICODE);
}
?>