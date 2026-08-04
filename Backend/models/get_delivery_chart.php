<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../config/db.php";

$sql = "
SELECT
    DATE(delivery_date) AS delivery_day,
    COUNT(delivery_id) AS total_orders

FROM delivery

WHERE delivery_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)

GROUP BY DATE(delivery_date)

ORDER BY delivery_day ASC
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