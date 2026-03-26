<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();
$message = '';
$message_type = 'info';

// Create table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `vacate_requests` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `student_id` INT NOT NULL,
      `request_date` DATE NOT NULL,
      `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
      `approved_by` INT NULL,
      `approved_at` DATETIME NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`approved_by`) REFERENCES `staff`(`id`) ON DELETE SET NULL
    )");
} catch (PDOException $e) {
    // Suppress error if table already exists or other issues
}

// Handle Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $requestId = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $staffId = (int)$_SESSION['user_id'];

    if ($action === 'approve') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT vr.student_id, s.room_id FROM vacate_requests vr JOIN students s ON vr.student_id = s.id WHERE vr.id = ? AND vr.status = 'pending'");
            $stmt->execute([$requestId]);
            $request_data = $stmt->fetch();

            if ($request_data) {
                $studentId = $request_data['student_id'];
                $roomId = $request_data['room_id'];

                $pdo->prepare("UPDATE vacate_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$staffId, $requestId]);
                $pdo->prepare("UPDATE students SET is_active = 0, room_id = NULL WHERE id = ?")->execute([$studentId]);

                if ($roomId) {
                    $pdo->prepare("UPDATE rooms SET current_occupancy = GREATEST(0, current_occupancy - 1) WHERE id = ?")->execute([$roomId]);
                }
                
                $pdo->commit();
                $message = "Vacate request approved. The student account has been deactivated.";
                $message_type = 'success';
            } else {
                $pdo->rollBack();
                $message = "Request not found or already processed.";
                $message_type = 'error';
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "An error occurred: " . $e->getMessage();
            $message_type = 'error';
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE vacate_requests SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
        $stmt->execute([$staffId, $requestId]);
        $message = "Request has been rejected.";
        $message_type = 'success';
    }
}

$requests = $pdo->query("
    SELECT vr.id, vr.request_date, s.name, s.student_id as admission_no, r.room_number
    FROM vacate_requests vr
    JOIN students s ON vr.student_id = s.id
    LEFT JOIN rooms r ON s.room_id = r.id
    WHERE vr.status = 'pending'
    ORDER BY vr.request_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vacate Requests - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Hostel Vacate Requests</h1>
        <p class="sub">Approve or reject student requests to vacate the hostel.</p>
        
        <?php if ($message): ?><div class="message <?= $message_type ?>" style="padding:12px; border:1px solid; background:rgba(46,204,113,0.1);"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="section">
            <?php if (empty($requests)): ?>
                <p style="color:var(--muted)">No pending vacate requests.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Student</th><th>Room</th><th>Request Date</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?><br><small style="color:var(--muted)"><?= htmlspecialchars($r['admission_no']) ?></small></td>
                            <td><?= htmlspecialchars($r['room_number'] ?? 'N/A') ?></td>
                            <td><?= date('d M, Y', strtotime($r['request_date'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn" style="background: #27ae60;" onclick="return confirm('Are you sure you want to approve this request? This will deactivate the student account.');">Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn" style="background: #c0392b;">Reject</button>
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