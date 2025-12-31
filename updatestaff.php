<?php
session_start();
include 'connect.php';

class connect_fixed extends connect {
    public function getConnection() {
        return $this->db_handle;
    }
}

$db = new connect_fixed();
$conn = $db->getConnection();

if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please login first'); window.location.href='login.php';</script>";
    exit;
}

// ✅ Logged in hotel info
$stmt = $conn->prepare("SELECT hotel_id FROM login WHERE email=?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo "<script>alert('Hotel not found!'); window.location.href='login.php';</script>";
    exit;
}
$user = $res->fetch_assoc();
$hotel_id = $user['hotel_id'];
$stmt->close();

$staff = [
    "staff_id" => "",
    "name" => "",
    "role_id" => "",
    "gender" => "",
    "dob" => "",
    "phone_no" => "",
    "email" => "",
    "salary" => "",
    "password" => ""
];

// ✅ Load staff data
if (isset($_GET['staff_id'])) {
    $sid = $_GET['staff_id'];
    $stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id=? AND hotel_id=?");
    $stmt->bind_param("si", $sid, $hotel_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $staff = $res->fetch_assoc();
    } else {
        echo "<script>alert('Staff not found!'); window.location.href='searchstaff.php';</script>";
        exit;
    }
    $stmt->close();
}

// ✅ Update staff
if (isset($_POST['update'])) {
    // --- Staff Update Query ---
    $stmt = $conn->prepare("UPDATE staff 
        SET name=?, role_id=?, gender=?, dob=?, phone_no=?, email=?, salary=?, password=? 
        WHERE staff_id=? AND hotel_id=?");
    $stmt->bind_param("ssssssissi", 
        $_POST['name'],
        $_POST['role_id'],
        $_POST['gender'],
        $_POST['dob'],
        $_POST['phone_no'],
        $_POST['email'],
        $_POST['salary'],
        $_POST['password'],
        $_POST['staff_id'],
        $hotel_id
    );

    if ($stmt->execute()) {
        $stmt->close();

        // --- Login Update Query ---
        $stmt2 = $conn->prepare("UPDATE login 
            SET name=?, email=?, phone_no=?, password=? 
            WHERE hotel_id=? AND name=? AND email=? AND phone_no=?");
        $stmt2->bind_param("ssssisss", 
            $_POST['name'],
            $_POST['email'],
            $_POST['phone_no'],
            $_POST['password'],
            $hotel_id,
            $staff['name'],      // old name
            $staff['email'],     // old email
            $staff['phone_no']   // old phone
        );
        $stmt2->execute();
        $stmt2->close();

        echo "<script>alert('Staff & Login updated successfully!'); window.location.href='searchstaff.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error: ".$stmt->error."');</script>";
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Staff</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.form-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin: 50px auto; max-width: 900px; }
h2 { font-size: 22px; margin-bottom: 20px; }
.btn { padding: 6px 18px; font-size: 14px; }
</style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Edit Staff Details</h2>
        <form method="POST" class="row g-3">
            <input type="hidden" name="staff_id" value="<?= htmlspecialchars($staff['staff_id']) ?>">

            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($staff['name']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Role</label>
                <input type="text" name="role_id" class="form-control" value="<?= htmlspecialchars($staff['role_id']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Gender</label>
                <input type="text" name="gender" class="form-control" value="<?= htmlspecialchars($staff['gender']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">DOB</label>
                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($staff['dob']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone_no" class="form-control" value="<?= htmlspecialchars($staff['phone_no']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Salary</label>
                <input type="number" step="0.01" name="salary" class="form-control" value="<?= htmlspecialchars($staff['salary']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Password (leave blank if no change)</label>
                <input type="password" name="password" class="form-control">
            </div>

            <div class="col-12 text-center mt-3">
                <button type="submit" name="update" class="btn btn-warning">Update Staff</button>
                <a href="searchstaff.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
