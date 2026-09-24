<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/db.php";

$input = json_decode(file_get_contents("php://input"), true) ?: [];

$serials = $input['serial_numbers'] ?? [];
if (!is_array($serials)) $serials = [$serials];
$serials = array_values(array_filter(array_map('trim', $serials), 'strlen'));

$type        = trim($input['maintenance_type'] ?? '');
$result      = trim($input['result'] ?? '');
$next_action = trim($input['next_action'] ?? '');
$description = trim($input['description'] ?? '');

if (empty($serials) || $type === '' || $result === '') {
    echo json_encode(["success" => false, "message" => "กรุณาเลือกถัง ประเภทการตรวจ และผลการตรวจ"], JSON_UNESCAPED_UNICODE);
    exit();
}

$today = date("Y-m-d");
$next_date = date("Y-m-d", strtotime("+1 year"));
$passed = ($result === "ผ่าน");

$conn->begin_transaction();
try {
    $ins = $conn->prepare(
        "INSERT INTO maintenance (serial_number, cylinder_id, maintenance_date, maintenance_type, result, next_action, next_maintenance_date, description)
         VALUES (?, (SELECT cylinder_id FROM gas_cylinder WHERE serial_number = ? LIMIT 1), ?, ?, ?, ?, ?, ?)"
    );
    // ตรวจผ่าน: เลื่อนวันตรวจครั้งถัดไปออกไป 1 ปี ถังจะหลุดจากรายการเลยกำหนด
    $upd = $conn->prepare("UPDATE gas_cylinder SET last_check_date = ?, next_check_date = ? WHERE serial_number = ?");

    $nextForRecord = $passed ? $next_date : null;
    foreach ($serials as $sn) {
        $ins->bind_param("ssssssss", $sn, $sn, $today, $type, $result, $next_action, $nextForRecord, $description);
        if (!$ins->execute()) throw new Exception($ins->error);

        if ($passed) {
            $upd->bind_param("sss", $today, $next_date, $sn);
            if (!$upd->execute()) throw new Exception($upd->error);
        }
    }
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "บันทึกผลตรวจ " . count($serials) . " รายการสำเร็จ"
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "บันทึกไม่สำเร็จ: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
$conn->close();
