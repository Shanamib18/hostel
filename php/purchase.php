<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

$pdo = getConnection();
$message = '';
$message_type = 'info';

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $message = "Purchase updated successfully.";
    $message_type = 'success';
}

// Ensure purchases table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(255) NOT NULL,
        bill_number VARCHAR(50),
        quantity DECIMAL(10,2) DEFAULT 1,
        amount DECIMAL(10,2) NOT NULL,
        purchase_date DATE NOT NULL,
        added_by_id INT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Ignore if table exists
}

// Ensure bill_number column exists for older tables
try {
    $pdo->exec("ALTER TABLE purchases ADD COLUMN bill_number VARCHAR(50) AFTER item_name");
} catch (PDOException $e) {}

// Ensure quantity column exists for older tables
try {
    $pdo->exec("ALTER TABLE purchases ADD COLUMN quantity DECIMAL(10,2) DEFAULT 1 AFTER bill_number");
} catch (PDOException $e) {}

// Ensure added_by_id column exists for older tables
try {
    $pdo->exec("ALTER TABLE purchases ADD COLUMN added_by_id INT AFTER purchase_date");
} catch (PDOException $e) {}

// Handle Add Purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $item_name = $_POST['item_name'] ?? '';
    $bill_number = $_POST['bill_number'] ?? '';
    $quantity = $_POST['quantity'] ?? 1;
    $amount = $_POST['amount'] ?? 0;
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $description = $_POST['description'] ?? '';
    
    if ($item_name && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO purchases (item_name, bill_number, quantity, amount, purchase_date, description, added_by_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$item_name, $bill_number, $quantity, $amount, $purchase_date, $description, $_SESSION['user_id']])) {
            $message = "Purchase recorded successfully.";
            $message_type = 'success';
        } else {
            $message = "Failed to record purchase."; 
            $message_type = 'error';
        }
    } else {
        $message = "Please provide item name and valid amount.";
        $message_type = 'error';
    }
}

// Handle Update Purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['id'];
    $item_name = $_POST['item_name'] ?? '';
    $bill_number = $_POST['bill_number'] ?? '';
    $quantity = $_POST['quantity'] ?? 1;
    $amount = $_POST['amount'] ?? 0;
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $description = $_POST['description'] ?? '';

    if ($item_name && $amount > 0 && $id) {
        $stmt = $pdo->prepare("UPDATE purchases SET item_name = ?, bill_number = ?, quantity = ?, amount = ?, purchase_date = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$item_name, $bill_number, $quantity, $amount, $purchase_date, $description, $id])) {
            header("Location: purchase.php?msg=updated");
            exit;
        } else {
            $message = "Failed to update purchase.";
            $message_type = 'error';
        }
    } else {
        $message = "Please provide item name and valid amount.";
        $message_type = 'error';
    }
}

// Handle Delete Purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM purchases WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Purchase deleted successfully.";
            $message_type = 'success';
        } else {
            $message = "Failed to delete purchase.";
            $message_type = 'error';
        }
    }
}

$period = $_GET['period'] ?? 'today';
$where_sql = "WHERE purchase_date = CURDATE()";
$period_label = "Today's";

if ($period === 'yesterday') {
    $where_sql = "WHERE purchase_date = CURDATE() - INTERVAL 1 DAY";
    $period_label = "Yesterday's";
} else if ($period === 'month') {
    $where_sql = "WHERE DATE_FORMAT(purchase_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
    $period_label = "This Month's";
} else if ($period === 'all') {
    $where_sql = "";
    $period_label = "All";
}

$periodTotal = $pdo->query("SELECT SUM(amount) FROM purchases $where_sql")->fetchColumn() ?: 0;
$overallTotal = $pdo->query("SELECT SUM(amount) FROM purchases")->fetchColumn() ?: 0;

