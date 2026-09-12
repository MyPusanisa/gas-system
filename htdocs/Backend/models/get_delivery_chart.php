<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$configPath = __DIR__ . "/../config/db.php";
if (!file_exists($configPath)) {
    $configPath = __DIR__ . "/../db.php";
}

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบไฟล์ db.php"]);
    exit();
}

try {
    // ดึงจำนวนออเดอร์แยกตามวันที่จัดส่ง
    $sql = "SELECT 
                DATE(created_at) AS delivery_day,
                COUNT(delivery_id) AS total_orders
            FROM deliveries
            WHERE created_at IS NOT NULL
            GROUP BY DATE(created_at)
            ORDER BY delivery_day ASC
            LIMIT 10";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "delivery_day" => $row['delivery_day'],
            "total_orders" => (int)$row['total_orders']
        ];
    }

    echo json_encode([
        "success" => true,
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>