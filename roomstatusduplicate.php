<?php
// Database connection aur navigation bar include kar rahe hain
include 'connect.php';
include 'navbar.php';

// ✅ connect.php ke connect class ko extend karke getConnection() method banaya
class connect_fixed extends connect {
    public function getConnection() {
        return $this->db_handle; // Database handle return karega
    }
}

// Database connection object create karna
$db = new connect_fixed();
$conn = $db->getConnection();

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Default room status data
$roomStatus = [
    "room_no" => "",
    "room_type" => "",
    "room_status" => "",
    "role_id" => "",
    "date" => "",
    "time" => ""
];

// Reset form
if (isset($_POST['new'])) {
    $roomStatus = [
        "room_no" => "",
        "room_type" => "",
        "room_status" => "",
        "role_id" => "",
        "date" => "",
        "time" => ""
    ];
}

// ========================== SAVE ==========================
if (isset($_POST['save'])) {
    $stmt = $conn->prepare("INSERT INTO roomstatus (room_no, room_type, room_status, role_id, date, time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss",
        $_POST['room_no'],
        $_POST['room_type'],
        $_POST['room_status'],
        $_POST['role_id'],
        $_POST['date'],
        $_POST['time']
    );
    if ($stmt->execute()) {
        // Also update room table cleaning status
        $updateRoom = $conn->prepare("UPDATE room SET roomstatus = 'Cleaned' WHERE roomno = ?");
        $updateRoom->bind_param("s", $_POST['room_no']);
        $updateRoom->execute();

        echo "<script>alert('Record saved successfully');</script>";
    } else {
        echo "<script>alert('Error saving record');</script>";
    }
}

// ========================== SEARCH ==========================
if (isset($_POST['search'])) {
    $stmt = $conn->prepare("SELECT * FROM room WHERE roomno = ?");
    $stmt->bind_param("s", $_POST['room_no']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $data = $res->fetch_assoc();
        $roomStatus['room_no'] = $data['roomno'];
        $roomStatus['room_type'] = $data['roomtype'];
        $roomStatus['room_status'] = $data['roomstatus'];
    } else {
        echo "<script>alert('Room not found');</script>";
    }
}

// ========================== UPDATE ==========================
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE roomstatus SET room_type=?, room_status=?, role_id=?, date=?, time=? WHERE room_no=?");
    $stmt->bind_param("ssssss",
        $_POST['room_type'],
        $_POST['room_status'],
        $_POST['role_id'],
        $_POST['date'],
        $_POST['time'],
        $_POST['room_no']
    );
    if ($stmt->execute()) {
        echo "<script>alert('Record updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating record');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Room Status Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body { background-color: #f8f9fa; }
    .form-container {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin: 80px auto;
        max-width: 800px;
    }
    h2 { font-size: 22px; margin-bottom: 15px; }
</style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Room Status Management</h2>

        <form method="POST" class="row g-3">

            <!-- Room Number -->
            <div class="col-md-6">
                <label class="form-label">Room No.</label>
                <input type="text" name="room_no" class="form-control" value="<?= htmlspecialchars($roomStatus['room_no']) ?>" required>
            </div>

            <!-- Room Type -->
            <div class="col-md-6">
                <label class="form-label">Room Type</label>
                <input type="text" name="room_type" class="form-control" value="<?= htmlspecialchars($roomStatus['room_type']) ?>">
            </div>

            <!-- Room Status -->
            <div class="col-md-6">
                <label class="form-label">Room Status</label>
                <input type="text" name="room_status" class="form-control" value="<?= htmlspecialchars($roomStatus['room_status']) ?>">
            </div>

            <!-- Role ID -->
            <div class="col-md-6">
                <label class="form-label">Role ID</label>
                <input type="text" name="role_id" class="form-control" value="<?= htmlspecialchars($roomStatus['role_id']) ?>">
            </div>

            <!-- Date -->
            <div class="col-md-6">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($roomStatus['date']) ?>">
            </div>

            <!-- Time -->
            <div class="col-md-6">
                <label class="form-label">Time</label>
                <input type="time" name="time" class="form-control" value="<?= htmlspecialchars($roomStatus['time']) ?>">
            </div>

            <!-- Action buttons -->
            <div class="col-12 text-center mt-3">
                <button type="submit" name="save" class="btn btn-primary">Save</button>
                <button type="submit" name="search" class="btn btn-info">Search</button>
                <button type="submit" name="update" class="btn btn-warning">Update</button>
                <button type="submit" name="new" class="btn btn-secondary">New</button>
                <a href="searchroomstatus.php" class="btn btn-success">All Search</a>
            </div>

        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
