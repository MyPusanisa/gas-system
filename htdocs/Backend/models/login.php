<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    $username = trim($data["username"] ?? '');
    $password = trim($data["password"] ?? '');

    if (empty($username) || empty($password)) {
        echo json_encode(["success" => false, "message" => "กรุณากรอก Username และ Password"], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // เชื่อมต่อฐานข้อมูล gas_system บนพอร์ต 3308 (หรือ 3306)
    $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3308);
    if ($conn->connect_error) {
        $conn = @new mysqli("127.0.0.1", "root", "", "gas_system", 3306);
    }

    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $conn->connect_error], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $conn->set_charset("utf8mb4");

    // 1. ค้นหาในตาราง delivery_staff
    $stmt = $conn->prepare("SELECT * FROM delivery_staff WHERE TRIM(username) = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $db_pass = trim($row['password']);
        if ($password === $db_pass || password_verify($password, $db_pass) || md5($password) === $db_pass) {
            echo json_encode([
                "success" => true,
                "role" => "staff",
                "id" => $row["staff_id"],
                "name" => $row["staff_name"],
                "username" => $row["username"]
            ], JSON_UNESCAPED_UNICODE);
            exit();
        } else {
            echo json_encode(["success" => false, "message" => "พบชื่อผู้ใช้ '{$username}' แต่รหัสผ่านไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    // 2. ค้นหาในตาราง admin
    $stmt2 = $conn->prepare("SELECT * FROM admin WHERE TRIM(username) = ? LIMIT 1");
    $stmt2->bind_param("s", $username);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($row2 = $result2->fetch_assoc()) {
        $db_pass = trim($row2['password']);
        if ($password === $db_pass || password_verify($password, $db_pass) || md5($password) === $db_pass) {
            echo json_encode([
                "success" => true,
                "role" => "admin",
                "id" => $row2["admin_id"] ?? $row2["id"] ?? 1,
                "name" => $row2["name"] ?? $row2["username"],
                "username" => $row2["username"]
            ], JSON_UNESCAPED_UNICODE);
            exit();
        } else {
            echo json_encode(["success" => false, "message" => "พบชื่อผู้ใช้ '{$username}' ใน admin แต่รหัสผ่านไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    echo json_encode(["success" => false, "message" => "ไม่พบชื่อผู้ใช้งาน '{$username}' ในระบบ"], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>