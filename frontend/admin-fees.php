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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['month'])) {
    try {
        $month_to_process = $_POST['month']; // Format: YYYY-MM
        list($year, $month_num) = explode('-', $month_to_process);

        // 1. Get fee structures
        $fee_structures = $pdo->query("SELECT id, fee_type, amount FROM fee_structure WHERE is_active = 1 AND period = 'monthly'")->fetchAll(PDO::FETCH_ASSOC);
        $mess_fee_struct = array_values(array_filter($fee_structures, fn($f) => $f['fee_type'] === 'Mess Fee'))[0] ?? null;
        $hostel_fee_struct = array_values(array_filter($fee_structures, fn($f) => $f['fee_type'] === 'Hostel Fee'))[0] ?? null;

        if (!$mess_fee_struct || !$hostel_fee_struct) {
            throw new Exception("Monthly 'Mess Fee' or 'Hostel Fee' must be defined in the fee structure.");
        }

        // 2. Get all active students
        $students = $pdo->query("SELECT id FROM students WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);

        // 3. Calculation constants
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$month_num, (int)$year);
        $total_possible_meals = $days_in_month * 3;
        $cost_per_meal = $total_possible_meals > 0 ? $mess_fee_struct['amount'] / $total_possible_meals : 0;

        $pdo->beginTransaction();

        $mess_updated = 0; $mess_inserted = 0; $hostel_inserted = 0;

        $stmt_get_mess_count = $pdo->prepare("SELECT COUNT(*) FROM mess_attendance WHERE student_id = ? AND DATE_FORMAT(marked_at, '%Y-%m') = ?");
        $stmt_find_pending = $pdo->prepare("SELECT id FROM fee_payments WHERE student_id = ? AND fee_structure_id = ? AND status = 'pending' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
        $stmt_update_pending = $pdo->prepare("UPDATE fee_payments SET amount = ?, notes = ? WHERE id = ?");
        $stmt_insert_pending = $pdo->prepare("INSERT INTO fee_payments (student_id, fee_structure_id, amount, payment_date, status, notes) VALUES (?, ?, ?, ?, 'pending', ?)");

        foreach ($students as $student_id) {
            $due_date = "$month_to_process-01";

            // --- Process Mess Fee ---
            $stmt_get_mess_count->execute([$student_id, $month_to_process]);
            $meals_taken = (int)$stmt_get_mess_count->fetchColumn();
            $calculated_mess_fee = round($meals_taken * $cost_per_meal, 2);
            $mess_notes = "Due for " . date("F Y", strtotime($due_date)) . ". Calculated from $meals_taken meals.";

            $stmt_find_pending->execute([$student_id, $mess_fee_struct['id'], $month_to_process]);
            if ($existing_id = $stmt_find_pending->fetchColumn()) {
                $stmt_update_pending->execute([$calculated_mess_fee, $mess_notes, $existing_id]);
                $mess_updated++;
            } else {
                $stmt_insert_pending->execute([$student_id, $mess_fee_struct['id'], $calculated_mess_fee, $due_date, $mess_notes]);
                $mess_inserted++;
            }

            // --- Process Hostel Fee (if not already present) ---
            $stmt_find_pending->execute([$student_id, $hostel_fee_struct['id'], $month_to_process]);
            if (!$stmt_find_pending->fetchColumn()) {
                $hostel_notes = "Monthly Hostel Fee for " . date("F Y", strtotime($due_date));
                $stmt_insert_pending->execute([$student_id, $hostel_fee_struct['id'], $hostel_fee_struct['amount'], $due_date, $hostel_notes]);
                $hostel_inserted++;
            }
        }

        $pdo->commit();
        $message = "Successfully processed dues for $month_to_process. Mess Dues: $mess_inserted created, $mess_updated updated. Hostel Dues: $hostel_inserted created.";
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
</head>
<body>
    <header class="header">
        <div class="logo">LH LBSCEK — Admin Portal</div>
        <div class="user">
            <span><?= htmlspecialchars($_SESSION['user_name']) ?> (<?= htmlspecialchars($_SESSION['user_role']) ?>)</span>
            <a href="logout.php">Logout</a>
        </div>
    </header>
    <main class="main">
        <h1>Generate/Update Monthly Dues</h1>
        <p class="sub">Calculate mess fees based on attendance and generate pending dues for all students.</p>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="section">
            <h2>Process Dues</h2>
            <form method="POST" action="admin-fees.php">
                <div class="form-group">
                    <label for="month">Select Month to Process:</label>
                    <input type="month" id="month" name="month" value="<?= date('Y-m', strtotime('first day of last month')) ?>" required>
                </div>
                <button type="submit" class="button">Generate Dues</button>
            </form>
            <p class="help-text">This will calculate mess fees for the selected month based on attendance and create or update a 'pending' payment record for each student. This process is idempotent; running it multiple times for the same month will only update the amounts to reflect the latest attendance data.</p>
        </div>
    </main>
</body>
</html>