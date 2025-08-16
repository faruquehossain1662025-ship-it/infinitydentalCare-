<?php
require_once '../config/config.php';

// Redirect to login if not logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

header('Location: dashboard.php');
exit;
?>
