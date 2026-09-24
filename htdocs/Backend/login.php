<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(["success" => false, "message" => "กรุณากรอก Username และ Password"]);
    exit();
}

// 1. ค้นหาในตาราง delivery_staff ก่อน
$stmt = $conn->prepare("SELECT staff_id, username, password, name FROM delivery_staff WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$resStaff = $stmt->get_result();

if ($resStaff && $resStaff->num_rows > 0) {
    $row = $resStaff->fetch_assoc();
    if ($password === $row['password'] || md5($password) === $row['password']) {
        echo json_encode([
            "success"  => true,
            "message"  => "เข้าสู่ระบบสำเร็จ",
            "role"     => "staff",
            "name"     => $row['name'],
            "username" => $row['username'],
            "staff_id" => $row['staff_id'],
            "admin_id" => null
        ]);
        exit();
    }
}

// 2. ถ้าไม่พบใน staff ให้ค้นหาในตาราง admin
$stmt = $conn->prepare("SELECT admin_id, username, password, name FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$resAdmin = $stmt->get_result();

if ($resAdmin && $resAdmin->num_rows > 0) {
    $row = $resAdmin->fetch_assoc();
    if ($password === $row['password'] || md5($password) === $row['password']) {
        echo json_encode([
            "success"  => true,
            "message"  => "เข้าสู่ระบบสำเร็จ",
            "role"     => "admin",
            "name"     => $row['name'],
            "username" => $row['username'],
            "admin_id" => $row['admin_id'],
            "staff_id" => null
        ]);
        exit();
    }
}

echo json_encode(["success" => false, "message" => "ไม่พบผู้ใช้ '$username' หรือรหัสผ่านไม่ถูกต้อง"]);
?>
