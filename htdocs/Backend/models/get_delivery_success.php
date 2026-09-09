<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/db.php";

// ดึงข้อมูลการจัดส่ง พร้อม cylinder_id
$sql = "SELECT delivery_id, cylinder_id, customer_name, address, brand, gas_type, size, status, proof_image_path, created_at 
        FROM deliveries 
        ORDER BY delivery_id DESC";

$result = mysqli_query($conn, $sql);
$data = array();

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
mysqli_close($conn);
?>