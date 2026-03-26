<?php
require_once 'config/db.php';
require_once 'auth.php';
requireLogin();

// Only allow admin or warden roles
if (!in_array(($_SESSION['user_role'] ?? ''), ['admin', 'warden'])) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getConnection();
$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['month'], $_POST['total_bill'])) {
    try {
        $month_to_process = $_POST['month']; // Format: YYYY-MM
        $total_bill = (float)$_POST['total_bill'];
        $additional_current_bill = (float)($_POST['additional_current_bill'] ?? 0);
        $published_date = $_POST['published_date'] ?? date('Y-m-d');
        $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+10 days'));
        list($year, $month_num) = explode('-', $month_to_process);

        // 1. Ensure monthly_bills table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS monthly_bills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            bill_month VARCHAR(7) NOT NULL,
            hostel_fee DECIMAL(10,2) DEFAULT 780.00,
            establishment_fee DECIMAL(10,2) DEFAULT 850.00,
            mess_fee DECIMAL(10,2) NOT NULL,
            current_bill DECIMAL(10,2) DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL,
            published_date DATE DEFAULT NULL,
            due_date DATE DEFAULT NULL,
            fine DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('pending', 'submitted', 'confirmed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_bill (student_id, bill_month),
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )");

        // Ensure status column has correct enum values (fix for existing tables)
        $pdo->exec("ALTER TABLE monthly_bills MODIFY COLUMN status ENUM('pending', 'submitted', 'confirmed') DEFAULT 'pending'");
        
        // Add new columns if they don't exist
        try { $pdo->exec("ALTER TABLE monthly_bills ADD COLUMN published_date DATE DEFAULT NULL"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE monthly_bills ADD COLUMN due_date DATE DEFAULT NULL"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE monthly_bills ADD COLUMN fine DECIMAL(10,2) DEFAULT 0.00"); } catch (PDOException $e) {}

        // Ensure mess_cut_requests table exists for calculation
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS mess_cut_requests (
                id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            )");
        } catch (PDOException $e) {
            // Ignore if it fails, the query below will catch it if it's a real issue.
        }

        // 2. Get all active students
        $students = $pdo->query("SELECT id FROM students WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
        $student_count = count($students);

        // 3. Calculation constants
        $start_date = "$month_to_process-01";
        $days_in_month = (int)date('t', strtotime($start_date));
        $month_end = "$month_to_process-$days_in_month";

        // Calculate payable days for each student based on mess cuts
        $student_payable_days = [];
        $total_payable_days = 0;

        $stmt_cuts = $pdo->prepare("
            SELECT start_date, end_date 
            FROM mess_cut_requests 
            WHERE student_id = ? 
            AND status = 'approved' 
            AND (
                (start_date BETWEEN ? AND ?) OR 
                (end_date BETWEEN ? AND ?) OR
                (start_date <= ? AND end_date >= ?)
            )
        ");

        foreach ($students as $sid) {
            $stmt_cuts->execute([$sid, $start_date, $month_end, $start_date, $month_end, $start_date, $month_end]);
            $cuts = $stmt_cuts->fetchAll();
            
            // Map days to handle overlaps
            $days_map = array_fill(1, $days_in_month, 0); // 0 = present
            foreach ($cuts as $cut) {
                $s = max(strtotime($start_date), strtotime($cut['start_date']));
                $e = min(strtotime($month_end), strtotime($cut['end_date']));
                $s_day = (int)date('j', $s);
                $e_day = (int)date('j', $e);
                for ($i = $s_day; $i <= $e_day; $i++) $days_map[$i] = 1;
            }
            
            $payable = $days_in_month - array_sum($days_map);
            $student_payable_days[$sid] = $payable;
            $total_payable_days += $payable;
        }

        $cost_per_day = $total_payable_days > 0 ? $total_bill / $total_payable_days : 0;

        $pdo->beginTransaction();

        $bills_processed = 0;
        $hostel_fee = 780.00;
        $establishment_fee = 850.00;
        $current_bill_share = ($student_count > 0) ? round($additional_current_bill / $student_count, 2) : 0;

        $stmt_check = $pdo->prepare("SELECT id FROM monthly_bills WHERE student_id = ? AND bill_month = ?");
        $stmt_insert = $pdo->prepare("INSERT INTO monthly_bills (student_id, bill_month, hostel_fee, establishment_fee, mess_fee, current_bill, total_amount, published_date, due_date, fine, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, 'pending')");
        $stmt_update = $pdo->prepare("UPDATE monthly_bills SET hostel_fee = ?, establishment_fee = ?, mess_fee = ?, current_bill = ?, total_amount = ?, published_date = ?, due_date = ? WHERE id = ?");

        foreach ($students as $student_id) {
            $days_payable = $student_payable_days[$student_id] ?? 0;
            $mess_fee = round($days_payable * $cost_per_day, 2);
            $total_amount = $hostel_fee + $establishment_fee + $mess_fee + $current_bill_share;

            $stmt_check->execute([$student_id, $month_to_process]);
            if ($existing_id = $stmt_check->fetchColumn()) {
                $stmt_update->execute([$hostel_fee, $establishment_fee, $mess_fee, $current_bill_share, $total_amount, $published_date, $due_date, $existing_id]);
            } else {
                $stmt_insert->execute([$student_id, $month_to_process, $hostel_fee, $establishment_fee, $mess_fee, $current_bill_share, $total_amount, $published_date, $due_date]);
            }
            $bills_processed++;
        }

        $pdo->commit();
        $message = "Successfully processed bills for $month_to_process. Processed $bills_processed students.";
        $message_type = 'success';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Error: " . $e->getMessage();
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Generate Monthly Dues</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .error { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        .info { background: rgba(52, 152, 219, 0.2); color: #3498db; border: 1px solid #3498db; }
        .help-text { color: #888; font-size: 0.9rem; margin-top: 10px; line-height: 1.5; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Generate/Update Monthly Dues</h1>
        <p class="sub">Calculate mess fees based on total bill and mess cuts, and generate pending dues.</p>
        <?php if ($message): ?><div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <div class="section">
            <h2>Process Dues</h2>
            <form method="POST" action="admin-fees.php">
                <div class="form-group"><label for="month">Select Month to Process:</label><input type="month" id="month" name="month" value="<?= date('Y-m', strtotime('first day of last month')) ?>" required style="max-width: 300px;"></div>
                <div class="form-group"><label for="total_bill">Total Mess Bill for Month (₹):</label><input type="number" id="total_bill" name="total_bill" step="0.01" min="0" required style="max-width: 300px;"></div>
                <div class="form-group"><label for="additional_current_bill">Additional Current Bill (Total, Optional):</label><input type="number" id="additional_current_bill" name="additional_current_bill" step="0.01" min="0" placeholder="e.g., 5000 for shared expenses" style="max-width: 300px;"></div>
                <div class="form-group"><label for="published_date">Publishing Date:</label><input type="date" id="published_date" name="published_date" value="<?= date('Y-m-d') ?>" required style="max-width: 300px;"></div>
                <div class="form-group"><label for="due_date">Due Date:</label><input type="date" id="due_date" name="due_date" value="<?= date('Y-m-d', strtotime('+10 days')) ?>" required style="max-width: 300px;"></div>
                <button type="submit" class="btn">Generate Dues</button>
            </form>
            <p class="help-text">This will split the total mess bill and any additional current bill among active students. It also adds the fixed Hostel Fee and Establishment Fee.</p>
        </div>
    </main>
</body>
</html>