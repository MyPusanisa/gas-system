<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$configPath = __DIR__ . "/../../config/db.php";
if (!file_exists($configPath)) $configPath = __DIR__ . "/../config/db.php";
if (!file_exists($configPath)) $configPath = __DIR__ . "/../../db.php";

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo json_encode(["success" => false, "message" => "ไม่พบไฟล์ db.php"]);
    exit();
}

try {
    $sql = "SELECT serial_number, brand, gas_type, size, next_check_date 
            FROM gas_cylinder 
            WHERE next_check_date IS NOT NULL 
            ORDER BY next_check_date ASC LIMIT 5";
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
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
$conn->close();
?# 1. ส่งไฟล์ Backend ทั้งหมดขึ้นเซิร์ฟเวอร์
scp -i "C:\Users\user\Downloads\gas-key.pem" -r "C:\Users\user\OneDrive\เดสก์ท็อป\dataST\DataSystem\App\htdocs\Backend" ubuntu@56.10.97.74:/var/www/html/

# 2. ปรับ Permission
ssh -i "C:\Users\user\Downloads\gas-key.pem" ubuntu@56.10.97.74 "sudo chmod -R 755 /var/www/html/Backend"