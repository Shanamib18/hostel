<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();
$hostelId = 1;
$roomsQuery = $pdo->prepare("
    SELECT 
        r.id, r.room_number, r.capacity,
        COUNT(s.id) as current_occupancy,
        GROUP_CONCAT(s.name SEPARATOR ', ') as occupants
    FROM rooms r
    LEFT JOIN students s ON r.id = s.room_id AND s.is_active = 1
    WHERE r.hostel_id = ?
    GROUP BY r.id, r.room_number, r.capacity
    ORDER BY r.room_number
");
$roomsQuery->execute([$hostelId]);
$rooms = $roomsQuery->fetchAll(PDO::FETCH_ASSOC);

$stats = ['occupied' => 0, 'available' => 0];
foreach ($rooms as $r) {
    $r['current_occupancy'] >= $r['capacity'] ? $stats['occupied']++ : $stats['available']++;
}
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
                    <tr><th>Room</th><th>Capacity</th><th>Occupancy</th><th>Status</th><th>Occupants</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['room_number']) ?></td>
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
