<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session destroy
$_SESSION = array();
session_destroy();

// Cache clear headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login
header("Location: index.php");
exit();
?>

