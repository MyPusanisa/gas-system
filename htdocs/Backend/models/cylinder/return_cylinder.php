<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/db.php"; 

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = isset($data['action']) ? $data['action'] : $action;
}

// -----------------------------------------------------------
// 0. ดึงรายการตัวเลือก Dropdown จากตาราง Master Data โดยตรง
// -----------------------------------------------------------
if ($action === 'get_options') {
    $brands = [];
    $gas_types = [];
    $sizes = [];

    // 1. ดึง ยี่ห้อ จาก gas_brands
    $res1 = $conn->query("SELECT brand_name FROM gas_brands ORDER BY id ASC");
    if ($res1) {
        while ($r = $res1->fetch_assoc()) { 
            $brands[] = $r['brand_name']; 
        }
    }

    // 2. ดึง ชนิดแก๊ส จาก gas_types
    $res2 = $conn->query("SELECT type_name FROM gas_types ORDER BY id ASC");
    if ($res2) {
        while ($r = $res2->fetch_assoc()) { 
            $gas_types[] = $r['type_name']; 
        }
    }

    // 3. ดึง ขนาดถัง จาก gas_sizes
    $res3 = $conn->query("SELECT size_name FROM gas_sizes ORDER BY id ASC");
    if ($res3) {
        while ($r = $res3->fetch_assoc()) { 
            $sizes[] = $r['size_name']; 
        }
    }

    echo json_encode([
        "success" => true,
        "brands" => $brands,
        "gas_types" => $gas_types,
        "sizes" => $sizes
    ]);
    exit;
}

// -----------------------------------------------------------
// 1. ค้นหา Serial Number
// -----------------------------------------------------------
else if ($action === 'search') {
    $serial = isset($_GET['serial']) ? trim($_GET['serial']) : '';

    if (empty($serial)) {
        echo json_encode(["success" => false, "message" => "กรุณาระบุ Serial Number"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT *, DATEDIFF(next_inspection_date, CURDATE()) AS days_left FROM gas_cylinder WHERE serial_number = ? LIMIT 1");
    $stmt->bind_param("s", $serial);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(["success" => true, "found" => true, "data" => $row]);
    } else {
        echo json_encode(["success" => true, "found" => false, "message" => "ไม่พบ Serial Number นี้ในระบบ"]);
    }
    $stmt->close();
}

// -----------------------------------------------------------
// 2. รับถังเดิมคืนเข้าคลัง
// -----------------------------------------------------------
else if ($action === 'return_existing') {
    $serial = isset($data['serial_number']) ? trim($data['serial_number']) : '';
    $import_date = isset($data['import_date']) && !empty($data['import_date']) ? $data['import_date'] : date('Y-m-d');

    if (empty($serial)) {
        echo json_encode(["success" => false, "message" => "ไม่ได้ระบุ Serial Number"]);
        exit;
    }

    $next_inspection = date('Y-m-d', strtotime($import_date . ' + 5 years'));

    $stmt = $conn->prepare("UPDATE gas_cylinder SET status = 'ในคลัง', current_location = 'คลังแก๊ส', import_date = ?, next_inspection_date = ? WHERE serial_number = ?");
    $stmt->bind_param("sss", $import_date, $next_inspection, $serial);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "รับถังแก๊สรหัส $serial คืนเข้าคลังเรียบร้อยแล้ว"]);
    } else {
        echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล"]);
    }
    $stmt->close();
}

// -----------------------------------------------------------
// 3. เพิ่มถังใหม่นอกระบบเข้าคลัง
// -----------------------------------------------------------
else if ($action === 'add_new') {
    $serial = isset($data['serial_number']) ? trim($data['serial_number']) : '';
    $brand = isset($data['brand']) ? trim($data['brand']) : '';
    $gas_type = isset($data['gas_type']) ? trim($data['gas_type']) : '';
    $size = isset($data['size']) ? trim($data['size']) : '';
    $import_date = isset($data['import_date']) && !empty($data['import_date']) ? $data['import_date'] : date('Y-m-d');

    if (empty($serial) || empty($brand) || empty($gas_type) || empty($size)) {
        echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบถ้วน"]);
        exit;
    }

    $next_inspection = date('Y-m-d', strtotime($import_date . ' + 5 years'));

    $stmt = $conn->prepare("INSERT INTO gas_cylinder (serial_number, brand, gas_type, size, status, current_location, import_date, next_inspection_date) VALUES (?, ?, ?, ?, 'ในคลัง', 'คลังแก๊ส', ?, ?)");
    $stmt->bind_param("ssssss", $serial, $brand, $gas_type, $size, $import_date, $next_inspection);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "ลงทะเบียนถังใหม่และนำเข้าคลังเรียบร้อยแล้ว"]);
    } else {
        echo json_encode(["success" => false, "message" => "ไม่สามารถเพิ่มข้อมูลได้: " . $conn->error]);
    }
    $stmt->close();
}

$conn->close();
?>