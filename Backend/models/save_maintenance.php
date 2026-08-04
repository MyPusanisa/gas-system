<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "ไม่ได้รับข้อมูล JSON"
    ]);
    exit;
}

// รับค่า serial_number มาจาก Frontend
$serial_number = $data["serial_number"] ?? null;
$maintenance_type = $data["maintenance_type"] ?? null;
$result = $data["result"] ?? null;
$description = $data["description"] ?? null;
$admin_id = $data["admin_id"] ?? null;

if (!$serial_number || !$maintenance_type || !$result) {
    echo json_encode([
        "success" => false,
        "message" => "ข้อมูลไม่ครบถ้วน (ต้องการ serial_number, maintenance_type, result)"
    ]);
    exit;
}

// 1. ค้นหา cylinder_id จาก serial_number ก่อน
$findSql = "SELECT cylinder_id FROM cylinders WHERE serial_number = ?";
$findStmt = mysqli_prepare($conn, $findSql);
mysqli_stmt_bind_param($findStmt, "s", $serial_number);
mysqli_stmt_execute($findStmt);
$findResult = mysqli_stmt_get_result($findStmt);

if ($row = mysqli_fetch_assoc($findResult)) {
    $cylinder_id = $row["cylinder_id"];
} else {
    // กรณีใช้ serial_number แทน cylinder_id ในกรณีที่ตารางบันทึกตรงๆ
    $cylinder_id = $serial_number; 
}
mysqli_stmt_close($findStmt);

/* เวลา server */
$maintenance_date = date("Y-m-d H:i:s");

/* คำนวณ next maintenance (+90 วัน) */
$next_maintenance_date = date("Y-m-d H:i:s", strtotime("+90 days"));

// 2. บันทึกข้อมูลการตรวจลงตาราง maintenance
$sql = "INSERT INTO maintenance (
    maintenance_date,
    description,
    maintenance_type,
    result,
    cylinder_id,
    admin_id,
    next_maintenance_date
) VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "sssssss",
    $maintenance_date,
    $description,
    $maintenance_type,
    $result,
    $cylinder_id,
    $admin_id,
    $next_maintenance_date
);

if (mysqli_stmt_execute($stmt)) {
    // 3. อัปเดตวันตรวจครั้งถัดไปในตาราง cylinders
    $updateSql = "UPDATE cylinders SET next_check_date = ?, last_check_date = ? WHERE serial_number = ? OR cylinder_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateSql);
    $todayDate = date("Y-m-d");
    $nextCheckDateOnly = date("Y-m-d", strtotime("+90 days"));
    
    if ($updateStmt) {
        mysqli_stmt_bind_param($updateStmt, "ssss", $nextCheckDateOnly, $todayDate, $serial_number, $cylinder_id);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    }

    echo json_encode([
        "success" => true,
        "message" => "บันทึกสำเร็จ"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => mysqli_stmt_error($stmt)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);