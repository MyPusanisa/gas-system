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

// 1. ดึงยี่ห้อ
$resBrands = mysqli_query($conn, "SELECT * FROM gas_brands ORDER BY id DESC");
$brands = array();
if ($resBrands) {
    while ($row = mysqli_fetch_assoc($resBrands)) { $brands[] = $row; }
}

// 2. ดึงชนิด
$resTypes = mysqli_query($conn, "SELECT * FROM gas_types ORDER BY id DESC");
$types = array();
if ($resTypes) {
    while ($row = mysqli_fetch_assoc($resTypes)) { $types[] = $row; }
}

// 3. ดึงขนาด
$resSizes = mysqli_query($conn, "SELECT * FROM gas_sizes ORDER BY id DESC");
$sizes = array();
if ($resSizes) {
    while ($row = mysqli_fetch_assoc($resSizes)) { $sizes[] = $row; }
}

// 4. ดึงสถานที่
$resLocations = mysqli_query($conn, "SELECT * FROM gas_locations ORDER BY id DESC");
$locations = array();
if ($resLocations) {
    while ($row = mysqli_fetch_assoc($resLocations)) { $locations[] = $row; }
}

echo json_encode([
    "success" => true,
    "brands" => $brands,
    "types" => $types,
    "sizes" => $sizes,
    "locations" => $locations
], JSON_UNESCAPED_UNICODE);

mysqli_close($conn);
?>