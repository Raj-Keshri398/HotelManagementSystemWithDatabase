<?php
session_start();
include 'connect.php';
include 'navbar.php';

// Agar login nahi hai to redirect
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
        body { background-color: #f8f9fa; }
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
                    <input type="text" name="t1" class="form-control shadow-sm"
                        placeholder="Enter Room Number to search"
                        value="<?= isset($_POST['b2']) ? htmlspecialchars($_POST['t1']) : ''; ?>">
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
        class SearchRoom extends Connect {

            public function __construct() {
                parent::__construct();
            }

            private function renderTable($result) {
                if (!$result || mysqli_num_rows($result) === 0) {
                    echo "<div class='alert alert-warning text-center shadow-sm'>No room found.</div>";
                    return;
                }

                // Desktop table
                echo "<div class='d-none d-md-block table-responsive shadow-sm rounded'>
                        <table class='table table-striped table-hover table-bordered align-middle text-nowrap'>
                        <thead class='table-dark text-center'>
                            <tr>
                                <th>Image</th>
                                <th>Room Number</th>
                                <th>Room Type</th>
                                <th>Room Charge</th>
                                <th>Description</th>
                                <th>Booking Status</th>
                                <th>Cleaning Status</th>
                            </tr>
                        </thead>
                        <tbody>";

                $rows = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $rows[] = $row;
                    echo $this->renderRow($row);
                }
                echo "</tbody></table></div>";

                // Mobile cards
                echo "<div class='d-block d-md-none'>";
                foreach ($rows as $row) echo $this->renderCard($row);
                echo "</div>";
            }

            private function renderRow($row) {
                $roomNo = htmlspecialchars($row['room_no']);
                $roomImage = !empty($row['room_image']) ? htmlspecialchars($row['room_image']) : 'images/default-room.jpg';

                return "<tr>
                    <td class='text-center'>
                        <img src='$roomImage' alt='Room Image' style='width:70px; height:70px; object-fit:cover; border-radius:5px;'>
                    </td>
                    <td>$roomNo</td>
                    <td>".htmlspecialchars($row['room_type'])."</td>
                    <td>".htmlspecialchars($row['room_charge'])."</td>
                    <td>".htmlspecialchars($row['description'])."</td>
                    <td>".htmlspecialchars($row['booking_status'])."</td>
                    <td>".htmlspecialchars($row['cleaning_status'])."</td>
                </tr>";
            }

            private function renderCard($row) {
                $roomNo = htmlspecialchars($row['room_no']);
                $roomImage = !empty($row['room_image']) ? htmlspecialchars($row['room_image']) : 'images/default-room.jpg';

                return "<div class='card shadow-sm mb-3'>
                    <img src='$roomImage' class='card-img-top' alt='Room Image' style='height:200px; object-fit:cover;'>
                    <div class='card-body'>
                        <p><strong>Room Number:</strong> $roomNo</p>
                        <p><strong>Room Type:</strong> ".htmlspecialchars($row['room_type'])."</p>
                        <p><strong>Room Charge:</strong> ".htmlspecialchars($row['room_charge'])."</p>
                        <p><strong>Description:</strong> ".htmlspecialchars($row['description'])."</p>
                        <p><strong>Booking Status:</strong> ".htmlspecialchars($row['booking_status'])."</p>
                        <p><strong>Cleaning Status:</strong> ".htmlspecialchars($row['cleaning_status'])."</p>
                    </div>
                </div>";
            }

            // ✅ Show all rooms (hotel-wise)
            public function showAllRooms($hotel_id) {
                $stmt = $this->db_handle->prepare("SELECT * FROM room WHERE hotel_id=? ORDER BY room_no ASC");
                $stmt->bind_param("i", $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }


            public function searchRoom($keyword, $hotel_id) {
                $keyword = trim($keyword);
                if ($keyword === '') { 
                    $this->showAllRooms($hotel_id); 
                    return; 
                }
                $stmt = $this->db_handle->prepare("SELECT * FROM room WHERE room_no LIKE ? AND hotel_id=?");
                $like = "%$keyword%";
                $stmt->bind_param("si", $like, $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }
        }

        $search = new SearchRoom();

        

        if (isset($_POST['b1'])) 
            $search->showAllRooms($hotel_id, $hotel_name);
        elseif (isset($_POST['b2'])) 
            $search->searchRoom(trim($_POST['t1']), $hotel_id, $hotel_name);
        else 
            $search->showAllRooms($hotel_id, $hotel_name);
    ?>

    
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
