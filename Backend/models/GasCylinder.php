<?php
class GasCylinder {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($serial_number, $status, $size, $manufacture_date, $expiry_date, $qr_code) {
        $sql = "INSERT INTO gascylinder 
        (serial_number, status, size, manufacture_date, expiry_date, qr_code, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", 
            $serial_number, $status, $size, 
            $manufacture_date, $expiry_date, $qr_code
        );

        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM gascylinder ORDER BY cylinder_id DESC";
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
        $sql = "SELECT * FROM gascylinder WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function update($id, $serial_number, $status, $size, $manufacture_date, $expiry_date, $qr_code) {
        $sql = "UPDATE gascylinder SET 
            serial_number = ?, 
            status = ?, 
            size = ?, 
            manufacture_date = ?, 
            expiry_date = ?, 
            qr_code = ?,
            updated_at = NOW()
            WHERE cylinder_id = ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssi", 
            $serial_number, $status, $size, 
            $manufacture_date, $expiry_date, $qr_code, $id
        );

        return mysqli_stmt_execute($stmt);
    }

    public function delete($id) {
        $sql = "DELETE FROM gascylinder WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function getBySerialNumber($serial_number) {
        $sql = "SELECT * FROM gascylinder WHERE serial_number = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $serial_number);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByStatus($status) {
        $sql = "SELECT * FROM gascylinder WHERE status = ? ORDER BY cylinder_id DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $status);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getBySize($size) {
        $sql = "SELECT * FROM gascylinder WHERE size = ? ORDER BY cylinder_id DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $size);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getExpiredCylinders() {
        $sql = "SELECT * FROM gascylinder WHERE expiry_date < CURDATE() AND status != 'expired' ORDER BY expiry_date ASC";
        return mysqli_query($this->conn, $sql);
    }

    public function getExpiringSoon($days = 30) {
        $sql = "SELECT * FROM gascylinder 
                WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND status != 'expired'
                ORDER BY expiry_date ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $days);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getAvailableCylinders() {
        $sql = "SELECT * FROM gascylinder WHERE status = 'available' ORDER BY serial_number ASC";
        return mysqli_query($this->conn, $sql);
    }

    public function updateStatus($id, $status) {
        $sql = "UPDATE gascylinder SET status = ?, updated_at = NOW() WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM gascylinder";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countByStatus($status) {
        $sql = "SELECT COUNT(*) as total FROM gascylinder WHERE status = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $status);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countBySize($size) {
        $sql = "SELECT COUNT(*) as total FROM gascylinder WHERE size = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $size);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function search($keyword) {
        $sql = "SELECT * FROM gascylinder 
                WHERE serial_number LIKE ? 
                OR status LIKE ? 
                OR size LIKE ?
                ORDER BY cylinder_id DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        $keyword = "%{$keyword}%";
        mysqli_stmt_bind_param($stmt, "sss", $keyword, $keyword, $keyword);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_cylinders,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_count,
                    SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use_count,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_count,
                    SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired_actual,
                    SUM(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon,
                    AVG(DATEDIFF(expiry_date, manufacture_date)) as avg_life_span
                FROM gascylinder";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function bulkInsert($data) {
        $sql = "INSERT INTO gascylinder (serial_number, status, size, manufacture_date, expiry_date, qr_code, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        $success = 0;
        $failed = 0;
        
        foreach ($data as $row) {
            mysqli_stmt_bind_param($stmt, "ssssss", 
                $row['serial_number'], 
                $row['status'], 
                $row['size'], 
                $row['manufacture_date'], 
                $row['expiry_date'], 
                $row['qr_code']
            );
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
        $sql = "DELETE FROM gascylinder WHERE cylinder_id IN ($placeholders)";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        return mysqli_stmt_execute($stmt);
    }

    public function getByQrCode($qr_code) {
        $sql = "SELECT * FROM gascylinder WHERE qr_code = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $qr_code);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function updateQrCode($id, $qr_code) {
        $sql = "UPDATE gascylinder SET qr_code = ?, updated_at = NOW() WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $qr_code, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function getRecentCylinders($limit = 10) {
        $sql = "SELECT * FROM gascylinder ORDER BY created_at DESC LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getCylindersWithMaintenanceHistory($id) {
        $sql = "SELECT g.*, 
                       m.maintenance_id, m.maintenance_date, m.maintenance_type, 
                       m.result, m.description as maintenance_description,
                       m.next_maintenance_date
                FROM gascylinder g
                LEFT JOIN maintenance m ON g.cylinder_id = m.cylinder_id
                WHERE g.cylinder_id = ?
                ORDER BY m.maintenance_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function assignToCustomer($cylinder_id, $customer_id) {
        $sql = "UPDATE gascylinder SET status = 'in_use', assigned_to = ?, assigned_date = NOW(), updated_at = NOW() 
                WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $cylinder_id);
        return mysqli_stmt_execute($stmt);
    }

    public function returnFromCustomer($cylinder_id) {
        $sql = "UPDATE gascylinder SET status = 'available', assigned_to = NULL, assigned_date = NULL, updated_at = NOW() 
                WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cylinder_id);
        return mysqli_stmt_execute($stmt);
    }

    public function getCylindersByCustomer($customer_id) {
        $sql = "SELECT * FROM gascylinder WHERE assigned_to = ? ORDER BY assigned_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function autoUpdateExpiredStatus() {
        $sql = "UPDATE gascylinder SET status = 'expired', updated_at = NOW() 
                WHERE expiry_date < CURDATE() AND status != 'expired'";
        return mysqli_query($this->conn, $sql);
    }

    public function getDistinctSizes() {
        $sql = "SELECT DISTINCT size FROM gascylinder ORDER BY size";
        return mysqli_query($this->conn, $sql);
    }

    public function getMonthlyReport($year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');
        
        $sql = "SELECT 
                    COUNT(*) as total_added,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
                FROM gascylinder
                WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $year, $month);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function exists($id) {
        $sql = "SELECT COUNT(*) as count FROM gascylinder WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function existsBySerialNumber($serial_number, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM gascylinder WHERE serial_number = ?";
        if ($exclude_id) {
            $sql .= " AND cylinder_id != ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $serial_number, $exclude_id);
        } else {
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $serial_number);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function getPaginated($limit, $offset, $search = null) {
        if ($search) {
            $sql = "SELECT * FROM gascylinder 
                    WHERE serial_number LIKE ? OR status LIKE ? OR size LIKE ?
                    ORDER BY cylinder_id DESC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "sssii", $search_param, $search_param, $search_param, $limit, $offset);
        } else {
            $sql = "SELECT * FROM gascylinder ORDER BY cylinder_id DESC LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getTotalPages($perPage, $search = null) {
        if ($search) {
            $sql = "SELECT COUNT(*) as total FROM gascylinder 
                    WHERE serial_number LIKE ? OR status LIKE ? OR size LIKE ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
        } else {
            $sql = "SELECT COUNT(*) as total FROM gascylinder";
            $stmt = mysqli_prepare($this->conn, $sql);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return ceil($row['total'] / $perPage);
    }

    public function softDelete($id) {
        $sql = "UPDATE gascylinder SET status = 'deleted', deleted_at = NOW() WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function restore($id) {
        $sql = "UPDATE gascylinder SET status = 'available', deleted_at = NULL WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function getDeleted() {
        $sql = "SELECT * FROM gascylinder WHERE status = 'deleted' ORDER BY deleted_at DESC";
        return mysqli_query($this->conn, $sql);
    }

    public function getSummaryBySize() {
        $sql = "SELECT 
                    size,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
                FROM gascylinder
                GROUP BY size
                ORDER BY size";
        return mysqli_query($this->conn, $sql);
    }
}
?>