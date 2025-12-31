<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'connect.php';



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

/* ----------------------------
   Default role values
   ---------------------------- */
$role = [
    "role_id"          => "",
    "role_name"        => "",
    "role_description" => "",
    "hotel_id"         => $_SESSION['hotel_id'] ?? ""
];

/* ----------------------------
   Handle "New"
   ---------------------------- */
if (isset($_POST['new'])) {
   $role = [
        "role_id"          => "",
        "role_name"        => "",
        "role_description" => "",
        "hotel_id"         => $_SESSION['hotel_id'] ?? ""
   ];
}

/* ----------------------------
   SAVE new role
   ---------------------------- */
if (isset($_POST['save'])) {
    $hotel_id         = intval($_SESSION['hotel_id'] ?? 0);
    $role_name        = trim($_POST['role_name']);
    $role_description = trim($_POST['role_description']);

    if ($hotel_id == 0 || empty($role_name)) {
        echo "<script>alert('Hotel ID ya Role Name missing hai.');</script>";
    } else {
        $check = $conn->prepare("SELECT role_id FROM role WHERE role_name = ? AND hotel_id = ?");
        $check->bind_param("si", $role_name, $hotel_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Role already exists in this hotel');</script>";
        } else {
            $stmt = $conn->prepare("INSERT INTO role (role_name, role_description, hotel_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $role_name, $role_description, $hotel_id);

            if ($stmt->execute()) {
                echo "<script>alert('Role saved successfully');</script>";
            } else {
                echo "<script>alert('Insert failed: " . htmlspecialchars($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
        $check->close();
    }
}

/* ----------------------------
   UPDATE role
   ---------------------------- */
if (isset($_POST['update'])) {
    $hotel_id         = intval($_SESSION['hotel_id'] ?? 0);
    $role_id          = intval($_POST['role_id']);
    $role_name        = trim($_POST['role_name']);
    $role_description = trim($_POST['role_description']);

    if ($role_id <= 0) {
        echo "<script>alert('Invalid role selected for update');</script>";
    } else {
        // Duplicate check
        $check = $conn->prepare("SELECT role_id FROM role WHERE role_name = ? AND hotel_id = ? AND role_id != ?");
        $check->bind_param("sii", $role_name, $hotel_id, $role_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Role already exists in this hotel');</script>";
        } else {
            $stmt = $conn->prepare("UPDATE role SET role_name=?, role_description=? WHERE role_id=? AND hotel_id=?");
            $stmt->bind_param("ssii", $role_name, $role_description, $role_id, $hotel_id);

            if ($stmt->execute()) {
                echo "<script>alert('Role updated successfully');</script>";
            } else {
                echo "<script>alert('Update failed: " . htmlspecialchars($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
        $check->close();
    }
}

/* ----------------------------
   LOAD role data for edit
   ---------------------------- */
if (isset($_GET['role_id']) && $_GET['role_id'] !== "") {
    $role_id = intval($_GET['role_id']);
    $stmt = $conn->prepare("SELECT role_id, role_name, role_description, hotel_id FROM role WHERE role_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $role = [
                "role_id"          => $row['role_id'],
                "role_name"        => $row['role_name'],
                "role_description" => $row['role_description'],
                "hotel_id"         => $row['hotel_id']
            ];
        } else {
            echo "<script>alert('Role not found'); window.location.href='role.php';</script>";
            exit;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Role Management</title>
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
        max-width: 700px;
    }
</style>
</head>
<body>
<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Role Management</h2>
        <form method="POST" class="row g-3" novalidate>

            <!-- Hotel -->
            <div class="col-md-12">
                <label class="form-label">Hotel</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['hotel_name'] ?? ''); ?>" disabled>
                <input type="hidden" name="hotel_id" value="<?php echo htmlspecialchars($_SESSION['hotel_id'] ?? ''); ?>">
            </div>

            <!-- Role Name -->
            <div class="col-md-12">
                <label class="form-label">Role Name</label>
                <input type="text" name="role_name" class="form-control" value="<?php echo htmlspecialchars($role['role_name']); ?>" required>
            </div>

            <!-- Role Description -->
            <div class="col-md-12">
                <label class="form-label">Role Description</label>
                <textarea name="role_description" class="form-control"><?php echo htmlspecialchars($role['role_description']); ?></textarea>
            </div>

            <input type="hidden" name="role_id" value="<?php echo htmlspecialchars($role['role_id']); ?>">

            <!-- Buttons -->
            <div class="col-12 text-center mt-3">
                <button type="submit" name="save" class="btn btn-primary">Save</button>
                <button type="submit" name="update" class="btn btn-warning">Update</button>
                <button type="submit" name="new" value="1" class="btn btn-secondary">New</button>
                <a href="Admin_dashboard.php" class="btn btn-danger">Back</a>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
