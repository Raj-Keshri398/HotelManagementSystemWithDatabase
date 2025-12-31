<?php
// ================= USER SESSION (from login.php) =================
session_name("user_session");
session_start();

// Agar user login hai (hotel session set hai)
$userEmail  = $_SESSION['email']      ?? null;
$hotelName  = $_SESSION['hotel_name'] ?? null;
$hotelId    = $_SESSION['hotel_id']   ?? null;

// ================= ADMIN SESSION (for admin panel) =================
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close(); // user_session close karna zaroori hai
}
session_name("admin_session");
session_start();

// agar admin login nahi hai to redirect
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Admin email
$adminEmail = $_SESSION['admin_email'] ?? "admin123@gmail.com";

// ================= FINAL DATA TO SHOW =================
$showHotel = $hotelName ?: "Demo";
$showEmail = $userEmail ?: $adminEmail;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { margin: 0; padding: 0; background-color: #f8f9fa; }

        /* Navbar */
        .navbar { 
            background: linear-gradient(45deg, #007bff, #0056b3); 
            margin-left: 250px; 
            transition: margin-left 0.3s ease;
        }
        .navbar-brand { color: white; font-weight: bold; }
        .navbar-brand:hover { color: #ffdd57; }

        /* Sidebar */
        .sidebar {
            height: 100%;
            background: linear-gradient(180deg, #343a40, #1d2124);
            padding: 20px;
            position: fixed;
            top: 0; left: 0;
            width: 250px;
            z-index: 1050;
            transition: left 0.3s ease;
        }
        .sidebar h4 { color: #fff; }
        .sidebar a {
            display: block;
            padding: 12px;
            margin: 5px 0;
            border-radius: 8px;
            color: #adb5bd;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #007bff;
            color: #fff;
        }

        /* Iframe */
        iframe {
            margin-left: 250px;
            width: calc(100% - 250px);
            height: calc(100vh - 56px);
            border: none;
            transition: margin-left 0.3s ease;
        }

        /* Overlay for small screens */
        .overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 1040;
        }
        .overlay.active { display: block; }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }
            .sidebar.active {
                left: 0;
            }
            .navbar {
                margin-left: 0;
            }
            iframe {
                margin-left: 0;
                width: 100%;
            }
            #sidebarToggle {
                display: inline-block;
            }
        }

        @media (min-width: 769px) {
            #sidebarToggle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <!-- Toggle button (only visible on small screen) -->
            <button class="btn btn-light me-2" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="#">
                <?php echo htmlspecialchars($showHotel); ?>
            </a>
            <span class="navbar-text text-white ms-2">
                Welcome, <?php echo htmlspecialchars($showEmail); ?>!
            </span>
            <div class="ms-auto">
                <a href="admin_logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h4 class="text-center">
            <i class="fas fa-hotel"></i><br>
            <?php echo htmlspecialchars($showHotel); ?>
        </h4>
        <a href="Admin_dashboard_content.php" target="contentFrame"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="frontpage.php"><i class="fas fa-book"></i> Booking Panel</a>
        <a href="searchbill.php" target="contentFrame"><i class="fas fa-file-invoice"></i> Bill</a>
        <a href="searchbooking.php" target="contentFrame"><i class="fas fa-calendar-check"></i> Booking</a>
        <a href="searchguest.php" target="contentFrame"><i class="fas fa-users"></i> Customer</a>
        <a href="searchroom.php" target="contentFrame"><i class="fas fa-bed"></i> Room</a>
        <a href="searchrole.php" target="contentFrame"><i class="fas fa-user-tie"></i> Role</a>
        <a href="searchstaff.php" target="contentFrame"><i class="fas fa-user-tie"></i> Staff</a>
        <a href="searchroomstatus.php" target="contentFrame"><i class="fas fa-utensils"></i> Room Last Status</a>
        <a href="searchfood.php" target="contentFrame"><i class="fas fa-utensils"></i> Food</a>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Iframe for Main Content -->
    <iframe name="contentFrame" src="dashboard_content.php" id="mainFrame"></iframe>

    <!-- JS -->
    <script>
        const sidebar = document.getElementById("sidebar");
        const toggleBtn = document.getElementById("sidebarToggle");
        const overlay = document.getElementById("overlay");
        const sidebarLinks = document.querySelectorAll(".sidebar a");

        // Toggle button click
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("active");
            overlay.classList.toggle("active");
        });

        // Overlay click (close sidebar)
        overlay.addEventListener("click", () => {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        });

        // Sidebar link click (close sidebar)
        sidebarLinks.forEach(link => {
            link.addEventListener("click", () => {
                sidebar.classList.remove("active");
                overlay.classList.remove("active");
            });
        });
    </script>
</body>
</html>
