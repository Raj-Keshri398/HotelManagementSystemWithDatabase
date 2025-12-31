<?php
session_start();
include 'connect.php';
include 'navbar.php';


// ✅ Extend connect class
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
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please login to manage guest.'); window.location.href='login.php';</script>";
    exit;
}

// ✅ Init guest array
$guest = [
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
    "country" => "",
    "companions" => "",
    "guest_photo" => "",
    "companion_photos" => ""
];

// ==================== VALIDATION ====================
function validateGuestData($phone, $email) {
    if (!preg_match('/^\d{10}$/', $phone)) {
        return "Phone number must be exactly 10 digits.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }
    return "";
}

// ==================== UPLOAD FUNCTIONS ====================
function uploadSingleImage($fileInput, $uploadDir, $oldFile = "") {
    if (!empty($_FILES[$fileInput]['name'])) {
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileTmp  = $_FILES[$fileInput]['tmp_name'];
        $fileName = time() . "_" . basename($_FILES[$fileInput]['name']);
        $target   = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($target, PATHINFO_EXTENSION));

        // Validate
        $check = getimagesize($fileTmp);
        if ($check === false) return $oldFile;

        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($fileType, $allowed)) return $oldFile;

        if ($_FILES[$fileInput]['size'] > 2*1024*1024) return $oldFile; // 2MB limit

        if (move_uploaded_file($fileTmp, $target)) {
            if ($oldFile && file_exists($uploadDir . $oldFile)) unlink($uploadDir . $oldFile);
            return $fileName;
        }
    }
    return $oldFile;
}

function uploadMultipleImages($fileInput, $uploadDir, $oldFiles = "") {
    $uploaded = [];
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (!empty($_FILES[$fileInput]['name'][0])) {
        foreach ($_FILES[$fileInput]['name'] as $key => $name) {
            if (!empty($name)) {
                $fileTmp  = $_FILES[$fileInput]['tmp_name'][$key];
                $fileName = time() . "_" . $key . "_" . basename($name);
                $target   = $uploadDir . $fileName;
                $fileType = strtolower(pathinfo($target, PATHINFO_EXTENSION));

                // Validate
                $check = getimagesize($fileTmp);
                if ($check === false) continue;

                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (!in_array($fileType, $allowed)) continue;

                if ($_FILES[$fileInput]['size'][$key] > 2*1024*1024) continue;

                if (move_uploaded_file($fileTmp, $target)) {
                    $uploaded[] = $fileName;
                }
            }
        }
    }

    if ($oldFiles) {
        $uploaded = array_merge(explode(",", $oldFiles), $uploaded);
    }
    return implode(",", $uploaded);
}

