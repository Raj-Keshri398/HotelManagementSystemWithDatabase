<?php
session_name("admin_session"); 
session_start();

// Agar already login hai → dashboard bhej do
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header("Location: Admin_dashboard.php");
    exit();
}

// Hardcoded credentials (sirf check ke liye)
$admin_email = "admin123@gmail.com";
$admin_pass  = "1234";

// Form submit hone par check
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['login_user']);
    $pass  = trim($_POST['login_pass']);

    if ($email === $admin_email && $pass === $admin_pass) {
        // ✅ Secure session set
        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_email'] = $email;   // 👈 jo user ne dala wahi save hoga
        $_SESSION['admin_name']  = "Super Admin";

        header("Location: Admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid Email or Password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(45deg, #ff9a9e, #fad0c4);
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}
.card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    width: 100%;
    max-width: 400px;
    padding: 20px;
}
</style>
</head>
<body>
<div class="card">
    <h4 class="text-center mb-3">Admin Login</h4>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <input type="email" name="login_user" class="form-control" placeholder="Email" required>
        </div>
        <div class="mb-3">
            <input type="password" name="login_pass" class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-dark w-100">Login</button>
    </form>
</div>
</body>
</html>
