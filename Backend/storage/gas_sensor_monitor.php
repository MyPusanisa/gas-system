<?php
// gas_sensor_monitor.php - ระบบตรวจวัดค่าแก๊ส MQ2

class GasSensorMonitor {
    private $conn;
    private $table = 'gas_sensor_readings';
    private $readings_buffer = [];
    private $buffer_size = 10;
    private $green_threshold = 520;
    private $yellow_threshold = 720;
    private $red_threshold = 721;
    private $red_alert_count = 0;
    private $sudden_change_threshold = 150;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            raw_value INT NOT NULL,
            filtered_value INT NOT NULL,
            average_value INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            is_red_alert BOOLEAN DEFAULT FALSE,
            alert_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_status (status)
        )";
        mysqli_query($this->conn, $sql);
    }

    public function processReading($raw_value) {
        $this->readings_buffer[] = $raw_value;
        
        if (count($this->readings_buffer) > $this->buffer_size) {
            array_shift($this->readings_buffer);
        }
        
        $filtered_value = $this->filterOutliers($this->readings_buffer);
        $average_value = $this->calculateAverage($this->readings_buffer);
        
        $status = $this->determineStatus($average_value);
        $is_red_alert = false;
        $alert_count = 0;
        
        if ($status == 'red') {
            $this->red_alert_count++;
            $alert_count = $this->red_alert_count;
            
            if ($this->red_alert_count >= 3) {
                $is_red_alert = true;
                $this->sendAlert($average_value);
                $this->red_alert_count = 0;
            }
        } else {
            if ($this->red_alert_count > 0) {
                $this->red_alert_count = 0;
            }
        }
        
        $sudden_change = $this->checkSuddenChange($filtered_value);
        if ($sudden_change && $average_value >= $this->red_threshold) {
            $is_red_alert = true;
            $this->sendSuddenChangeAlert($average_value);
        }
        
        $this->saveReading($raw_value, $filtered_value, $average_value, $status, $is_red_alert, $alert_count);
        
        return [
            'raw_value' => $raw_value,
            'filtered_value' => $filtered_value,
            'average_value' => $average_value,
            'status' => $status,
            'is_red_alert' => $is_red_alert,
            'status_color' => $this->getStatusColor($status),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function filterOutliers($readings) {
        if (count($readings) <= 2) {
            return !empty($readings) ? array_sum($readings) / count($readings) : 0;
        }
        
        $sorted = $readings;
        sort($sorted);
        
        array_shift($sorted);
        array_pop($sorted);
        
        if (empty($sorted)) {
            return !empty($readings) ? array_sum($readings) / count($readings) : 0;
        }
        
        return array_sum($sorted) / count($sorted);
    }

    private function calculateAverage($readings) {
        if (empty($readings)) {
            return 0;
        }
        return array_sum($readings) / count($readings);
    }

    private function determineStatus($value) {
        if ($value <= $this->green_threshold) {
            return 'green';
        } elseif ($value <= $this->yellow_threshold) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    private function getStatusColor($status) {
        switch ($status) {
            case 'green':
                return '#00ff00';
            case 'yellow':
                return '#ffff00';
            case 'red':
                return '#ff0000';
            default:
                return '#cccccc';
        }
    }

    private function checkSuddenChange($current_value) {
        $sql = "SELECT average_value FROM {$this->table} ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $last = mysqli_fetch_assoc($result);
            $change = abs($current_value - $last['average_value']);
            return $change >= $this->sudden_change_threshold;
        }
        
        return false;
    }

    private function sendAlert($value) {
        $alert_data = [
            'type' => 'gas_leak',
            'message' => "⚠️ ระดับแก๊สสูงผิดปกติ! ค่า: {$value}",
            'value' => $value,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO gas_sensor_alerts (alert_type, message, value, is_read, created_at) 
                VALUES ('gas_leak', ?, ?, 0, NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        $message = $alert_data['message'];
        mysqli_stmt_bind_param($stmt, "si", $message, $value);
        mysqli_stmt_execute($stmt);
        
        return $alert_data;
    }

    private function sendSuddenChangeAlert($value) {
        $sql = "INSERT INTO gas_sensor_alerts (alert_type, message, value, is_read, created_at) 
                VALUES ('sudden_change', ?, ?, 0, NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        $message = "⚠️ ค่าแก๊สเปลี่ยนแปลงฉับพลัน! ค่า: {$value}";
        mysqli_stmt_bind_param($stmt, "si", $message, $value);
        mysqli_stmt_execute($stmt);
    }

    private function saveReading($raw, $filtered, $average, $status, $is_red_alert, $alert_count) {
        $sql = "INSERT INTO {$this->table} (raw_value, filtered_value, average_value, status, is_red_alert, alert_count) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        $is_red_alert_int = $is_red_alert ? 1 : 0;
        mysqli_stmt_bind_param($stmt, "iiisii", $raw, $filtered, $average, $status, $is_red_alert_int, $alert_count);
        mysqli_stmt_execute($stmt);
    }

    public function getLatestReadings($limit = 100) {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $readings = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $readings[] = $row;
        }
        
        return array_reverse($readings);
    }

    public function getCurrentStatus() {
        $sql = "SELECT average_value, status, created_at FROM {$this->table} ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        
        return ['average_value' => 0, 'status' => 'green', 'created_at' => null];
    }

    public function getUnreadAlerts() {
        $sql = "SELECT * FROM gas_sensor_alerts WHERE is_read = 0 ORDER BY created_at DESC";
        $result = mysqli_query($this->conn, $sql);
        
        $alerts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $alerts[] = $row;
        }
        
        return $alerts;
    }

    public function markAlertAsRead($alert_id) {
        $sql = "UPDATE gas_sensor_alerts SET is_read = 1 WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $alert_id);
        return mysqli_stmt_execute($stmt);
    }

    public function getReadingsForChart($hours = 24) {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00') as time,
                    AVG(average_value) as value,
                    status
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00')
                ORDER BY created_at ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $hours);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'time' => $row['time'],
                'value' => round($row['value'], 2),
                'status' => $row['status']
            ];
        }
        
        return $data;
    }

    public function getDailyStatistics() {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    MIN(average_value) as min_value,
                    MAX(average_value) as max_value,
                    AVG(average_value) as avg_value,
                    SUM(CASE WHEN status = 'red' THEN 1 ELSE 0 END) as red_count,
                    SUM(CASE WHEN status = 'yellow' THEN 1 ELSE 0 END) as yellow_count,
                    SUM(CASE WHEN status = 'green' THEN 1 ELSE 0 END) as green_count
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        $result = mysqli_query($this->conn, $sql);
        
        $stats = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $stats[] = $row;
        }
        
        return $stats;
    }

    public function clearOldReadings($days = 30) {
        $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $days);
        mysqli_stmt_execute($stmt);
        
        $sql = "DELETE FROM gas_sensor_alerts WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $days);
        mysqli_stmt_execute($stmt);
        
        return true;
    }
}

