<?php
class Admin {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($username, $password, $name_admin, $role = 'staff') {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO admin (username, password, name_admin, role, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $username, $hashed_password, $name_admin, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT admin_id, username, name_admin, role, status, last_login, created_at, updated_at 
                FROM admin 
                ORDER BY admin_id DESC";
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
        $sql = "SELECT admin_id, username, name_admin, role, status, last_login, created_at, updated_at 
                FROM admin 
                WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByUsername($username) {
        $sql = "SELECT * FROM admin WHERE username = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function update($id, $username, $name_admin, $role = null) {
        if ($role) {
            $sql = "UPDATE admin SET 
                    username = ?, 
                    name_admin = ?, 
                    role = ?,
                    updated_at = NOW()
                    WHERE admin_id = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $username, $name_admin, $role, $id);
        } else {
            $sql = "UPDATE admin SET 
                    username = ?, 
                    name_admin = ?, 
                    updated_at = NOW()
                    WHERE admin_id = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $username, $name_admin, $id);
        }
        return mysqli_stmt_execute($stmt);
    }

    public function updatePassword($id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE admin SET password = ?, updated_at = NOW() WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function delete($id) {
        $sql = "DELETE FROM admin WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function verifyPassword($username, $password) {
        $sql = "SELECT admin_id, username, name_admin, role, status, password FROM admin WHERE username = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            if ($row['status'] != 'active') {
                return ['success' => false, 'message' => 'บัญชีนี้ถูกระงับการใช้งาน'];
            }
            
            if (password_verify($password, $row['password'])) {
                $this->updateLastLogin($row['admin_id']);
                unset($row['password']);
                return ['success' => true, 'data' => $row];
            }
        }
        return ['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
    }

