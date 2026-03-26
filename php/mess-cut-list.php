<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();

// Filtering
$status_filter = $_GET['status'] ?? 'all';
$month_filter = $_GET['month'] ?? '';

$where_clauses = [];
$params = [];

if ($status_filter !== 'all' && in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $where_clauses[] = "m.status = ?";
    $params[] = $status_filter;
}

if ($month_filter) {
    $where_clauses[] = "DATE_FORMAT(m.start_date, '%Y-%m') = ?";
    $params[] = $month_filter;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Fetch Requests
$stmt = $pdo->prepare("
    SELECT m.*, s.name, s.student_id as admission_no 
    FROM mess_cut_requests m 
    JOIN students s ON m.student_id = s.id 
    $where_sql
    ORDER BY m.start_date DESC, m.id DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Fetch distinct months for filter dropdown
$months = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(start_date, '%Y-%m') as month 
    FROM mess_cut_requests 
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Cut List - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .status-pending { color: #f1c40f; }
        .status-approved { color: #2ecc71; }
        .status-rejected { color: #e74c3c; }
        .filter-form { display: flex; gap: 16px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1>Mess Cut List</h1>
        <p class="sub">View all student mess cut requests.</p>

        <div class="section">
            <form method="GET" class="filter-form">
                <div class="form-group"><label for="month">Month</label><select name="month" id="month" onchange="this.form.submit()"><option value="">All Months</option><?php foreach ($months as $month): ?><option value="<?= htmlspecialchars($month) ?>" <?= ($month_filter === $month) ? 'selected' : '' ?>><?= date('F Y', strtotime($month . '-01')) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="status">Status</label><select name="status" id="status" onchange="this.form.submit()"><option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>All</option><option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>Pending</option><option value="approved" <?= ($status_filter === 'approved') ? 'selected' : '' ?>>Approved</option><option value="rejected" <?= ($status_filter === 'rejected') ? 'selected' : '' ?>>Rejected</option></select></div>
                <a href="mess-cut-list.php" class="btn btn-outline" style="align-self: end;">Clear Filters</a>
            </form>

            <?php if (empty($requests)): ?>
                <p style="color:var(--muted)">No mess cut requests found for the selected filters.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Student ID</th><th>Name</th><th>Date (from - to)</th><th>Number of Days</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($requests as $r): $days = (strtotime($r['end_date']) - strtotime($r['start_date'])) / 86400 + 1; ?>
                        <tr><td><?= htmlspecialchars($r['admission_no']) ?></td><td><?= htmlspecialchars($r['name']) ?></td><td><?= date('d M Y', strtotime($r['start_date'])) ?> - <?= date('d M Y', strtotime($r['end_date'])) ?></td><td><?= $days ?></td><td><span class="status-<?= strtolower($r['status']) ?>"><?= ucfirst(htmlspecialchars($r['status'])) ?></span></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>