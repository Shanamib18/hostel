<header class="header">
    <div class="logo">LH LBSCEK</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="attendance.php">Attendance</a>
        <a href="students.php">Students</a>
        <a href="rooms.php">Rooms</a>
        <a href="admin-payments.php">Payments</a>
    </nav>
    <div class="user">
        <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
        <a href="logout.php">Logout</a>
    </div>
</header>
