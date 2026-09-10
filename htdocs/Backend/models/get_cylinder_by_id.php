<?php
require_once __DIR__ . '/../config/database.php';

$cylinder_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($cylinder_id)) {
    echo json_encode(["success" => false, "message" => "กรุณาระบุรหัสถังแก๊ส"]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM cylinders WHERE cylinder_id = :id LIMIT 1");
    $stmt->execute([':id' => $cylinder_id]);
    $cylinder = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cylinder) {
        echo json_encode(["success" => true, "data" => $cylinder]);
    } else {
        echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลถังแก๊สใบนี้ในระบบ"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>