<?php
require_once 'config/db.php';
require_once 'auth.php';
requireLogin();
if (($_SESSION['user_type'] ?? '') !== 'student') {
    header('Location: dashboard.php');
    exit;
}

$pdo = getConnection();
$studentId = (int)$_SESSION['user_id'];

$mess_cut_message = null;
$vacate_error_message = null;
$vacate_message = null;

if (isset($_GET['vacate_error'])) {
    $vacate_error_message = ['type' => 'error', 'text' => 'Could not process your request to vacate. Please contact the admin.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_mess_cut'])) {
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';

        if (!empty($start_date) && !empty($end_date)) {
            if ($start_date > $end_date) {
                $mess_cut_message = ['type' => 'error', 'text' => 'Start date cannot be after the end date.'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO mess_cut_requests (student_id, start_date, end_date, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$studentId, $start_date, $end_date]);
                $mess_cut_message = ['type' => 'success', 'text' => 'Your mess cut request has been submitted for approval.'];
            }
        } else {
            $mess_cut_message = ['type' => 'error', 'text' => 'Please select both a start and end date.'];
        }
    } elseif (isset($_POST['delete_mess_cut'])) {
        $cutId = $_POST['cut_id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM mess_cut_requests WHERE id = ? AND student_id = ? AND status = 'pending'");
        $stmt->execute([$cutId, $studentId]);
        if ($stmt->rowCount() > 0) {
            $mess_cut_message = ['type' => 'success', 'text' => 'Mess cut request deleted successfully.'];
        }
    } elseif (isset($_POST['mark_as_paid'])) {
        $bill_id = $_POST['bill_id'] ?? 0;
        $transaction_id = trim($_POST['transaction_id'] ?? '');
        
        // Check for fine applicability and update status
        $bill = $pdo->prepare("SELECT * FROM monthly_bills WHERE id = ? AND student_id = ?");
        $bill->execute([$bill_id, $studentId]);
        $b = $bill->fetch();
        
        if ($b && $b['status'] === 'pending') {
            $fineToAdd = 0;
            if ($b['due_date'] && date('Y-m-d') > $b['due_date'] && $b['fine'] == 0) {
                $fineToAdd = 10.00;
            }
            $stmt = $pdo->prepare("UPDATE monthly_bills SET status = 'submitted', transaction_id = ?, fine = fine + ?, total_amount = total_amount + ? WHERE id = ?");
            $stmt->execute([$transaction_id, $fineToAdd, $fineToAdd, $bill_id]);
        }
        header("Location: student-portal.php"); // Redirect to prevent re-submission
        exit;
    } elseif (isset($_POST['request_vacate'])) {
        // Check if a pending request already exists
        $stmt = $pdo->prepare("SELECT id FROM vacate_requests WHERE student_id = ? AND status = 'pending'");
        $stmt->execute([$studentId]);
        if ($stmt->fetch()) {
            $vacate_message = ['type' => 'error', 'text' => 'You already have a pending request to vacate.'];
        } else {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `vacate_requests` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY, `student_id` INT NOT NULL, `request_date` DATE NOT NULL,
                  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', `approved_by` INT NULL, `approved_at` DATETIME NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
                )");
            } catch (PDOException $e) {}

            $stmt = $pdo->prepare("INSERT INTO vacate_requests (student_id, request_date) VALUES (?, CURDATE())");
            $stmt->execute([$studentId]);
            $vacate_message = ['type' => 'success', 'text' => 'Your request to vacate the hostel has been submitted for admin approval.'];
        }
    }
}

$mess = $pdo->prepare("SELECT meal_type, marked_at, method FROM mess_attendance WHERE student_id = ? AND DATE(marked_at) = CURDATE() ORDER BY marked_at DESC");
$mess->execute([$studentId]);
$messRows = $mess->fetchAll(PDO::FETCH_ASSOC);

$entryExit = $pdo->prepare("SELECT type, recorded_at, method FROM entry_exit_logs WHERE student_id = ? AND DATE(recorded_at) = CURDATE() ORDER BY recorded_at DESC");
$entryExit->execute([$studentId]);
$eeRows = $entryExit->fetchAll(PDO::FETCH_ASSOC);

$payRows = [];
$duesRows = [];
try {
    $payments = $pdo->prepare("SELECT * FROM monthly_bills WHERE student_id = ? AND status = 'confirmed' ORDER BY bill_month DESC LIMIT 20");
    $payments->execute([$studentId]);
    $payRows = $payments->fetchAll(PDO::FETCH_ASSOC);

    $pendingDues = $pdo->prepare("SELECT * FROM monthly_bills WHERE student_id = ? AND status IN ('pending', 'submitted') ORDER BY bill_month ASC");
    $pendingDues->execute([$studentId]);
    $duesRows = $pendingDues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($e->getCode() !== '42S02') throw $e; // Ignore 'table not found', rethrow others
}

