<?php
session_start();
require_once 'connect.php';

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
			margin: 0;
			padding: 0;
			box-sizing: border-box;
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
                    <th>Action</th>
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
            <td class=\"text-center\">
                <button type=\"button\" class=\"btn btn-sm btn-danger\" onclick=\"openAuthModal('delete', '{$id}')\">
                    <i class=\"fa-solid fa-trash\"></i>
                </button>
                <button type=\"button\" class=\"btn btn-sm btn-primary ms-1\" onclick=\"openAuthModal('edit', '{$id}')\">
                    <i class=\"fa-solid fa-pen-to-square\"></i>
                </button>
            </td>
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
                <button type=\"button\" class=\"btn btn-sm btn-danger w-100 mt-2\" onclick=\"openAuthModal('delete', '{$id}')\">
                    <i class=\"fa-solid fa-trash\"></i>
                </button>
                <button type=\"button\" class=\"btn btn-sm btn-primary mt-2\" onclick=\"openAuthModal('edit', '{$id}')\">
                    <i class=\"fa-solid fa-pen-to-square\"></i>
                </button>
            </div>
        </div>";
    }

    // Delete booking (only from logged-in hotel)
    public function deleteBooking($bookingId, $hotel_id)
    {
        $stmt = $this->db_handle->prepare("DELETE FROM booking WHERE booking_id = ? AND hotel_id=?");
        $stmt->bind_param("si", $bookingId, $hotel_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "<script>alert('Booking Deleted Successfully'); window.location.href='searchbooking1.php';</script>";
            exit;
        } else {
            echo "<div class='alert alert-danger text-center mt-3'>Error: Booking not found or already deleted.</div>";
        }
        $stmt->close();
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

// ✅ Admin authentication for delete or edit (hardcoded, no DB check)
if (isset($_POST['action']) && isset($_POST['admin_id']) && isset($_POST['admin_pass'])) {
    $adminEmail = "admin123@gmail.com";
    $adminPass  = "1234";

    if ($_POST['admin_id'] === $adminEmail && $_POST['admin_pass'] === $adminPass) {
        // Admin verified
        if ($_POST['action'] == "delete") {
            $search->deleteBooking($_POST['booking_id'], $hotel_id);
        } elseif ($_POST['action'] == "edit") {
            echo "<script>window.location.href='updatebooking.php?booking_id=" . urlencode($_POST['booking_id']) . "&hotel_id=" . urlencode($hotel_id) . "';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Invalid Admin Credentials!');</script>";
    }
}

if (isset($_POST['action']) && $_POST['action'] == "delete") {
    $search->deleteBooking($_POST['booking_id'], $hotel_id);
} elseif (isset($_POST['b1'])) {
    $search->showAllBookings($hotel_id);
} elseif (isset($_POST['b2'])) {
    $search->searchBooking($_POST['t1'], $hotel_id);
} else {
    $search->showAllBookings($hotel_id);
}

?>

    <!-- Admin Auth Modal -->
    <div class="modal fade" id="authModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admin Authentication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="modalAction">
                <input type="hidden" name="booking_id" id="modalBookingId">
                <div class="mb-3">
                <label>Admin ID</label>
                <input type="text" name="admin_id" class="form-control" required>
                </div>
                <div class="mb-3">
                <label>Password</label>
                <input type="password" name="admin_pass" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-dark" type="submit">Confirm</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAuthModal(action, bookingId) {
    document.getElementById('modalAction').value = action;
    document.getElementById('modalBookingId').value = bookingId;
    var modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
}
</script>
</body>
</html>
