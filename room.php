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

/* ----------------------------
   Default room values
   ---------------------------- */
$room = [
    "room_id"        => "",
    "hotel_id"       => "",
    "room_no"        => "",
    "room_type"      => "",
    "room_charge"    => "",
    "description"    => "",
    "booking_status" => "Available",
    "cleaning_status"=> "Cleaned",
    "room_image"     => ""
];

/* ----------------------------
   Helper: upload image
   ---------------------------- */
function uploadRoomImage($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Image upload error code: " . intval($file['error']) . "');</script>";
        return null;
    }

    $maxBytes = 4 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        echo "<script>alert('Image too large (max 4 MB)');</script>";
        return null;
    }

    $allowedExt = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        echo "<script>alert('Invalid image type. Allowed: JPG, PNG, GIF');</script>";
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMime = ['image/jpeg','image/png','image/gif'];
    if (!in_array($mime, $allowedMime)) {
        echo "<script>alert('Invalid image MIME type');</script>";
        return null;
    }

    $uploadDir = __DIR__ . '/uploads/rooms/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $uploadDir . $newName;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'uploads/rooms/' . $newName;
    }
    echo "<script>alert('Failed to move uploaded file');</script>";
    return null;
}

/* ----------------------------
   Handle "New"
   ---------------------------- */
if (isset($_POST['new'])) {
   $room = [
        "room_id"        => "",
        "hotel_id"       => "",
        "room_no"        => "",
        "room_type"      => "",
        "room_charge"    => "",
        "description"    => "",
        "booking_status" => "Available",
        "cleaning_status"=> "Cleaned",
        "room_image"     => ""
    ];
}

/* ----------------------------
   SAVE new room
   ---------------------------- */
