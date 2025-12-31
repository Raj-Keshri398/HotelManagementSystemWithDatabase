<?php 
session_start();
include 'connect.php';

// ✅ Database connection wrapper
class connect_fixed extends connect {
    public function getConnection() {
        return $this->db_handle;
    }
}

$db = new connect_fixed();
$conn = $db->getConnection();

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ✅ Auth class
class Auth {
    private $conn;
    public function __construct($dbConn) {
        $this->conn = $dbConn;
    }

    // 🔹 Login Function
    public function login() {
        $hotel_id   = trim($_POST["hotel_id"]);
        $hotel_name = trim($_POST["hotel_name"]);
        $user       = trim($_POST["login_user"]);
        $pass       = trim($_POST["login_pass"]);

        $stmt = $this->conn->prepare("SELECT * FROM login WHERE hotel_id=? AND hotel_name=? AND email=? AND password=?");
        if (!$stmt) {
            $_SESSION['flash_message'] = "SQL Error: " . $this->conn->error;
            return;
        }
        $stmt->bind_param("ssss", $hotel_id, $hotel_name, $user, $pass);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $today = date('Y-m-d');
            $currentTime = date('Y-m-d H:i:s');

            // Total login count +1
            $totalLogins = $row['total_logins'] + 1;

            // Agar last_login_date same hai to today_logins badhao, nahi to reset karo
            if ($row['last_login_date'] === $today) {
                $todayLogins = $row['today_logins'] + 1;
            } else {
                $todayLogins = 1; // new day so reset
            }

            // Update database
            $update = $this->conn->prepare("
                UPDATE login 
                SET total_logins=?, today_logins=?, last_login=?, last_login_date=? 
                WHERE email=? AND hotel_id=? AND hotel_name=?
            ");
            $update->bind_param("iisssss", $totalLogins, $todayLogins, $currentTime, $today, $user, $hotel_id, $hotel_name);
            $update->execute();

            $_SESSION['email']      = $user;
            $_SESSION['hotel_id']   = $hotel_id;
            $_SESSION['hotel_name'] = $hotel_name;

            header("Location: frontpage.php");
            exit();
        } else {
            $_SESSION['flash_message'] = "Invalid Login Details!";
        }
    }

    // 🔹 Reset Password
    public function resetPassword() {
        $hotel_id   = trim($_POST["hotel_id"]);
        $hotel_name = trim($_POST["hotel_name"]);
        $email      = trim($_POST["reset_email"]);
        $newPass    = trim($_POST["reset_pass"]);

        $check = $this->conn->prepare("SELECT email FROM login WHERE hotel_id=? AND hotel_name=? AND email=?");
        $check->bind_param("sss", $hotel_id, $hotel_name, $email);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $update = $this->conn->prepare("UPDATE login SET password=? WHERE hotel_id=? AND hotel_name=? AND email=?");
            $update->bind_param("ssss", $newPass, $hotel_id, $hotel_name, $email);
            if ($update->execute()) {
                $_SESSION['flash_message'] = "Password updated successfully! Please login.";
            } else {
                $_SESSION['flash_message'] = "Error updating password.";
            }
        } else {
            $_SESSION['flash_message'] = "Hotel details or Email not found!";
        }
    }
}

$ob = new Auth($conn);

// ✅ Handle form submissions
if (isset($_POST["login_btn"])) {
    $ob->login();
}
if (isset($_POST["reset_btn"])) {
    $ob->resetPassword();
}

// ✅ Show flash messages once
if (isset($_SESSION['flash_message'])) {
    echo "<script>alert('" . addslashes($_SESSION['flash_message']) . "');</script>";
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login & Reset</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    animation: bgAnimation 10s infinite alternate;
    background-size: 400% 400%;
    font-family: Arial, sans-serif;
    padding: 10px;
    min-height: 100vh; /* Auto adjust on mobile */
    display: flex;
    justify-content: center;
    align-items: center;
}
@keyframes bgAnimation {
    0% { background: linear-gradient(45deg, #ff9a9e, #fad0c4); }
    50% { background: linear-gradient(45deg, #a1c4fd, #c2e9fb); }
    100% { background: linear-gradient(45deg, #ffdde1, #ee9ca7); }
}
.card {
    animation: fadeIn 1s ease-out;
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    width: 100%;
    max-width: 400px; /* desktop limit */
}
.nav-tabs .nav-link.active {
    background-color: black !important;
    color: white !important;
}
</style>
</head>
<body>

<div class="card p-4 w-100 w-md-auto">
    <ul class="nav nav-tabs mb-3" id="formTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Login</button>
        </li>
    </ul>
    <div class="tab-content">
        <!-- 🔹 Login Form -->
        <div class="tab-pane fade show active" id="login" role="tabpanel">
            <form method="post">
                <div class="mb-3">
                    <input type="text" name="hotel_id" class="form-control" placeholder="Hotel ID" required>
                </div>
                <div class="mb-3">
                    <input type="text" name="hotel_name" class="form-control" placeholder="Hotel Name" required>
                </div>
                <div class="mb-3">
                    <input type="email" name="login_user" class="form-control" placeholder="Email" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="login_pass" class="form-control" placeholder="Password" required>
                </div>
                <button type="submit" name="login_btn" class="btn btn-dark w-100">Login</button>
                <div class="text-center mt-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#resetModal">Forgot Password?</a>
                </div>
            </form>
        </div>
</div>

<!-- 🔹 Reset Password Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content p-3">
      <h5 class="text-center">Reset Password</h5>
        <form method="post">
            <div class="mb-3">
                <input type="text" name="hotel_id" class="form-control" placeholder="Hotel ID" required>
            </div>
            <div class="mb-3">
                <input type="text" name="hotel_name" class="form-control" placeholder="Hotel Name" required>
            </div>
            <div class="mb-3">
                <input type="email" name="reset_email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="reset_pass" class="form-control" placeholder="Enter new password" required>
            </div>
            <button type="submit" name="reset_btn" class="btn btn-dark w-100">Update Password</button>
        </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
