<?php
// อนุญาตให้ React (พอร์ต 5173) ดึงข้อมูลได้
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// การเชื่อมต่อ Database พอร์ต 3307
$conn = new mysqli("127.0.0.1", "root", "", "gas_system", 3307);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// ดึง 10 ค่าล่าสุด
$sql = "SELECT gas_value FROM gas_sensor_logs ORDER BY id DESC LIMIT 10";
$result = $conn->query($sql);

$values = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $values[] = (int)$row['gas_value'];
    }
}

$current_value = 0;

if (count($values) > 0) {
    $filtered_values = $values;
    
    if (count($filtered_values) >= 3) {
        sort($filtered_values);
        array_shift($filtered_values);
        array_pop($filtered_values);
    }

    $avg_value = array_sum($filtered_values) / count($filtered_values);
    $current_value = round($avg_value);
}

echo json_encode([
    "success" => true,
    "level" => $current_value
]);

$conn->close();
?>