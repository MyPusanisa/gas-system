<?php
$dbHost = "localhost";
$dbName = "gas_system";
$dbUser = "root";
$dbPass = "";

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. สร้างตาราง staff
    $pdo->exec("CREATE TABLE IF NOT EXISTS staff (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(150) NOT NULL
    )");

    // 2. เพิ่มผู้ใช้ somchai (รหัสผ่าน 1234)
    $hash = password_hash("1234", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO staff (username, password, name) VALUES ('somchai', :pass, 'สมชาย ส่งไว') ON DUPLICATE KEY UPDATE password = :pass");
    $stmt->execute([':pass' => $hash]);

    echo "<h1>✅ เพิ่มผู้ใช้ somchai (รหัสผ่าน: 1234) สำเร็จแล้ว!</h1>";
} catch (PDOException $e) {
    echo "<h1>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</h1>";
}
?>
