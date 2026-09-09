<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once "../../config/db.php";

// ดึงข้อมูลพนักงาน พร้อมนับจำนวนงาน pending (งานที่กำลังจัดส่ง)
$sql = "SELECT 
            s.staff_id,
            s.staff_name,
            s.staff_phone,
            s.address,
            s.username,
            s.password,
            s.status,
            COUNT(CASE WHEN d.status = 'pending' THEN 1 END) AS pending_jobs
        FROM delivery_staff s
        LEFT JOIN deliveries d ON s.staff_id = d.staff_id
        GROUP BY s.staff_id
        ORDER BY s.staff_id ASC";

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