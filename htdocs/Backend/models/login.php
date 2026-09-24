<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    require_once __DIR__ . '/../config/database.php';

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    if (!is_array($data)) {
        $data = [];
    }

    $username = trim((string)($data["username"] ?? ''));
    $password = trim((string)($data["password"] ?? ''));

    if (empty($username) || empty($password)) {
        echo json_encode(["success" => false, "message" => "กรุณากรอก Username และ Password"], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $table = "admin";
    if ($conn->query("SHOW TABLES LIKE 'admin'")->num_rows === 0) {
        if ($conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0) {
            $table = "users";
        } else {
            echo json_encode(["success" => false, "message" => "ไม่พบตารางผู้ใช้งานในฐานข้อมูล"], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    $stmt = $conn->prepare("SELECT * FROM `{$table}` WHERE TRIM(username) = ? LIMIT 1");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "SQL Error: " . $conn->error], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $db_pass = trim((string)($row['password'] ?? ''));
        $isValid = ($password === $db_pass)
            || ($db_pass !== '' && password_verify($password, $db_pass))
            || ($db_pass !== '' && md5($password) === $db_pass);

        if ($isValid) {
            echo json_encode([
                "success" => true,
                "role" => $row["role"] ?? "admin",
                "id" => $row["admin_id"] ?? $row["id"] ?? 1,
                "name" => $row["name"] ?? $row["name_admin"] ?? $row["username"] ?? "Admin",
                "username" => $row["username"]
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        echo json_encode(["success" => false, "message" => "รหัสผ่านไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
        exit();
    }

    echo json_encode(["success" => false, "message" => "ไม่พบผู้ใช้ '{$username}' ในระบบ"], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(["success" => false, "message" => "System Error: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>