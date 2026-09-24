<?php
function getDatabaseConfig() {
    return [
        "host" => getenv("DB_HOST") ?: "127.0.0.1",
        "port" => (int)(getenv("DB_PORT") ?: 3306),
        "username" => getenv("DB_USERNAME") ?: "root",
        "password" => getenv("DB_PASSWORD") ?: "",
        "database" => getenv("DB_NAME") ?: "gas_system"
    ];
}

function getDbConnection() {
    $config = getDatabaseConfig();
    $conn = @new mysqli($config["host"], $config["username"], $config["password"], $config["database"], $config["port"]);

    if ($conn->connect_error) {
        throw new RuntimeException("Database connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

// บางไฟล์ (customer/*, delivery/update.php) เขียนด้วย PDO — สร้าง $pdo ให้ด้วย
function getPdoConnection() {
    $config = getDatabaseConfig();
    $dsn = "mysql:host={$config["host"]};port={$config["port"]};dbname={$config["database"]};charset=utf8mb4";
    return new PDO($dsn, $config["username"], $config["password"], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

try {
    $conn = getDbConnection();
    $pdo = getPdoConnection();
} catch (Throwable $e) {
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>