<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../config/db.php";

$sql = "
SELECT 
    m.maintenance_id,
    m.maintenance_date,
    m.description,
    m.maintenance_type,
    m.result,
    m.next_maintenance_date,
    m.cylinder_id,
    m.admin_id,

    g.brand,
    g.size,
    g.status

FROM maintenance m

INNER JOIN gas_cylinder g
ON m.cylinder_id = g.cylinder_id

WHERE m.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)

ORDER BY m.next_maintenance_date ASC
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

    $today = date("Y-m-d");

    // คำนวณสถานะ
    if ($row["next_maintenance_date"] < $today) {
        $row["due_status"] = "เลยกำหนด";
    } else {
        $row["due_status"] = "ใกล้ถึงกำหนด";
    }

    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);

mysqli_close($conn);