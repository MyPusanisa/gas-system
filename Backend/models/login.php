<?php

include_once "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$username = $data["username"] ?? '';
$password = $data["password"] ?? '';


// =====================================
// ตรวจ admin
// =====================================
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

        echo json_encode([
            "success" => true,
            "role" => "admin",

            "admin_id" => $row["admin_id"],
            "username" => $row["username"],
            "name" => $row["name"]
        ]);

        exit();
    }
}


// =====================================
// ตรวจ staff
// =====================================
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

        echo json_encode([
            "success" => true,
            "role" => "staff",

            "staff_id" => $row["staff_id"],
            "username" => $row["username"],
            "name" => $row["staff_name"]
        ]);

        exit();
    }
}


// =====================================
// login ไม่สำเร็จ
// =====================================
echo json_encode([
    "success" => false,
    "message" => "Invalid username or password"
]);