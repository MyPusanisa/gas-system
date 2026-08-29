<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once "../../config/db.php";

$method = $_SERVER['REQUEST_METHOD'];

// 1. ดึงรายการตัวเลือกทั้งหมด (GET Request)
if ($method === 'GET') {
    $brands = $conn->query("SELECT id, brand_name FROM gas_brands ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
    $types = $conn->query("SELECT id, type_name FROM gas_types ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
    $sizes = $conn->query("SELECT id, size_name FROM gas_sizes ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
    $locations = $conn->query("SELECT id, location_name FROM gas_locations ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
    $statuses = $conn->query("SELECT id, status_name FROM gas_statuses ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => [
            "brands" => $brands,
            "types" => $types,
            "sizes" => $sizes,
            "locations" => $locations,
            "statuses" => $statuses
        ]
    ]);
    exit;
}

// 2. จัดการ เพิ่ม/ลบ ตัวเลือก (POST Request)
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $action = $data['action'] ?? '';
    $target = $data['target'] ?? '';

    $tableMap = [
        'brand'    => ['table' => 'gas_brands',    'column' => 'brand_name',    'id' => 'id'],
        'type'     => ['table' => 'gas_types',     'column' => 'type_name',     'id' => 'id'],
        'size'     => ['table' => 'gas_sizes',     'column' => 'size_name',     'id' => 'id'],
        'location' => ['table' => 'gas_locations', 'column' => 'location_name', 'id' => 'id'],
        'status'   => ['table' => 'gas_statuses',  'column' => 'status_name',   'id' => 'id']
    ];

    if (!isset($tableMap[$target])) {
        echo json_encode(["success" => false, "message" => "Target ไม่ถูกต้อง"]);
        exit;
    }

    $config = $tableMap[$target];
    $table = $config['table'];
    $colName = $config['column'];
    $idCol = $config['id'];

    if ($action === 'add') {
        $value = trim($data['value'] ?? '');
        if (empty($value)) {
            echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูล"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO $table ($colName) VALUES (?)");
        $stmt->bind_param("s", $value);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "เพิ่มข้อมูลเรียบร้อย"]);
        } else {
            echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการเพิ่ม"]);
        }

    } elseif ($action === 'delete') {
        $id = $data['id'] ?? 0;
        if (!$id) {
            echo json_encode(["success" => false, "message" => "ID ไม่ถูกต้อง"]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM $table WHERE $idCol = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "ลบข้อมูลเรียบร้อย"]);
        } else {
            echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการลบ"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Action ไม่ถูกต้อง"]);
    }
    exit;
}
?>