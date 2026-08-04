<?php
// ใส่ path ให้ถูกต้องตามโครงสร้างโปรเจคของคุณ
require_once "../../config/db.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // เปลี่ยนเป็นโดเมนจริงเมื่อขึ้น production
header('Access-Control-Allow-Methods: GET');

// Optional: ตรวจสอบสิทธิ์ staff (ถ้ามี session)
// session_start();
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

try {
    // สมมติว่า db.php สร้างการเชื่อมต่อแบบ PDO ในตัวแปร $pdo
    // ถ้าใช้ mysqli ให้ปรับตาม
    global $pdo;
    
    // ปรับเงื่อนไข WHERE ให้ตรงกับนิยาม "งานเข้าใหม่" ของคุณ
    // ตัวอย่าง: status = 'pending' หรือ 'new' หรือ created_at > วันที่กำหนด
    $sql = "SELECT COUNT(*) as new_jobs_count FROM delivery WHERE status = 'pending'";
    
    // ถ้าต้องการนับเฉพาะงานที่ assign ให้ staff คนนั้น
    // if (isset($_SESSION['staff_id'])) {
    //     $sql .= " AND assigned_to = :staff_id";
    //     $stmt = $pdo->prepare($sql);
    //     $stmt->execute([':staff_id' => $_SESSION['staff_id']]);
    // } else {
    //     $stmt = $pdo->query($sql);
    // }
    
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = (int)($result['new_jobs_count'] ?? 0);
    
    echo json_encode(['count' => $count]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>