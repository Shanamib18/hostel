<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();
$hostelId = 1;
$rooms = $pdo->query("SELECT r.*, (SELECT GROUP_CONCAT(s.name SEPARATOR ', ') FROM students s WHERE s.room_id = r.id AND s.is_active = 1) as occupants FROM rooms r WHERE r.hostel_id = $hostelId ORDER BY r.floor, r.room_number")->fetchAll(PDO::FETCH_ASSOC);
$stats = $pdo->query("SELECT 
    SUM(CASE WHEN current_occupancy >= capacity THEN 1 ELSE 0 END) as occupied,
    SUM(CASE WHEN current_occupancy < capacity THEN 1 ELSE 0 END) as available
FROM rooms WHERE hostel_id = $hostelId")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Room Occupancy</h1>
        <p class="sub">Ladies Hostel — Occupancy overview</p>
        <div class="stats-grid" style="margin-bottom:24px">
            <div class="stat-card"><span class="stat-icon">✓</span><div><strong><?= $stats['occupied'] ?? 0 ?></strong><span>Occupied</span></div></div>
            <div class="stat-card"><span class="stat-icon">○</span><div><strong><?= $stats['available'] ?? 0 ?></strong><span>Available</span></div></div>
        </div>
        <div class="section">
            <table>
                <thead>
                    <tr><th>Room</th><th>Floor</th><th>Capacity</th><th>Occupancy</th><th>Status</th><th>Occupants</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['room_number']) ?></td>
                        <td><?= htmlspecialchars($r['floor'] ?? '-') ?></td>
                        <td><?= $r['capacity'] ?></td>
                        <td><?= $r['current_occupancy'] ?>/<?= $r['capacity'] ?></td>
                        <td><?= $r['current_occupancy'] >= $r['capacity'] ? 'Occupied' : 'Available' ?></td>
                        <td><?= htmlspecialchars($r['occupants'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
