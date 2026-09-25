<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(204); exit; }

$dbHost = "127.0.0.1";
$dbName = "gas_system";
$dbUser = "root";
$dbPass = "";

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

$username = trim($input["username"] ?? "");
$password = trim($input["password"] ?? "");

if ($username === "" || $password === "") {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "กรุณากรอก Username และ Password"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ดึงผู้ใช้ทั้งหมดมาเทียบกรณีที่มีช่องว่างหรือตัวอักษรพิเศษซ่อนอยู่
    $stmt = $pdo->prepare("SELECT * FROM delivery_staff WHERE TRIM(LOWER(username)) = LOWER(:u)");
    $stmt->execute([":u" => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        // ลองหาจากตาราง staff
        try {
            $stmt = $pdo->prepare("SELECT * FROM staff WHERE TRIM(LOWER(username)) = LOWER(:u)");
            $stmt->execute([":u" => $username]);
            $user = $stmt->fetch();
        } catch (Exception $e) {}
    }

    if ($user) {
        // บัญชีที่ admin ปิดใช้งานเข้าระบบไม่ได้
        if (($user["status"] ?? "active") === "inactive") {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "บัญชีนี้ถูกปิดใช้งาน กรุณาติดต่อผู้ดูแลระบบ"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $storedPass = trim($user["password"] ?? $user["pass"] ?? "");
        if ($password === $storedPass || md5($password) === strtolower($storedPass) || password_verify($password, $storedPass)) {
            echo json_encode([
                "success"  => true,
                "message"  => "เข้าสู่ระบบสำเร็จ",
                "role"     => "staff",
                "staff_id" => $user["staff_id"] ?? $user["id"] ?? null,
                "username" => $user["username"],
                "name"     => $user["staff_name"] ?? $user["name"] ?? $user["username"]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "รหัสผ่านไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ลองค้นหาในตาราง admin
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE TRIM(LOWER(username)) = LOWER(:u)");
    $stmt->execute([":u" => $username]);
    $admin = $stmt->fetch();

    if ($admin) {
        $storedPass = trim($admin["password"] ?? $admin["pass"] ?? "");
        if ($password === $storedPass || md5($password) === strtolower($storedPass) || password_verify($password, $storedPass)) {
            echo json_encode([
                "success"  => true,
                "message"  => "เข้าสู่ระบบสำเร็จ",
                "role"     => "admin",
                "admin_id" => $admin["admin_id"] ?? $admin["id"] ?? 1,
                "username" => $admin["username"],
                "name"     => $admin["name"] ?? $admin["username"]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ไม่พบผู้ใช้ '$username' ในระบบ"], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Query Error: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
