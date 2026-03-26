<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();
$message = '';

// Handle Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE mess_cut_requests SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['request_id']]);
    $message = "Request marked as " . htmlspecialchars($_POST['status']);
}

// Fetch Pending Requests
$requests = $pdo->query("
    SELECT m.*, s.name, s.student_id as admission_no 
    FROM mess_cut_requests m 
    JOIN students s ON m.student_id = s.id 
    WHERE m.status = 'pending' 
    ORDER BY m.start_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Cut Requests - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .action-btn { padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; font-size: 0.8rem; margin-right: 4px; }
        .btn-approve { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .btn-reject { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        .btn-approve:hover { background: #2ecc71; color: #fff; }
        .btn-reject:hover { background: #e74c3c; color: #fff; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Mess Cut Requests</h1>
        <p class="sub">Manage student absence requests.</p>
        
        <?php if ($message): ?><div class="section" style="padding:12px; color:#2ecc71; border:1px solid #2ecc71; background:rgba(46,204,113,0.1);"><?= $message ?></div><?php endif; ?>

        <div class="section">
            <?php if (empty($requests)): ?>
                <p style="color:var(--muted)">No pending requests.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Student</th><th>Start Date</th><th>End Date</th><th>Days</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($requests as $r): 
                            $days = (strtotime($r['end_date']) - strtotime($r['start_date'])) / 86400 + 1;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?><br><small style="color:var(--muted)"><?= htmlspecialchars($r['admission_no']) ?></small></td>
                            <td><?= date('d M, Y', strtotime($r['start_date'])) ?></td>
                            <td><?= date('d M, Y', strtotime($r['end_date'])) ?></td>
                            <td><?= $days ?> Days</td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="status" value="approved" class="action-btn btn-approve">Approve</button>
                                    <button type="submit" name="status" value="rejected" class="action-btn btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>