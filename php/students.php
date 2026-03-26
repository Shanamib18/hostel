<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();
$hostelId = 1;
$students = $pdo->query("SELECT s.id, s.student_id, s.name, s.email, s.phone, s.parent_name, s.parent_phone, s.department, s.year, r.room_number FROM students s LEFT JOIN rooms r ON s.room_id = r.id WHERE s.hostel_id = $hostelId AND s.is_active = 1 ORDER BY r.room_number, s.name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Students</h1>
        <p class="sub">Ladies Hostel — <?= count($students) ?> students</p>
        <div style="margin-bottom: 20px;">
            <a href="manage-students.php" class="btn">Manage Students</a>
        </div>
        <div class="section">
            <table>
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Room</th><th>Parent</th><th>Department</th><th>Year</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['student_id']) ?></td>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td><?= htmlspecialchars($s['room_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['parent_name'] ?? '-') ?><br><small style="color:#888"><?= htmlspecialchars($s['parent_phone'] ?? '') ?></small></td>
                        <td><?= htmlspecialchars($s['department'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['year'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
