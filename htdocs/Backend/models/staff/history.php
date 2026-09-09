<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config/db.php';

$staff_id = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;
$period   = isset($_GET['period']) ? $_GET['period'] : 'day'; 

// 1. เงื่อนไขช่วงเวลา
$date_condition = "1=1";
if ($period === 'day') {
    $date_condition = "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} else if ($period === 'month') {
    $date_condition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
} else if ($period === 'year') {
    $date_condition = "YEAR(created_at) = YEAR(CURDATE())";
}

$staff_condition = "";
if ($staff_id > 0) {
    $staff_condition = "AND staff_id = $staff_id";
}

// 2. ดึงรายการออเดอร์ทั้งหมด
$sql = "SELECT delivery_id, staff_id, customer_name, address, gas_type, size, status, created_at 
        FROM deliveries 
        WHERE $date_condition $staff_condition 
        ORDER BY created_at DESC";

$result = $conn->query($sql);
$history = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}

// 3. สรุปจำนวนออเดอร์รายวัน (นับสถานะ success)
$daily_summary_sql = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m-%d') AS date,
                        DATE_FORMAT(created_at, '%d/%m') AS formatted_date,
                        COUNT(delivery_id) AS total_orders,
                        COUNT(CASE WHEN LOWER(status) = 'success' THEN 1 END) AS completed_orders
                      FROM deliveries 
                      WHERE $date_condition $staff_condition
                      GROUP BY DATE(created_at)
                      ORDER BY DATE(created_at) ASC";

$daily_result = $conn->query($daily_summary_sql);
$daily_summary = [];

if ($daily_result && $daily_result->num_rows > 0) {
    while ($row = $daily_result->fetch_assoc()) {
        $row['total_orders'] = (int)$row['total_orders'];
        $row['completed_orders'] = (int)$row['completed_orders'];
        $daily_summary[] = $row;
    }
}

// 4. สรุปรอบส่งพนักงาน
$staff_summary_sql = "SELECT 
                        s.staff_id,
                        s.staff_name,
                        COUNT(d.delivery_id) AS total_rounds
                      FROM staff s
                      LEFT JOIN deliveries d ON s.staff_id = d.staff_id AND LOWER(d.status) = 'success'
                      GROUP BY s.staff_id, s.staff_name
                      ORDER BY total_rounds DESC";

$staff_summary_result = $conn->query($staff_summary_sql);
$staff_rounds = [];

if ($staff_summary_result && $staff_summary_result->num_rows > 0) {
    while ($row = $staff_summary_result->fetch_assoc()) {
        $row['total_rounds'] = (int)$row['total_rounds'];
        $staff_rounds[] = $row;
    }
}

// ส่ง JSON กลับไป
echo json_encode([
    "success" => true, 
    "total_completed" => count(array_filter($history, fn($i) => strtolower($i['status']) === 'success')),
    "daily_summary" => $daily_summary,
    "staff_rounds" => $staff_rounds,
    "data" => $history
], JSON_UNESCAPED_UNICODE);
?>