    public function updateLastLogin($id) {
        $sql = "UPDATE admin SET last_login = NOW() WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function updateStatus($id, $status) {
        $valid_statuses = ['active', 'inactive', 'suspended'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $sql = "UPDATE admin SET status = ?, updated_at = NOW() WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function updateRole($id, $role) {
        $valid_roles = ['admin', 'manager', 'staff', 'viewer'];
        if (!in_array($role, $valid_roles)) {
            return false;
        }
        
        $sql = "UPDATE admin SET role = ?, updated_at = NOW() WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $role, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM admin";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countByRole($role) {
        $sql = "SELECT COUNT(*) as total FROM admin WHERE role = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $role);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countByStatus($status) {
        $sql = "SELECT COUNT(*) as total FROM admin WHERE status = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $status);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function search($keyword) {
        $sql = "SELECT admin_id, username, name_admin, role, status, last_login, created_at 
                FROM admin 
                WHERE username LIKE ? 
                OR name_admin LIKE ? 
                OR role LIKE ?
                ORDER BY admin_id DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        $keyword = "%{$keyword}%";
        mysqli_stmt_bind_param($stmt, "sss", $keyword, $keyword, $keyword);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_admins,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                    SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as manager_count,
                    SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff_count,
                    SUM(CASE WHEN role = 'viewer' THEN 1 ELSE 0 END) as viewer_count,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_count,
                    SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_this_week
                FROM admin";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function getRecentLogins($limit = 10) {
        $sql = "SELECT admin_id, username, name_admin, role, last_login 
                FROM admin 
                WHERE last_login IS NOT NULL 
                ORDER BY last_login DESC 
                LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getInactiveAdmins($days = 30) {
        $sql = "SELECT admin_id, username, name_admin, role, last_login 
                FROM admin 
                WHERE status = 'active' 
                AND (last_login IS NULL OR last_login <= DATE_SUB(NOW(), INTERVAL ? DAY))
                ORDER BY last_login ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $days);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function bulkDelete($ids) {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM admin WHERE admin_id IN ($placeholders)";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        return mysqli_stmt_execute($stmt);
    }

    public function bulkUpdateStatus($ids, $status) {
        if (empty($ids)) return false;
        $valid_statuses = ['active', 'inactive', 'suspended'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE admin SET status = ?, updated_at = NOW() WHERE admin_id IN ($placeholders)";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $types = 's' . str_repeat('i', count($ids));
        $params = array_merge([$status], $ids);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        return mysqli_stmt_execute($stmt);
    }

    public function exists($id) {
        $sql = "SELECT COUNT(*) as count FROM admin WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function existsByUsername($username, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM admin WHERE username = ?";
        if ($exclude_id) {
            $sql .= " AND admin_id != ?";
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

    public function getPaginated($limit, $offset, $search = null) {
        if ($search) {
            $sql = "SELECT admin_id, username, name_admin, role, status, last_login, created_at 
                    FROM admin 
                    WHERE username LIKE ? OR name_admin LIKE ? OR role LIKE ?
                    ORDER BY admin_id DESC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "sssii", $search_param, $search_param, $search_param, $limit, $offset);
        } else {
            $sql = "SELECT admin_id, username, name_admin, role, status, last_login, created_at 
                    FROM admin 
                    ORDER BY admin_id DESC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getTotalPages($perPage, $search = null) {
        if ($search) {
            $sql = "SELECT COUNT(*) as total FROM admin 
                    WHERE username LIKE ? OR name_admin LIKE ? OR role LIKE ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
        } else {
            $sql = "SELECT COUNT(*) as total FROM admin";
            $stmt = mysqli_prepare($this->conn, $sql);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return ceil($row['total'] / $perPage);
    }

    public function getByRole($role) {
        $sql = "SELECT admin_id, username, name_admin, status, last_login 
                FROM admin 
                WHERE role = ? 
                ORDER BY name_admin ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $role);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getActiveAdmins() {
        $sql = "SELECT admin_id, username, name_admin, role 
                FROM admin 
                WHERE status = 'active' 
                ORDER BY name_admin ASC";
        return mysqli_query($this->conn, $sql);
    }

    public function getAdminWorkload($admin_id = null) {
        if ($admin_id) {
            $sql = "SELECT 
                        a.admin_id,
                        a.username,
                        a.name_admin,
                        COUNT(DISTINCT m.maintenance_id) as total_maintenance,
                        COUNT(DISTINCT m.cylinder_id) as cylinders_serviced,
                        MAX(m.maintenance_date) as last_maintenance_date
                    FROM admin a
                    LEFT JOIN maintenance m ON a.admin_id = m.admin_id
                    WHERE a.admin_id = ?
                    GROUP BY a.admin_id";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $admin_id);
            mysqli_stmt_execute($stmt);
            return mysqli_stmt_get_result($stmt);
        } else {
            $sql = "SELECT 
                        a.admin_id,
                        a.username,
                        a.name_admin,
                        COUNT(DISTINCT m.maintenance_id) as total_maintenance,
                        COUNT(DISTINCT m.cylinder_id) as cylinders_serviced,
                        MAX(m.maintenance_date) as last_maintenance_date
                    FROM admin a
                    LEFT JOIN maintenance m ON a.admin_id = m.admin_id
                    GROUP BY a.admin_id
                    ORDER BY total_maintenance DESC";
            return mysqli_query($this->conn, $sql);
        }
    }

    public function changePassword($id, $old_password, $new_password) {
        $sql = "SELECT password FROM admin WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if (!$row || !password_verify($old_password, $row['password'])) {
            return ['success' => false, 'message' => 'รหัสผ่านเดิมไม่ถูกต้อง'];
        }
        
        $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE admin SET password = ?, updated_at = NOW() WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed_new, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'];
        }
        return ['success' => false, 'message' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้'];
    }

    public function resetPassword($id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE admin SET password = ?, updated_at = NOW() WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function getProfile($id) {
        $sql = "SELECT admin_id, username, name_admin, role, status, last_login, created_at, updated_at 
                FROM admin 
                WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function updateProfile($id, $name_admin) {
        $sql = "UPDATE admin SET name_admin = ?, updated_at = NOW() WHERE admin_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $name_admin, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function hasPermission($admin_id, $required_role) {
        $roles_priority = [
            'admin' => 4,
            'manager' => 3,
            'staff' => 2,
            'viewer' => 1
        ];
        
        $sql = "SELECT role FROM admin WHERE admin_id = ? AND status = 'active'";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if (!$row) {
            return false;
        }
        
        $user_role_level = $roles_priority[$row['role']] ?? 0;
        $required_level = $roles_priority[$required_role] ?? 0;
        
        return $user_role_level >= $required_level;
    }

    public function getActivityLog($admin_id = null, $limit = 50) {
        if ($admin_id) {
            $sql = "SELECT * FROM admin_activity_log 
                    WHERE admin_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $admin_id, $limit);
        } else {
            $sql = "SELECT l.*, a.username, a.name_admin 
                    FROM admin_activity_log l
                    LEFT JOIN admin a ON l.admin_id = a.admin_id
                    ORDER BY l.created_at DESC 
                    LIMIT ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $limit);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function logActivity($admin_id, $action, $description = null) {
        $sql = "INSERT INTO admin_activity_log (admin_id, action, description, ip_address, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "isss", $admin_id, $action, $description, $ip_address);
        return mysqli_stmt_execute($stmt);
    }

    public function getDashboardStats() {
        $stats = [
            'total_admins' => $this->countAll(),
            'active_admins' => $this->countByStatus('active'),
            'total_roles' => [
                'admin' => $this->countByRole('admin'),
                'manager' => $this->countByRole('manager'),
                'staff' => $this->countByRole('staff'),
                'viewer' => $this->countByRole('viewer')
            ],
            'recent_logins' => $this->getRecentLogins(5)
        ];
        return $stats;
    }

    public function validatePasswordStrength($password) {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีตัวพิมพ์เล็กอย่างน้อย 1 ตัว';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว';
        }
        if (!preg_match('/[\W_]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีอักขระพิเศษอย่างน้อย 1 ตัว';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>