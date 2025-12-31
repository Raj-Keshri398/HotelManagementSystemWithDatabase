<?php
session_start();
include 'connect.php';
include 'navbar.php';

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

// Fetch logged-in hotel info
if(!isset($_SESSION['email'])){
    echo "<script>alert('Please login first'); window.location.href='login.php';</script>";
    exit;
}

$stmt = $conn->prepare("SELECT hotel_id, hotel_name FROM login WHERE email=?");
if(!$stmt){
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo "<script>alert('Hotel not found for this login.'); window.location.href='login.php';</script>";
    exit;
}
$userHotel = $res->fetch_assoc();
$hotel_id = $userHotel['hotel_id'];
$hotel_name = $userHotel['hotel_name'];
$stmt->close();

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
    "num_of_adults" => "",
    "num_of_children" => ""
];

// Reset form if "New" clicked
if (isset($_POST['new'])) {
    $booking = [
        "booking_id" => "",
        "hotel_id" => "",
        "guest_id" => "",
        "phone_no" => "",
        "room_id" => "",
        "deposit_payment" => "",
        "room_charge" => "",
        "check_in" => "",
        "num_of_adults" => "",
        "num_of_children" => ""
    ];
}

// Validate numeric fields
function validateBooking($deposit, $charge, $adults, $children) {
    if (!is_numeric($deposit) || $deposit < 0) return "Deposit must be positive";
    if (!is_numeric($charge) || $charge < 0) return "Room charge must be positive";
    if (!is_numeric($adults) || $adults < 0) return "Adults must be positive";
    if (!is_numeric($children) || $children < 0) return "Children must be positive";
    return "";
}

// Generate booking ID
function generateBookingId($conn, $hotel_id) {
    $stmt = $conn->prepare("SELECT hotel_name FROM hotel WHERE hotel_id=?");
    if(!$stmt) return $hotel_id . mt_rand(100000,999999); // fallback
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $hotel = isset($data['hotel_name']) ? preg_replace('/\s+/', '', strtoupper($data['hotel_name'])) : '';
    return $hotel_id . $hotel . mt_rand(100000, 999999);
}

// Save booking
if (isset($_POST['save'])) {
    $err = validateBooking($_POST['deposit_payment'], $_POST['room_charge'], $_POST['num_of_adults'], $_POST['num_of_children']);
    if ($err !== "") {
        echo "<script>alert('$err');</script>";
    } else {
        // Split room_id - room_no
        list($roomId, $roomNo) = explode(" - ", $_POST['room_id']);

        // Generate booking ID
        $booking_id = generateBookingId($conn, $_POST['hotel_id']);

        // Get guest info
        $guestSql = $conn->prepare("SELECT guest_id_no, name, phone_no FROM guest WHERE guest_id_no=?");
        if(!$guestSql){
            die("Prepare failed: " . $conn->error);
        }
        $guestSql->bind_param("s", $_POST['guest_id']);
        $guestSql->execute();
        $guestData = $guestSql->get_result()->fetch_assoc();
        $guestFull = $guestData['guest_id_no'] . " - " . $guestData['name'];
        $phoneNo = $guestData['phone_no'];
        $guestSql->close();

        // Combine room_id & room_no
        $roomFull = $_POST['room_id']; // "123 - 101"

        // Insert booking
        $stmt = $conn->prepare("INSERT INTO booking 
            (booking_id, hotel_id, guest_id, phone_no, room_id, deposit_payment, room_charge, check_in, check_out, num_of_adults, num_of_children) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)");
        if(!$stmt){
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "sisssddsii",
            $booking_id,
            $hotel_id,
            $guestFull,
            $phoneNo,
            $roomFull,
            $_POST['deposit_payment'],
            $_POST['room_charge'],    
            $_POST['check_in'],
            $_POST['num_of_adults'],
            $_POST['num_of_children']
        );

        if ($stmt->execute()) {
            // Update room as booked
            $conn->query("UPDATE room SET booking_status='Booked' WHERE room_id='" . intval($roomId) . "'");
            // Update guest status
            $conn->query("UPDATE guest SET guest_status='bye' WHERE guest_id_no='" . $guestData['guest_id_no'] . "'");
            echo "<script>alert('Booking saved successfully! ID: $booking_id'); window.location.href='booking.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error: ".$stmt->error."');</script>";
        }
    }
}

