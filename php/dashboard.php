<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();
$hostelId = 1;

// Stats
$roomStats = $pdo->query("SELECT COUNT(r.id) as total, SUM(CASE WHEN COALESCE(s.current_occupancy, 0) >= r.capacity THEN 1 ELSE 0 END) as occupied FROM rooms r LEFT JOIN (SELECT room_id, COUNT(id) as current_occupancy FROM students WHERE is_active = 1 GROUP BY room_id) s ON r.id = s.room_id WHERE r.hostel_id = $hostelId")->fetch();
$todayEntry = $pdo->query("SELECT COUNT(*) as c FROM entry_exit_logs e JOIN students s ON e.student_id = s.id WHERE s.hostel_id = $hostelId AND e.type = 'entry' AND DATE(e.recorded_at) = CURDATE()")->fetch()['c'];
$todayExit = $pdo->query("SELECT COUNT(*) as c FROM entry_exit_logs e JOIN students s ON e.student_id = s.id WHERE s.hostel_id = $hostelId AND e.type = 'exit' AND DATE(e.recorded_at) = CURDATE()")->fetch()['c'];
$messStats = $pdo->query("SELECT meal_type, COUNT(*) as c FROM mess_attendance m JOIN students s ON m.student_id = s.id WHERE s.hostel_id = $hostelId AND DATE(m.marked_at) = CURDATE() GROUP BY meal_type")->fetchAll(PDO::FETCH_KEY_PAIR);
try {
    $paymentPending = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM monthly_bills m JOIN students s ON m.student_id = s.id WHERE s.hostel_id = $hostelId AND m.status IN ('pending', 'submitted')")->fetch()['total'];
    $paymentRequests = $pdo->query("SELECT COUNT(*) as c FROM monthly_bills WHERE status = 'submitted'")->fetch()['c'];
} catch (PDOException $e) {
    $paymentPending = 0;
    $paymentRequests = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Staff Dashboard</h1>
        <p class="sub">Ladies Hostel LBSCEK — Overview</p>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">🏠</span>
                <div>
                    <strong><?= $roomStats['occupied'] ?? 0 ?></strong> / <?= $roomStats['total'] ?? 0 ?>
                    <span>Rooms Occupied</span>
                </div>
            </div>
            <div class="stat-card" onclick="location.href='attendance.php?mode=entry'" style="cursor:pointer">
                <span class="stat-icon">↗</span>
                <div>
                    <strong><?= $todayEntry ?></strong>
                    <span>Entries Today</span>
                </div>
            </div>
            <div class="stat-card" onclick="location.href='attendance.php?mode=exit'" style="cursor:pointer">
                <span class="stat-icon">↘</span>
                <div>
                    <strong><?= $todayExit ?></strong>
                    <span>Exits Today</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">🍽</span>
                <div>
                    <strong>B:<?= $messStats['breakfast'] ?? 0 ?> L:<?= $messStats['lunch'] ?? 0 ?> D:<?= $messStats['dinner'] ?? 0 ?></strong>
                    <span>Mess (B/L/D)</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">₹</span>
                <div>
                    <strong>₹<?= number_format($paymentPending, 0) ?></strong>
                    <span>Pending Payments</span>
                </div>
            </div>
            <div class="stat-card" onclick="location.href='admin-payments.php'" style="cursor:pointer">
                <span class="stat-icon">🔔</span>
                <div>
                    <strong><?= $paymentRequests ?></strong>
                    <span>Payment Requests</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Quick Actions</h2>
            <div class="actions">
                <a href="attendance.php?mode=mess" class="btn">Mark Mess Attendance</a>
                <a href="attendance.php?mode=entry" class="btn">Record Entry</a>
                <a href="attendance.php?mode=exit" class="btn">Record Exit</a>
                <a href="manage-students.php" class="btn">Manage Students (Add/Remove)</a>
                <a href="students.php" class="btn btn-outline">View Students</a>
                <a href="rooms.php" class="btn btn-outline">Room Occupancy</a>
                <a href="payments.php" class="btn btn-outline">Payments</a>
                <a href="admin-fees.php" class="btn btn-outline">Generate Monthly Dues</a>
                <a href="admin-payments.php" class="btn btn-outline">Payment Requests</a>
                <a href="mess-cut-list.php" class="btn btn-outline">Mess Cut List</a>
                <a href="admin-vacate-requests.php" class="btn btn-outline">Vacate Requests</a>
                <a href="vacated-students.php" class="btn btn-outline">Vacated Students</a>
                <a href="purchase.php" class="btn btn-outline">Purchase</a>
            </div>
        </div>
    </main>
</body>
</html>
