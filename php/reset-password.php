<?php
require_once 'config/db.php';
session_start();

$token = $_GET['token'] ?? '';
$message = '';
$message_type = 'info';
$token_valid = false;
$user_info = null;

if (empty($token)) {
    $message = 'No reset token provided. Please use the link sent to you.';
    $message_type = 'error';
} else {
    $pdo = getConnection();
    // Check student tokens first
    $stmt = $pdo->prepare("SELECT student_id, expires_at FROM password_reset_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $reset_data = $stmt->fetch();
    
    if ($reset_data) {
        $user_info = ['type' => 'student', 'id' => $reset_data['student_id'], 'expires_at' => $reset_data['expires_at']];
    } else {
        // If not a student, check staff tokens
        $stmt = $pdo->prepare("SELECT staff_id, expires_at FROM staff_password_reset_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $reset_data = $stmt->fetch();
        if ($reset_data) {
            $user_info = ['type' => 'staff', 'id' => $reset_data['staff_id'], 'expires_at' => $reset_data['expires_at']];
        }
    }

    if ($user_info) {
        if (strtotime($user_info['expires_at']) > time()) {
            $token_valid = true;
        } else {
            $message = 'This reset token has expired. Please request a new one.';
            $message_type = 'error';
        }
    } else {
        $message = 'Invalid reset token. Please check the link or request a new one.';
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($password) || strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'error';
    } elseif ($password !== $password_confirm) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $table_to_update = $user_info['type'] === 'student' ? 'students' : 'staff';
        $reset_table_to_delete = $user_info['type'] === 'student' ? 'password_reset_tokens' : 'staff_password_reset_tokens';

        $pdo->beginTransaction();
        try {
            $update_stmt = $pdo->prepare("UPDATE {$table_to_update} SET password_hash = ? WHERE id = ?");
            $update_stmt->execute([$password_hash, $user_info['id']]);

            $delete_stmt = $pdo->prepare("DELETE FROM {$reset_table_to_delete} WHERE token = ?");
            $delete_stmt->execute([$token]);
            
            $pdo->commit();

            $_SESSION['login_message'] = 'Password has been reset successfully. You can now log in with your new password.';
            header('Location: login.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Failed to update password. Please try again.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); color: #eee; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border-radius: 16px; padding: 40px; max-width: 400px; width: 100%; border: 1px solid rgba(255,255,255,0.1); }
        h1 { font-size: 1.5rem; margin-bottom: 8px; }
        .sub { color: #94a3b8; font-size: 0.9rem; margin-bottom: 24px; }
        label { display: block; margin-bottom: 6px; font-size: 0.9rem; }
        input[type="password"] { width: 100%; padding: 12px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: #fff; font-size: 1rem; margin-bottom: 16px; }
        .btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #e94560, #c23a51); border: none; border-radius: 10px; color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .message { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .message.error { background: rgba(233,69,96,0.2); border: 1px solid #e94560; color: #fca5a5; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Reset Your Password</h1>
        <p class="sub">Enter and confirm your new password.</p>
        <?php if ($message): ?><div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($token_valid): ?>
        <form method="POST">
            <label>New Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
            <label>Confirm New Password</label>
            <input type="password" name="password_confirm" placeholder="••••••••" required>
            <button type="submit" class="btn">Update Password</button>
        </form>
        <?php endif; ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="login.php" style="color: #94a3b8; font-size: 0.9rem; text-decoration: none;">&larr; Back to Login</a>
        </div>
    </div>
</body>
</html>