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
    // ดึงชื่อพนักงานและคอลัมน์ delivery_count จากตาราง delivery_staff โดยตรง
    $sql = "SELECT 
                staff_name,
                COALESCE(delivery_count, 0) AS delivery_count
            FROM delivery_staff
            ORDER BY delivery_count DESC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $staffRounds = [];
    while ($row = $result->fetch_assoc()) {
        $staffRounds[] = [
            "staff_name" => $row["staff_name"],
            "count" => (int)$row["delivery_count"]
        ];
    }

    echo json_encode([
        "success" => true,
        "data" => $staffRounds
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>