<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once "../config/db.php";

// ❌ ของเดิม: FROM delivery d
// ✅ แก้ไขเป็น: FROM deliveries d (เติม s)
$sql = "SELECT 
            ds.staff_name,
            COUNT(d.delivery_id) AS delivery_count
        FROM deliveries d
        JOIN delivery_staff ds 
            ON d.staff_id = ds.staff_id
        GROUP BY d.staff_id
        ORDER BY delivery_count DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => $conn->error
    ]);
    exit;
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
]);

$conn->close();
?>