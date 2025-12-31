<?php
// Include database connection and booking classes
include 'connect.php';

// Extend the connect class to access database handle
class connect_fixed extends connect {
    public function getDbConnection() {
        return $this->db_handle; // Return DB handle
    }
}

// Booking class extending connect_fixed
class OnlineBooking extends connect_fixed {

    // Get the DB connection handle
    public function getDbConnection() {
        return $this->db_handle;
    }


    // Fetch available distinct room types from DB
    public function getBooking() {
        $rooms = [];
        if ($this->db_handle) {
            $sql = "SELECT DISTINCT room_type FROM room WHERE booking_status='Available'";
            $result = $this->db_handle->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rooms[] = $row['room_type'];
                }
            }
        }
        return $rooms;
    }

    // ✅ Generate new Booking ID (manual, not auto increment)
    public function generateBookingId() {
        $conn = $this->getDbConnection();
        $prefix = "BK";
        $datePart = date("Ymd"); // e.g. 20250812

        // Find last booking ID for today
        $stmt = $conn->prepare("SELECT booking_id FROM online_booking WHERE booking_id LIKE ? ORDER BY booking_id DESC LIMIT 1");
        $like = $prefix . $datePart . "%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $lastId = null;
        if ($row = $result->fetch_assoc()) {
            $lastId = $row['booking_id'];
        }

        if ($lastId) {
            // Increment last number
            $lastNumber = intval(substr($lastId, -4));
            $newNumber = str_pad($lastNumber + 1, 4, "0", STR_PAD_LEFT);
        } else {
            // Start with 0001
            $newNumber = "0001";
        }

        return $prefix . $datePart . $newNumber; // e.g. BK202508120001
    }

    // Save booking to database
    public function save($data) {
        $conn = $this->getDbConnection();

        $stmt = $conn->prepare(
            "INSERT INTO online_booking 
            (booking_id, name, mobile_no, room_type, room_price, description, booking_date, booking_time, number_of_people) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssssssssi",
            $data['booking_id'],
            $data['name'],
            $data['mobile_no'],
            $data['room_type'],
            $data['room_price'],
            $data['description'],
            $data['booking_date'],
            $data['booking_time'],
            $data['number_of_people']
        );
        return $stmt->execute();
    }
}

// Create object
$ob = new OnlineBooking();

