<?php 
session_start();

// Include DB connection
include 'connect.php';
include 'navbar.php';

// Agar login nahi hai to redirect
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please login first'); window.location.href='login.php';</script>";
    exit;
}

// ✅ Fix DB connection
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

// ✅ Hotel id from session
$hotel_id = $_SESSION['hotel_id'];

// ✅ Handle Cleaning Status Update
if (isset($_POST['update_cleaning'])) {
    $room_id_val = mysqli_real_escape_string($conn, $_POST['room_id']);
    $staff_id_val = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $status = mysqli_real_escape_string($conn, $_POST['cleaning_status']);

    // Room No nikalna
    $room_q = mysqli_query($conn, "SELECT room_no FROM room WHERE room_id='$room_id_val' AND hotel_id='$hotel_id'");
    $room_row = mysqli_fetch_assoc($room_q);
    $room_no = $room_row['room_no'];

    // Staff name nikalna
    $staff_q = mysqli_query($conn, "SELECT name FROM staff WHERE staff_id='$staff_id_val' AND hotel_id='$hotel_id'");
    $staff_row = mysqli_fetch_assoc($staff_q);
    $staff_name = $staff_row['name'];

    // Final values
    $room_final = $room_id_val . " - " . $room_no;
    $staff_final = $staff_id_val . " - " . $staff_name;

    // Update room table
    $update_room = "UPDATE room SET cleaning_status='$status' WHERE room_id='$room_id_val' AND hotel_id='$hotel_id'";
    if (mysqli_query($conn, $update_room)) {
        // Insert into room_status
        $insert_status = "INSERT INTO room_status (room_id, staff_id, hotel_id, cleaning_date_time, cleaning_status)
                          VALUES ('$room_final', '$staff_final', '$hotel_id', NOW(), '$status')";
        if (mysqli_query($conn, $insert_status)) {
            echo "<script>alert('Cleaning status updated successfully!'); window.location.href='roomstatus.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error inserting room_status: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Error updating room: " . mysqli_error($conn) . "');</script>";
    }
}

// ✅ Handle Search Query
$search_room = "";
$where_clause = "WHERE hotel_id='$hotel_id' AND booking_status='Available' AND cleaning_status='Uncleaned'";
if (isset($_GET['search_room']) && !empty($_GET['search_room'])) {
    $search_room = mysqli_real_escape_string($conn, $_GET['search_room']);
    $where_clause .= " AND room_no LIKE '%$search_room%'";
}

// ✅ Fetch Rooms
$query = "SELECT room_id, room_no, booking_status, cleaning_status 
          FROM room 
          $where_clause 
          ORDER BY room_no ASC";
$result = mysqli_query($conn, $query);

// ✅ Cleaning History (last 10 from room_status only)
$history_query = "
    SELECT status_id, room_id, staff_id, cleaning_date_time, cleaning_status
    FROM room_status
    WHERE hotel_id = '$hotel_id'
    ORDER BY status_id DESC
    LIMIT 10
";
$history_result = mysqli_query($conn, $history_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Room Cleaning Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-space { margin-top: 80px; }
        .card { border-radius: 12px; }
    </style>
</head>
<body>

<div class="container navbar-space">

    <h3 class="text-center mb-4">
        <i class="fa-solid fa-broom"></i> Room Cleaning Status
    </h3>

    <div class="row g-4">

        <!-- Left Form -->
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm p-3">
                <h5 class="mb-3"><i class="fa-solid fa-check"></i> Update Cleaning Status</h5>
                <form method="post">
                    <!-- Room Dropdown -->
                    <div class="mb-3">
                        <label class="form-label">Select Room</label>
                        <select name="room_id" class="form-select" required>
                            <option value="">-- Select Room --</option>
                            <?php
                            $room_query = "SELECT room_id, room_no FROM room WHERE hotel_id='$hotel_id' AND cleaning_status='Uncleaned'";
                            $room_result = mysqli_query($conn, $room_query);
                            if ($room_result && mysqli_num_rows($room_result) > 0) {
                                while ($room = mysqli_fetch_assoc($room_result)) {
                                    echo "<option value='{$room['room_id']}'>Room {$room['room_no']}</option>";
                                }
                            } else {
                                echo "<option value=''>No uncleaned rooms</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <!-- Staff Dropdown -->
                    <div class="mb-3">
                        <label class="form-label">Select Staff</label>
                        <select name="staff_id" class="form-select" required>
                            <option value="">-- Select Staff --</option>
                            <?php
                            $staff_query = "SELECT staff_id, name, role_id FROM staff WHERE hotel_id='$hotel_id'";
                            $staff_result = mysqli_query($conn, $staff_query);
                            while ($staff = mysqli_fetch_assoc($staff_result)) {
                                echo "<option value='{$staff['staff_id']}'>{$staff['staff_id']} - {$staff['name']} - {$staff['role_id']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <!-- Cleaning Status Dropdown -->
                    <div class="mb-3">
                        <label class="form-label">Cleaning Status</label>
                        <select name="cleaning_status" class="form-select" required>
                            <option value="">-- Select Status --</option>
                            <option value="Cleaned">Cleaned</option>
                            <option value="Uncleaned">Uncleaned</option>
                        </select>
                    </div>
                    <button type="submit" name="update_cleaning" class="btn btn-success w-100">
                        <i class="fa-solid fa-check"></i> Update
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Search + Table -->
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm p-3">
                <h5 class="mb-3"><i class="fa-solid fa-magnifying-glass"></i> Search Room Status</h5>
                <form method="get" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search_room" class="form-control"
                               placeholder="Search by Room No" value="<?= htmlspecialchars($search_room) ?>">
                        <button class="btn btn-dark" type="submit">
                            <i class="fa-solid fa-search"></i>
                        </button>
                    </div>
                </form>

                <?php
                $rooms = [];
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $rooms[] = $row;
                    }
                }

                if (count($rooms) === 0) {
                    echo "<div class='alert alert-warning text-center'>No uncleaned rooms found.</div>";
                } else {
                    // Desktop Table
                    echo "<div class='d-none d-md-block table-responsive'>
                            <table class='table table-bordered table-hover text-center align-middle'>
                            <thead class='table-dark'>
                                <tr><th>Room No</th><th>Booking Status</th><th>Cleaning Status</th></tr>
                            </thead><tbody>";
                    foreach ($rooms as $row) {
                        echo "<tr>
                                <td>{$row['room_no']}</td>
                                <td>{$row['booking_status']}</td>
                                <td>{$row['cleaning_status']}</td>
                              </tr>";
                    }
                    echo "</tbody></table></div>";

                    // Mobile Cards
                    echo "<div class='d-block d-md-none'>";
                    foreach ($rooms as $row) {
                        echo "<div class='card shadow-sm mb-2'>
                                <div class='card-body'>
                                    <p><strong>Room No:</strong> {$row['room_no']}</p>
                                    <p><strong>Booking:</strong> {$row['booking_status']}</p>
                                    <p><strong>Status:</strong> {$row['cleaning_status']}</p>
                                </div>
                              </div>";
                    }
                    echo "</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Cleaning History -->
    <div class="card shadow-sm p-3 mt-4">
        <h5><i class="fa-solid fa-clock-rotate-left"></i> Recent Cleaning History</h5>
        <?php
        $history = [];
        if ($history_result && mysqli_num_rows($history_result) > 0) {
            while ($row = mysqli_fetch_assoc($history_result)) {
                $history[] = $row;
            }
        }
        if (count($history) === 0) {
            echo "<div class='alert alert-info mt-3 text-center'>No cleaning history found.</div>";
        } else {
            // Desktop Table
            echo "<div class='d-none d-md-block table-responsive mt-3'>
                    <table class='table table-bordered table-striped text-center'>
                    <thead class='table-dark'>
                        <tr><th>Room</th><th>Staff</th><th>Date Time</th><th>Status</th></tr>
                    </thead><tbody>";
            foreach ($history as $row) {
                $dt = date("Y-m-d H:i:s", strtotime($row['cleaning_date_time']));
                echo "<tr>
                        <td>{$row['room_id']}</td>
                        <td>{$row['staff_id']}</td>
                        <td>{$dt}</td>
                        <td>{$row['cleaning_status']}</td>
                      </tr>";
            }
            echo "</tbody></table></div>";

            // Mobile Cards
            echo "<div class='d-block d-md-none mt-3'>";
            foreach ($history as $row) {
                $dt = date("Y-m-d H:i:s", strtotime($row['cleaning_date_time']));
                echo "<div class='card shadow-sm mb-2'>
                        <div class='card-body'>
                            <p><strong>Room:</strong> {$row['room_id']}</p>
                            <p><strong>Staff:</strong> {$row['staff_id']}</p>
                            <p><strong>Date Time:</strong> {$dt}</p>
                            <p><strong>Status:</strong> {$row['cleaning_status']}</p>
                        </div>
                      </div>";
            }
            echo "</div>";
        }
        ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
