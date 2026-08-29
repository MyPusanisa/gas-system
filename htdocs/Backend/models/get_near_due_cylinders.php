<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../config/db.php";

// แก้ไขจาก cylinders เป็น gas_cylinder
$sql = "SELECT * FROM gas_cylinder";
$result = $conn->query($sql);

$data = [];
$today = new DateTime();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['next_check_date'])) {
            $checkDate = new DateTime($row['next_check_date']);
            $interval = $today->diff($checkDate);
            $daysLeft = (int)$interval->format("%r%a");

            if ($daysLeft <= 30) {
                $row['days_left'] = $daysLeft;
                $data[] = $row;
            }
        }
    }
}

echo json_encode([
    "success" => true,
    "data" => $data
], JSON_UNESCAPED_UNICODE);
?>