<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once "../../config/db.php";

$sql = "SELECT 
            staff_id,
            staff_name,
            staff_phone,
            address,
            username,
            status
        FROM delivery_staff
        ORDER BY staff_id ASC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["success" => false, "message" => $conn->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["success" => true, "data" => $data]);
?>