<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();

// Ensure the table exists to prevent "Base table or view not found" errors
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `vacate_requests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT NOT NULL,
        `request_date` DATE NOT NULL,
        `reason` TEXT,
        `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        `approved_by` INT NULL,
        `approved_at` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    // Silently continue; the query below will handle missing data gracefully
}

$vacated_students = [];
$error_message = '';

try {
    $vacated_students = $pdo->query("
        SELECT 
            s.student_id, 
            s.name, 
            s.email, 
            s.phone, 
            s.department, 
            s.year, 
            vr.approved_at as vacated_date
        FROM students s
        LEFT JOIN vacate_requests vr ON s.id = vr.student_id AND vr.status = 'approved'
        WHERE s.is_active = 0
        ORDER BY vr.approved_at DESC, s.name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Unable to fetch records. Please ensure the database is properly initialized.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacated Students - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Vacated Students</h1>
        <p class="sub">List of all students who have vacated the hostel.</p>
        
        <?php if ($error_message): ?>
            <div class="message error" style="padding:15px; background:rgba(231,76,60,0.1); border:1px solid #e74c3c; border-radius:8px; margin-bottom:20px; color:#e74c3c;">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <?php if (empty($vacated_students)): ?>
                <p style="color:var(--muted)">No students have been marked as vacated.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Student ID</th><th>Name</th><th>Email / Phone</th><th>Department / Year</th><th>Vacated Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($vacated_students as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['student_id']) ?></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?><br><small style="color:#888"><?= htmlspecialchars($s['phone'] ?? '') ?></small></td>
                            <td><?= htmlspecialchars($s['department'] ?? '-') ?> - <?= htmlspecialchars($s['year'] ?? '-') ?></td>
                            <td><?= $s['vacated_date'] ? date('d M, Y', strtotime($s['vacated_date'])) : 'N/A' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>