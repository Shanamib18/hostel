<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();
$hostelId = 1;
$mode = $_GET['mode'] ?? 'mess';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $mealType = $_POST['meal_type'] ?? '';
    $entryType = $_POST['entry_type'] ?? '';

    if ($mode === 'mess' && $studentId && in_array($mealType, ['breakfast','lunch','dinner'])) {
        $pdo->prepare("INSERT INTO mess_attendance (student_id, meal_type, method, verified) VALUES (?, ?, 'manual', 0) ON DUPLICATE KEY UPDATE marked_at = NOW()")->execute([$studentId, $mealType]);
        $message = 'Mess attendance marked successfully.';
    } elseif (in_array($mode, ['entry','exit']) && $studentId) {
        $type = $mode ?: $entryType;
        $pdo->prepare("INSERT INTO entry_exit_logs (student_id, type, method, verified) VALUES (?, ?, 'manual', 0)")->execute([$studentId, $type]);
        $message = ucfirst($type) . ' recorded successfully.';
    } else {
        $message = 'Invalid input.';
    }
}

$students = $pdo->query("SELECT id, student_id, name FROM students WHERE hostel_id = $hostelId AND is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$logs = [];
if ($mode === 'mess') {
    $logs = $pdo->query("SELECT m.marked_at as time, m.meal_type as info, s.name, s.student_id FROM mess_attendance m JOIN students s ON m.student_id = s.id WHERE s.hostel_id = $hostelId AND DATE(m.marked_at) = CURDATE() ORDER BY m.marked_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} elseif (in_array($mode, ['entry', 'exit'])) {
    $logs = $pdo->query("SELECT e.recorded_at as time, e.type as info, s.name, s.student_id FROM entry_exit_logs e JOIN students s ON e.student_id = s.id WHERE s.hostel_id = $hostelId AND e.type = '$mode' AND DATE(e.recorded_at) = CURDATE() ORDER BY e.recorded_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Attendance</h1>
        <p class="sub"><?= $mode === 'mess' ? 'Mark mess attendance' : "Record $mode" ?></p>
        <?php if ($message): ?><p style="margin-bottom:16px;color:#4ade80"><?= htmlspecialchars($message) ?></p><?php endif; ?>

        <div class="section" style="margin-bottom: 20px; padding: 15px;">
            <div class="actions" style="justify-content: flex-start;">
                <a href="?mode=mess" class="btn <?= $mode === 'mess' ? '' : 'btn-outline' ?>">Mess Log</a>
                <a href="?mode=entry" class="btn <?= $mode === 'entry' ? '' : 'btn-outline' ?>">Entry List</a>
                <a href="?mode=exit" class="btn <?= $mode === 'exit' ? '' : 'btn-outline' ?>">Exit List</a>
            </div>
        </div>

        <div class="section">
            <form method="POST" action="?mode=<?= htmlspecialchars($mode) ?>">
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['student_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($mode === 'mess'): ?>
                <div class="form-group">
                    <label>Meal</label>
                    <select name="meal_type" required>
                        <option value="breakfast">Breakfast</option>
                        <option value="lunch">Lunch</option>
                        <option value="dinner">Dinner</option>
                    </select>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn">Submit</button>
            </form>
        </div>

        <div class="section">
            <h2><?= $mode === 'mess' ? 'Recent Activity' : ($mode === 'entry' ? "Today's Entries" : "Today's Exits") ?> (<?= date('d M') ?>)</h2>
            <?php if (empty($logs)): ?>
                <p style="color:var(--muted)">No records for today.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Student Name</th><th>Info</th><th>Time</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['name']) ?> (<?= htmlspecialchars($l['student_id']) ?>)</td>
                        <td><?= htmlspecialchars(ucfirst($l['info'])) ?></td>
                        <td><?= date('H:i', strtotime($l['time'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>
