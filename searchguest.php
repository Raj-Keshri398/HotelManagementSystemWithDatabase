<?php
session_start();
include 'connect.php';

//  Agar login nahi hai to redirect
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

    <!-- Top bar with search and refresh -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col">
            <form method="post" class="row g-2 search-form" autocomplete="off">
                <div class="col-12 col-md">
                    <input type="text" name="t1" class="form-control shadow-sm"
                        placeholder="Enter Guest ID to search"
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
        class SearchGuest extends Connect {

            public function __construct() {
                parent::__construct();
            }

            private function renderTable($result) {
                if (!$result || mysqli_num_rows($result) === 0) {
                    echo "<div class='alert alert-warning text-center shadow-sm'>No guest found.</div>";
                    return;
                }

                // Desktop table
                echo "<div class='d-none d-md-block table-responsive shadow-sm rounded'>
                        <table class='table table-striped table-hover table-bordered align-middle text-nowrap'>
                        <thead class='table-dark text-center'>
                            <tr>
                                <th>ID Type</th>
                                <th>Guest ID</th>
                                <th>Title</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>DOB</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>City</th>
                                <th>Country</th>
                                <th>Time</th>
                                <th>Action</th>
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
                $guestId = (int)$row['guest_id']; // ✅ primary key
                return "<tr>
                    <td>".htmlspecialchars($row['id_type'] ?? '')."</td>
                    <td>".htmlspecialchars($row['guest_id_no'] ?? '')."</td>
                    <td>".htmlspecialchars($row['title'] ?? '')."</td>
                    <td>".htmlspecialchars($row['name'] ?? '')."</td>
                    <td>".htmlspecialchars($row['gender'] ?? '')."</td>
                    <td>".htmlspecialchars($row['dob'] ?? '')."</td>
                    <td>".htmlspecialchars($row['phone_no'] ?? '')."</td>
                    <td>".htmlspecialchars($row['email'] ?? '')."</td>
                    <td>".htmlspecialchars($row['address'] ?? '')."</td>
                    <td>".htmlspecialchars($row['city'] ?? '')."</td>
                    <td>".htmlspecialchars($row['country'] ?? '')."</td>
                    <td>".htmlspecialchars($row['created_at'] ?? '')."</td>
                    <td class='text-center'>
                        <button type='button' class='btn btn-sm btn-danger' onclick=\"openAuthModal('delete', '$guestId')\">
                            <i class='fa-solid fa-trash'></i>
                        </button>
                        <button type='button' class='btn btn-sm btn-primary ms-1' onclick=\"openAuthModal('edit', '$guestId')\">
                            <i class='fa-solid fa-pen-to-square'></i>
                        </button>
                    </td>
                </tr>";
            }

            private function renderCard($row) {
                $guestId = (int)$row['guest_id']; // ✅ primary key
                return "<div class='card shadow-sm mb-3'>
                    <div class='card-body'>
                        <p><strong>ID Type:</strong> ".htmlspecialchars($row['id_type'] ?? '')."</p>
                        <p><strong>Guest ID:</strong> ".htmlspecialchars($row['guest_id_no'] ?? '')."</p>
                        <p><strong>Title:</strong> ".htmlspecialchars($row['title'] ?? '')."</p>
                        <p><strong>Name:</strong> ".htmlspecialchars($row['name'] ?? '')."</p>
                        <p><strong>Gender:</strong> ".htmlspecialchars($row['gender'] ?? '')."</p>
                        <p><strong>DOB:</strong> ".htmlspecialchars($row['dob'] ?? '')."</p>
                        <p><strong>Phone:</strong> ".htmlspecialchars($row['phone_no'] ?? '')."</p>
                        <p><strong>Email:</strong> ".htmlspecialchars($row['email'] ?? '')."</p>
                        <p><strong>Address:</strong> ".htmlspecialchars($row['address'] ?? '')."</p>
                        <p><strong>City:</strong> ".htmlspecialchars($row['city'] ?? '')."</p>
                        <p><strong>Country:</strong> ".htmlspecialchars($row['country'] ?? '')."</p>
                        <p><strong>Time:</strong> ".htmlspecialchars($row['created_at'] ?? '')."</p>
                        <button type='button' class='btn btn-sm btn-danger w-100 mt-2' onclick=\"openAuthModal('delete', '$guestId')\">
                            <i class='fa-solid fa-trash'></i>
                        </button>
                        <button type='button' class='btn btn-sm btn-primary mt-2' onclick=\"openAuthModal('edit', '$guestId')\">
                            <i class='fa-solid fa-pen-to-square'></i>
                        </button>
                    </div>
                </div>";
            }

            // ✅ Show All Guests
            public function showAllGuests($hotel_id) {
                $stmt = $this->db_handle->prepare("SELECT * FROM guest WHERE hotel_id=? ORDER BY guest_id_no ASC");
                if (!$stmt) { die("Prepare failed (showAllGuests): " . $this->db_handle->error); }
                $stmt->bind_param("i", $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }

            // ✅ Search Guest
            public function searchGuest($keyword, $hotel_id) {
                $keyword = trim($keyword);
                if ($keyword === '') { 
                    $this->showAllGuests($hotel_id); 
                    return; 
                }
                $stmt = $this->db_handle->prepare("SELECT * FROM guest WHERE guest_id_no LIKE ? AND hotel_id=?");
                if (!$stmt) { die("Prepare failed (searchGuest): " . $this->db_handle->error); }
                $like = "%$keyword%";
                $stmt->bind_param("si", $like, $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }

            // ✅ Delete Guest
            public function deleteGuest($guestId, $hotel_id) {
                $stmt = $this->db_handle->prepare("DELETE FROM guest WHERE guest_id=? AND hotel_id=?");
                if (!$stmt) { die("Prepare failed (deleteGuest): " . $this->db_handle->error); }
                $stmt->bind_param("ii", $guestId, $hotel_id);
                if ($stmt->execute()) {
                    echo "<script>alert('Record Deleted Successfully'); window.location.href='searchguest.php';</script>";
                    exit;
                } else {
                    echo "<div class='alert alert-danger text-center mt-3'>Error deleting record.</div>";
                }
                $stmt->close();
            }
        }

        $search = new SearchGuest();


        // ✅ Admin authentication for delete or edit (hardcoded, no DB check)
        if (isset($_POST['action']) && isset($_POST['admin_id']) && isset($_POST['admin_pass'])) {
            $adminEmail = "admin123@gmail.com";
            $adminPass  = "1234";

            if ($_POST['admin_id'] === $adminEmail && $_POST['admin_pass'] === $adminPass) {
                // Admin verified
                if ($_POST['action'] == "delete") {
                    $search->deleteGuest($_POST['guest_id'], $hotel_id);
                } elseif ($_POST['action'] == "edit") {
                    echo "<script>window.location.href='updateguest.php?guest_id=" 
                        . urlencode($_POST['guest_id']) . "&hotel_id=" 
                        . urlencode($hotel_id) . "';</script>";
                    exit;
                }
            } else {
                echo "<script>alert('Invalid Admin Credentials!');</script>";
            }
        }


        if (isset($_POST['b1'])) 
            $search->showAllGuests($hotel_id);
        elseif (isset($_POST['b2'])) 
            $search->searchGuest(trim($_POST['t1']), $hotel_id);
        else 
            $search->showAllGuests($hotel_id);

    ?>


    <!-- Admin Auth Modal -->
    <div class="modal fade" id="authModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admin Authentication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="modalAction">
                <input type="hidden" name="guest_id" id="modalGuestId">
                <div class="mb-3">
                <label>Admin ID</label>
                <input type="text" name="admin_id" class="form-control" required>
                </div>
                <div class="mb-3">
                <label>Password</label>
                <input type="password" name="admin_pass" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-dark" type="submit">Confirm</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAuthModal(action, guest_id) {
    document.getElementById('modalAction').value = action;
    document.getElementById('modalGuestId').value = guest_id;
    var modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
}
</script>
</body>
</html>