if (isset($_POST['save'])) {
    if (isset($_SESSION['hotel_id']) && $_SESSION['hotel_id'] > 0) {
        $hotel_id = intval($_SESSION['hotel_id']);
    } else {
        $hotel_id = intval($_POST['hotel_id'] ?? 0);
    }

    $room_no        = trim($_POST['room_no']);
    $room_type      = trim($_POST['room_type']);
    $room_charge    = floatval($_POST['room_charge']);
    $description    = trim($_POST['description']);
    $booking_status = $_POST['booking_status'] ?? 'Available';
    $cleaning_status= $_POST['cleaning_status'] ?? 'Cleaned';

    if ($hotel_id == 0 || empty($room_no)) {
        echo "<script>alert('Hotel ID ya Room Number missing hai.');</script>";
    } else {
        $check = $conn->prepare("SELECT room_no FROM room WHERE room_no = ? AND hotel_id = ?");
        $check->bind_param("si", $room_no, $hotel_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Room Number already exists in this hotel');</script>";
        } else {
            $imagePath = "";
            if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] == 0) {
                $targetDir = "uploads/rooms/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $imagePath = $targetDir . basename($_FILES["room_image"]["name"]);
                move_uploaded_file($_FILES["room_image"]["tmp_name"], $imagePath);
            }

            $stmt = $conn->prepare("INSERT INTO room 
                (hotel_id, room_no, room_type, room_charge, description, booking_status, cleaning_status, room_image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdssss",
                $hotel_id, $room_no, $room_type, $room_charge,
                $description, $booking_status, $cleaning_status, $imagePath
            );

            if ($stmt->execute()) {
                echo "<script>alert('Room saved successfully');</script>";
            } else {
                echo "<script>alert('Insert failed: " . htmlspecialchars($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
        $check->close();
    }
}

/* ----------------------------
   UPDATE room
   ---------------------------- */
/* ----------------------------
   UPDATE room
   ---------------------------- */
if (isset($_POST['update'])) {
    if (!isset($_SESSION['hotel_id']) || !isset($_SESSION['hotel_name'])) {
        echo "<script>alert('Unauthorized: No hotel assigned.');</script>";
        exit;
    }

    $hotel_id       = intval($_SESSION['hotel_id']);
    $room_id        = intval($_POST['room_id']);
    $room_no        = trim($_POST['room_no']);
    $room_type      = trim($_POST['room_type']);
    $room_charge    = (float)$_POST['room_charge'];
    $description    = trim($_POST['description']);
    $booking_status = trim($_POST['booking_status']);
    $cleaning_status= trim($_POST['cleaning_status']);

    if ($room_id <= 0) {
        echo "<script>alert('Invalid room selected for update');</script>";
    } else {
        // ✅ Step 1: Duplicate check (same hotel me, dusre room_id ke liye)
        $check = $conn->prepare("SELECT room_id FROM room WHERE room_no = ? AND hotel_id = ? AND room_id != ?");
        $check->bind_param("sii", $room_no, $hotel_id, $room_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Room Number already exists in this hotel');</script>";
            $check->close();
        } else {
            $check->close();

            // ✅ Step 2: Purana image fetch karo
            $stmt = $conn->prepare("SELECT room_image FROM room WHERE room_id = ? AND hotel_id = ?");
            $existingImage = "";
            if ($stmt) {
                $stmt->bind_param("ii", $room_id, $hotel_id);
                $stmt->execute();
                $stmt->bind_result($existingImage);
                $stmt->fetch();
                $stmt->close();
            }

            // ✅ Step 3: Naya image upload (agar diya gaya hai)
            $newImagePath = $existingImage;
            if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploaded = uploadRoomImage($_FILES['room_image']);
                if ($uploaded) {
                    // Purana image delete karo
                    if (!empty($existingImage) && strpos($existingImage, 'uploads/rooms/') === 0) {
                        $oldFull = __DIR__ . '/' . $existingImage;
                        if (is_file($oldFull)) @unlink($oldFull);
                    }
                    $newImagePath = $uploaded;
                }
            }

            // ✅ Step 4: Update query run karo
            $stmt = $conn->prepare("UPDATE room 
                SET room_no=?, room_type=?, room_charge=?, description=?, booking_status=?, cleaning_status=?, room_image=? 
                WHERE room_id=? AND hotel_id=?");
            if ($stmt === false) {
                echo "<script>alert('Prepare failed: " . htmlspecialchars($conn->error) . "');</script>";
            } else {
                $stmt->bind_param(
                    "ssdssssii",
                    $room_no,
                    $room_type,
                    $room_charge,
                    $description,
                    $booking_status,
                    $cleaning_status,
                    $newImagePath,
                    $room_id,
                    $hotel_id
                );
                if ($stmt->execute()) {
                    echo "<script>alert('Room updated successfully'); window.location.href='searchroom.php'</script>";
                } else {
                    echo "<script>alert('Update failed: " . htmlspecialchars($stmt->error) . "');</script>";
                }
                $stmt->close();
            }
        }
    }
}


/* ----------------------------
   LOAD room data for edit
   ---------------------------- */
if (isset($_GET['room_id']) && $_GET['room_id'] !== "") {
    $room_id = intval($_GET['room_id']);
    $stmt = $conn->prepare("SELECT room_id, hotel_id, room_no, room_type, room_charge, description, booking_status, cleaning_status, room_image 
        FROM room WHERE room_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $room = [
                "room_id" => $row['room_id'],  // ✅ added
                "hotel_id" => $row['hotel_id'],
                "room_no" => $row['room_no'],
                "room_type" => $row['room_type'],
                "room_charge" => $row['room_charge'],
                "description" => $row['description'],
                "booking_status" => $row['booking_status'],
                "cleaning_status" => $row['cleaning_status'],
                "room_image" => $row['room_image']
            ];
        } else {
            echo "<script>alert('Room not found'); window.location.href='searchroom.php';</script>";
            exit;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"> <!-- page encoding -->
<title>Room Management</title> <!-- page title -->
<meta name="viewport" content="width=device-width, initial-scale=1"> <!-- responsive meta -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- bootstrap CSS -->
<style>
    body { background-color: #f8f9fa; } /* set page background */
    .form-container {
        background: #fff; /* white box background */
        padding: 20px; /* inner padding */
        border-radius: 8px; /* rounded corners */
        box-shadow: 0 6px 25px rgba(0,0,0,0.08); /* subtle shadow */
        margin: 60px auto; /* vertical margin and center */
        max-width: 900px; /* max width */
    }
    .thumb { max-width: 160px; margin-top: 10px; border-radius:6px; } /* image preview style */
</style>
</head>
<body>
<div class="container"> <!-- bootstrap container -->
    <div class="form-container"> <!-- white form card -->
        <h2 class="text-center mb-4">Room Management</h2> <!-- heading -->

        <!-- form starts: multipart for file upload, novalidate to allow server-side validation -->
        <form method="POST" class="row g-3" enctype="multipart/form-data" novalidate>
            
            <!-- Show Hotel Name from Session -->
            <div class="col-md-6">
                <label class="form-label">Hotel</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['hotel_name'] ?? ''); ?>" disabled>
                <input type="hidden" name="hotel_id" value="<?php echo htmlspecialchars($_SESSION['hotel_id'] ?? ''); ?>">
            </div>


            <!-- Room Number -->
            <div class="col-md-6">
                <label class="form-label">Room Number</label>
                <input type="text" name="room_no" class="form-control" value="<?php echo htmlspecialchars($room['room_no']); ?>" required> <!-- room_no input prefilled -->
            </div>

            <!-- Room Type -->
            <div class="col-md-6">
                <label class="form-label">Room Type</label>
                <input type="text" name="room_type" class="form-control" value="<?php echo htmlspecialchars($room['room_type']); ?>" required> <!-- room_type input -->
            </div>

            <!-- Room Charge -->
            <div class="col-md-6">
                <label class="form-label">Room Charge</label>
                <input type="number" step="0.01" name="room_charge" class="form-control" value="<?php echo htmlspecialchars($room['room_charge']); ?>" required> <!-- room_charge input -->
            </div>

            <!-- Description -->
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($room['description']); ?></textarea> <!-- description -->
            </div>

            <!-- Booking Status -->
            <div class="col-md-6">
                <label class="form-label">Booking Status</label>
                <select name="booking_status" class="form-select"> <!-- booking status select -->
                    <option value="Available" <?php echo ($room['booking_status']=="Available")?"selected":""; ?>>Available</option>
                    <option value="Booked" <?php echo ($room['booking_status']=="Booked")?"selected":""; ?>>Booked</option>
                </select>
            </div>

            <!-- Cleaning Status -->
            <div class="col-md-6">
                <label class="form-label">Cleaning Status</label>
                <select name="cleaning_status" class="form-select"> <!-- cleaning status select -->
                    <option value="Cleaned" <?php echo ($room['cleaning_status']=="Cleaned")?"selected":""; ?>>Cleaned</option>
                    <option value="Uncleaned" <?php echo ($room['cleaning_status']=="Uncleaned")?"selected":""; ?>>Uncleaned</option>
                </select>
            </div>

            <!-- Image -->
            <div class="col-12">
                <label class="form-label">Room Image (optional)</label>
                <input type="file" name="room_image" class="form-control"> <!-- file input for image -->
                <?php if (!empty($room['room_image'])): ?> <!-- if there is an image path -->
                    <div>
                        <img src="<?php echo htmlspecialchars($room['room_image']); ?>" alt="Room Image" class="thumb"> <!-- show preview -->
                    </div>
                <?php endif; ?>
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['room_id']); ?>">

            </div>

            <!-- Buttons -->
            <div class="col-12 text-center mt-3">
                <button type="submit" name="save" class="btn btn-primary">Save</button> <!-- Save button (submits with name=save) -->
                <button type="submit" name="update" class="btn btn-warning">Update</button> <!-- Update button (submits with name=update) -->
                <button type="submit" name="new" value="1" class="btn btn-secondary">New</button> <!-- New button: value="1" ensures POST includes 'new' key when clicked -->
                <a href="Admin_dashboard.php" class="btn btn-danger"><i class="fas fa-book"></i> Back</a>
            </div>
        </form> <!-- form end -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> <!-- bootstrap JS -->
</body>
</html>
