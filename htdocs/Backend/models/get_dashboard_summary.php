<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include_once "../config/db.php";

$sql = "SELECT 
            COUNT(*) as total_cylinders,
            SUM(CASE WHEN status = 'ในคลัง' THEN 1 ELSE 0 END) as in_stock,
            SUM(CASE WHEN status = 'ปกติ' THEN 1 ELSE 0 END) as ready,
            SUM(CASE WHEN next_check_date < CURDATE() THEN 1 ELSE 0 END) as expired
        FROM gas_cylinder";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "data" => [
        "total_cylinders" => (int)$row["total_cylinders"],
        "in_stock" => (int)$row["in_stock"],
        "ready" => (int)$row["ready"],
        "expired" => (int)$row["expired"]
    ]
]);
?>