$editPurchase = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editPurchase = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($editPurchase) {
    $purchases = $pdo->query("SELECT * FROM purchases ORDER BY purchase_date DESC, id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    $period_label_list = "Recent";
} else {
    $purchases = $pdo->query("SELECT * FROM purchases $where_sql ORDER BY purchase_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $period_label_list = $period_label;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .error { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        .btn-danger { background: #c0392b; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main">
        <h1><?= $editPurchase ? 'Edit Purchase' : 'Record Purchase' ?></h1>
        <p class="sub">Log hostel expenses and purchases.</p>

        <div class="actions" style="margin-bottom: 20px;">
            <a href="purchase.php?period=today" class="btn <?= ($period ?? 'today') === 'today' ? '' : 'btn-outline' ?>">Today</a>
            <a href="purchase.php?period=yesterday" class="btn <?= ($period ?? '') === 'yesterday' ? '' : 'btn-outline' ?>">Yesterday</a>
            <a href="purchase.php?period=month" class="btn <?= ($period ?? '') === 'month' ? '' : 'btn-outline' ?>">This Month</a>
            <a href="purchase.php?period=all" class="btn <?= ($period ?? '') === 'all' ? '' : 'btn-outline' ?>">All Time</a>
        </div>

        <div class="stats-grid" style="margin-bottom: 20px;">
            <div class="stat-card">
                <span class="stat-icon">₹</span>
                <div>
                    <strong>₹<?= number_format($periodTotal, 2) ?></strong>
                    <span><?= $period_label ?> Total</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">Σ</span>
                <div>
                    <strong>₹<?= number_format($overallTotal, 2) ?></strong>
                    <span>Overall Total</span>
                </div>
            </div>
        </div>

        <?php if ($message): ?><div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="section">
            <form method="POST" action="purchase.php">
                <input type="hidden" name="action" value="<?= $editPurchase ? 'update' : 'add' ?>">
                <?php if ($editPurchase): ?>
                    <input type="hidden" name="id" value="<?= $editPurchase['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" required placeholder="e.g., Vegetables, Cleaning Supplies" value="<?= htmlspecialchars($editPurchase['item_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Bill Number</label>
                    <input type="text" name="bill_number" placeholder="e.g., INV-12345" value="<?= htmlspecialchars($editPurchase['bill_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" step="0.01" value="<?= htmlspecialchars($editPurchase['quantity'] ?? '1') ?>" required placeholder="e.g., 5 or 2.5">
                </div>
                <div class="form-group">
                    <label>Amount (₹)</label>
                    <input type="number" name="amount" step="0.01" required placeholder="0.00" value="<?= htmlspecialchars($editPurchase['amount'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="purchase_date" value="<?= htmlspecialchars($editPurchase['purchase_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <input type="text" name="description" placeholder="Additional details..." value="<?= htmlspecialchars($editPurchase['description'] ?? '') ?>">
                </div>
                <button type="submit" class="btn" style="width: 100%;"><?= $editPurchase ? 'Update Purchase' : 'Save Purchase' ?></button>
                <?php if ($editPurchase): ?>
                    <a href="purchase.php" class="btn" style="width: 100%; margin-top: 10px; background: #7f8c8d; text-align: center; display: block;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="section">
            <h2><?= $period_label_list ?> Purchases (<?= count($purchases) ?>)</h2>
            <?php if (empty($purchases)): ?>
                <p style="color:var(--muted)">No purchases recorded for this period.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                    <thead><tr><th>Date</th><th>Bill No</th><th>Item</th><th>Qty</th><th>Description</th><th>Amount</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($purchases as $p): ?>
                        <tr>
                            <td><?= date('d M, Y', strtotime($p['purchase_date'])) ?></td>
                            <td><?= htmlspecialchars($p['bill_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['item_name']) ?></td>
                            <td><?= rtrim(rtrim(htmlspecialchars($p['quantity'] ?? '1'), '0'), '.') ?></td>
                            <td><?= htmlspecialchars($p['description'] ?? '-') ?></td>
                            <td>₹<?= number_format($p['amount'], 2) ?></td>
                            <td>
                                <a href="?edit=<?= $p['id'] ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem; background-color: #2980b9; display: inline-block; margin-right: 5px;">Edit</a>
                                <form method="POST" onsubmit="return confirm('Delete this purchase record?');" style="display: inline-block;">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>