$messCuts = $pdo->prepare("SELECT * FROM mess_cut_requests WHERE student_id = ? ORDER BY start_date DESC LIMIT 10");
$messCuts->execute([$studentId]);
$messCutRows = $messCuts->fetchAll(PDO::FETCH_ASSOC);

$vacateRequest = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM vacate_requests WHERE student_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$studentId]);
    $vacateRequest = $stmt->fetch();
} catch (PDOException $e) {
    // Ignore if table doesn't exist yet
    if ($e->getCode() !== '42S02') throw $e;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .message.success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .message.error { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        .status-pending { color: #f1c40f; font-weight: 500; } .status-approved, .status-confirmed { color: #2ecc71; font-weight: 500; } .status-rejected { color: #e74c3c; font-weight: 500; } .status-submitted { color: #3498db; font-weight: 500; }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">LH LBSCEK — Student Portal</div>
        <div class="user">
            <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </header>
    <main class="main">
        <h1>Student Dashboard</h1>
        <p class="sub">View your attendance and payments</p>

        <?php if ($vacate_error_message): ?>
            <div class="message <?= $vacate_error_message['type'] ?>" style="margin-bottom: 16px; padding: 12px; border-radius: 8px;"><?= htmlspecialchars($vacate_error_message['text']) ?></div>
        <?php endif; ?>

        <div class="section">
            <h2>Request Mess Cut</h2>
            <p class="sub" style="margin-bottom: 16px;">Select the dates you will be absent to request a mess cut.</p>
            
            <?php if ($mess_cut_message): ?>
                <div class="message <?= $mess_cut_message['type'] ?>" style="margin-bottom: 16px; padding: 12px; border-radius: 8px;"><?= htmlspecialchars($mess_cut_message['text']) ?></div>
            <?php endif; ?>

            <form method="POST" action="student-portal.php">
                <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px;">
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" required style="max-width: none;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" required style="max-width: none;">
                    </div>
                </div>
                <button type="submit" name="request_mess_cut" class="btn">Submit Request</button>
            </form>

            <h3 style="margin-top: 32px; margin-bottom: 16px; font-size: 1.1rem;">My Mess Cut Requests</h3>
            <?php if (empty($messCutRows)): ?>
                <p class="empty" style="padding:16px 0;color:var(--muted)">You have not made any mess cut requests.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>Start Date</th><th>End Date</th><th>Days</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($messCutRows as $cut): ?>
                    <tr>
                        <td><?= date('d M, Y', strtotime($cut['start_date'])) ?></td>
                        <td><?= date('d M, Y', strtotime($cut['end_date'])) ?></td>
                        <td><?= (strtotime($cut['end_date']) - strtotime($cut['start_date'])) / 86400 + 1 ?> Days</td>
                        <td><span class="status-<?= strtolower(htmlspecialchars($cut['status'])) ?>"><?= ucfirst(htmlspecialchars($cut['status'])) ?></span></td>
                        <td>
                            <?php if ($cut['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                <input type="hidden" name="cut_id" value="<?= $cut['id'] ?>">
                                <button type="submit" name="delete_mess_cut" style="background:none; border:none; color:#e74c3c; cursor:pointer; text-decoration:underline; padding:0; font-family:inherit; font-size:inherit;">Delete</button>
                            </form>
                            <?php else: ?>
                            <span style="color:var(--muted)">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Today's Mess Attendance</h2>
            <?php if (empty($messRows)): ?>
                <p class="empty" style="padding:16px;color:var(--muted)">No mess attendance recorded for today.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>Meal</th><th>Time</th><th>Method</th></tr></thead>
                <tbody>
                    <?php foreach ($messRows as $r): ?>
                    <tr><td><?= htmlspecialchars($r['meal_type']) ?></td><td><?= date('H:i', strtotime($r['marked_at'])) ?></td><td><?= htmlspecialchars($r['method']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Today's Entry / Exit</h2>
            <?php if (empty($eeRows)): ?>
                <p class="empty" style="padding:16px;color:var(--muted)">No entry/exit records for today.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>Type</th><th>Time</th><th>Method</th></tr></thead>
                <tbody>
                    <?php foreach ($eeRows as $r): ?>
                    <tr><td><?= htmlspecialchars($r['type']) ?></td><td><?= date('H:i', strtotime($r['recorded_at'])) ?></td><td><?= htmlspecialchars($r['method']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Pending Dues</h2>
            <?php if (empty($duesRows)): ?>
                <p class="empty" style="padding:16px;color:var(--muted)">No pending dues found.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>Month</th><th>Due Date</th><th>Hostel Fee</th><th>Establishment Fee</th><th>Mess Bill</th><th>Current Bill</th><th>Fine</th><th>Total Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($duesRows as $due): 
                        $isOverdue = ($due['status'] === 'pending' && $due['due_date'] && date('Y-m-d') > $due['due_date']);
                        $displayFine = $due['fine'];
                        $displayTotal = $due['total_amount'];
                        if ($isOverdue && $due['fine'] == 0) {
                            $displayFine = 10.00;
                            $displayTotal += 10.00;
                        }
                    ?>
                    <tr>
                        <td><?= date('F Y', strtotime($due['bill_month'] . '-01')) ?></td>
                        <td><?= $due['due_date'] ? date('d M, Y', strtotime($due['due_date'])) : '-' ?></td>
                        <td>₹<?= number_format($due['hostel_fee'], 2) ?></td>
                        <td>₹<?= number_format($due['establishment_fee'], 2) ?></td>
                        <td>₹<?= number_format($due['mess_fee'], 2) ?></td>
                        <td>₹<?= number_format($due['current_bill'], 2) ?></td>
                        <td style="<?= $displayFine > 0 ? 'color:#e74c3c' : '' ?>">₹<?= number_format($displayFine, 2) ?></td>
                        <td><strong>₹<?= number_format($displayTotal, 2) ?></strong></td>
                        <td>
                            <?php if ($due['status'] === 'submitted'): ?>
                                <span class="status-submitted">Payment Submitted (Waiting for Admin Confirmation)</span>
                            <?php else: ?>
                                <span class="status-pending">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($due['status'] === 'pending'): ?>
                            <form method="POST"><input type="hidden" name="bill_id" value="<?= $due['id'] ?>"><input type="text" name="transaction_id" placeholder="Transaction ID" required><button type="submit" name="mark_as_paid" class="btn" style="padding: 6px 12px; font-size: 0.9rem;">Pay / Mark as Paid</button></form>
                            <?php else: ?>
                                <span style="color:var(--muted)">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>My Payments</h2>
            <?php if (empty($payRows)): ?>
                <p class="empty" style="padding:16px;color:var(--muted)">No payments yet.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>Month</th><th>Total Amount</th><th>Payment Status</th></tr></thead>
                <tbody>
                    <?php foreach ($payRows as $p): ?>
                    <tr>
                        <td><?= date('F Y', strtotime($p['bill_month'] . '-01')) ?></td>
                        <td>₹<?= number_format($p['total_amount'], 2) ?></td>
                        <td><span class="status-confirmed">Payment Confirmed</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Hostel Actions</h2>
            
            <?php if ($vacate_message): ?>
                <div class="message <?= $vacate_message['type'] ?>" style="margin-bottom: 16px; padding: 12px; border-radius: 8px;"><?= htmlspecialchars($vacate_message['text']) ?></div>
            <?php endif; ?>

            <?php if ($vacateRequest && $vacateRequest['status'] === 'pending'): ?>
                <p class="sub" style="margin-bottom: 16px;">Your request to vacate the hostel is currently pending approval.</p>
                <div class="message info" style="background: rgba(52, 152, 219, 0.2); color: #3498db; border: 1px solid #3498db; padding: 12px; border-radius: 8px;">
                    Requested on: <?= date('d M, Y', strtotime($vacateRequest['request_date'])) ?>. Status: <strong>Pending</strong>.
                </div>
            <?php elseif ($vacateRequest && $vacateRequest['status'] === 'rejected'): ?>
                <p class="sub" style="margin-bottom: 16px;">Your previous request to vacate was rejected. You can submit a new request if needed.</p>
                <form method="POST" action="student-portal.php" onsubmit="return confirm('Are you sure you want to submit a new request to vacate the hostel?');">
                    <button type="submit" name="request_vacate" class="btn" style="background: #c0392b;">Request to Vacate Hostel</button>
                </form>
            <?php elseif (!$vacateRequest || $vacateRequest['status'] === 'rejected'): ?>
                <p class="sub" style="margin-bottom: 16px;">If you are vacating the hostel, please use the button below to send a request to the admin.</p>
                <form method="POST" action="student-portal.php" onsubmit="return confirm('Are you sure you want to submit a request to vacate the hostel?');">
                    <button type="submit" name="request_vacate" class="btn" style="background: #c0392b;">Request to Vacate Hostel</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