// API endpoint สำหรับรับค่าจาก ESP32
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_reading') {
    include_once 'db_connection.php';
    
    $data = json_decode(file_get_contents('php://input'), true);
    $raw_value = isset($data['value']) ? intval($data['value']) : 0;
    
    $monitor = new GasSensorMonitor($conn);
    $result = $monitor->processReading($raw_value);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// API endpoint สำหรับดึงข้อมูลแสดงบนเว็บ
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    include_once 'db_connection.php';
    $monitor = new GasSensorMonitor($conn);
    
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_current':
            echo json_encode($monitor->getCurrentStatus());
            break;
        case 'get_history':
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
            echo json_encode($monitor->getLatestReadings($limit));
            break;
        case 'get_chart':
            $hours = isset($_GET['hours']) ? intval($_GET['hours']) : 24;
            echo json_encode($monitor->getReadingsForChart($hours));
            break;
        case 'get_alerts':
            echo json_encode($monitor->getUnreadAlerts());
            break;
        case 'get_stats':
            echo json_encode($monitor->getDailyStatistics());
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

// API endpoint สำหรับ mark alert as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'mark_alert_read') {
    include_once 'db_connection.php';
    
    $data = json_decode(file_get_contents('php://input'), true);
    $alert_id = isset($data['alert_id']) ? intval($data['alert_id']) : 0;
    
    $monitor = new GasSensorMonitor($conn);
    $result = $monitor->markAlertAsRead($alert_id);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
    exit;
}
?>

