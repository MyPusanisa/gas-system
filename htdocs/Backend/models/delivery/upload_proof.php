<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "message" => "PHP Error: " . $errstr . " (Line " . $errline . ")"
    ]);
    exit;
});

set_exception_handler(function($exception) {
    http_response_code(200);
    echo json_encode([
        "success" => false,
        "message" => "PHP Exception: " . $exception->getMessage()
    ]);
    exit;
});

try {
    include_once "../../config/db.php";

    if (!isset($_FILES['proof'])) {
        echo json_encode([
            "success" => false,
            "message" => "ไม่พบไฟล์รูปภาพ (field: proof)"
        ]);
        exit;
    }

    $deliveryId = $_POST['delivery_id'] ?? null;

    if (!$deliveryId) {
        echo json_encode([
            "success" => false,
            "message" => "ไม่พบ delivery_id"
        ]);
        exit;
    }

    $uploadDir = "../../uploads/";

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            echo json_encode([
                "success" => false,
                "message" => "ไม่สามารถสร้างโฟลเดอร์ uploads ได้"
            ]);
            exit;
        }
    }

    $file = $_FILES['proof'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            "success" => false,
            "message" => "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (Code: " . $file['error'] . ")"
        ]);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!$ext) {
        $ext = 'jpg';
    }

    $fileName = uniqid("proof_", true) . "." . $ext;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // 📌 แก้ไขจุดนี้: อัปเดตทั้ง proof_image_path และ status เป็น 'pending_approval' พร้อมกัน
        $stmt = $conn->prepare("
            UPDATE deliveries
            SET proof_image_path = ?, status = 'pending_approval'
            WHERE delivery_id = ?
        ");

        if (!$stmt) {
            echo json_encode([
                "success" => false,
                "message" => "SQL Prepare Failed: " . $conn->error
            ]);
            exit;
        }

        $stmt->bind_param("si", $fileName, $deliveryId);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "file" => $fileName,
                "message" => "อัปโหลดรูปภาพและส่งงานเรียบร้อย รอแอดมินตรวจสอบ"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "อัปเดต DB ไม่สำเร็จ: " . $stmt->error
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            "success" => false,
            "message" => "ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์ uploads ได้"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>