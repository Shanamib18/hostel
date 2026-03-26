<?php
require_once 'config/db.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $dest = ($_SESSION['user_type'] ?? 'staff') === 'student' ? 'student-portal.php' : 'dashboard.php';
    header('Location: ' . $dest);
    exit;
}

$error = '';
$success = '';

if (isset($_SESSION['login_message'])) {
    $success = $_SESSION['login_message'];
    unset($_SESSION['login_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $type = $_POST['type'] ?? 'staff';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $pdo = getConnection();
        
        if ($type === 'student') {
            $sql = "SELECT id, name, email, password_hash, is_active FROM students WHERE email = ?";
        } elseif ($type === 'mess_secy') {
            // Ensure mess_secretaries table exists
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS mess_secretaries (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            } catch (PDOException $e) {}
            $sql = "SELECT id, name, email, password_hash, 'mess_secy' as role FROM mess_secretaries WHERE email = ?";
        } else {
            $sql = "SELECT id, name, email, password_hash, role FROM staff WHERE email = ?";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($type === 'student' && !$user['is_active']) {
                $error = 'Your account has been deactivated. Please contact the admin.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = ($type === 'student') ? 'student' : 'staff';
                if ($type !== 'student') {
                    $_SESSION['user_role'] = $user['role'];
                }
                
                if ($type === 'student') {
                    header('Location: student-portal.php');
                } elseif (($user['role'] ?? '') === 'mess_secy') {
                    header('Location: mess-secretary-dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); color: #eee; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border-radius: 16px; padding: 40px; max-width: 400px; width: 100%; border: 1px solid rgba(255,255,255,0.1); }
        h1 { font-size: 1.5rem; margin-bottom: 8px; }
        .sub { color: #94a3b8; font-size: 0.9rem; margin-bottom: 24px; }
        label { display: block; margin-bottom: 6px; font-size: 0.9rem; }
        input[type="email"], input[type="password"] { width: 100%; padding: 12px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: #fff; font-size: 1rem; margin-bottom: 16px; }
        input::placeholder { color: #64748b; }
        .type-toggle { display: flex; gap: 12px; margin-bottom: 20px; }
        .type-toggle label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .type-toggle input { margin: 0; width: auto; }
        .btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #e94560, #c23a51); border: none; border-radius: 10px; color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .btn:hover { opacity: 0.95; }
        .error { background: rgba(233,69,96,0.2); border: 1px solid #e94560; color: #fca5a5; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .forgot-password { text-align: right; margin-top: -8px; margin-bottom: 16px; }
        .success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .forgot-password a { color: #94a3b8; font-size: 0.85rem; text-decoration: none; }
        .forgot-password a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Ladies Hostel LBSCEK</h1>
        <p class="sub">Hostel Management System</p>
        <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="type-toggle">
                <label><input type="radio" name="type" value="staff" checked> Staff</label>
                <label><input type="radio" name="type" value="student"> Student</label>
                <label><input type="radio" name="type" value="mess_secy"> Mess Secretary</label>
            </div>
            <label>Email</label>
            <input type="email" name="email" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
            <div class="forgot-password">
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
    </div>
</body>
</html>
