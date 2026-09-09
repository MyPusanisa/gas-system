<?php
// เปิดการแสดงผล Error เพื่อหาจุดที่ผิดพลาด
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gas_system";

// ป้องกันกรณีการเชื่อมต่อค้างหรือเกิด Fatal Error
try {
    $conn = @new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Connect failed: " . $conn->connect_error]);
        exit();
    }

    $sql = "SELECT gas_value FROM gas_sensor_logs ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $gas_val = (int)$row['gas_value'];
        echo json_encode([
            "success" => true,
            "gas_value" => $gas_val,
            "gasLevel" => $gas_val
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "gas_value" => 0,
            "gasLevel" => 0
        ]);
    }

    $conn->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>