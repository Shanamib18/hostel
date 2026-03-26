<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireStaff() {
    requireLogin();
    if ($_SESSION['user_type'] !== 'staff') {
        header('HTTP/1.1 403 Forbidden');
        echo 'Staff access required';
        exit;
    }
}
