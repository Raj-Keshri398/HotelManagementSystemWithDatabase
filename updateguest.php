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

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['email']) || !isset($_SESSION['hotel_id'])) {
    echo "<script>alert('Please login to manage guest.'); window.location.href='login.php';</script>";
    exit;
}

$hotel_id = $_SESSION['hotel_id']; // ✅ Hotel restriction

// Guest data default
$guest = [
    "guest_id" => "",
    "id_type" => "",
    "guest_id_no" => "",
    "title" => "",
    "name" => "",
    "gender" => "",
    "dob" => "",
    "phone_no" => "",
    "email" => "",
    "address" => "",
    "city" => "",
    "country" => ""
];

// Validate phone/email
function validateGuestData($phone, $email) {
    if (!preg_match('/^\d{10}$/', $phone)) {
        return "Phone number must be exactly 10 digits.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }
    return "";
}

// ✅ Update guest
if (isset($_POST['update'])) {
    $guest_id = intval($_POST['guest_id']);
    $errorMsg = validateGuestData($_POST['phone_no'], $_POST['email']);
    if ($errorMsg !== "") {
        echo "<script>alert('$errorMsg');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE guest 
            SET id_type=?, title=?, name=?, gender=?, dob=?, phone_no=?, email=?, address=?, city=?, country=? 
            WHERE guest_id=? AND hotel_id=?");
        $stmt->bind_param(
            "ssssssssssii",
            $_POST['id_type'],
            $_POST['title'],
            $_POST['name'],
            $_POST['gender'],
            $_POST['dob'],
            $_POST['phone_no'],
            $_POST['email'],
            $_POST['address'],
            $_POST['city'],
            $_POST['country'],
            $guest_id,
            $hotel_id
        );
        if ($stmt->execute()) {
            echo "<script>alert('Guest updated successfully'); window.location.href='searchguest.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error: ".$stmt->error."');</script>";
        }
    }
}

// ✅ Load guest for editing
if (isset($_GET['guest_id'])) {
    $guest_id = intval($_GET['guest_id']);
    $stmt = $conn->prepare("SELECT * FROM guest WHERE guest_id = ? AND hotel_id = ?");
    $stmt->bind_param("ii", $guest_id, $hotel_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $guest = $res->fetch_assoc();
    } else {
        echo "<script>alert('Guest not found for this hotel'); window.location.href='searchguest.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Guest</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.form-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin: 80px auto; max-width: 800px; }
.form-label { font-size: 14px; font-weight: 500; }
.form-control, textarea { font-size: 14px; padding: 6px 10px; }
textarea { resize: none; height: 60px; }
h2 { font-size: 22px; margin-bottom: 15px; }
.btn { padding: 6px 16px; font-size: 14px; }
</style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Update Guest</h2>
        <form method="POST" class="row g-3">
            <!-- ✅ Hidden Guest ID -->
            <input type="hidden" name="guest_id" value="<?= htmlspecialchars($guest['guest_id']) ?>">

            <div class="col-md-6">
                <label class="form-label">ID Type</label>
                <input type="text" name="id_type" class="form-control" value="<?= htmlspecialchars($guest['id_type']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Guest Title</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($guest['title']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Guest ID Number</label>
                <input type="text" name="guest_id_no" class="form-control" value="<?= htmlspecialchars($guest['guest_id_no']) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($guest['name']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Gender</label>
                <input type="text" name="gender" class="form-control" value="<?= htmlspecialchars($guest['gender']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($guest['dob']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone_no" class="form-control" value="<?= htmlspecialchars($guest['phone_no']) ?>" maxlength="10" required oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10);">
            </div>

            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($guest['email']) ?>" required>
            </div>

            <div class="col-12">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control"><?= htmlspecialchars($guest['address']) ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($guest['city']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Country</label>
                <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($guest['country']) ?>">
            </div>

            <div class="col-12 text-center mt-3">
                <button type="submit" name="update" class="btn btn-warning">Update</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
