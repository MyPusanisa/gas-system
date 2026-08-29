<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once "../config/db.php";

$role = $_GET["role"] ?? "";
$staff_id = $_GET["staff_id"] ?? "";

$sql = "SELECT 
            delivery_id,
            cylinder_id,
            customer_id,
            admin_id,
            staff_id,
            delivery_date,
            return_date,
            status,
            created_at
        FROM delivery";


// ถ้าเป็น staff ให้เห็นเฉพาะงานตัวเอง
if ($role === "staff" && !empty($staff_id)) {

    $sql .= " WHERE staff_id = '$staff_id'";

}

$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);

if (!$result) {

    echo json_encode([
        "success" => false,
        "error" => $conn->error
    ]);

    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {

    $data[] = $row;

}

echo json_encode([
    "success" => true,
    "items" => $data
]);