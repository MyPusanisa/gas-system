<?php
class Customer {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($name, $phone, $address, $email = null, $tax_id = null) {
        $sql = "INSERT INTO customer (name, phone, address, email, tax_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $name, $phone, $address, $email, $tax_id);
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT g.cylinder_id) as total_cylinders,
                       MAX(g.assigned_date) as last_assigned_date
                FROM customer c
                LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to
                GROUP BY c.customer_id
                ORDER BY c.customer_id DESC";
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
        $sql = "SELECT c.*, 
                       COUNT(DISTINCT g.cylinder_id) as total_cylinders,
                       GROUP_CONCAT(g.serial_number SEPARATOR ', ') as cylinder_serials
                FROM customer c
                LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to
                WHERE c.customer_id = ?
                GROUP BY c.customer_id";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByPhone($phone) {
        $sql = "SELECT * FROM customer WHERE phone = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $phone);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getByEmail($email) {
        $sql = "SELECT * FROM customer WHERE email = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function update($id, $name, $phone, $address, $email = null, $tax_id = null) {
        $sql = "UPDATE customer SET 
                name = ?, 
                phone = ?, 
                address = ?, 
                email = ?,
                tax_id = ?,
                updated_at = NOW()
                WHERE customer_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $name, $phone, $address, $email, $tax_id, $id);
        return mysqli_stmt_execute($stmt);
    }

    public function delete($id) {
        $sql = "DELETE FROM customer WHERE customer_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function search($keyword) {
        $sql = "SELECT c.*, 
                       COUNT(g.cylinder_id) as total_cylinders
                FROM customer c
                LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to
                WHERE c.name LIKE ? 
                OR c.phone LIKE ? 
                OR c.address LIKE ?
                OR c.email LIKE ?
                OR c.tax_id LIKE ?
                GROUP BY c.customer_id
                ORDER BY c.name ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        $keyword = "%{$keyword}%";
        mysqli_stmt_bind_param($stmt, "sssss", $keyword, $keyword, $keyword, $keyword, $keyword);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM customer";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countActive() {
        $sql = "SELECT COUNT(DISTINCT c.customer_id) as total 
                FROM customer c
                INNER JOIN gascylinder g ON c.customer_id = g.assigned_to
                WHERE g.status = 'in_use'";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN email IS NOT NULL THEN 1 END) as has_email,
                    COUNT(CASE WHEN tax_id IS NOT NULL THEN 1 END) as has_tax_id,
                    COUNT(DISTINCT g.cylinder_id) as total_cylinders_rented,
                    AVG(cyl_counts.cylinder_count) as avg_cylinders_per_customer
                FROM customer c
                LEFT JOIN (
                    SELECT assigned_to, COUNT(*) as cylinder_count
                    FROM gascylinder
                    WHERE status = 'in_use'
                    GROUP BY assigned_to
                ) cyl_counts ON c.customer_id = cyl_counts.assigned_to";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function getCustomersWithCylinders() {
        $sql = "SELECT c.*, 
                       COUNT(g.cylinder_id) as cylinder_count,
                       GROUP_CONCAT(g.serial_number SEPARATOR ', ') as cylinders
                FROM customer c
                INNER JOIN gascylinder g ON c.customer_id = g.assigned_to
                WHERE g.status = 'in_use'
                GROUP BY c.customer_id
                ORDER BY cylinder_count DESC";
        return mysqli_query($this->conn, $sql);
    }

    public function getCustomersWithoutCylinders() {
        $sql = "SELECT c.* 
                FROM customer c
                LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to AND g.status = 'in_use'
                WHERE g.cylinder_id IS NULL
                ORDER BY c.name ASC";
        return mysqli_query($this->conn, $sql);
    }

    public function getCustomerHistory($id) {
        $sql = "SELECT 
                    g.cylinder_id,
                    g.serial_number,
                    g.size,
                    g.assigned_date,
                    g.status as cylinder_status,
                    m.maintenance_id,
                    m.maintenance_date,
                    m.maintenance_type,
                    m.result
                FROM customer c
                LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to
                LEFT JOIN maintenance m ON g.cylinder_id = m.cylinder_id
                WHERE c.customer_id = ?
                ORDER BY g.assigned_date DESC, m.maintenance_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getTopCustomers($limit = 10) {
        $sql = "SELECT c.*, 
                       COUNT(g.cylinder_id) as cylinder_count,
                       MAX(g.assigned_date) as last_assigned
                FROM customer c
                INNER JOIN gascylinder g ON c.customer_id = g.assigned_to
                WHERE g.status = 'in_use'
                GROUP BY c.customer_id
                ORDER BY cylinder_count DESC
                LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function exists($id) {
        $sql = "SELECT COUNT(*) as count FROM customer WHERE customer_id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function existsByPhone($phone, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM customer WHERE phone = ?";
        if ($exclude_id) {
            $sql .= " AND customer_id != ?";
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

    public function existsByEmail($email, $exclude_id = null) {
        if (!$email) return false;
        $sql = "SELECT COUNT(*) as count FROM customer WHERE email = ?";
        if ($exclude_id) {
            $sql .= " AND customer_id != ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $email, $exclude_id);
        } else {
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function bulkInsert($data) {
        $sql = "INSERT INTO customer (name, phone, address, email, tax_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        $success = 0;
        $failed = 0;
        
        foreach ($data as $row) {
            $name = $row['name'];
            $phone = $row['phone'];
            $address = $row['address'];
            $email = $row['email'] ?? null;
            $tax_id = $row['tax_id'] ?? null;
            
            mysqli_stmt_bind_param($stmt, "sssss", $name, $phone, $address, $email, $tax_id);
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
        $sql = "DELETE FROM customer WHERE customer_id IN ($placeholders)";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        return mysqli_stmt_execute($stmt);
    }

    public function getPaginated($limit, $offset, $search = null) {
        if ($search) {
            $sql = "SELECT c.*, 
                           COUNT(g.cylinder_id) as total_cylinders
                    FROM customer c
                    LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to AND g.status = 'in_use'
                    WHERE c.name LIKE ? 
                    OR c.phone LIKE ? 
                    OR c.address LIKE ?
                    OR c.email LIKE ?
                    GROUP BY c.customer_id
                    ORDER BY c.name ASC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "ssssii", $search_param, $search_param, $search_param, $search_param, $limit, $offset);
        } else {
            $sql = "SELECT c.*, 
                           COUNT(g.cylinder_id) as total_cylinders
                    FROM customer c
                    LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to AND g.status = 'in_use'
                    GROUP BY c.customer_id
                    ORDER BY c.customer_id DESC 
                    LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getTotalPages($perPage, $search = null) {
        if ($search) {
            $sql = "SELECT COUNT(*) as total FROM customer 
                    WHERE name LIKE ? OR phone LIKE ? OR address LIKE ? OR email LIKE ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            $search_param = "%{$search}%";
            mysqli_stmt_bind_param($stmt, "ssss", $search_param, $search_param, $search_param, $search_param);
        } else {
            $sql = "SELECT COUNT(*) as total FROM customer";
            $stmt = mysqli_prepare($this->conn, $sql);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return ceil($row['total'] / $perPage);
    }

    public function getRecentCustomers($limit = 10) {
        $sql = "SELECT c.*, 
                       COUNT(g.cylinder_id) as total_cylinders
                FROM customer c
                LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to
                GROUP BY c.customer_id
                ORDER BY c.created_at DESC 
                LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function getCustomerSummary() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN g.cylinder_id IS NOT NULL THEN 1 END) as has_cylinders,
                    COUNT(CASE WHEN g.cylinder_id IS NULL THEN 1 END) as no_cylinders,
                    SUM(CASE WHEN g.status = 'in_use' THEN 1 ELSE 0 END) as active_rentals
                FROM customer c
                LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to AND g.status = 'in_use'";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function updateCustomerInfo($id, $data) {
        $fields = [];
        $params = [];
        $types = "";
        
        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
            $types .= "s";
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'];
            $types .= "s";
        }
        if (isset($data['address'])) {
            $fields[] = "address = ?";
            $params[] = $data['address'];
            $types .= "s";
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
            $types .= "s";
        }
        if (isset($data['tax_id'])) {
            $fields[] = "tax_id = ?";
            $params[] = $data['tax_id'];
            $types .= "s";
        }
        
        if (empty($fields)) return false;
        
        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE customer SET " . implode(", ", $fields) . " WHERE customer_id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        return mysqli_stmt_execute($stmt);
    }

    public function getCustomerRentalHistory($id) {
        $sql = "SELECT 
                    g.cylinder_id,
                    g.serial_number,
                    g.size,
                    g.assigned_date,
                    g.status,
                    CASE 
                        WHEN g.assigned_date IS NOT NULL THEN DATEDIFF(NOW(), g.assigned_date)
                        ELSE 0
                    END as days_rented
                FROM customer c
                LEFT JOIN gascylinder g ON c.customer_id = g.assigned_to
                WHERE c.customer_id = ?
                ORDER BY g.assigned_date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }

    public function exportToArray() {
        $sql = "SELECT customer_id, name, phone, address, email, tax_id, created_at 
                FROM customer 
                ORDER BY name ASC";
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    public function getCustomersWithMaintenanceRequests() {
        $sql = "SELECT DISTINCT c.*, 
                       COUNT(m.maintenance_id) as total_maintenance_requests
                FROM customer c
                INNER JOIN gascylinder g ON c.customer_id = g.assigned_to
                INNER JOIN maintenance m ON g.cylinder_id = m.cylinder_id
                GROUP BY c.customer_id
                ORDER BY total_maintenance_requests DESC";
        return mysqli_query($this->conn, $sql);
    }
}
?>