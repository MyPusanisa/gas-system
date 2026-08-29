<?php
require_once __DIR__ . '/../../config/db.php';

$staff_id = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;
$period   = isset($_GET['period']) ? $_GET['period'] : 'day'; 

// กรองตามเงื่อนไขวันที่
$date_condition = "DATE(created_at) = CURDATE()"; // รายวัน
if ($period === 'month') {
    $date_condition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
} else if ($period === 'year') {
    $date_condition = "YEAR(created_at) = YEAR(CURDATE())";
}

$sql = "SELECT delivery_id, customer_name, address, gas_type, size, status, created_at 
        FROM deliveries 
        WHERE staff_id = $staff_id AND $date_condition 
        ORDER BY created_at DESC";

$result = $conn->query($sql);
$history = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}

echo json_encode([
    "success" => true, 
    "total_completed" => count(array_filter($history, fn($i) => $i['status'] === 'completed')),
    "data" => $history
]);
?>