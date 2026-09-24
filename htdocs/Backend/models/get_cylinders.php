<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/db.php";

// ค้นหาถังที่มีสถานะ 'ในคลัง'
$sql = "SELECT * FROM gas_cylinder WHERE TRIM(status) = 'ในคลัง' ORDER BY serial_number DESC";
$result = mysqli_query($conn, $sql);

$data = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(["success" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["success" => false, "message" => mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
}

mysqli_close($conn);
?>