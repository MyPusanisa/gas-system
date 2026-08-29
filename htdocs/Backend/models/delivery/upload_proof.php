<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once "../../config/db.php";

if (!isset($_FILES['proof'])) {
    echo json_encode([
        "success" => false,
        "message" => "ไม่พบไฟล์"
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
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['proof'];

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);

$fileName = uniqid("proof_", true) . "." . $ext;

$targetPath = $uploadDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {

    $stmt = $conn->prepare("
        UPDATE delivery
        SET proof_image_path = ?
        WHERE delivery_id = ?
    ");

    $stmt->bind_param("si", $fileName, $deliveryId);

    $stmt->execute();

    echo json_encode([
        "success" => true,
        "file" => $fileName
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "อัปโหลดไฟล์ไม่สำเร็จ"
    ]);
}