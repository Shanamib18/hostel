<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();

$payments = [];
try {
    // Main query to get total pending amount per student
    $stmt = $pdo->prepare("
        SELECT
            s.name as student_name,
            s.student_id as admission_no,
            r.room_number,
            s.is_active,
            SUM(CASE 
                WHEN mb.status = 'pending' AND mb.due_date IS NOT NULL AND CURDATE() > mb.due_date AND mb.fine = 0 
                THEN mb.total_amount + 10 
                WHEN mb.status IN ('pending', 'submitted') THEN mb.total_amount 
                ELSE 0 
            END) as total_due,
            SUM(CASE WHEN mb.status = 'submitted' THEN 1 ELSE 0 END) as submitted_count,
            s.id as student_db_id
        FROM
            students s
        JOIN
            monthly_bills mb ON s.id = mb.student_id
        LEFT JOIN
            rooms r ON s.room_id = r.id
        GROUP BY
            s.id, s.name, s.student_id, r.room_number, s.is_active
        HAVING total_due > 0 OR submitted_count > 0
        ORDER BY
            submitted_count DESC, s.is_active DESC, total_due DESC, s.name ASC
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    if ($e->getCode() !== '42S02') throw $e;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .status-submitted { color: #3498db; font-weight: 600; }
        .status-pending { color: #f1c40f; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Pending Payments</h1>
        <p class="sub">Total outstanding dues from students.</p>

        <div class="section">
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <div style="display: flex; justify-content: flex-end; margin-bottom: 20px; gap: 10px;">
                <a href="export-payments.php?format=pdf" class="btn" target="_blank">Download PDF</a>
                <a href="export-payments.php?format=excel" class="btn">Download Excel</a>
            </div>
            <?php endif; ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Admission No</th>
                            <th>Room No</th>
                            <th>Student Status</th>
                            <th>Payment Status</th>
                            <th style="text-align:right;">Total Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="6" style="text-align:center;">No pending payments found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['student_name']) ?></td>
                                    <td><?= htmlspecialchars($payment['admission_no']) ?></td>
                                    <td><?= htmlspecialchars($payment['room_number'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if ($payment['is_active']): ?>
                                            <span style="color:#2ecc71; font-weight: 500;">Active</span>
                                        <?php else: ?>
                                            <span style="color:#e74c3c; font-weight: 500;">Vacated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['submitted_count'] > 0): ?>
                                            <span class="status-submitted">Request Submitted</span>
                                        <?php elseif ($payment['total_due'] > 0): ?>
                                            <span class="status-pending">Pending</span>
                                        <?php else: ?>
                                            <span style="color:#2ecc71;font-weight:500">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">₹<?= number_format($payment['total_due'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>