<?php
session_start();
include 'connect.php';

// Extend connect class to get database connection
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

// Check login
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please login to update booking.'); window.location.href='login.php';</script>";
    exit;
}

// Initialize booking data
$booking = [
    "booking_id" => "",
    "hotel_id" => "",
    "guest_id" => "",
    "phone_no" => "",
    "room_id" => "",
    "deposit_payment" => "",
    "room_charge" => "",
    "check_in" => "",
    "check_out" => "",
    "num_of_adults" => "",
    "num_of_children" => ""
];

// Load booking data if editing
if (isset($_GET['booking_id']) && isset($_GET['hotel_id'])) {
    $bid = $_GET['booking_id'];
    $hid = $_GET['hotel_id'];

    $stmt = $conn->prepare("SELECT * FROM booking WHERE booking_id = ? AND hotel_id = ?");
    $stmt->bind_param("ss", $bid, $hid);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $booking = $res->fetch_assoc();  
    } else {
        echo "<script>alert('Booking not found for given Hotel!'); window.location.href='searchbooking.php';</script>";
        exit;
    }
}

// Update booking
if (isset($_POST['update'])) {
    $check_in = str_replace("T", " ", $_POST['check_in']) . ":00";
    $check_out = str_replace("T", " ", $_POST['check_out']) . ":00";

    $stmt = $conn->prepare("UPDATE booking 
        SET guest_id=?, phone_no=?, room_id=?, deposit_payment=?, room_charge=?, 
            check_in=?, check_out=?, num_of_adults=?, num_of_children=? 
        WHERE booking_id=? AND hotel_id=?");

    $stmt->bind_param(
        "sssddssiiss",
        $_POST['guest_id'],
        $_POST['phone_no'],
        $_POST['room_id'],
        $_POST['deposit_payment'],
        $_POST['room_charge'],
        $check_in,
        $check_out,
        $_POST['num_of_adults'],
        $_POST['num_of_children'],
        $_POST['booking_id'],
        $_POST['hotel_id']
    );

    if ($stmt->execute()) {
        echo "<script>alert('Booking updated successfully! ID: ".$_POST['booking_id']."'); window.location.href='searchbooking.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error: ".$stmt->error."');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Booking</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.form-container { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin: 80px auto; max-width: 800px; }
.form-label { font-size: 14px; font-weight: 500; }
.form-control { font-size: 14px; padding: 6px 10px; }
h2 { font-size: 22px; margin-bottom: 15px; }
.btn { padding: 6px 18px; font-size: 14px; }
</style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Update Booking</h2>
        <form method="POST" class="row g-3" novalidate>

            <div class="col-md-6">
                <label class="form-label">Booking ID</label>
                <input type="text" name="booking_id" class="form-control" value="<?= htmlspecialchars($booking['booking_id']) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">Hotel ID</label>
                <input type="text" name="hotel_id" class="form-control" value="<?= htmlspecialchars($booking['hotel_id']) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">Guest ID / Name</label>
                <input type="text" name="guest_id" class="form-control" value="<?= htmlspecialchars($booking['guest_id']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone_no" class="form-control" value="<?= htmlspecialchars($booking['phone_no']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Room</label>
                <input type="text" name="room_id" class="form-control" value="<?= htmlspecialchars($booking['room_id']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Deposit Payment</label>
                <input type="number" step="0.01" name="deposit_payment" class="form-control" value="<?= htmlspecialchars($booking['deposit_payment']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Room Charge</label>
                <input type="number" step="0.01" name="room_charge" class="form-control" value="<?= htmlspecialchars($booking['room_charge']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Check-in</label>
                <input type="datetime-local" name="check_in" class="form-control" 
                       value="<?= !empty($booking['check_in']) ? date('Y-m-d\TH:i', strtotime($booking['check_in'])) : '' ?>" 
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Check-out</label>
                <input type="datetime-local" name="check_out" class="form-control" 
                       value="<?= !empty($booking['check_out']) ? date('Y-m-d\TH:i', strtotime($booking['check_out'])) : '' ?>" 
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Adults</label>
                <input type="number" name="num_of_adults" class="form-control" value="<?= htmlspecialchars($booking['num_of_adults']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Children</label>
                <input type="number" name="num_of_children" class="form-control" value="<?= htmlspecialchars($booking['num_of_children']) ?>" required>
            </div>

            <div class="col-12 text-center mt-3">
                <button type="submit" name="update" class="btn btn-warning">Update Booking</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
