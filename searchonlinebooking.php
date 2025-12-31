<?php
session_start();
require_once 'connect.php';
include 'navbar.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please login first'); window.location.href='login.php';</script>";
    exit;
}

// Get hotel_id and hotel_name
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
body { background-color: #f8f9fa; margin:0; padding:0; box-sizing:border-box; }
.navbar-space { margin-top: 80px; }
.search-form input { height: 45px; }
</style>
</head>
<body>
<div class="container navbar-space">

<!-- Search & Refresh -->
<div class="row g-3 align-items-center mb-4">
    <div class="col">
        <form method="post" class="row g-2 search-form" autocomplete="off">
            <div class="col-12 col-md">
                <input type="text" name="t1" class="form-control shadow-sm" placeholder="Enter Booking ID or Guest Name"
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

class SearchOnlineBooking extends Connect
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

        echo "<div class='d-none d-md-block table-responsive shadow-sm rounded'>
                <table class='table table-striped table-hover table-bordered align-middle text-nowrap'>
                <thead class='table-dark text-center'>
                <tr>
                    <th>Booking ID</th>
                    <th>Name</th>
                    <th>Mobile No</th>
                    <th>Room Type</th>
                    <th>Room Price</th>
                    <th>Description</th>
                    <th>Booking Date</th>
                    <th>Booking Time</th>
                    <th>No. of People</th>
                    <th>Status</th>
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

        // Mobile Card View
        echo "<div class='d-block d-md-none'>";
        foreach ($rows as $row) {
            echo $this->renderCard($row);
        }
        echo "</div>";
    }

    private function renderRow($row)
    {
        $id = htmlspecialchars($row['booking_id']);
        $status = ucfirst($row['status']);
        $actionContent = ($row['status'] === 'confirmed' || $row['status'] === 'cancelled') 
            ? "<span class='text-success'>{$status}</span>"
            : "<button type='button' class='btn btn-sm btn-success' onclick=\"updateStatus('{$id}','confirmed')\">Confirm</button>
               <button type='button' class='btn btn-sm btn-danger ms-1' onclick=\"updateStatus('{$id}','cancelled')\">Cancel</button>
               <button type='button' class='btn btn-sm btn-warning ms-1' onclick=\"openAuthModal('delete','{$id}')\">Delete</button>";

        return "<tr>
            <td>{$id}</td>
            <td>{$row['name']}</td>
            <td>{$row['mobile_no']}</td>
            <td>{$row['room_type']}</td>
            <td>{$row['room_price']}</td>
            <td>{$row['description']}</td>
            <td>{$row['booking_date']}</td>
            <td>{$row['booking_time']}</td>
            <td>{$row['number_of_people']}</td>
            <td class='text-center'>{$status}</td>
            <td class='text-center' id='action-{$id}'>
                {$actionContent}
            </td>
        </tr>";
    }

    private function renderCard($row)
    {
        $id = htmlspecialchars($row['booking_id']);
        $status = ucfirst($row['status']);
        $actionContent = ($row['status'] === 'confirmed' || $row['status'] === 'cancelled') 
            ? "<span class='text-success'>{$status}</span>"
            : "<button type='button' class='btn btn-sm btn-success w-100 mt-2' onclick=\"updateStatus('{$id}','confirmed')\">Confirm</button>
               <button type='button' class='btn btn-sm btn-danger w-100 mt-2' onclick=\"updateStatus('{$id}','cancelled')\">Cancel</button>
               <button type='button' class='btn btn-sm btn-warning w-100 mt-2' onclick=\"openAuthModal('delete','{$id}')\">Delete</button>";

        return "<div class='card shadow-sm mb-3'>
            <div class='card-body'>
                <p><strong>Booking ID:</strong> {$id}</p>
                <p><strong>Name:</strong> {$row['name']}</p>
                <p><strong>Mobile:</strong> {$row['mobile_no']}</p>
                <p><strong>Room Type:</strong> {$row['room_type']}</p>
                <p><strong>Room Price:</strong> {$row['room_price']}</p>
                <p><strong>Description:</strong> {$row['description']}</p>
                <p><strong>Booking Date:</strong> {$row['booking_date']}</p>
                <p><strong>Booking Time:</strong> {$row['booking_time']}</p>
                <p><strong>No. of People:</strong> {$row['number_of_people']}</p>
                <p><strong>Status:</strong> {$status}</p>
                <div id='action-{$id}'>{$actionContent}</div>
            </div>
        </div>";
    }

    public function showAllBookings($hotel_id)
    {
        $stmt = $this->db_handle->prepare("SELECT * FROM online_booking WHERE hotel_id=? ORDER BY online_booking_id ASC");
        $stmt->bind_param("i", $hotel_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $this->renderTable($res);
        $stmt->close();
    }

    public function searchBooking($keyword, $hotel_id)
    {
        $keyword = "%".trim($keyword)."%";
        $stmt = $this->db_handle->prepare("SELECT * FROM online_booking WHERE (booking_id LIKE ? OR name LIKE ?) AND hotel_id=?");
        $stmt->bind_param("ssi", $keyword, $keyword, $hotel_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $this->renderTable($res);
        $stmt->close();
    }

    public function deleteBooking($bookingId)
    {
        $stmt = $this->db_handle->prepare("DELETE FROM online_booking WHERE booking_id=?");
        $stmt->bind_param("s", $bookingId);
        if($stmt->execute()){
            echo "<script>alert('Booking deleted successfully'); window.location.href='searchonlinebooking.php?refresh=1';</script>";
            exit;
        } else {
            echo "<div class='alert alert-danger text-center mt-3'>Error: Could not delete booking.</div>";
        }
        $stmt->close();
    }
}

$search = new SearchOnlineBooking();

// Admin authentication for delete
if(isset($_POST['action'], $_POST['admin_id'], $_POST['admin_pass'], $_POST['booking_id'])){
    $adminEmail = "admin123@gmail.com";
    $adminPass  = "1234";

    if($_POST['admin_id'] === $adminEmail && $_POST['admin_pass'] === $adminPass){
        if($_POST['action'] == "delete"){
            $search->deleteBooking($_POST['booking_id']);
        }
    } else {
        echo "<script>alert('Invalid Admin Credentials!');</script>";
    }
}

// Handle search/refresh
if(isset($_POST['b1']) || isset($_GET['refresh'])) {
    $search->showAllBookings($hotel_id);
} elseif(isset($_POST['b2'])) {
    $search->searchBooking($_POST['t1'], $hotel_id);
} else {
    $search->showAllBookings($hotel_id);
}
?>

<!-- Admin Auth Modal for Delete -->
<div class="modal fade" id="authModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admin Authentication for Delete</h5>
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
                <button class="btn btn-dark" type="submit">Delete Booking</button>
            </div>
        </form>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Open modal for delete
function openAuthModal(action, bookingId) {
    document.getElementById('modalAction').value = action;
    document.getElementById('modalBookingId').value = bookingId;
    var modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
}

// Update status via AJAX for Confirm/Cancel
function updateStatus(bookingId, status) {
    fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'booking_id=' + encodeURIComponent(bookingId) + '&status=' + encodeURIComponent(status)
    })
    .then(response => response.text())
    .then(data => {
        alert('Booking status updated to ' + status.charAt(0).toUpperCase() + status.slice(1));
        // Remove Confirm/Cancel buttons and show status text
        let actionCell = document.getElementById('action-' + bookingId);
        if(actionCell){
            actionCell.innerHTML = "<span class='text-success'>" + status.charAt(0).toUpperCase() + status.slice(1) + "</span>";
        }
    })
    .catch(err => console.error(err));
}
</script>
</body>
</html>
