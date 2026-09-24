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
        $msg = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? "รูปมีขนาดใหญ่เกินไป กรุณาถ่ายใหม่"
            : "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (Code: " . $file['error'] . ")";
        echo json_encode(["success" => false, "message" => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // รับเฉพาะไฟล์รูปภาพจริง — เดิมใช้นามสกุลตามที่ส่งมา ทำให้อัปโหลด .php เข้าโฟลเดอร์ที่เปิดผ่านเว็บได้
    $imageTypes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !isset($imageTypes[$info[2]])) {
        echo json_encode(["success" => false, "message" => "ไฟล์ต้องเป็นรูปภาพ (JPG, PNG, WEBP)"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $ext = $imageTypes[$info[2]];

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