// Load booking data if editing
if (isset($_GET['booking_id'])) {
    $bid = $_GET['booking_id'];
    $stmt = $conn->prepare("SELECT * FROM booking WHERE booking_id = ?");
    $stmt->bind_param("s", $bid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $booking = $res->fetch_assoc();
    } else {
        echo "<script>alert('Booking not found'); window.location.href='searchbooking1.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.form-container { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin: 80px auto; max-width: 800px; }
.form-label { font-size: 14px; font-weight: 500; }
.form-control, .form-select { font-size: 14px; padding: 6px 10px; }
h2 { font-size: 22px; margin-bottom: 15px; }
.btn { padding: 4px 12px; font-size: 14px; }
</style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Booking Management</h2>
        <form method="POST" class="row g-3" novalidate>

            <div class="col-md-6">
                <label class="form-label">Hotel</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($hotel_name) ?>" readonly>
                <input type="hidden" name="hotel_id" value="<?= $hotel_id ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Guest</label>
                <select name="guest_id" id="guest_id" class="form-select" required>
                    <option value="">-- Select Guest --</option>
                    <?php
                        $guests = $conn->prepare("SELECT guest_id_no, name, phone_no FROM guest WHERE guest_status='welcome' AND hotel_id=?");
                        $guests->bind_param("i", $hotel_id);
                        $guests->execute();
                        $guestRes = $guests->get_result();
                        while ($g = $guestRes->fetch_assoc()) {
                            $sel = ($booking['guest_id'] == ($g['guest_id_no'] . " - " . $g['name'])) ? "selected" : "";
                            echo "<option value='{$g['guest_id_no']}' data-phone='{$g['phone_no']}' $sel>{$g['guest_id_no']} - {$g['name']}</option>";
                        }
                        $guests->close();
                    ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone_no" id="phone_no" class="form-control" value="<?= htmlspecialchars($booking['phone_no']) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">Room</label>
                <select name="room_id" id="room_id" class="form-select" required>
                    <option value="">-- Select Room --</option>
                    <?php
                    $rooms = $conn->prepare("SELECT room_id, room_no, room_charge FROM room WHERE booking_status='Available' AND cleaning_status='Cleaned' AND hotel_id=?");
                    $rooms->bind_param("i", $hotel_id);
                    $rooms->execute();
                    $roomRes = $rooms->get_result();
                    while ($r = $roomRes->fetch_assoc()) {
                        $roomFullValue = $r['room_id'] . " - " . $r['room_no'];
                        $sel = ($booking['room_id'] == $roomFullValue) ? "selected" : "";
                        echo "<option value='{$roomFullValue}' data-charge='{$r['room_charge']}' $sel>Room {$r['room_no']}</option>";
                    }
                    $rooms->close();
                    ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Deposit Payment</label>
                <input type="number" step="0.01" name="deposit_payment" class="form-control" value="<?= htmlspecialchars($booking['deposit_payment']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Room Charge</label>
                <input type="number" step="0.01" name="room_charge" id="room_charge" class="form-control" value="<?= htmlspecialchars($booking['room_charge']) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">Check-IN</label>
                <input type="datetime-local" name="check_in" class="form-control" value="<?= htmlspecialchars($booking['check_in']) ?>" required>
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
                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['booking_id']) ?>">
                <button type="submit" name="save" class="btn btn-primary">Save</button>
                <button type="submit" name="new" class="btn btn-secondary">New</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('guest_id').addEventListener('change', function(){
    let phone = this.options[this.selectedIndex].getAttribute('data-phone');
    document.getElementById('phone_no').value = phone || '';
});
document.getElementById('room_id').addEventListener('change', function(){
    let charge = this.options[this.selectedIndex].getAttribute('data-charge');
    document.getElementById('room_charge').value = charge || '';
});
</script>

</body>
</html>
