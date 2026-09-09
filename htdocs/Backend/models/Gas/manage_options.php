<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/db.php";

mysqli_set_charset($conn, "utf8mb4");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['action']) || empty($data['target'])) {
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ครบถ้วน"], JSON_UNESCAPED_UNICODE);
    exit();
}

$action = $data['action'];
$target = $data['target'];

$targetMap = [
    'brand'    => ['table' => 'gas_brands', 'column' => 'brand_name'],
    'gas_type' => ['table' => 'gas_types',  'column' => 'type_name'],
    'gasType'  => ['table' => 'gas_types',  'column' => 'type_name'],
    'size'     => ['table' => 'gas_sizes',  'column' => 'size_name'],
    'location' => ['table' => 'gas_locations', 'column' => 'location_name'],
];

if (!isset($targetMap[$target])) {
    echo json_encode(["success" => false, "message" => "ประเภทข้อมูลไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
    exit();
}

$tableName = $targetMap[$target]['table'];
$columnName = $targetMap[$target]['column'];

if ($action === 'add' && !empty($data['value'])) {
    $val = mysqli_real_escape_string($conn, trim($data['value']));
    $sql = "INSERT INTO {$tableName} ({$columnName}) VALUES ('{$val}')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "เพิ่มรายการสำเร็จ"], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'delete' && !empty($data['id'])) {
    $id = intval($data['id']);
    $sql = "DELETE FROM {$tableName} WHERE id = {$id}";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "ลบรายการสำเร็จ"], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["success" => false, "message" => "คำสั่งไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
}

mysqli_close($conn);
?>