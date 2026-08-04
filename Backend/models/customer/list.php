<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include_once "../../config/db.php";

$sql = "SELECT customer_id, name, phone, address, map_pin FROM customer";
$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode(["success" => true, "data" => $data]);
?>