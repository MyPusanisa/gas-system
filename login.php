<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method ไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
|--------------------------------------------------------------------------
| ตั้งค่าฐานข้อมูล
|--------------------------------------------------------------------------
*/
$dbHost = "localhost";
$dbName = "gas_system";
$dbUser = "root";
$dbPass = "";

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "ไม่สามารถเชื่อมต่อฐานข้อมูลได้"], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
|--------------------------------------------------------------------------
| รับข้อมูล JSON
|--------------------------------------------------------------------------
*/
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

$username = isset($input["username"]) ? trim($input["username"]) : "";
$password = isset($input["password"]) ? trim($input["password"]) : "";

if ($username === "" || $password === "") {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "กรุณากรอก Username และ Password ให้ครบถ้วน"], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
|--------------------------------------------------------------------------
| ฟังก์ชันตรวจสอบรหัสผ่าน (รองรับทั้ง Hash, Plaintext และ MD5)
|--------------------------------------------------------------------------
*/
function passwordMatches($plainPassword, $storedPassword)
{
    if (password_verify($plainPassword, $storedPassword)) return true;
    if ($plainPassword === $storedPassword) return true;
    if (md5($plainPassword) === $storedPassword) return true;
    return false;
}

try {
    // 1. ตรวจสอบ Admin
    $adminSql = "SELECT * FROM admins WHERE username = :username LIMIT 1";
    $adminStmt = $pdo->prepare($adminSql);
    $adminStmt->execute([":username" => $username]);
    $admin = $adminStmt->fetch();

    if ($admin && passwordMatches($password, $admin["password"])) {
        echo json_encode([
            "success"  => true,
            "message"  => "เข้าสู่ระบบสำเร็จ",
            "role"     => "admin",
            "admin_id" => $admin["id"] ?? $admin["admin_id"] ?? 1,
            "staff_id" => null,
            "username" => $admin["username"],
            "name"     => $admin["name"] ?? $admin["username"]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. ตรวจสอบ Staff (พนักงานส่งแก๊ส)
    $staffSql = "SELECT * FROM staff WHERE username = :username LIMIT 1";
    $staffStmt = $pdo->prepare($staffSql);
    $staffStmt->execute([":username" => $username]);
    $staff = $staffStmt->fetch();

    if ($staff && passwordMatches($password, $staff["password"])) {
        echo json_encode([
            "success"  => true,
            "message"  => "เข้าสู่ระบบสำเร็จ",
            "role"     => "staff",
            "admin_id" => null,
            "staff_id" => $staff["id"] ?? $staff["staff_id"] ?? 1,
            "username" => $staff["username"],
            "name"     => $staff["name"] ?? $staff["username"]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Login ไม่สำเร็จ
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในระบบฐานข้อมูล"], JSON_UNESCAPED_UNICODE);
}
