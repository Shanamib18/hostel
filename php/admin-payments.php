<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();

// Ensure the status column is up-to-date to prevent silent failures on update.
try {
    $pdo->exec("ALTER TABLE monthly_bills MODIFY COLUMN status ENUM('pending', 'submitted', 'confirmed') DEFAULT 'pending'");
} catch (PDOException $e) {
    // Suppress errors if table doesn't exist yet or permissions are wrong. The main query will handle it.
}
$message = '';
$message_type = 'info';

// Handle Approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_payment'])) {
    $bill_id = $_POST['bill_id'] ?? 0;
    $stmt = $pdo->prepare("UPDATE monthly_bills SET status = 'confirmed' WHERE id = ? AND status = 'submitted'");
    $stmt->execute([$bill_id]);
    if ($stmt->rowCount() > 0) {
        $message = "Payment has been confirmed successfully.";
        $message_type = 'success';
    } else {
        $message = "Could not confirm payment. It might have been already confirmed or the request was invalid.";
        $message_type = 'error';
    }
}

// Fetch Submitted Payments
$requests = [];
try {
    $stmt = $pdo->prepare("
        SELECT mb.*, s.name as student_name, s.student_id as admission_no
        FROM monthly_bills mb
        JOIN students s ON mb.student_id = s.id
        WHERE mb.status = 'submitted'
        ORDER BY mb.created_at ASC
    ");
    $stmt->execute();
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    if ($e->getCode() !== '42S02') throw $e;
    $message = "Monthly bills table not found. Please generate bills first.";
    $message_type = 'error';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Requests - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .error { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        .info { background: rgba(52, 152, 219, 0.2); color: #3498db; border: 1px solid #3498db; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Payment Confirmation Requests</h1>
        <p class="sub">Review and confirm payments submitted by students.</p>
        
        <?php if ($message): ?><div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="section">
            <?php if (empty($requests)): ?>
                <p style="color:var(--muted)">No pending payment requests to confirm.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Student</th><th>Month</th><th>Mess Bill</th><th>Fine</th><th>Total Amount</th><th>Payment Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['student_name']) ?><br><small style="color:var(--muted)"><?= htmlspecialchars($r['admission_no']) ?></small></td>
                            <td><?= date('F Y', strtotime($r['bill_month'] . '-01')) ?></td>
                            <td>₹<?= number_format($r['mess_fee'], 2) ?></td>
                            <td style="<?= $r['fine'] > 0 ? 'color:#e74c3c' : '' ?>">₹<?= number_format($r['fine'], 2) ?></td>
                            <td><strong>₹<?= number_format($r['total_amount'], 2) ?></strong></td>
                            <td><span style="color:#3498db;font-weight:600">Payment Submitted</span></td>
                            <td><form method="POST"><input type="hidden" name="bill_id" value="<?= $r['id'] ?>"><button type="submit" name="approve_payment" class="btn" style="padding: 6px 12px; font-size: 0.9rem; background: #27ae60;">Approve Payment</button></form></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>