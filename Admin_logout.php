<?php
// ✅ Admin ke liye specific session use karo
session_name("admin_session");
session_start();

// ✅ Admin session clear karo
$_SESSION = [];
session_unset();
session_destroy();

// ✅ Cache clear (back button issue avoid karne ke liye)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// ✅ Redirect admin ko wapas login page pe bhejo
header("Location: frontpage.php");
exit();
?>