// ==================== SAVE/UPDATE ====================
if (isset($_POST['save'])) {
    if (!isset($_SESSION['hotel_id']) || !isset($_SESSION['hotel_name'])) {
        echo "<script>alert('Unauthorized: No hotel assigned.'); window.location.href='login.php';</script>";
        exit;
    }

    $hotel_id   = $_SESSION['hotel_id'];
    $guest_id   = $_POST['guest_id'] ?? "";
    $guest_id_no= trim($_POST['guest_id_no']);

    // Validate
    $errorMsg = validateGuestData($_POST['phone_no'], $_POST['email']);
    if ($errorMsg !== "") {
        echo "<script>alert('$errorMsg');</script>";
    } else {
        if ($guest_id) {
            // UPDATE
            $check = $conn->prepare("SELECT guest_id FROM guest WHERE guest_id_no=? AND hotel_id=? AND guest_id<>?");
            $check->bind_param("sii", $guest_id_no, $hotel_id, $guest_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo "<script>alert('Guest ID number already exists.');</script>";
            } else {
                $old = $conn->prepare("SELECT guest_photo, companion_photos FROM guest WHERE guest_id=? AND hotel_id=?");
                $old->bind_param("ii", $guest_id, $hotel_id);
                $old->execute();
                $oldData = $old->get_result()->fetch_assoc();

                $new_guest_photo = uploadSingleImage('guest_photo', "uploads/guests/", $oldData['guest_photo']);
                $new_companion_photos = uploadMultipleImages('companion_photos', "uploads/companions/", $oldData['companion_photos']);

                $stmt = $conn->prepare("UPDATE guest SET 
                    id_type=?, guest_id_no=?, guest_photo=?, title=?, name=?, gender=?, dob=?, phone_no=?, email=?, address=?, city=?, country=?, companions=?, companion_photos=? 
                    WHERE guest_id=? AND hotel_id=?");
                $stmt->bind_param("ssssssssssssssii",
                    $_POST['id_type'], $guest_id_no, $new_guest_photo, $_POST['title'], $_POST['name'], $_POST['gender'],
                    $_POST['dob'], $_POST['phone_no'], $_POST['email'], $_POST['address'],
                    $_POST['city'], $_POST['country'], $_POST['companions'],
                    $new_companion_photos, $guest_id, $hotel_id
                );

                if ($stmt->execute()) {
                    echo "<script>alert('Guest updated successfully'); window.location.href='searchguest1.php';</script>";
                    exit;
                } else {
                    echo "<script>alert('Update failed: ".$stmt->error."');</script>";
                }
            }
        } else {
            // INSERT (✅ Fixed column order)
            $check = $conn->prepare("SELECT guest_id_no FROM guest WHERE guest_id_no=? AND hotel_id=?");
            $check->bind_param("si", $guest_id_no, $hotel_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo "<script>alert('Guest ID already exists.');</script>";
            } else {
                $guest_photo = uploadSingleImage('guest_photo', "uploads/guests/");
                $companion_photos_str = uploadMultipleImages('companion_photos', "uploads/companions/");

                $stmt = $conn->prepare("INSERT INTO guest 
                    (id_type, guest_id_no, guest_photo, title, name, gender, dob, phone_no, email, address, city, country, hotel_id, companions, companion_photos) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssssis", 
                    $_POST['id_type'],        // s
                    $guest_id_no,             // s
                    $guest_photo,             // s
                    $_POST['title'],          // s
                    $_POST['name'],           // s
                    $_POST['gender'],         // s
                    $_POST['dob'],            // s
                    $_POST['phone_no'],       // s
                    $_POST['email'],          // s
                    $_POST['address'],        // s
                    $_POST['city'],           // s
                    $_POST['country'],        // s
                    $hotel_id,                // i
                    $_POST['companions'],     // s
                    $companion_photos_str     // s
                );

                if ($stmt->execute()) {
                    echo "<script>alert('Guest saved successfully'); window.location.href='searchguest1.php';</script>";
                    exit;
                } else {
                    echo "<script>alert('Insert failed: ".$stmt->error."');</script>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Guest Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.form-container { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin: 80px auto; max-width: 800px; }
.form-label { font-size: 14px; font-weight: 500; }
.form-control, .form-select, textarea { font-size: 14px; padding: 6px 10px; }
textarea { resize: none; height: 60px; }
h2 { font-size: 22px; margin-bottom: 15px; }
.btn { padding: 4px 12px; font-size: 14px; }
</style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Guest Management</h2>
        <form method="POST" class="row g-3" enctype="multipart/form-data">

            <div class="col-md-6">
                <label class="form-label">ID Type</label>
                <select name="id_type" class="form-select" required>
                    <option value="">Select</option>
                    <option <?= ($guest['id_type']=="Aadhar card")?"selected":"" ?>>Aadhar card</option>
                    <option <?= ($guest['id_type']=="Voter Id Card")?"selected":"" ?>>Voter Id Card</option>
                    <option <?= ($guest['id_type']=="Passport")?"selected":"" ?>>Passport</option>
                    <option <?= ($guest['id_type']=="Driving License")?"selected":"" ?>>Driving License</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Guest Title</label>
                <select name="title" class="form-select" required>
                    <option value="">Select</option>
                    <option <?= ($guest['title']=="MR")?"selected":"" ?>>MR</option>
                    <option <?= ($guest['title']=="MRS")?"selected":"" ?>>MRS</option>
                    <option <?= ($guest['title']=="MISS")?"selected":"" ?>>MISS</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Guest ID Number</label>
                <input type="text" name="guest_id_no" class="form-control" value="<?= htmlspecialchars($guest['guest_id_no']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($guest['name']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select" required>
                    <option value="">Select</option>
                    <option <?= ($guest['gender']=="Male")?"selected":"" ?>>Male</option>
                    <option <?= ($guest['gender']=="Female")?"selected":"" ?>>Female</option>
                    <option <?= ($guest['gender']=="Other")?"selected":"" ?>>Other</option>
                </select>
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

            <div class="col-12">
                <label class="form-label">Companions (Other Guests)</label>
                <textarea name="companions" class="form-control" placeholder="Enter companion details like ID-No - Name"><?= htmlspecialchars($guest['companions']) ?></textarea>
                <small class="text-muted">Example: 1234 - Rajesh, 5678 - Suresh</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">Guest Photo</label>
                <input type="file" name="guest_photo" class="form-control" accept="image/*">
                <?php if (!empty($guest['guest_photo'])): ?>
                    <img src="uploads/guests/<?= htmlspecialchars($guest['guest_photo']) ?>" width="80" class="mt-2">
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">Companions Photos</label>
                <input type="file" name="companion_photos[]" class="form-control" accept="image/*" multiple>
                <?php if (!empty($guest['companion_photos'])): 
                    $photos = explode(",", $guest['companion_photos']);
                    foreach ($photos as $p): ?>
                        <img src="uploads/companions/<?= htmlspecialchars($p) ?>" width="60" class="mt-2 me-1">
                    <?php endforeach; 
                endif; ?>
            </div>

            <input type="hidden" name="guest_id" value="<?= isset($guest['guest_id']) ? $guest['guest_id'] : '' ?>">

            <div class="col-12 text-center mt-3">
                <button type="submit" name="save" class="btn btn-primary">Save</button>
                <button type="reset" class="btn btn-secondary">New</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
