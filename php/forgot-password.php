<?php
require_once 'config/db.php';
session_start();

$message = '';
$message_type = 'info';
$show_form = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $type = $_POST['type'] ?? 'staff';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'A valid email address is required.';
        $message_type = 'error';
    } else {
        $pdo = getConnection();
        
        $user = null;
        $user_id = null;
        $reset_table = '';
        $id_column = '';

        if ($type === 'staff') {
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $user_id = $user['id'];
                $reset_table = 'staff_password_reset_tokens';
                $id_column = 'staff_id';
                // Ensure staff reset table exists
                $pdo->exec("CREATE TABLE IF NOT EXISTS `staff_password_reset_tokens` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `staff_id` INT NOT NULL,
                    `token` VARCHAR(255) NOT NULL UNIQUE,
                    `expires_at` DATETIME NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE
                )");
            }
        } else { // student
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $user_id = $user['id'];
                $reset_table = 'password_reset_tokens';
                $id_column = 'student_id';
            }
        }

        // To prevent email enumeration, always show a generic success message.
        // Only generate a token if the user was actually found.
        if ($user) {
            try {
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now

                $sql = "INSERT INTO {$reset_table} ({$id_column}, token, expires_at) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $token, $expires_at]);

                // For development, we store the link in the session to display it.
                // In a production environment, you would email this link.
                $_SESSION['dev_reset_link'] = "http://localhost/hostel/php/reset-password.php?token=" . $token;

            } catch (Exception $e) {
                // Silently fail if DB error occurs, to not expose system details.
            }
        }
        
        $_SESSION['status_message'] = "If an account with that email exists, a password reset link has been generated.";
        header('Location: forgot-password.php?status=sent');
        exit;
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'sent' && isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    $message_type = 'success';
    $show_form = false;
    unset($_SESSION['status_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); color: #eee; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border-radius: 16px; padding: 40px; max-width: 400px; width: 100%; border: 1px solid rgba(255,255,255,0.1); }
        h1 { font-size: 1.5rem; margin-bottom: 8px; }
        .sub { color: #94a3b8; font-size: 0.9rem; margin-bottom: 24px; }
        label { display: block; margin-bottom: 6px; font-size: 0.9rem; }
        input[type="email"] { width: 100%; padding: 12px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: #fff; font-size: 1rem; margin-bottom: 16px; }
        .type-toggle { display: flex; gap: 12px; margin-bottom: 20px; }
        .type-toggle label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #e94560, #c23a51); border: none; border-radius: 10px; color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .message { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; line-height: 1.5; }
        .message.success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .message.error { background: rgba(233,69,96,0.2); border: 1px solid #e94560; color: #fca5a5; }
        .dev-link { padding: 15px; background: rgba(0,0,0,0.3); border-radius: 8px; word-wrap: break-word; margin-top: 15px; font-family: monospace; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Forgot Password</h1>
        <p class="sub">Enter your email to receive a reset link.</p>
        <?php if ($message): ?><div class="message <?= $message_type ?>"><?= $message ?></div><?php endif; ?>
        <?php if (isset($_SESSION['dev_reset_link'])): ?>
            <p style="font-size:0.9rem; color: #f1c40f;">For development: The reset link is shown below.</p>
            <div class="dev-link"><?= htmlspecialchars($_SESSION['dev_reset_link']) ?></div>
            <?php unset($_SESSION['dev_reset_link']); ?>
        <?php endif; ?>
        <?php if ($show_form): ?>
        <form method="POST">
            <div class="type-toggle">
                <label><input type="radio" name="type" value="staff" checked> Staff</label>
                <label><input type="radio" name="type" value="student"> Student</label>
            </div>
            <label>Email Address</label>
            <input type="email" name="email" placeholder="your@email.com" required>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
        <?php endif; ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="login.php" style="color: #94a3b8; font-size: 0.9rem; text-decoration: none;">&larr; Back to Login</a>
        </div>
    </div>
</body>
</html>