// Handle form submission when booking form submitted
if (isset($_POST["b1"])) {
    // Collect and sanitize form data
    $data = [
        'name'             => isset($_POST['t1']) ? trim($_POST['t1']) : '',
        'mobile_no'        => isset($_POST['t2']) ? trim($_POST['t2']) : '',
        'room_type'        => isset($_POST['t3']) ? trim($_POST['t3']) : '',
        'room_price'       => isset($_POST['t4']) ? trim($_POST['t4']) : '',
        'description'      => isset($_POST['t5']) ? trim($_POST['t5']) : '',
        'booking_date'     => isset($_POST['t6']) ? trim($_POST['t6']) : '',
        'booking_time'     => isset($_POST['t7']) ? trim($_POST['t7']) : '',
        'number_of_people' => isset($_POST['t8']) ? intval($_POST['t8']) : 0,
    ];

    // Basic form validation
    if (
        !empty($data['name']) &&
        preg_match('/^\d{10}$/', $data['mobile_no']) &&
        !empty($data['room_type']) &&
        is_numeric($data['room_price']) &&
        !empty($data['description']) &&
        !empty($data['booking_date']) &&
        !empty($data['booking_time']) &&
        $data['number_of_people'] > 0
    ) {
        // ✅ Check for duplicate booking (mobile_no + booking_date + booking_time)
        $stmt = $ob->getDbConnection()->prepare(
            "SELECT COUNT(*) AS cnt 
            FROM online_booking 
            WHERE mobile_no = ? AND booking_date = ? AND booking_time = ?"
        );

        if ($stmt === false) {
            die("SQL Prepare Error: " . $ob->getDbConnection()->error);
        }

        $stmt->bind_param("sss", $data['mobile_no'], $data['booking_date'], $data['booking_time']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();


        if ($result['cnt'] > 0) {
            echo "<script>alert('Booking already exists for this mobile number, date, and time!');</script>";
        } else {
            // ✅ Generate Booking ID
            $data['booking_id'] = $ob->generateBookingId();

            // Save booking
            if ($ob->save($data)) {
                // Redirect to confirmation page with booking_id
                header("Location: confirmation.php?booking_id=" . urlencode($data['booking_id']));
                exit();
            } else {
                echo "<script>alert('Error saving booking');</script>";
            }
        }
    } else {
        echo "<script>alert('Please fill all required fields correctly');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta tags -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Page title -->
    <title>Grand Paradise - Homepage</title>

    <!-- External CSS links -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Link to external stylesheet -->
    <link href="style.css" rel="stylesheet" />
</head>
<body>

<!-- Header Section -->
<header class="header-banner">
    <div class="overlay">
        <div class="container text-center text-white">
            <h1 class="display-3 fw-bold">Hotel Management System</h1>
            <p class="lead">Thanks for finding me!</p>
        </div>
    </div>
</header>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-secondary sticky-top">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#cda">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Service</a></li>
                <li class="nav-item"><a class="nav-link" href="#gallery">Gallery</a></li>
                <li class="nav-item"><a class="nav-link" href="#about_us">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact_us">Contacts</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Rooms Section -->
<div class="container my-4">
    <h2 class="text-center mb-4">Our Luxurious Rooms</h2>
    <div class="row g-4">
        <?php
        // Fetch DB handle from booking object
         $db = $ob->getDbConnection();

        if ($db) {
            // Select room details
            $sql = "SELECT room_id, room_image, room_type, room_charge, description FROM room";
            $result = $db->query($sql);

            if ($result && $result->num_rows > 0) {
                // Loop through each room and output cards
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card room-card">
                            <img src="<?= htmlspecialchars($row['room_image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($row['room_type']) ?>">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?= htmlspecialchars($row['room_type']) ?></h5>
                                <p class="card-text desc-text"><?= htmlspecialchars($row['description']) ?></p>
                                <p class="card-text price-text">Price: ₹<?= number_format($row['room_charge'], 2) ?></p>
                                <button
                                    class="btn btn-outline-dark book-here-btn"
                                    type="button"
                                    data-roomtype="<?= htmlspecialchars($row['room_type']) ?>"
                                    data-roomprice="<?= $row['room_charge'] ?>"
                                    data-roomdesc="<?= htmlspecialchars($row['description']) ?>"
                                >Book Here</button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p class='text-center text-muted'>No rooms available at the moment.</p>";
            }
        } else {
            echo "<p class='text-center text-danger'>Database connection failed.</p>";
        }
        ?>
    </div>
</div>

<!-- Booking Form Overlay -->
<div id="bookingOverlay">
    <div>
        <!-- Close Button -->
        <button id="closeBooking" title="Close">&times;</button>

        <h2 class="text-center mb-4">Book Online</h2>
        <form method="post" class="row g-3" id="booking_form_overlay">
            <div class="col-md-6">
                <label class="form-label">Your Name</label>
                <input type="text" name="t1" class="form-control" required />
            </div>

            <div class="col-md-6">
                <label class="form-label">Mobile Number</label>
                <input 
                    type="text" 
                    name="t2" 
                    pattern="\d{10}" 
                    maxlength="10" 
                    class="form-control" 
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                    required 
                />
            </div>

            <div class="col-md-6">
                <label class="form-label">Room Type</label>
                <input type="text" name="t3" id="room_type_overlay" class="form-control" readonly required />
            </div>
            <div class="col-md-6">
                <label class="form-label">Room Price (₹)</label>
                <input type="text" id="room_price_overlay" name="t4" class="form-control" readonly />
            </div>
            <div class="col-md-6">
                <label class="form-label">Description</label>
                <textarea id="room_description_overlay" name="t5" class="form-control" rows="2" readonly></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">Booking Date</label>
                <input type="date" name="t6" class="form-control" required />
            </div>
            <div class="col-md-6">
                <label class="form-label">Booking Time</label>
                <input type="time" name="t7" class="form-control" required />
            </div>
            <div class="col-md-6">
                <label class="form-label">Number of People</label>
                <input type="number" name="t8" min="1" class="form-control" required />
            </div>

            <div class="col-12 text-center">
                <input type="submit" name="b1" value="Book Now" class="btn btn-primary px-5" />
            </div>
        </form>
    </div>
</div>

<!-- ====================================== Services Section ======================================== -->
<section id="services" class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-5">Our Services</h2>
    <div class="row g-4">
      <div class="col-md-4 text-center service-card">
        <i class='bx bxs-bed fs-1 text-warning'></i>
        <h5 class="mt-3">Luxurious Rooms</h5>
        <p>Experience world-class comfort with our premium rooms designed for relaxation.</p>
      </div>
      <div class="col-md-4 text-center service-card">
        <i class='bx bxs-food-menu fs-1 text-warning'></i>
        <h5 class="mt-3">Fine Dining</h5>
        <p>Enjoy multi-cuisine dishes prepared by top chefs from around the world.</p>
      </div>
      <div class="col-md-4 text-center service-card">
        <i class='bx bxs-spa fs-1 text-warning'></i>
        <h5 class="mt-3">Spa & Wellness</h5>
        <p>Rejuvenate your senses with our premium spa and wellness treatments.</p>
      </div>
    </div>
  </div>
</section>


<!-- Gallery Section -->
<section id="gallery" class="py-5">
  <div class="container">
    <h2 class="text-center mb-5">Gallery</h2>
    <!-- Horizontal scroll container -->
    <div class="gallery-scroll d-flex gap-3 overflow-auto">
      <img src="pic1.jpg" alt="Gallery Image 1" class="gallery-img" />
      <img src="pic2.jpg" alt="Gallery Image 2" class="gallery-img" />
      <img src="pic3.jpg" alt="Gallery Image 3" class="gallery-img" />
      <img src="pic4.jpg" alt="Gallery Image 4" class="gallery-img" />
      <img src="pic5.jpg" alt="Gallery Image 5" class="gallery-img" />
      <img src="pic1.jpg" alt="Gallery Image 1" class="gallery-img" />
      <img src="pic2.jpg" alt="Gallery Image 2" class="gallery-img" />
      <img src="pic3.jpg" alt="Gallery Image 3" class="gallery-img" />
      <img src="pic4.jpg" alt="Gallery Image 4" class="gallery-img" />
      <img src="pic5.jpg" alt="Gallery Image 5" class="gallery-img" />
      <img src="pic1.jpg" alt="Gallery Image 1" class="gallery-img" />
      <img src="pic2.jpg" alt="Gallery Image 2" class="gallery-img" />
      <img src="pic3.jpg" alt="Gallery Image 3" class="gallery-img" />
      <img src="pic4.jpg" alt="Gallery Image 4" class="gallery-img" />
      <img src="pic5.jpg" alt="Gallery Image 5" class="gallery-img" />
      <!-- Add more images as needed -->
    </div>
  </div>
</section>


<!-- About Us Section -->
<section id="about_us" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">About Us</h2>
        <div class="row align-items-center">
            <div class="col-md-6"><img src="pic1.jpg" class="img-fluid rounded shadow" alt="About Us"></div>
            <div class="col-md-6 mt-4 mt-md-0">
                <p>Welcome to Grand Paradise, where luxury meets comfort...</p>
                <ul>
                    <li>24/7 Room Service</li>
                    <li>Luxury Spa</li>
                    <li>Free High-Speed Wi-Fi</li>
                    <li>Conference & Banquet Halls</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Contact Us Section -->
<section id="contact_us" class="py-5" style="background: linear-gradient(to right, #fdfbfb, #ebedee);">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold">Contact Us</h2>
        <div class="row g-4 align-items-center">
            <div class="col-md-6">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <input type="text" name="name" class="form-control shadow-sm" placeholder="Enter name" required />
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control shadow-sm" placeholder="Enter email" required />
                    </div>
                    <div class="mb-3">
                        <textarea name="message" class="form-control shadow-sm" rows="4" placeholder="Write your message" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold shadow-sm">Send Message</button>
                </form>
            </div>
            <div class="col-md-6 text-dark">
                <div class="p-4 rounded shadow-sm" style="background-color: white;">
                    <h5 class="fw-bold mb-3">Hotel Location</h5>
                    <p class="mb-2"><i class="bi bi-geo-alt-fill text-warning"></i> CDA Building, Patna, Bihar - 800024</p>
                    <p class="mb-2"><i class="bi bi-telephone-fill text-warning"></i> +91 1234567890</p>
                    <p><i class="bi bi-envelope-fill text-warning"></i> keshriraj093@gmail.com</p>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18..." width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Footer -->
<footer class="bg-dark text-white pt-5 pb-3">
    <div class="container">
        <div class="row">
            <!-- About -->
            <div class="col-md-4 mb-3">
                <h5 class="mb-3">About Us</h5>
                <p>
                    Welcome to Our Hotel — your comfort is our priority.
                    Enjoy premium rooms, great service, and a memorable stay.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="col-md-4 mb-3">
                <h5 class="mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                     <li class="nav-item"><a class="nav-link" href="login.php"><i class='bx bxs-home-alt-2'></i> Admin</a></li>
                </ul>
            </div>

            <!-- Social Media -->
            <div class="col-md-4 mb-3">
                <h5 class="mb-3">Follow Us</h5>
                <a href="#" style="color:#fff; margin-right:10px; font-size:20px;">🌐</a>
                <a href="#" style="color:#fff; margin-right:10px; font-size:20px;">📘</a>
                <a href="#" style="color:#fff; font-size:20px;">📷</a>
                <div>
                    <a href="https://facebook.com" target="_blank" class="text-white fs-4 me-3">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://instagram.com" target="_blank" class="text-white fs-4 me-3">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://x.com" target="_blank" class="text-white fs-4">
                        <i class="fab fa-x-twitter"></i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="bg-light">
        <div class="text-center">
            <p class="mb-0">&copy; 2025 Our Hotel. All rights reserved.</p>
        </div>
    </div>
</footer>




<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Booking Overlay Elements
    const bookingOverlay = document.getElementById('bookingOverlay');
    const closeBookingBtn = document.getElementById('closeBooking');

    // Show booking overlay and fill form with room data when "Book Here" button clicked
    document.querySelectorAll('.book-here-btn').forEach(button => {
        button.addEventListener('click', () => {
            // Get data attributes from button
            const roomType = button.getAttribute('data-roomtype');
            const roomPrice = button.getAttribute('data-roomprice');
            const roomDesc = button.getAttribute('data-roomdesc');

            // Fill booking form fields with room info
            document.getElementById('room_type_overlay').value = roomType;
            document.getElementById('room_price_overlay').value = roomPrice;
            document.getElementById('room_description_overlay').value = roomDesc;

            // Display the booking overlay (modal)
            bookingOverlay.style.display = 'flex';
        });
    });

    // Close booking overlay on close button click
    closeBookingBtn.addEventListener('click', () => {
        bookingOverlay.style.display = 'none';
    });

    // Close overlay when clicking outside form content
    bookingOverlay.addEventListener('click', (e) => {
        if (e.target === bookingOverlay) {
            bookingOverlay.style.display = 'none';
        }
    });


    // ===================================== service section ======================================

    // Select all service cards
    const serviceCards = document.querySelectorAll('.service-card');

    // On click, toggle active class on clicked and remove from others
    serviceCards.forEach(card => {
        card.addEventListener('click', () => {
            // Remove 'active' class from all cards
            serviceCards.forEach(c => c.classList.remove('active'));
            // Add 'active' to clicked card
            card.classList.add('active');
        });
    });

</script>

</body>
</html>