<!-- HTML + JavaScript สำหรับแสดงผลบนเว็บ -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบตรวจวัดแก๊สร้านแก๊ส</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .gauge-container {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .gauge {
            width: 300px;
            height: 300px;
            margin: 0 auto;
            position: relative;
        }
        
        .gauge svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        
        .gauge-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .gauge-value .value {
            font-size: 48px;
            font-weight: bold;
            color: white;
        }
        
        .gauge-value .unit {
            font-size: 18px;
            color: #ccc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 24px;
            font-weight: bold;
            margin-top: 20px;
        }
        
        .status-green {
            background: #00ff00;
            color: #003300;
            box-shadow: 0 0 20px rgba(0,255,0,0.5);
        }
        
        .status-yellow {
            background: #ffff00;
            color: #666600;
            box-shadow: 0 0 20px rgba(255,255,0,0.5);
        }
        
        .status-red {
            background: #ff0000;
            color: #660000;
            box-shadow: 0 0 20px rgba(255,0,0,0.5);
            animation: pulse 0.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .card {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        
        .card h3 {
            color: white;
            margin-bottom: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(0,0,0,0.5);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #00ff00;
        }
        
        .stat-card .stat-label {
            color: #ccc;
            margin-top: 5px;
        }
        
        .alert-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        
        .alert-popup-content {
            background: #ff0000;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            color: white;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        th {
            background: rgba(0,0,0,0.3);
        }
        
        .red-row {
            background: rgba(255,0,0,0.2);
        }
        
        .chart-container {
            height: 400px;
        }
        
        .btn-refresh {
            background: #00ff00;
            color: #003300;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .alert-badge {
            background: red;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="gauge-container">
            <div class="gauge" id="gauge">
                <svg>
                    <circle cx="150" cy="150" r="140" fill="none" stroke="#333" stroke-width="20"/>
                    <circle cx="150" cy="150" r="140" fill="none" stroke="#00ff00" stroke-width="20" 
                            stroke-dasharray="879.2" stroke-dashoffset="879.2" id="gauge-needle"/>
                </svg>
                <div class="gauge-value">
                    <div class="value" id="current-value">0</div>
                    <div class="unit">ppm</div>
                </div>
            </div>
            <div>
                <div class="status-badge" id="status-badge">ปกติ</div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" id="min-value">-</div>
                <div class="stat-label">ค่าต่ำสุดวันนี้</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="max-value">-</div>
                <div class="stat-label">ค่าสูงสุดวันนี้</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="avg-value">-</div>
                <div class="stat-label">ค่าเฉลี่ยวันนี้</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="alert-count">0</div>
                <div class="stat-label">การแจ้งเตือนวันนี้</div>
            </div>
        </div>
        
        <div class="card">
            <h3>กราฟแสดงค่าแก๊สย้อนหลัง 24 ชั่วโมง</h3>
            <div class="chart-container">
                <canvas id="gas-chart"></canvas>
            </div>
        </div>
        
        <div class="card">
            <h3>ประวัติการอ่านค่าล่าสุด</h3>
            <div class="table-container">
                <table id="history-table">
                    <thead>
                        <tr>
                            <th>เวลา</th>
                            <th>ค่าดิบ</th>
                            <th>ค่ากรอง</th>
                            <th>ค่าเฉลี่ย</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody id="history-body">
                        <tr><td colspan="5">กำลังโหลด...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="alert-container" class="alert-popup"></div>
    
    <script>
        let chart = null;
        let previousStatus = '';
        let alertSound = null;
        
        $(document).ready(function() {
            initChart();
            loadCurrentData();
            loadHistory();
            loadChartData();
            loadStatistics();
            
            setInterval(function() {
                loadCurrentData();
                loadHistory();
                loadChartData();
                loadStatistics();
                checkAlerts();
            }, 1000);
        });
        
        function initChart() {
            const ctx = document.getElementById('gas-chart').getContext('2d');
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'ค่าแก๊ส (ppm)',
                        data: [],
                        borderColor: '#00ff00',
                        backgroundColor: 'rgba(0, 255, 0, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255,255,255,0.1)' },
                            ticks: { color: 'white' }
                        },
                        x: {
                            ticks: { color: 'white', maxRotation: 45, minRotation: 45 }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: 'white' } }
                    }
                }
            });
        }
        
        function loadCurrentData() {
            $.getJSON('gas_sensor_monitor.php?action=get_current', function(data) {
                $('#current-value').text(Math.round(data.average_value));
                updateGauge(data.average_value);
                updateStatus(data.status, data.average_value);
                
                if (previousStatus !== data.status && data.status === 'red') {
                    showAlert('⚠️ ระดับแก๊สสูงผิดปกติ! ค่า: ' + Math.round(data.average_value) + ' ppm');
                }
                previousStatus = data.status;
            });
        }
        
        function updateGauge(value) {
            const maxValue = 1000;
            const percentage = Math.min(value / maxValue, 1);
            const circumference = 879.2;
            const offset = circumference * (1 - percentage);
            
            let color = '#00ff00';
            if (value > 720) color = '#ff0000';
            else if (value > 520) color = '#ffff00';
            
            $('#gauge-needle').attr('stroke', color);
            $('#gauge-needle').attr('stroke-dashoffset', offset);
        }
        
        function updateStatus(status, value) {
            const badge = $('#status-badge');
            let text = '', colorClass = '';
            
            if (status === 'green') {
                text = '🟢 ปลอดภัย';
                colorClass = 'status-green';
            } else if (status === 'yellow') {
                text = '🟡 ระวัง';
                colorClass = 'status-yellow';
            } else {
                text = '🔴 อันตราย!';
                colorClass = 'status-red';
            }
            
            badge.text(text + ' (' + Math.round(value) + ' ppm)');
            badge.removeClass('status-green status-yellow status-red');
            badge.addClass(colorClass);
        }
        
        function loadHistory() {
            $.getJSON('gas_sensor_monitor.php?action=get_history&limit=20', function(data) {
                const tbody = $('#history-body');
                tbody.empty();
                
                if (data.length === 0) {
                    tbody.html('<tr><td colspan="5">ไม่มีข้อมูล</td></tr>');
                    return;
                }
                
                data.reverse().forEach(function(row) {
                    let statusText = '', statusClass = '';
                    if (row.status === 'green') {
                        statusText = '🟢 ปลอดภัย';
                        statusClass = '';
                    } else if (row.status === 'yellow') {
                        statusText = '🟡 ระวัง';
                        statusClass = '';
                    } else {
                        statusText = '🔴 อันตราย';
                        statusClass = 'red-row';
                    }
                    
                    tbody.append(`
                        <tr class="${statusClass}">
                            <td>${row.created_at}</td>
                            <td>${row.raw_value}</td>
                            <td>${row.filtered_value}</td>
                            <td>${row.average_value}</td>
                            <td>${statusText}</td>
                        </tr>
                    `);
                });
            });
        }
        
        function loadChartData() {
            $.getJSON('gas_sensor_monitor.php?action=get_chart&hours=24', function(data) {
                const labels = data.map(item => item.time);
                const values = data.map(item => item.value);
                
                chart.data.labels = labels;
                chart.data.datasets[0].data = values;
                
                const bgColor = values.map(v => {
                    if (v > 720) return 'rgba(255,0,0,0.2)';
                    if (v > 520) return 'rgba(255,255,0,0.2)';
                    return 'rgba(0,255,0,0.2)';
                });
                chart.data.datasets[0].backgroundColor = bgColor;
                
                const borderColor = values.map(v => {
                    if (v > 720) return '#ff0000';
                    if (v > 520) return '#ffff00';
                    return '#00ff00';
                });
                chart.data.datasets[0].borderColor = borderColor;
                
                chart.update();
            });
        }
        
        function loadStatistics() {
            $.getJSON('gas_sensor_monitor.php?action=get_stats', function(data) {
                if (data.length > 0) {
                    const today = data[0];
                    $('#min-value').text(today.min_value || '-');
                    $('#max-value').text(today.max_value || '-');
                    $('#avg-value').text(today.avg_value ? Math.round(today.avg_value) : '-');
                    $('#alert-count').text(today.red_count || 0);
                }
            });
        }
        
        function checkAlerts() {
            $.getJSON('gas_sensor_monitor.php?action=get_alerts', function(alerts) {
                alerts.forEach(function(alert) {
                    showAlert(alert.message);
                    $.post('gas_sensor_monitor.php?action=mark_alert_read', 
                           JSON.stringify({alert_id: alert.id}),
                           function() {});
                });
            });
        }
        
        function showAlert(message) {
            const alertContainer = $('#alert-container');
            const alertId = 'alert-' + Date.now();
            
            const alertHtml = `
                <div class="alert-popup-content" id="${alertId}" onclick="$(this).remove()">
                    <strong>⚠️ แจ้งเตือน!</strong><br>
                    ${message}
                </div>
            `;
            
            alertContainer.append(alertHtml);
            
            if (alertSound) {
                alertSound.play();
            }
            
            setTimeout(function() {
                $('#' + alertId).fadeOut(300, function() { $(this).remove(); });
            }, 5000);
        }
        
        function refreshData() {
            loadCurrentData();
            loadHistory();
            loadChartData();
            loadStatistics();
        }
    </script>
</body>
</html>