<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/db.php";

try {
    $brandsQuery = $conn->query("SELECT id, brand_name FROM gas_brands ORDER BY id ASC");
    $brands = $brandsQuery ? $brandsQuery->fetch_all(MYSQLI_ASSOC) : [];

    $typesQuery = $conn->query("SELECT id, type_name FROM gas_types ORDER BY id ASC");
    $types = $typesQuery ? $typesQuery->fetch_all(MYSQLI_ASSOC) : [];

    $sizesQuery = $conn->query("SELECT id, size_name FROM gas_sizes ORDER BY id ASC");
    $sizes = $sizesQuery ? $sizesQuery->fetch_all(MYSQLI_ASSOC) : [];

    // ตัวอย่างใน get_options.php
    $locationsResult = $conn->query("SELECT * FROM gas_locations ORDER BY id DESC");
    $locations = $locationsResult ? $locationsResult->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode([
        "success" => true,
        "brands" => $brands,
        "types" => $types,
        "sizes" => $sizes,
        "locations" => $locations
        "statuses"  => $statuses
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>