<?php
require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->customer_name) && !empty($data->staff_id)) {
    $customer_name = $conn->real_escape_string($data->customer_name);
    $phone         = $conn->real_escape_string($data->phone ?? '');
    $address       = $conn->real_escape_string($data->address ?? '');
    $map_pin       = $conn->real_escape_string($data->map_pin ?? '');
    $brand         = $conn->real_escape_string($data->brand ?? '');
    $gas_type      = $conn->real_escape_string($data->gas_type ?? '');
    $size          = $conn->real_escape_string($data->size ?? '');
    $staff_id      = (int)$data->staff_id;

    $sql = "INSERT INTO deliveries (customer_name, phone, address, map_pin, brand, gas_type, size, staff_id, status) 
            VALUES ('$customer_name', '$phone', '$address', '$map_pin', '$brand', '$gas_type', '$size', $staff_id, 'pending')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "message" => "สร้างงานจัดส่งสำเร็จ"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ครบถ้วน"]);
}
?>