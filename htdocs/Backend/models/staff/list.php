<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/db.php"; 

try {
    // เปลี่ยนมาใช้ชื่อตาราง delivery_staff และ deliveries ให้ตรงตามฐานข้อมูลจริง
    $sql = "SELECT 
                s.staff_id,
                s.staff_name,
                s.staff_phone,
                s.username,
                s.password,
                s.address,
                s.status,
                COUNT(CASE WHEN d.status = 'pending' THEN 1 END) AS pending_jobs
            FROM delivery_staff s
            LEFT JOIN deliveries d ON s.staff_id = d.staff_id
            GROUP BY s.staff_id
            ORDER BY s.staff_id ASC";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $staffs = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $staffs
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>