<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once "../../config/db.php";

$input = json_decode(file_get_contents('php://input'), true);
$serial = $input['serial_number'] ?? $input['id'] ?? '';

if (empty($serial)) {
    echo json_encode(["success" => false, "message" => "ไม่พบ Serial Number ของถังแก๊ส"]);
    exit();
}

try {
    $sql = "DELETE FROM gas_cylinder WHERE serial_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $serial);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "ลบถังแก๊สสำเร็จ"]);
    } else {
        echo json_encode(["success" => false, "message" => "ไม่สามารถลบข้อมูลได้"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>