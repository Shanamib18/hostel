<?php
require_once 'config/db.php';
require_once 'auth.php';
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
requireStaff();

$pdo = getConnection();
$message = '';
$error = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $message = "Student updated successfully.";
}

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    $student_id = trim($_POST['student_id']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $parent_name = trim($_POST['parent_name']);
    $parent_phone = trim($_POST['parent_phone']);
    $department = trim($_POST['department']);
    $year = trim($_POST['year']);
    $room_number = trim($_POST['room_number']);
    
    // Basic validation
    if (empty($name) || empty($student_id) || empty($email) || empty($room_number)) {
        $error = "Name, Student ID, Email, and Room Number are required.";
    } else {
        // Check if room exists
        $stmt = $pdo->prepare("SELECT id, capacity, current_occupancy FROM rooms WHERE room_number = ? LIMIT 1");
        $stmt->execute([$room_number]);
        $room = $stmt->fetch();

        if ($room) {
            if ($room['current_occupancy'] >= $room['capacity']) {
                $error = "Room '$room_number' is already full (Capacity: {$room['capacity']}).";
            } else {
                try {
                    $pdo->beginTransaction();
                    // Default password: password123
                    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
                    
                    $sql = "INSERT INTO students (hostel_id, room_id, student_id, name, email, password_hash, phone, parent_name, parent_phone, department, year) 
                            VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$room['id'], $student_id, $name, $email, $password_hash, $phone, $parent_name, $parent_phone, $department, $year]);
                    
                    // Update room occupancy
                    $pdo->prepare("UPDATE rooms SET current_occupancy = current_occupancy + 1 WHERE id = ?")->execute([$room['id']]);
                    $pdo->commit();
                    
                    $message = "Student added successfully.";
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error = "Error: Student ID or Email already exists.";
                    } else {
                        $error = "Database error: " . $e->getMessage();
                    }
                }
            }
        } else {
            $error = "Room number '$room_number' does not exist.";
        }
    }
}

// Handle Update Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $student_id = trim($_POST['student_id']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $parent_name = trim($_POST['parent_name']);
    $parent_phone = trim($_POST['parent_phone']);
    $department = trim($_POST['department']);
    $year = trim($_POST['year']);
    $room_number = trim($_POST['room_number']);
    $old_room_id = $_POST['old_room_id'];

    if (empty($name) || empty($student_id) || empty($email) || empty($room_number)) {
        $error = "Name, Student ID, Email, and Room Number are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, capacity, current_occupancy FROM rooms WHERE room_number = ? LIMIT 1");
        $stmt->execute([$room_number]);
        $room = $stmt->fetch();

        if ($room) {
            $roomChanged = ($room['id'] != $old_room_id);
            if ($roomChanged && $room['current_occupancy'] >= $room['capacity']) {
                $error = "Room '$room_number' is already full (Capacity: {$room['capacity']}).";
            } else {
                try {
                    $pdo->beginTransaction();
                    $sql = "UPDATE students SET room_id = ?, student_id = ?, name = ?, email = ?, phone = ?, parent_name = ?, parent_phone = ?, department = ?, year = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$room['id'], $student_id, $name, $email, $phone, $parent_name, $parent_phone, $department, $year, $id]);
                    
                    if ($roomChanged) {
                        $pdo->prepare("UPDATE rooms SET current_occupancy = current_occupancy + 1 WHERE id = ?")->execute([$room['id']]);
                        if ($old_room_id) {
                            $pdo->prepare("UPDATE rooms SET current_occupancy = GREATEST(current_occupancy - 1, 0) WHERE id = ?")->execute([$old_room_id]);
                        }
                    }
                    $pdo->commit();
                    header("Location: manage-students.php?msg=updated");
                    exit;
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = "Database error: " . $e->getMessage();
                }
            }
        } else {
            $error = "Room number '$room_number' does not exist.";
        }
    }
}

// Handle Remove Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['student_id_db'];
    try {
        $pdo->beginTransaction();
        // Get student's room to update occupancy
        $stmt = $pdo->prepare("SELECT room_id FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $student = $stmt->fetch();

        // Delete related records first to avoid Foreign Key errors
        $pdo->prepare("DELETE FROM mess_attendance WHERE student_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM entry_exit_logs WHERE student_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM fee_payments WHERE student_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM password_reset_tokens WHERE student_id = ?")->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($student && $student['room_id']) {
            $pdo->prepare("UPDATE rooms SET current_occupancy = GREATEST(current_occupancy - 1, 0) WHERE id = ?")->execute([$student['room_id']]);
        }
        $pdo->commit();
        $message = "Student removed successfully.";
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Error removing student (check for related records like attendance): " . $e->getMessage();
    }
}

