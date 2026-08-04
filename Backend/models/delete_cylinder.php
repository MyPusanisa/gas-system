<?php
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['cylinder_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing cylinder_id']);
    exit;
}

$cylinder_id = $data['cylinder_id'];
$stmt = $conn->prepare("DELETE FROM gas_cylinder WHERE cylinder_id = ?");
$stmt->bind_param("s", $cylinder_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>