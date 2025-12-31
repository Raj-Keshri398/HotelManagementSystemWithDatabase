<?php
session_start();
require_once 'connect.php';
include 'navbar.php';

//  Agar login nahi hai to redirect
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please login first'); window.location.href='login.php';</script>";
    exit;
}

// ✅ Current logged-in user ka hotel_id aur hotel_name nikalna
$db = new Connect();
$conn = $db->getDbHandle();
$stmt = $conn->prepare("SELECT hotel_id, hotel_name FROM login WHERE email=?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<script>alert('Hotel not found for this user.'); window.location.href='login.php';</script>";
    exit;
}
$userHotel = $res->fetch_assoc();
$hotel_id = $userHotel['hotel_id'];
$hotel_name = $userHotel['hotel_name'];
$stmt->close();
?>


<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-space {
            margin-top: 80px;
        }
        .search-form input {
            height: 45px;
        }
    </style>
</head>
<body>

<div class="container navbar-space">

    <!-- Search & Refresh -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col">
            <form method="post" class="row g-2 search-form" autocomplete="off">
                <div class="col-12 col-md">
                    <input type="text" name="t1" class="form-control shadow-sm" placeholder="Enter Booking ID or Guest ID"
                        value="<?php echo isset($_POST['b2']) ? htmlspecialchars($_POST['t1']) : ''; ?>">
                </div>
                <div class="col-6 col-md-auto">
                    <button type="submit" name="b2" class="btn btn-dark w-100" title="Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
                <div class="col-6 col-md-auto">
                    <button type="submit" name="b1" class="btn btn-dark w-100" title="Refresh">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php

class SearchBooking extends Connect
{
    public function __construct()
    {
        parent::__construct();
    }

    private function renderTable($result)
    {
        if (!$result || mysqli_num_rows($result) === 0) {
            echo "<div class='alert alert-warning text-center shadow-sm'>No booking found.</div>";
            return;
        }

        // Table (Desktop)
        echo "<div class='d-none d-md-block table-responsive shadow-sm rounded'>
                <table class='table table-striped table-hover table-bordered align-middle text-nowrap'>
                <thead class='table-dark text-center'>
                <tr>
                    <th>Booking ID</th>
                    <th>Hotel ID</th>
                    <th>Guest ID/Name</th>
                    <th>Room Number</th>
                    <th>Deposit Payment</th>
                    <th>Room Charge</th>
                    <th>Check-In</th>
                    <th>Check-Out</th>
                    <th>Adults</th>
                    <th>Children</th>
                </tr>
                </thead>
                <tbody>";

        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
            echo $this->renderRow($row);
        }

        echo "</tbody></table></div>";

        // Card View (Mobile)
        echo "<div class='d-block d-md-none'>";
        foreach ($rows as $row) {
            echo $this->renderCard($row);
        }
        echo "</div>";
    }

    private function renderRow($row)
    {
        $id = htmlspecialchars($row['booking_id']);
        return "<tr>
            <td>{$id}</td>
            <td>{$row['hotel_id']}</td>
            <td>{$row['guest_id']}</td>
            <td>{$row['room_id']}</td>
            <td>{$row['deposit_payment']}</td>
            <td>{$row['room_charge']}</td>
            <td>{$row['check_in']}</td>
            <td>{$row['check_out']}</td>
            <td>{$row['num_of_adults']}</td>
            <td>{$row['num_of_children']}</td>
            
        </tr>";
    }

    private function renderCard($row)
    {
        $id = htmlspecialchars($row['booking_id']);
        return "<div class='card shadow-sm mb-3'>
            <div class='card-body'>
                <p><strong>Booking ID:</strong> {$id}</p>
                <p><strong>Hotel ID:</strong> {$row['hotel_id']}</p>
                <p><strong>Guest ID:</strong> {$row['guest_id']}</p>
                <p><strong>Room ID:</strong> {$row['room_id']}</p>
                <p><strong>Deposit Payment:</strong> {$row['deposit_payment']}</p>
                <p><strong>Room Charge:</strong> {$row['room_charge']}</p>
                <p><strong>Check-In:</strong> {$row['check_in']}</p>
                <p><strong>Check-Out:</strong> {$row['check_out']}</p>
                <p><strong>Adults:</strong> {$row['num_of_adults']}</p>
                <p><strong>Children:</strong> {$row['num_of_children']}</p>
                
            </div>
        </div>";
    }

    

    // Show all bookings of logged-in hotel
    public function showAllBookings($hotel_id)
    {
        $stmt = $this->db_handle->prepare("SELECT * FROM booking WHERE hotel_id=? ORDER BY booking_id ASC");
        $stmt->bind_param("i", $hotel_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $this->renderTable($res);
        $stmt->close();
    }

    // Search by booking_id or guest_id, but only inside same hotel
    public function searchBooking($keyword, $hotel_id)
    {
        $keyword = "%".trim($keyword)."%";
        $stmt = $this->db_handle->prepare("SELECT * FROM booking WHERE (booking_id LIKE ? OR guest_id LIKE ?) AND hotel_id=?");
        $stmt->bind_param("ssi", $keyword, $keyword, $hotel_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $this->renderTable($res);
        $stmt->close();
    }
}

$search = new SearchBooking();

if (isset($_POST['b1'])) {
    $search->showAllBookings($hotel_id);
} elseif (isset($_POST['b2'])) {
    $search->searchBooking($_POST['t1'], $hotel_id);
} else {
    $search->showAllBookings($hotel_id);
}

?>

    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</script>
</body>
</html>
