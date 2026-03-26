<?php
require_once 'config/db.php';
session_start();

// Ensure only Mess Secretary can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mess_secy') {
    header('Location: login.php');
    exit;
}

$pdo = getConnection();

// Ensure mess_cut_requests table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS mess_cut_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)");

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['req_id'], $_POST['status'])) {
        $id = (int)$_POST['req_id'];
        $status = $_POST['status']; 
        if (in_array($status, ['approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE mess_cut_requests SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                $message = "Request marked as " . ucfirst($status);
                $message_type = 'success';
            } else {
                $message = "Failed to update status.";
                $message_type = 'error';
            }
        }
    }
}

// Fetch Pending Requests
$pending = $pdo->query("
    SELECT m.*, s.name, s.student_id as roll 
    FROM mess_cut_requests m 
    JOIN students s ON m.student_id = s.id 
    WHERE m.status = 'pending' 
    ORDER BY m.start_date ASC
")->fetchAll();

// Fetch History (Recent 10)
$history = $pdo->query("
    SELECT m.*, s.name, s.student_id as roll 
    FROM mess_cut_requests m 
    JOIN students s ON m.student_id = s.id 
    WHERE m.status != 'pending' 
    ORDER BY m.id DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Secretary Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #1a1a2e; color: #eee; padding: 20px; display: flex; justify-content: center; }
        .container { max-width: 900px; width: 100%; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .logo { font-size: 1.4rem; font-weight: 700; color: #e94560; }
        .logout { color: #94a3b8; text-decoration: none; padding: 6px 12px; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; }
        .card { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05); }
        h2 { font-size: 1.2rem; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: #94a3b8; font-weight: 500; font-size: 0.9rem; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; margin-right: 5px; font-weight: 600; }
        .btn-approve { background: #2ecc71; }
        .btn-reject { background: #e74c3c; }
        .status-approved { color: #2ecc71; } .status-rejected { color: #e74c3c; }
        .message { padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .message.success { background: rgba(46,204,113,0.2); border: 1px solid #2ecc71; color: #2ecc71; }
        .message.error { background: rgba(231,76,60,0.2); border: 1px solid #e74c3c; color: #e74c3c; }
        .empty { color: #64748b; text-align: center; padding: 20px; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">Mess Portal</div>
            <div><?= htmlspecialchars($_SESSION['user_name']) ?> <a href="logout.php" class="logout">Logout</a></div>
        </header>
        <h1>Verify Mess Cuts</h1>
        <?php if($message): ?><div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        
        <div class="card">
            <h2>Pending Requests</h2>
            <?php if(empty($pending)): ?><div class="empty">No pending requests.</div><?php else: ?>
            <table>
                <thead><tr><th>Student</th><th>Dates</th><th>Days</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach($pending as $req): $days = (strtotime($req['end_date']) - strtotime($req['start_date']))/86400 + 1; ?>
                    <tr>
                        <td><?= htmlspecialchars($req['name']) ?> <br><small style="color:#94a3b8"><?= htmlspecialchars($req['roll']) ?></small></td>
                        <td><?= date('d M', strtotime($req['start_date'])) ?> - <?= date('d M', strtotime($req['end_date'])) ?></td>
                        <td><?= $days ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="req_id" value="<?= $req['id'] ?>">
                                <button name="status" value="approved" class="btn btn-approve">✓</button>
                                <button name="status" value="rejected" class="btn btn-reject">✗</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Recent History</h2>
            <?php if(empty($history)): ?><div class="empty">No recent history.</div><?php else: ?>
            <table>
                <thead><tr><th>Student</th><th>Dates</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach($history as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['name']) ?></td>
                        <td><?= date('d M', strtotime($req['start_date'])) ?> - <?= date('d M', strtotime($req['end_date'])) ?></td>
                        <td><span class="status-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>