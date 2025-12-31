<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent page caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Agar login nahi hai to login.php bhejo
if (!isset($_SESSION['email']) || !isset($_SESSION['hotel_id'])) {
    header("Location: login.php");
    exit();
}
?>
<?php include 'navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hotel Grand Paradise - Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts & Icons -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background: url('h-img4.jpg') no-repeat center center/cover;
        height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Overlay for readability */
    .overlay {
        background-color: rgba(0, 0, 0, 0.6);
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 56px; /* offset for fixed navbar */
        padding: 15px; /* mobile padding */
    }

    .welcome-box {
        background-color: rgba(255, 255, 255, 0.05);
        padding: 40px 20px;
        border-radius: 15px;
        text-align: center;
        color: white;
        max-width: 700px;
        width: 100%;
        box-shadow: 0px 0px 20px rgba(0,0,0,0.5);
    }

    .welcome-box h1 {
        font-weight: 700;
        color: #c58713;
        margin-bottom: 20px;
    }

    .welcome-box p {
        font-size: 1.1rem;
        margin-bottom: 30px;
    }

    .btn-custom {
        background-color: #c58713;
        color: white;
        border: none;
        padding: 12px 25px;
        font-size: 1rem;
        border-radius: 5px;
        transition: background 0.3s ease;
        text-decoration: none;
        display: inline-block;
        width: 100%; /* full width mobile */
    }

    .btn-custom:hover {
        background-color: #a96e0e;
        color: white;
    }

    /* Desktop button layout */
    @media (min-width: 576px) {
        .btn-custom {
            width: auto;
        }
    }

    /* Navbar dropdown hover for desktop */
    @media (min-width: 992px) {
        .navbar .dropdown:hover .dropdown-menu {
            display: block;
        }
    }
</style>
</head>
<body>

<div class="overlay">
    <div class="welcome-box">
        <h1>Welcome to Hotel Grand Paradise</h1>
        <p>Effortlessly manage rooms, bookings, guests, payments, and more — all in one place.</p>
        
        <!-- Responsive button group -->
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <a href="booking.php" class="btn-custom"><i class="fas fa-calendar-check"></i> Make a Booking</a>
            <a href="searchroom1.php" class="btn-custom"><i class="fas fa-bed"></i> View Rooms</a>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Extra JS for mobile dropdown toggle fix
document.addEventListener('DOMContentLoaded', function () {
    if (window.innerWidth < 992) {
        document.querySelectorAll('.navbar .dropdown-toggle').forEach(function (dropdownToggle) {
            dropdownToggle.addEventListener('click', function (e) {
                e.preventDefault();
                let dropdownMenu = this.nextElementSibling;
                if (dropdownMenu.classList.contains('show')) {
                    bootstrap.Dropdown.getOrCreateInstance(this).hide();
                } else {
                    bootstrap.Dropdown.getOrCreateInstance(this).show();
                }
            });
        });
    }
});
</script>

</body>
</html>
