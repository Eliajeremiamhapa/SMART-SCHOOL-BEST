<?php
// ACCOUNTANT/includes/header.php
require_once '../../config/database.php';

// Only accountant can access accountant folder
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
    header('Location: ../login.php');
    exit();
}
?>