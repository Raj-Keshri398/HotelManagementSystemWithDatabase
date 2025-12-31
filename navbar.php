<?php
// ✅ Agar session start nahi hua hai to start karein (aur custom session name set karein)
if (session_status() === PHP_SESSION_NONE) {
    session_name("user_session"); // Alag session name user ke liye (admin se conflict avoid karne ke liye)
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hotel Management System</title>

<!-- ✅ Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- ✅ Font Awesome (Icons ke liye) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

<style>
   /* ✅ Navbar ke liye styling */
    .navbar { 
        background-color: #000; /* Black background */
    }

    .navbar-brand {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: #c58713 !important; /* Golden color branding */
    }

    .nav-link { 
        color: white !important; /* Default link color */
    }

    .nav-link:hover { 
        color: #c58713 !important; /* Hover pe golden */
    }

    /* ✅ Dropdown Styling */
    .dropdown-menu {
        background-color: #000 !important; /* Black dropdown */
        border: none !important;
        display: none;              /* Default hidden */
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        margin-top: 0;              /* Dropdown ka gap remove */
    }

    /* ✅ Hover pe dropdown dikhana */
    .nav-item.dropdown:hover .dropdown-menu {
        display: block;
        opacity: 1;
        visibility: visible;
    }

    /* ✅ Dropdown items styling */
    .dropdown-menu a {
        color: white !important;
    }
    .dropdown-menu a:hover {
        background-color: #c58713 !important;
        color: #000 !important;
    }
</style>
</head>
<body>

<!-- ✅ NAVBAR START -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        
        <!-- ✅ Navbar Brand (Hotel Name) -->
        <h2 class="navbar-brand mb-0 fs-4 text-warning">
            <?php 
                // Agar session me hotel ka naam hai to dikhaye, warna default text
                if (isset($_SESSION['hotel_name'])) {
                    echo htmlspecialchars($_SESSION['hotel_name']); 
                } else {
                    echo "Hotel Grand Paradise"; // Default jab tak login na ho
                }
            ?>
        </h2>
            
        <!-- ✅ TOGGLE BUTTON (Mobile/Tablet ke liye) -->
        <button class="navbar-toggler" type="button">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- ✅ COLLAPSEABLE NAVBAR -->
        <div class="collapse navbar-collapse" id="hotelNavbar">

            <!-- ✅ NAVBAR LINKS -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Dashboard -->
                <li class="nav-item"><a class="nav-link" href="frontpage.php">Dashboard</a></li>
                
                <!-- Booking Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#">Booking</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="searchbooking1.php">Booking Details</a></li>
                        <li><a class="dropdown-item" href="booking.php">Add Booking</a></li>
                        <li><a class="dropdown-item" href="searchonlinebooking.php">Online Booking</a></li>
                    </ul>
                </li>
                
                <!-- Room Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#">Room</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="searchroom1.php">Room Details</a></li>
                        <li><a class="dropdown-item" href="roomstatus.php">Room Status</a></li>
                    </ul>
                </li>
                
                <!-- Guest Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#">Guest</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="searchguest1.php">Guest Details</a></li>
                        <li><a class="dropdown-item" href="guest.php">Add Guest</a></li>
                    </ul>
                </li>

                <!-- Payment Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#">Payment</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="bill.php">Generate Invoice</a></li>
                        <li><a class="dropdown-item" href="searchbill1.php">Invoice Details</a></li>
                    </ul>
                </li>

                <!-- ✅ Admin Page Link -->
                <li class="nav-item">
                    <?php if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true): ?>
                        <a class="nav-link" href="Admin_dashboard.php">Admin</a>
                    <?php else: ?>
                        <a class="nav-link" href="Admin_login.php">Admin</a>
                    <?php endif; ?>
                </li>
            </ul>

            <!-- ✅ RIGHT SIDE LOGIN / LOGOUT -->
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['email']) && isset($_SESSION['hotel_id'])): ?>
                    <!-- Agar user login hai -->
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($_SESSION['email']); ?> 
                            <span class="text-warning"> (<?php echo htmlspecialchars($_SESSION['hotel_id']); ?>)</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <!-- ✅ User Logout -->
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                <?php else: ?>
                    <!-- Agar user login nahi hai -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                <?php endif; ?>
            </ul>

        </div>
    </div>
</nav>
<!-- ✅ NAVBAR END -->

<!-- ✅ Bootstrap Bundle JS (Dropdown + Collapse ke liye) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.querySelector('.navbar-collapse'); // Navbar collapse container
    const toggler = document.querySelector('.navbar-toggler'); // Toggle button
    const bsCollapse = new bootstrap.Collapse(navbar, { toggle: false }); // Collapse control

    /* ============================
       DROPDOWN LOGIC
    ============================ */

    // ✅ Desktop hover dropdown
    function enableDesktopHover() {
        document.querySelectorAll('.nav-item.dropdown').forEach(function (dropdown) {
            dropdown.addEventListener('mouseenter', function () {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                bootstrap.Dropdown.getOrCreateInstance(toggle).show();
            });
            dropdown.addEventListener('mouseleave', function () {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
            });
        });
    }

    // ✅ Mobile dropdown (click pe open/close)
    if (window.innerWidth <= 778) {
        document.querySelectorAll('.nav-item.dropdown > a').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault(); // Default link ka action block
                let dropdownMenu = link.nextElementSibling;
                dropdownMenu.classList.toggle("show"); // Toggle dropdown
            });
        });
    }

    /* ============================
       TOGGLE BUTTON LOGIC
    ============================ */
    toggler.addEventListener('click', function (e) {
        e.stopPropagation(); 
        if (navbar.classList.contains('show')) {
            bsCollapse.hide(); // Close navbar
        } else {
            bsCollapse.show(); // Open navbar
        }
    });

    // ✅ Click outside se navbar band karna
    document.addEventListener('click', function (event) {
        const isClickInsideNavbar = navbar.contains(event.target);
        const isClickOnToggler = toggler.contains(event.target);

        if (navbar.classList.contains('show') && !isClickInsideNavbar && !isClickOnToggler) {
            bsCollapse.hide();
        }
    });
});
</script>

</body>
</html>