// Fetch all students
$stmt = $pdo->query("SELECT s.*, r.room_number FROM students s LEFT JOIN rooms r ON s.room_id = r.id ORDER BY s.id DESC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editStudent = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT s.*, r.room_number FROM students s LEFT JOIN rooms r ON s.room_id = r.id WHERE s.id = ?");
    $stmt->execute([$_GET['edit']]);
    $editStudent = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students - LBSCEK Hostel</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #e94560; --bg: #1a1a2e; --card: #16213e; --text: #eee; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h1 { font-size: 1.8rem; }
        .btn { padding: 10px 20px; background: var(--primary); border: none; border-radius: 6px; color: #fff; cursor: pointer; text-decoration: none; font-size: 0.9rem; }
        .btn:hover { opacity: 0.9; }
        .btn-danger { background: #c0392b; }
        
        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        
        .card { background: var(--card); padding: 25px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); }
        .card h2 { margin-bottom: 20px; font-size: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-size: 0.9rem; color: #aaa; }
        input { width: 100%; padding: 10px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #fff; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
        th { color: #aaa; font-weight: 500; }
        tr:hover { background: rgba(255,255,255,0.02); }
        
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .alert-error { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
        
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Students (Updated)</h1>
            <a href="dashboard.php" class="btn" style="background: transparent; border: 1px solid #aaa;">&larr; Back to Dashboard</a>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="grid">
            <!-- Add Student Form -->
            <div class="card">
                <h2><?= $editStudent ? 'Edit Student' : 'Add New Student' ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $editStudent ? 'update' : 'add' ?>">
                    <?php if ($editStudent): ?>
                        <input type="hidden" name="id" value="<?= $editStudent['id'] ?>">
                        <input type="hidden" name="old_room_id" value="<?= $editStudent['room_id'] ?>">
                    <?php endif; ?>
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" required value="<?= htmlspecialchars($editStudent['name'] ?? '') ?>" placeholder="e.g. Jane Doe"></div>
                    <div class="form-group"><label>Student ID</label><input type="text" name="student_id" required value="<?= htmlspecialchars($editStudent['student_id'] ?? '') ?>" placeholder="e.g. STU001"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?= htmlspecialchars($editStudent['email'] ?? '') ?>" placeholder="student@lbscek.ac.in"></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" value="<?= htmlspecialchars($editStudent['phone'] ?? '') ?>" placeholder="e.g. 9876543210"></div>
                    <div class="form-group"><label>Parent Name</label><input type="text" name="parent_name" value="<?= htmlspecialchars($editStudent['parent_name'] ?? '') ?>" placeholder="Parent/Guardian Name"></div>
                    <div class="form-group"><label>Parent Phone</label><input type="text" name="parent_phone" value="<?= htmlspecialchars($editStudent['parent_phone'] ?? '') ?>" placeholder="Parent Phone Number"></div>
                    <div class="form-group"><label>Department</label><input type="text" name="department" value="<?= htmlspecialchars($editStudent['department'] ?? '') ?>" placeholder="e.g. CSE"></div>
                    <div class="form-group"><label>Year</label><input type="number" name="year" value="<?= htmlspecialchars($editStudent['year'] ?? '') ?>" placeholder="e.g. 1, 2, 3, 4" min="1" max="5"></div>
                    <div class="form-group"><label>Room Number</label><input type="text" name="room_number" required value="<?= htmlspecialchars($editStudent['room_number'] ?? '') ?>" placeholder="e.g. 1-01"></div>
                    <button type="submit" class="btn" style="width: 100%"><?= $editStudent ? 'Update Student' : 'Add Student' ?></button>
                    <?php if ($editStudent): ?><a href="manage-students.php" class="btn" style="width: 100%; margin-top: 10px; background: #7f8c8d; text-align: center; display: block;">Cancel</a><?php endif; ?>
                </form>
            </div>

            <!-- Student List -->
            <div class="card">
                <h2>Student List</h2>
                <div style="overflow-x: auto;">
                    <table>
                        <thead><tr><th>ID</th><th>Name</th><th>Room</th><th>Parent</th><th>Dept/Year</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['student_id']) ?></td>
                                <td><?= htmlspecialchars($s['name']) ?><br><small style="color:#888"><?= htmlspecialchars($s['phone'] ?? '') ?></small></td>
                                <td><?= htmlspecialchars($s['room_number'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($s['parent_name'] ?? '-') ?><br><small style="color:#888"><?= htmlspecialchars($s['parent_phone'] ?? '') ?></small></td>
                                <td><?= htmlspecialchars($s['department'] ?? '-') ?> - <?= htmlspecialchars($s['year'] ?? '-') ?></td>
                                <td>
                                    <a href="?edit=<?= $s['id'] ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem; background-color: #2980b9; margin-right: 5px; display: inline-block;">Edit</a>
                                    <form method="POST" onsubmit="return confirm('Remove this student?');" style="display: inline-block;">
                                        <input type="hidden" name="action" value="delete"><input type="hidden" name="student_id_db" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem; vertical-align: top;">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>