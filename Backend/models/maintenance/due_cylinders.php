<?php
require_once __DIR__ . '/../../config/db.php';

$sql = "SELECT cylinder_id, serial_number, brand, size, next_maintenance_date 
        FROM cylinders 
        WHERE DATE(next_maintenance_date) <= CURDATE() AND status != 'maintenance'
        ORDER BY next_maintenance_date ASC";

$result = $conn->query($sql);
$cylinders = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cylinders[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "data" => $cylinders
]);
?>