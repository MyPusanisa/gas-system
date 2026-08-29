<?php
class Staff {
    private $conn;
    private $table = "delivery_staff"; // กำหนดชื่อตารางหลักให้ตรงกันทั้งระบบ

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($staff_name, $staff_phone, $username, $password, $address, $status = 'active') {
        $sql = "INSERT INTO {$this->table} (staff_name, staff_phone, username, password, address, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $staff_name, $staff_phone, $username, $password, $address, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT staff_id, staff_name, staff_phone, username, password, address, status 
                FROM {$this->table} 
                ORDER BY staff_id ASC";
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
            mysqli_stmt_execute($stmt);
            return mysqli_stmt_get_result($stmt);
        }
        return mysqli_query($this->conn, $sql);
    }

    public function getById($id) {
        $sql = "SELECT staff_id, staff_name, staff_phone, username, password, address, status 
                FROM {$this->table} 
                WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByPhone($phone) {
        $sql = "SELECT * FROM {$this->table} WHERE staff_phone = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $phone);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function update($id, $staff_name, $staff_phone, $address, $status = null, $password = null) {
        if ($password !== null && $password !== '') {
            if ($status !== null) {
                $sql = "UPDATE {$this->table} SET staff_name = ?, staff_phone = ?, password = ?, address = ?, status = ? WHERE staff_id = ?";
                $stmt = mysqli_prepare($this->conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssi", $staff_name, $staff_phone, $password, $address, $status, $id);
            } else {
                $sql = "UPDATE {$this->table} SET staff_name = ?, staff_phone = ?, password = ?, address = ? WHERE staff_id = ?";
                $stmt = mysqli_prepare($this->conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $staff_name, $staff_phone, $password, $address, $id);
            }
        } else {
            if ($status !== null) {
                $sql = "UPDATE {$this->table} SET staff_name = ?, staff_phone = ?, address = ?, status = ? WHERE staff_id = ?";
                $stmt = mysqli_prepare($this->conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $staff_name, $staff_phone, $address, $status, $id);
            } else {
                $sql = "UPDATE {$this->table} SET staff_name = ?, staff_phone = ?, address = ? WHERE staff_id = ?";
                $stmt = mysqli_prepare($this->conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssi", $staff_name, $staff_phone, $address, $id);
            }
        }
        return mysqli_stmt_execute($stmt);
    }

    public function updatePassword($id, $new_password) {
        $sql = "UPDATE {$this->table} SET password = ? WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $new_password, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function updateStatus($id, $status) {
        $valid_statuses = ['active', 'inactive', 'on_leave', 'terminated'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET status = ? WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function verifyPassword($username, $password) {
        $sql = "SELECT staff_id, staff_name, username, status, password FROM {$this->table} WHERE username = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            if ($row['status'] != 'active') {
                return ['success' => false, 'message' => 'บัญชีนี้ถูกระงับการใช้งาน'];
            }
            
            // ตรวจสอบทั้งแบบ Hash และ Plain Text เพื่อรองรับระบบเก่า/ใหม่
            $isValid = password_verify($password, $row['password']) || ($password === $row['password']);
            
            if ($isValid) {
                unset($row['password']);
                return ['success' => true, 'data' => $row];
            }
        }
        return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
    }

    public function search($keyword) {
        $sql = "SELECT staff_id, staff_name, staff_phone, username, password, address, status 
                FROM {$this->table} 
                WHERE staff_name LIKE ? 
                OR staff_phone LIKE ? 
                OR username LIKE ?
                OR address LIKE ?
                ORDER BY staff_name ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        $keyword = "%{$keyword}%";
        mysqli_stmt_bind_param($stmt, "ssss", $keyword, $keyword, $keyword, $keyword);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countByStatus($status) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $status);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function exists($id) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function existsByUsername($username, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = ?";
        if ($exclude_id) {
            $sql .= " AND staff_id != ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $username, $exclude_id);
        } else {
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $username);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function bulkDelete($ids) {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} WHERE staff_id IN ($placeholders)";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        return mysqli_stmt_execute($stmt);
    }

    public function getPaginated($limit, $offset, $search = null) {
        if ($search) {
            $sql = "SELECT staff_id, staff_name, staff_phone, username, password, address, status 
                    FROM {$this->table} 
                    WHERE staff_name LIKE ? 
                    OR staff_phone LIKE ? 
                    OR username LIKE ?
                    OR address LIKE ?
                    ORDER BY staff_id DESC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "ssssii", $search_param, $search_param, $search_param, $search_param, $limit, $offset);
        } else {
            $sql = "SELECT staff_id, staff_name, staff_phone, username, password, address, status 
                    FROM {$this->table} 
                    ORDER BY staff_id DESC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }
}
?>