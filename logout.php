<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'Logout dari sistem');
    
    // Destroy session
    session_destroy();
}

header("Location: login.php");
exit();
?>