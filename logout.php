<?php
<<<<<<< HEAD
session_start();
session_destroy();
header("Location: login.html");
exit();
?>
=======
require_once 'config/database.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'Logout dari sistem');
    
    // Destroy session
    session_destroy();
}

header("Location: login.php");
exit();
?>
>>>>>>> 94829e8a6f569c7a9dce94a4cdb8ec37192bc89c
