<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../config/db.php";

$sql = "
SELECT
    DATE(delivery_date) as order_date,
    COUNT(delivery_id) as total_orders

FROM delivery

GROUP BY DATE(delivery_date)

ORDER BY order_date ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);
    exit;
}

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);

mysqli_close($conn);