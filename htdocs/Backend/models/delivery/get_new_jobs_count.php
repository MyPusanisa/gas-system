<?php
session_start();


require_once "../config/db.php";  // ให้ $conn เป็น MySQLi object

$method = $_SERVER['REQUEST_METHOD'];

// =====================================
// 1. POST: LOGIN (admin หรือ staff)
// =====================================
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    // --- ตรวจสอบ admin ---
    $sql = "SELECT * FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password == $row['password']) {
            $_SESSION['user_id'] = $row['admin_id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['username'] = $row['username'];
            $_SESSION['name'] = $row['name'];
            // ไม่จำเป็นต้องเก็บ staff_id สำหรับ admin

            echo json_encode([
                "success" => true,
                "role" => "admin",
                "admin_id" => $row["admin_id"],
                "username" => $row["username"],
                "name" => $row["name"]
            ]);
            exit;
        }
    }

    // --- ตรวจสอบ staff ---
    $sql = "SELECT * FROM delivery_staff WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password == $row['password']) {
            $_SESSION['user_id'] = $row['staff_id'];
            $_SESSION['role'] = 'staff';
            $_SESSION['username'] = $row['username'];
            $_SESSION['name'] = $row['staff_name'];
            $_SESSION['staff_id'] = $row['staff_id'];   // เพิ่มบรรทัดนี้

            echo json_encode([
                "success" => true,
                "role" => "staff",
                "staff_id" => $row["staff_id"],
                "username" => $row["username"],
                "name" => $row["staff_name"]
            ]);
            exit;
        }
    }

    // login ล้มเหลว
    echo json_encode(["success" => false, "message" => "Invalid username or password"]);
    exit;
}

// =====================================
// 2. GET: ดึงจำนวนงานของ staff ที่ login อยู่
// =====================================
if ($method === 'GET') {
    // ตรวจสอบว่าเป็น staff และมี session
    if (!isset($_SESSION['staff_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'count' => 0]);
        exit;
    }

    $staffId = $_SESSION['staff_id'];

    // นับงานที่ staff คนนี้รับผิดชอบ และยังไม่เสร็จ (ปรับเงื่อนไขตามต้องการ)
    $sql = "SELECT COUNT(*) as job_count 
            FROM delivery 
            WHERE staff_id = ? 
              AND status NOT IN ('completed', 'cancelled', 'rejected')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = (int)($row['job_count'] ?? 0);

    echo json_encode(['count' => $count]);
    exit;
}

// ถ้าใช้ method อื่น
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>