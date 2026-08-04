<?php
class Staff {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($staff_name, $staff_phone, $username, $password, $address, $status = 'active') {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO staff (staff_name, staff_phone, username, password, address, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $staff_name, $staff_phone, $username, $hashed_password, $address, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT staff_id, staff_name, staff_phone, username, address, status, created_at, updated_at, last_login 
                FROM staff 
                ORDER BY staff_id DESC";
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
        $sql = "SELECT staff_id, staff_name, staff_phone, username, address, status, created_at, updated_at, last_login 
                FROM staff 
                WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByUsername($username) {
        $sql = "SELECT * FROM staff WHERE username = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByPhone($phone) {
        $sql = "SELECT * FROM staff WHERE staff_phone = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $phone);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function update($id, $staff_name, $staff_phone, $address, $status = null) {
        if ($status !== null) {
            $sql = "UPDATE staff SET 
                    staff_name = ?, 
                    staff_phone = ?, 
                    address = ?,
                    status = ?,
                    updated_at = NOW()
                    WHERE staff_id = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssi", $staff_name, $staff_phone, $address, $status, $id);
        } else {
            $sql = "UPDATE staff SET 
                    staff_name = ?, 
                    staff_phone = ?, 
                    address = ?,
                    updated_at = NOW()
                    WHERE staff_id = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $staff_name, $staff_phone, $address, $id);
        }
        return mysqli_stmt_execute($stmt);
    }

    public function updatePassword($id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE staff SET password = ?, updated_at = NOW() WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function updateStatus($id, $status) {
        $valid_statuses = ['active', 'inactive', 'on_leave', 'terminated'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $sql = "UPDATE staff SET status = ?, updated_at = NOW() WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function delete($id) {
        $sql = "DELETE FROM staff WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function verifyPassword($username, $password) {
        $sql = "SELECT staff_id, staff_name, username, status, password FROM staff WHERE username = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            if ($row['status'] != 'active') {
                return ['success' => false, 'message' => 'บัญชีนี้ถูกระงับการใช้งาน'];
            }
            
            if (password_verify($password, $row['password'])) {
                $this->updateLastLogin($row['staff_id']);
                unset($row['password']);
                return ['success' => true, 'data' => $row];
            }
        }
        return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
    }

