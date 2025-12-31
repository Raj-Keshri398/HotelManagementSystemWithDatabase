<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'connect.php';

// ✅ Session check
if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

// ✅ DB connection
class ConnectFixed extends connect {
    public function getConnection() {
        return $this->db_handle;
    }
}
$db = new ConnectFixed();
$conn = $db->getConnection();
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

/* ----------------------------
   Default values
---------------------------- */
$hotel = [
    "hotel_id"     => "",
    "hotel_code"   => "",
    "hotel_name"   => "",
    "address"      => "",
    "pincode"      => "",
    "city"         => "",
    "country"      => "",
    "contact_name" => "",
    "phone_no"     => "",
    "email"        => "",
    "password"     => ""
];

/* ----------------------------
   RESET ("New")
---------------------------- */
if (isset($_POST['new'])) {
    $hotel = [
        "hotel_id"     => "",
        "hotel_code"   => "",
        "hotel_name"   => "",
        "address"      => "",
        "pincode"      => "",
        "city"         => "",
        "country"      => "",
        "contact_name" => "",
        "phone_no"     => "",
        "email"        => "",
        "password"     => ""
    ];
}

/* ----------------------------
   SAVE new hotel
---------------------------- */
if (isset($_POST['save'])) {
    $hcode    = trim($_POST['hotel_code']);
    $hname    = trim($_POST['hotel_name']);
    $address  = trim($_POST['address']);
    $pincode  = trim($_POST['pincode']);
    $city     = trim($_POST['city']);
    $country  = trim($_POST['country']);
    $cname    = trim($_POST['contact_name']);
    $phone    = trim($_POST['phone_no']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($hcode === "" || $hname === "") {
        echo "<script>alert('Hotel Code and Hotel Name are required');</script>";
    } else {
        $check = $conn->prepare("SELECT hotel_code FROM hotel WHERE hotel_code=?");
        $check->bind_param("s", $hcode);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo "<script>alert('Hotel Code already exists');</script>";
        } else {
            // Insert into hotel table
            $stmt = $conn->prepare("INSERT INTO hotel (hotel_code, hotel_name, address, pincode, city, country, contact_name, phone_no, email, password) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $hcode, $hname, $address, $pincode, $city, $country, $cname, $phone, $email, $password);
            if ($stmt->execute()) {
                $new_hotel_id = $stmt->insert_id;

                // ✅ Insert also into login table
                $stmt2 = $conn->prepare("INSERT INTO login (hotel_id, hotel_name, name, phone_no, email, password) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("isssss", $new_hotel_id, $hname, $cname, $phone, $email, $password);
                $stmt2->execute();
                $stmt2->close();

                echo "<script>alert('Hotel saved successfully (with login entry)');</script>";
                $hotel = [
                    "hotel_id"     => $new_hotel_id,
                    "hotel_code"   => $hcode,
                    "hotel_name"   => $hname,
                    "address"      => $address,
                    "pincode"      => $pincode,
                    "city"         => $city,
                    "country"      => $country,
                    "contact_name" => $cname,
                    "phone_no"     => $phone,
                    "email"        => $email,
                    "password"     => $password
                ];
            } else {
                echo "<script>alert('Insert failed: " . htmlspecialchars($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
        $check->close();
    }
}

/* ----------------------------
   UPDATE hotel
---------------------------- */
if (isset($_POST['update'])) {
    $id       = trim($_POST['hotel_id']);
    $hcode    = trim($_POST['hotel_code']);
    $hname    = trim($_POST['hotel_name']);
    $address  = trim($_POST['address']);
    $pincode  = trim($_POST['pincode']);
    $city     = trim($_POST['city']);
    $country  = trim($_POST['country']);
    $cname    = trim($_POST['contact_name']);
    $phone    = trim($_POST['phone_no']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($id === "") {
        echo "<script>alert('Hotel ID is required for update');</script>";
    } else {
        // Update hotel table
        $stmt = $conn->prepare("UPDATE hotel 
                                SET hotel_code=?, hotel_name=?, address=?, pincode=?, city=?, country=?, contact_name=?, phone_no=?, email=?, password=? 
                                WHERE hotel_id=?");
        $stmt->bind_param("ssssssssssi", $hcode, $hname, $address, $pincode, $city, $country, $cname, $phone, $email, $password, $id);
        if ($stmt->execute()) {
            // ✅ Update also in login table
            $stmt2 = $conn->prepare("UPDATE login 
                                     SET hotel_name=?, name=?, phone_no=?, email=?, password=? 
                                     WHERE hotel_id=?");
            $stmt2->bind_param("sssssi", $hname, $cname, $phone, $email, $password, $id);
            $stmt2->execute();
            $stmt2->close();

            echo "<script>alert('Hotel updated successfully (with login entry)');</script>";
            $hotel = [
                "hotel_id"     => $id,
                "hotel_code"   => $hcode,
                "hotel_name"   => $hname,
                "address"      => $address,
                "pincode"      => $pincode,
                "city"         => $city,
                "country"      => $country,
                "contact_name" => $cname,
                "phone_no"     => $phone,
                "email"        => $email,
                "password"     => $password
            ];
        } else {
            echo "<script>alert('Update failed: " . htmlspecialchars($stmt->error) . "');</script>";
        }
        $stmt->close();
    }
}

/* ----------------------------
   DELETE hotel
---------------------------- */
if (isset($_POST['delete'])) {
    $id = trim($_POST['hotel_id']);
    if ($id === "") {
        echo "<script>alert('Hotel ID required to delete');</script>";
    } else {
        // Delete hotel
        $stmt = $conn->prepare("DELETE FROM hotel WHERE hotel_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // ✅ Delete also from login
            $stmt2 = $conn->prepare("DELETE FROM login WHERE hotel_id=?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $stmt2->close();

            echo "<script>alert('Hotel deleted successfully (and login entry removed)');</script>";
            $hotel = [
                "hotel_id"     => "",
                "hotel_code"   => "",
                "hotel_name"   => "",
                "address"      => "",
                "pincode"      => "",
                "city"         => "",
                "country"      => "",
                "contact_name" => "",
                "phone_no"     => "",
                "email"        => "",
                "password"     => ""
            ];
        } else {
            echo "<script>alert('Delete failed: " . htmlspecialchars($stmt->error) . "');</script>";
        }
        $stmt->close();
    }
}

/* ----------------------------
   SEARCH hotel (by GET)
---------------------------- */
if (isset($_GET['hotel_id']) && $_GET['hotel_id'] !== "") {
    $id = $_GET['hotel_id'];
    $stmt = $conn->prepare("SELECT * FROM hotel WHERE hotel_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $hotel = $res->fetch_assoc();
    } else {
        echo "<script>alert('Hotel not found');</script>";
    }
    $stmt->close();
}
?>

<!-- ✅ FRONTEND FORM -->
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Hotel Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f8f9fa; }
    .form-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 6px 25px rgba(0,0,0,0.08);
        margin: 60px auto;
        max-width: 1000px;
    }
</style>
</head>
<body>
<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Hotel Management</h2>

        <form method="POST" class="row g-3">
            <input type="hidden" name="hotel_id" value="<?php echo htmlspecialchars($hotel['hotel_id']); ?>">

            <div class="col-md-6">
                <label class="form-label">Hotel Code</label>
                <input type="text" name="hotel_code" class="form-control" value="<?php echo htmlspecialchars($hotel['hotel_code']); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Hotel Name</label>
                <input type="text" name="hotel_name" class="form-control" value="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($hotel['address']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Pincode</label>
                <input type="text" name="pincode" class="form-control" value="<?php echo htmlspecialchars($hotel['pincode']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($hotel['city']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Country</label>
                <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($hotel['country']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contact Person Name</label>
                <input type="text" name="contact_name" class="form-control" value="<?php echo htmlspecialchars($hotel['contact_name']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone No</label>
                <input type="text" name="phone_no" class="form-control" value="<?php echo htmlspecialchars($hotel['phone_no']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($hotel['email']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" value="<?php echo htmlspecialchars($hotel['password']); ?>">
            </div>

            <div class="col-12 text-center mt-3">
                <button type="submit" name="save" class="btn btn-primary">Save</button>
                <button type="submit" name="update" class="btn btn-warning">Update</button>
                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                <button type="submit" name="new" class="btn btn-secondary">New</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
