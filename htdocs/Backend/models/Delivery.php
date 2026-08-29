<?php
class Delivery {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($delivery_date, $cylinder_id, $customer_id, $admin_id, $return_date = null, $notes = null) {
        $sql = "INSERT INTO delivery (delivery_date, return_date, cylinder_id, customer_id, admin_id, notes, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'delivered', NOW(), NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssiisss", $delivery_date, $return_date, $cylinder_id, $customer_id, $admin_id, $notes);
        
        if (mysqli_stmt_execute($stmt)) {
            $delivery_id = mysqli_insert_id($this->conn);
            $this->updateCylinderStatus($cylinder_id, 'delivered');
            return $delivery_id;
        }
        return false;
    }

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial, 
                       c.size as cylinder_size,
                       cust.name as customer_name,
                       cust.phone as customer_phone,
                       a.username as admin_username,
                       a.name_admin as admin_name
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                LEFT JOIN admin a ON d.admin_id = a.admin_id
                ORDER BY d.delivery_date DESC";
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
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial, 
                       c.size as cylinder_size,
                       c.status as cylinder_status,
                       cust.name as customer_name,
                       cust.phone as customer_phone,
                       cust.address as customer_address,
                       a.username as admin_username,
                       a.name_admin as admin_name
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                LEFT JOIN admin a ON d.admin_id = a.admin_id
                WHERE d.delivery_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function update($id, $delivery_date, $return_date = null, $notes = null) {
        $sql = "UPDATE delivery SET 
                delivery_date = ?, 
                return_date = ?,
                notes = ?,
                updated_at = NOW()
                WHERE delivery_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $delivery_date, $return_date, $notes, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function delete($id) {
        $delivery = $this->getById($id);
        $row = mysqli_fetch_assoc($delivery);
        $cylinder_id = $row['cylinder_id'];
        
        $sql = "DELETE FROM delivery WHERE delivery_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $this->updateCylinderStatus($cylinder_id, 'available');
            return true;
        }
        return false;
    }

    public function returnCylinder($id, $return_date = null) {
        if (!$return_date) {
            $return_date = date('Y-m-d H:i:s');
        }
        
        $sql = "UPDATE delivery SET 
                return_date = ?,
                status = 'returned',
                updated_at = NOW()
                WHERE delivery_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $return_date, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $delivery = $this->getById($id);
            $row = mysqli_fetch_assoc($delivery);
            $this->updateCylinderStatus($row['cylinder_id'], 'available');
            return true;
        }
        return false;
    }

    public function getActiveDeliveries() {
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial,
                       cust.name as customer_name,
                       cust.phone as customer_phone,
                       DATEDIFF(NOW(), d.delivery_date) as days_out
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                WHERE d.return_date IS NULL OR d.status = 'delivered'
                ORDER BY d.delivery_date ASC";
        return mysqli_query($this->conn, $sql);
    }

    public function getReturnedDeliveries($limit = null) {
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial,
                       cust.name as customer_name,
                       DATEDIFF(d.return_date, d.delivery_date) as rental_days
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                WHERE d.return_date IS NOT NULL AND d.status = 'returned'
                ORDER BY d.return_date DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        return mysqli_query($this->conn, $sql);
    }

    public function getOverdueDeliveries($overdue_days = 30) {
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial,
                       cust.name as customer_name,
                       cust.phone as customer_phone,
                       DATEDIFF(NOW(), d.delivery_date) as days_out
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                WHERE (d.return_date IS NULL OR d.status = 'delivered')
                AND d.delivery_date <= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY d.delivery_date ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $overdue_days);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByCustomer($customer_id) {
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial,
                       c.size as cylinder_size
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                WHERE d.customer_id = ?
                ORDER BY d.delivery_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByCylinder($cylinder_id) {
        $sql = "SELECT d.*, 
                       cust.name as customer_name
                FROM delivery d
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                WHERE d.cylinder_id = ?
                ORDER BY d.delivery_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cylinder_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByAdmin($admin_id) {
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial,
                       cust.name as customer_name
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                WHERE d.admin_id = ?
                ORDER BY d.delivery_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByDateRange($start_date, $end_date) {
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial,
                       cust.name as customer_name,
                       a.name_admin as admin_name
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                LEFT JOIN admin a ON d.admin_id = a.admin_id
                WHERE DATE(d.delivery_date) BETWEEN ? AND ?
                ORDER BY d.delivery_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM delivery";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countActive() {
        $sql = "SELECT COUNT(*) as total FROM delivery WHERE return_date IS NULL OR status = 'delivered'";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countReturned() {
        $sql = "SELECT COUNT(*) as total FROM delivery WHERE return_date IS NOT NULL AND status = 'returned'";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_deliveries,
                    SUM(CASE WHEN return_date IS NULL OR status = 'delivered' THEN 1 ELSE 0 END) as active_deliveries,
                    SUM(CASE WHEN return_date IS NOT NULL AND status = 'returned' THEN 1 ELSE 0 END) as returned_deliveries,
                    AVG(CASE WHEN return_date IS NOT NULL THEN DATEDIFF(return_date, delivery_date) ELSE 0 END) as avg_rental_days,
                    SUM(CASE WHEN delivery_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as last_30_days,
                    COUNT(DISTINCT cylinder_id) as unique_cylinders_used,
                    COUNT(DISTINCT customer_id) as unique_customers
                FROM delivery";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function search($keyword) {
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial,
                       cust.name as customer_name,
                       cust.phone as customer_phone
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                WHERE c.serial_number LIKE ? 
                OR cust.name LIKE ? 
                OR cust.phone LIKE ?
                ORDER BY d.delivery_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        $keyword = "%{$keyword}%";
        mysqli_stmt_bind_param($stmt, "sss", $keyword, $keyword, $keyword);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function updateCylinderStatus($cylinder_id, $status) {
        $sql = "UPDATE gascylinder SET status = ?, updated_at = NOW() WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $cylinder_id);
        return mysqli_stmt_execute($stmt);
    }

    public function getDeliveryReport($start_date, $end_date) {
        $sql = "SELECT 
                    DATE(d.delivery_date) as date,
                    COUNT(*) as total_deliveries,
                    COUNT(CASE WHEN d.return_date IS NOT NULL THEN 1 END) as returns,
                    COUNT(DISTINCT d.customer_id) as unique_customers,
                    COUNT(DISTINCT d.cylinder_id) as unique_cylinders
                FROM delivery d
                WHERE DATE(d.delivery_date) BETWEEN ? AND ?
                GROUP BY DATE(d.delivery_date)
                ORDER BY date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getCustomerDeliverySummary($customer_id) {
        $sql = "SELECT 
                    COUNT(*) as total_deliveries,
                    COUNT(CASE WHEN return_date IS NULL THEN 1 END) as active_deliveries,
                    COUNT(CASE WHEN return_date IS NOT NULL THEN 1 END) as completed_deliveries,
                    AVG(CASE WHEN return_date IS NOT NULL THEN DATEDIFF(return_date, delivery_date) ELSE 0 END) as avg_rental_days,
                    MAX(delivery_date) as last_delivery_date
                FROM delivery
                WHERE customer_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getCylinderDeliveryHistory($cylinder_id) {
        $sql = "SELECT d.*, cust.name as customer_name
                FROM delivery d
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                WHERE d.cylinder_id = ?
                ORDER BY d.delivery_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cylinder_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function bulkInsert($data) {
        $sql = "INSERT INTO delivery (delivery_date, return_date, cylinder_id, customer_id, admin_id, notes, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'delivered', NOW(), NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        $success = 0;
        $failed = 0;
        
        foreach ($data as $row) {
            $delivery_date = $row['delivery_date'];
            $return_date = $row['return_date'] ?? null;
            $cylinder_id = $row['cylinder_id'];
            $customer_id = $row['customer_id'];
            $admin_id = $row['admin_id'];
            $notes = $row['notes'] ?? null;
            
            mysqli_stmt_bind_param($stmt, "ssiiss", $delivery_date, $return_date, $cylinder_id, $customer_id, $admin_id, $notes);
            if (mysqli_stmt_execute($stmt)) {
                $success++;
                $this->updateCylinderStatus($cylinder_id, 'delivered');
            } else {
                $failed++;
            }
        }
        
        return ['success' => $success, 'failed' => $failed];
    }

    public function bulkDelete($ids) {
        if (empty($ids)) return false;
        
        foreach ($ids as $id) {
            $delivery = $this->getById($id);
            $row = mysqli_fetch_assoc($delivery);
            if ($row) {
                $this->updateCylinderStatus($row['cylinder_id'], 'available');
            }
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM delivery WHERE delivery_id IN ($placeholders)";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        return mysqli_stmt_execute($stmt);
    }

    public function getPaginated($limit, $offset, $search = null) {
        if ($search) {
            $sql = "SELECT d.*, 
                           c.serial_number as cylinder_serial,
                           cust.name as customer_name,
                           a.name_admin as admin_name
                    FROM delivery d
                    LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                    LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                    LEFT JOIN admin a ON d.admin_id = a.admin_id
                    WHERE c.serial_number LIKE ? 
                    OR cust.name LIKE ? 
                    OR cust.phone LIKE ?
                    ORDER BY d.delivery_date DESC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "sssii", $search_param, $search_param, $search_param, $limit, $offset);
        } else {
            $sql = "SELECT d.*, 
                           c.serial_number as cylinder_serial,
                           cust.name as customer_name,
                           a.name_admin as admin_name
                    FROM delivery d
                    LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                    LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                    LEFT JOIN admin a ON d.admin_id = a.admin_id
                    ORDER BY d.delivery_date DESC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getTotalPages($perPage, $search = null) {
        if ($search) {
            $sql = "SELECT COUNT(*) as total FROM delivery d
                    LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                    LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                    WHERE c.serial_number LIKE ? 
                    OR cust.name LIKE ? 
                    OR cust.phone LIKE ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
        } else {
            $sql = "SELECT COUNT(*) as total FROM delivery";
            $stmt = mysqli_prepare($this->conn, $sql);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return ceil($row['total'] / $perPage);
    }

    public function getMonthlyReport($year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');
        
        $sql = "SELECT 
                    COUNT(*) as total_deliveries,
                    COUNT(CASE WHEN return_date IS NOT NULL THEN 1 END) as total_returns,
                    SUM(CASE WHEN return_date IS NOT NULL THEN DATEDIFF(return_date, delivery_date) ELSE 0 END) as total_rental_days
                FROM delivery
                WHERE YEAR(delivery_date) = ? AND MONTH(delivery_date) = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $year, $month);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getTopCustomers($limit = 10) {
        $sql = "SELECT 
                    cust.customer_id,
                    cust.name,
                    cust.phone,
                    COUNT(d.delivery_id) as total_deliveries,
                    COUNT(CASE WHEN d.return_date IS NULL THEN 1 END) as active_deliveries,
                    MAX(d.delivery_date) as last_delivery
                FROM customer cust
                INNER JOIN delivery d ON cust.customer_id = d.customer_id
                GROUP BY cust.customer_id
                ORDER BY total_deliveries DESC
                LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getTopCylinders($limit = 10) {
        $sql = "SELECT 
                    c.cylinder_id,
                    c.serial_number,
                    c.size,
                    COUNT(d.delivery_id) as total_deliveries,
                    SUM(CASE WHEN d.return_date IS NULL THEN 1 ELSE 0 END) as current_out
                FROM gascylinder c
                INNER JOIN delivery d ON c.cylinder_id = d.cylinder_id
                GROUP BY c.cylinder_id
                ORDER BY total_deliveries DESC
                LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function exists($id) {
        $sql = "SELECT COUNT(*) as count FROM delivery WHERE delivery_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function isCylinderAvailableForDelivery($cylinder_id) {
        $sql = "SELECT COUNT(*) as count FROM delivery 
                WHERE cylinder_id = ? AND (return_date IS NULL OR status = 'delivered')";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cylinder_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] == 0;
    }

    public function getCurrentDeliveryByCylinder($cylinder_id) {
        $sql = "SELECT d.*, cust.name as customer_name, cust.phone
                FROM delivery d
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                WHERE d.cylinder_id = ? AND (d.return_date IS NULL OR d.status = 'delivered')
                ORDER BY d.delivery_date DESC
                LIMIT 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cylinder_id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function extendReturnDate($id, $new_return_date) {
        $sql = "UPDATE delivery SET 
                return_date = ?,
                notes = CONCAT(IFNULL(notes, ''), ' Extended to ', ?),
                updated_at = NOW()
                WHERE delivery_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $new_return_date, $new_return_date, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function getDeliverySummaryByAdmin($admin_id = null) {
        if ($admin_id) {
            $sql = "SELECT 
                        a.admin_id,
                        a.name_admin,
                        COUNT(d.delivery_id) as total_deliveries,
                        COUNT(CASE WHEN d.return_date IS NOT NULL THEN 1 END) as completed_returns,
                        COUNT(CASE WHEN d.return_date IS NULL THEN 1 END) as active_deliveries
                    FROM admin a
                    LEFT JOIN delivery d ON a.admin_id = d.admin_id
                    WHERE a.admin_id = ?
                    GROUP BY a.admin_id";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $admin_id);
        } else {
            $sql = "SELECT 
                        a.admin_id,
                        a.name_admin,
                        COUNT(d.delivery_id) as total_deliveries,
                        COUNT(CASE WHEN d.return_date IS NOT NULL THEN 1 END) as completed_returns,
                        COUNT(CASE WHEN d.return_date IS NULL THEN 1 END) as active_deliveries
                    FROM admin a
                    LEFT JOIN delivery d ON a.admin_id = d.admin_id
                    GROUP BY a.admin_id
                    ORDER BY total_deliveries DESC";
            $stmt = mysqli_prepare($this->conn, $sql);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getLatestDeliveries($limit = 10) {
        $sql = "SELECT d.*, 
                       c.serial_number as cylinder_serial,
                       cust.name as customer_name,
                       a.name_admin as admin_name
                FROM delivery d
                LEFT JOIN gascylinder c ON d.cylinder_id = c.cylinder_id
                LEFT JOIN customer cust ON d.customer_id = cust.customer_id
                LEFT JOIN admin a ON d.admin_id = a.admin_id
                ORDER BY d.created_at DESC
                LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function validateDelivery($cylinder_id, $customer_id) {
        $errors = [];
        
        if (!$this->isCylinderAvailableForDelivery($cylinder_id)) {
            $errors[] = "ถังแก๊สนี้กำลังถูกส่งมอบให้ลูกค้ารายอื่นอยู่";
        }
        
        $sql = "SELECT status FROM gascylinder WHERE cylinder_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $cylinder_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $cylinder = mysqli_fetch_assoc($result);
        
        if ($cylinder && $cylinder['status'] == 'expired') {
            $errors[] = "ถังแก๊สนี้หมดอายุ ไม่สามารถส่งมอบได้";
        }
        
        if ($cylinder && $cylinder['status'] == 'maintenance') {
            $errors[] = "ถังแก๊สนี้กำลังอยู่ในระหว่างการซ่อมบำรุง";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>