    public function updateLastLogin($id) {
        $sql = "UPDATE staff SET last_login = NOW() WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function search($keyword) {
        $sql = "SELECT staff_id, staff_name, staff_phone, username, address, status, last_login 
                FROM staff 
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
        $sql = "SELECT COUNT(*) as total FROM staff";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countByStatus($status) {
        $sql = "SELECT COUNT(*) as total FROM staff WHERE status = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $status);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_staff,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
                    SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as on_leave_count,
                    SUM(CASE WHEN status = 'terminated' THEN 1 ELSE 0 END) as terminated_count,
                    SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_this_week
                FROM staff";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function getActiveStaff() {
        $sql = "SELECT staff_id, staff_name, staff_phone FROM staff WHERE status = 'active' ORDER BY staff_name ASC";
        return mysqli_query($this->conn, $sql);
    }

    public function getRecentStaff($limit = 10) {
        $sql = "SELECT staff_id, staff_name, staff_phone, username, status, created_at 
                FROM staff 
                ORDER BY created_at DESC 
                LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function exists($id) {
        $sql = "SELECT COUNT(*) as count FROM staff WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function existsByUsername($username, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM staff WHERE username = ?";
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

    public function existsByPhone($phone, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM staff WHERE staff_phone = ?";
        if ($exclude_id) {
            $sql .= " AND staff_id != ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $phone, $exclude_id);
        } else {
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $phone);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function bulkInsert($data) {
        $sql = "INSERT INTO staff (staff_name, staff_phone, username, password, address, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        $success = 0;
        $failed = 0;
        
        foreach ($data as $row) {
            $hashed_password = password_hash($row['password'], PASSWORD_DEFAULT);
            $staff_name = $row['staff_name'];
            $staff_phone = $row['staff_phone'];
            $username = $row['username'];
            $address = $row['address'];
            $status = $row['status'] ?? 'active';
            
            mysqli_stmt_bind_param($stmt, "ssssss", $staff_name, $staff_phone, $username, $hashed_password, $address, $status);
            if (mysqli_stmt_execute($stmt)) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        return ['success' => $success, 'failed' => $failed];
    }

    public function bulkDelete($ids) {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM staff WHERE staff_id IN ($placeholders)";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        return mysqli_stmt_execute($stmt);
    }

    public function bulkUpdateStatus($ids, $status) {
        if (empty($ids)) return false;
        $valid_statuses = ['active', 'inactive', 'on_leave', 'terminated'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE staff SET status = ?, updated_at = NOW() WHERE staff_id IN ($placeholders)";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $types = 's' . str_repeat('i', count($ids));
        $params = array_merge([$status], $ids);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        return mysqli_stmt_execute($stmt);
    }

    public function getPaginated($limit, $offset, $search = null) {
        if ($search) {
            $sql = "SELECT staff_id, staff_name, staff_phone, username, address, status, last_login, created_at 
                    FROM staff 
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
            $sql = "SELECT staff_id, staff_name, staff_phone, username, address, status, last_login, created_at 
                    FROM staff 
                    ORDER BY staff_id DESC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getTotalPages($perPage, $search = null) {
        if ($search) {
            $sql = "SELECT COUNT(*) as total FROM staff 
                    WHERE staff_name LIKE ? 
                    OR staff_phone LIKE ? 
                    OR username LIKE ?
                    OR address LIKE ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "ssss", $search_param, $search_param, $search_param, $search_param);
        } else {
            $sql = "SELECT COUNT(*) as total FROM staff";
            $stmt = mysqli_prepare($this->conn, $sql);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return ceil($row['total'] / $perPage);
    }

    public function getInactiveStaff($days = 30) {
        $sql = "SELECT staff_id, staff_name, staff_phone, username, status, last_login 
                FROM staff 
                WHERE status = 'active' 
                AND (last_login IS NULL OR last_login <= DATE_SUB(NOW(), INTERVAL ? DAY))
                ORDER BY last_login ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $days);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function changePassword($id, $old_password, $new_password) {
        $sql = "SELECT password FROM staff WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if (!$row || !password_verify($old_password, $row['password'])) {
            return ['success' => false, 'message' => 'รหัสผ่านเดิมไม่ถูกต้อง'];
        }
        
        $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE staff SET password = ?, updated_at = NOW() WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed_new, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'];
        }
        return ['success' => false, 'message' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้'];
    }

    public function resetPassword($id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE staff SET password = ?, updated_at = NOW() WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'รีเซ็ตรหัสผ่านสำเร็จ'];
        }
        return ['success' => false, 'message' => 'ไม่สามารถรีเซ็ตรหัสผ่านได้'];
    }

    public function getProfile($id) {
        $sql = "SELECT staff_id, staff_name, staff_phone, username, address, status, last_login, created_at, updated_at 
                FROM staff 
                WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function updateProfile($id, $staff_name, $staff_phone, $address) {
        $sql = "UPDATE staff SET 
                staff_name = ?, 
                staff_phone = ?, 
                address = ?,
                updated_at = NOW()
                WHERE staff_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $staff_name, $staff_phone, $address, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function getStaffWorkload($staff_id = null) {
        if ($staff_id) {
            $sql = "SELECT 
                        s.staff_id,
                        s.staff_name,
                        COUNT(DISTINCT d.delivery_id) as total_deliveries,
                        COUNT(DISTINCT m.maintenance_id) as total_maintenance,
                        MAX(d.delivery_date) as last_delivery_date,
                        MAX(m.maintenance_date) as last_maintenance_date
                    FROM staff s
                    LEFT JOIN delivery d ON s.staff_id = d.staff_id
                    LEFT JOIN maintenance m ON s.staff_id = m.staff_id
                    WHERE s.staff_id = ?
                    GROUP BY s.staff_id";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $staff_id);
        } else {
            $sql = "SELECT 
                        s.staff_id,
                        s.staff_name,
                        COUNT(DISTINCT d.delivery_id) as total_deliveries,
                        COUNT(DISTINCT m.maintenance_id) as total_maintenance,
                        MAX(d.delivery_date) as last_delivery_date,
                        MAX(m.maintenance_date) as last_maintenance_date
                    FROM staff s
                    LEFT JOIN delivery d ON s.staff_id = d.staff_id
                    LEFT JOIN maintenance m ON s.staff_id = m.staff_id
                    GROUP BY s.staff_id
                    ORDER BY total_deliveries DESC";
            $stmt = mysqli_prepare($this->conn, $sql);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getStaffLogins($limit = 50) {
        $sql = "SELECT staff_id, staff_name, username, last_login 
                FROM staff 
                WHERE last_login IS NOT NULL 
                ORDER BY last_login DESC 
                LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function validatePasswordStrength($password) {
        $errors = [];
        if (strlen($password) < 6) {
            $errors[] = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function getStaffSummary() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive,
                    COUNT(CASE WHEN status = 'on_leave' THEN 1 END) as on_leave,
                    COUNT(CASE WHEN status = 'terminated' THEN 1 END) as terminated
                FROM staff";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function getStaffWithoutAdminAccount() {
        $sql = "SELECT s.* 
                FROM staff s
                LEFT JOIN admin a ON s.username = a.username
                WHERE a.admin_id IS NULL
                ORDER BY s.staff_name ASC";
        return mysqli_query($this->conn, $sql);
    }
}
?>