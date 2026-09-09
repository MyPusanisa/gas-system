<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$configPath = __DIR__ . "/../../config/db.php";
if (!file_exists($configPath)) {
    $configPath = __DIR__ . "/../../db.php";
}

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบไฟล์ db.php"]);
    exit();
}

try {
    // JOIN กับตาราง delivery_staff เพื่อดึง staff_name
    $sql = "SELECT d.*, 
                   COALESCE(s.staff_name, CONCAT('พนักงาน ID: ', d.staff_id)) as staff_name 
            FROM deliveries d 
            LEFT JOIN delivery_staff s ON d.staff_id = s.staff_id 
            WHERE d.status = 'pending_approval' OR d.status = 'pending'
            ORDER BY d.delivery_id DESC";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode([
            "success" => false, 
            "message" => "SQL Error: " . $conn->error
        ]);
        exit();
    }

    $deliveries = [];
    while ($row = $result->fetch_assoc()) {
        $deliveries[] = $row;
    }

    echo json_encode([
        "success" => true, 
        "data" => $deliveries
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Exception: " . $e->getMessage()
    ]);
}
?>