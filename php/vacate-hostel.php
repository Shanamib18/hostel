<?php
require_once 'auth.php';
requireLogin();

// This file is deprecated. Vacate requests are now handled in student-portal.php and approved by an admin.
header('Location: student-portal.php');
exit;
?>