<?php
session_start();
include 'connect.php';

// ✅ Extend connect class to get database connection
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

// ✅ Check login
if(!isset($_SESSION['email'])){
    echo "<script>alert('Please login first'); window.location.href='login.php';</script>";
    exit;
}

// ✅ Get current logged-in hotel info
$stmt = $conn->prepare("SELECT hotel_id, hotel_name FROM login WHERE email=?");
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

// ✅ Initialize staff data
$staff = [
    "staff_id" => "",
    "hotel_id" => $hotel_id,
    "hotel_name" => $hotel_name,
    "role_id" => "",
    "name" => "",
    "dob" => "",
    "gender" => "",
    "phone_no" => "",
    "email" => "",
    "password" => "",
    "salary" => ""
];

// ✅ Reset form
if (isset($_POST['new'])) {
    $staff = [
        "staff_id" => "",
        "hotel_id" => $hotel_id,
        "hotel_name" => $hotel_name,
        "role_id" => "",
        "name" => "",
        "dob" => "",
        "gender" => "",
        "phone_no" => "",
        "email" => "",
        "password" => "",
        "salary" => ""
    ];
}

// Validation
function validateStaff($salary) {
    if (!is_numeric($salary) || $salary < 0) return "Salary must be positive";
    return "";
}

// Generate staff_id
function generateStaffId($conn, $hotel_id) {
    $stmt = $conn->prepare("SELECT hotel_name FROM hotel WHERE hotel_id=?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    // Hotel name without spaces and in uppercase
    $hotel = isset($data['hotel_name']) ? preg_replace('/\s+/', '', strtoupper($data['hotel_name'])) : 'HOTEL';

    // Random 4-digit number
    $rand = mt_rand(1000, 9999);

    // Final staff_id format
    return $hotel . "--" . $rand;
}

// ✅ Save staff
if (isset($_POST['save'])) {
    $err = validateStaff($_POST['salary']);
    if ($err !== "") {
        echo "<script>alert('$err');</script>";
    } else {
        $staff_id = generateStaffId($conn, $hotel_id);

        // ✅ Insert into staff table
        $stmt = $conn->prepare("INSERT INTO staff 
            (staff_id, hotel_id, hotel_name, role_id, name, dob, gender, phone_no, email, password, salary) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if(!$stmt){
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "sissssssssd",
            $staff_id,
            $hotel_id,
            $hotel_name,
            $_POST['role_id'],
            $_POST['name'],
            $_POST['dob'],
            $_POST['gender'],
            $_POST['phone_no'],
            $_POST['email'],
            $_POST['password'],
            $_POST['salary']
        );

        if ($stmt->execute()) {
            $stmt->close();

            // ✅ Insert also into login table
            $stmt2 = $conn->prepare("INSERT INTO login 
                (name, phone_no, email, password, hotel_id, hotel_name) 
                VALUES (?, ?, ?, ?, ?, ?)");
            if(!$stmt2){
                die("Prepare failed: " . $conn->error);
            }
            $stmt2->bind_param(
                "ssssis",
                $_POST['name'],
                $_POST['phone_no'],
                $_POST['email'],
                $_POST['password'],
                $hotel_id,
                $hotel_name
            );
            $stmt2->execute();
            $stmt2->close();

            echo "<script>alert('Staff & Login created successfully! Staff ID: $staff_id'); window.location.href='staff.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error saving staff: ".$stmt->error."');</script>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Management</title>
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
        <h2 class="text-center mb-4">Staff Management</h2>
        <form method="POST" class="row g-3" novalidate>

            <div class="col-md-6">
                <label class="form-label">Hotel</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($hotel_name) ?>" readonly>
                <input type="hidden" name="hotel_id" value="<?= $hotel_id ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                    <option value="">-- Select Role --</option>
                    <?php
                        $roles = $conn->prepare("SELECT role_id, role_name FROM role WHERE hotel_id=?");
                        $roles->bind_param("i", $hotel_id);
                        $roles->execute();
                        $roleRes = $roles->get_result();
                        while ($r = $roleRes->fetch_assoc()) {
                            $value = $r['role_id'] . " - " . $r['role_name'];
                            echo "<option value='{$r['role_id']}'>$value</option>";
                        }
                        $roles->close();
                    ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">-- Select Gender --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" 
                        name="phone_no" 
                        class="form-control" 
                        maxlength="10" 
                        pattern="[0-9]{10}" 
                        title="Please enter exactly 10 digits" 
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')" 
                        required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Salary</label>
                <input type="number" step="0.01" name="salary" class="form-control" required>
            </div>

            <div class="col-12 text-center mt-3">
                <button type="submit" name="save" class="btn btn-primary">Save</button>
                <button type="submit" name="update" class="btn btn-warning">Update Booking</button>
                <button type="submit" name="new" class="btn btn-secondary">New</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
