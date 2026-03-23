<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    // Not logged in, redirect to login
    header("Location: admin_login.php");
    exit();
}
?>