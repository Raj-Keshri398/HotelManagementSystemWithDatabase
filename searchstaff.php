<?php
session_start();
include 'connect.php';

// Agar login nahi hai to redirect
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

    <!-- Search & Refresh -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col">
            <form method="post" class="row g-2 search-form" autocomplete="off">
                <div class="col-12 col-md">
                    <input type="text" name="t1" class="form-control shadow-sm"
                        placeholder="Enter Staff Name or Email"
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
        class SearchStaff extends Connect {

            public function __construct() {
                parent::__construct();
            }

            private function renderTable($result) {
                if (!$result || mysqli_num_rows($result) === 0) {
                    echo "<div class='alert alert-warning text-center shadow-sm'>No staff found.</div>";
                    return;
                }

                // Desktop table
                echo "<div class='d-none d-md-block table-responsive shadow-sm rounded'>
                        <table class='table table-striped table-hover table-bordered align-middle text-nowrap'>
                        <thead class='table-dark text-center'>
                            <tr>
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Gender</th>
                                <th>DOB</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Salary</th>
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
                $staffId = htmlspecialchars($row['staff_id']);
                return "<tr>
                    <td>$staffId</td>
                    <td>".htmlspecialchars($row['name'])."</td>
                    <td>".htmlspecialchars($row['role_id'])."</td>
                    <td>".htmlspecialchars($row['gender'])."</td>
                    <td>".htmlspecialchars($row['dob'])."</td>
                    <td>".htmlspecialchars($row['phone_no'])."</td>
                    <td>".htmlspecialchars($row['email'])."</td>
                    <td>".htmlspecialchars($row['salary'])."</td>
                    <td class='text-center'>
                        <button type='button' class='btn btn-sm btn-danger' onclick=\"openAuthModal('delete', '$staffId')\">
                            <i class='fa-solid fa-trash'></i>
                        </button>
                        <button type='button' class='btn btn-sm btn-primary ms-1' onclick=\"openAuthModal('edit', '$staffId')\">
                            <i class='fa-solid fa-pen-to-square'></i>
                        </button>
                    </td>
                </tr>";
            }

            private function renderCard($row) {
                $staffId = htmlspecialchars($row['staff_id']);
                return "<div class='card shadow-sm mb-3'>
                    <div class='card-body'>
                        <p><strong>Staff ID:</strong> $staffId</p>
                        <p><strong>Name:</strong> ".htmlspecialchars($row['name'])."</p>
                        <p><strong>Role:</strong> ".htmlspecialchars($row['role_id'])."</p>
                        <p><strong>Gender:</strong> ".htmlspecialchars($row['gender'])."</p>
                        <p><strong>DOB:</strong> ".htmlspecialchars($row['dob'])."</p>
                        <p><strong>Phone:</strong> ".htmlspecialchars($row['phone_no'])."</p>
                        <p><strong>Email:</strong> ".htmlspecialchars($row['email'])."</p>
                        <p><strong>Salary:</strong> ".htmlspecialchars($row['salary'])."</p>
                        <button type='button' class='btn btn-sm btn-danger w-100 mt-2' onclick=\"openAuthModal('delete', '$staffId')\">
                            <i class='fa-solid fa-trash'></i>
                        </button>
                        <button type='button' class='btn btn-sm btn-primary mt-2' onclick=\"openAuthModal('edit', '$staffId')\">
                            <i class='fa-solid fa-pen-to-square'></i>
                        </button>
                    </div>
                </div>";
            }

            // ✅ Show all staff (hotel-wise)
            public function showAllStaff($hotel_id) {
                $stmt = $this->db_handle->prepare("SELECT * FROM staff WHERE hotel_id=? ORDER BY name ASC");
                $stmt->bind_param("i", $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }

            // ✅ Search staff by name/email
            public function searchStaff($keyword, $hotel_id) {
                $keyword = trim($keyword);
                if ($keyword === '') { 
                    $this->showAllStaff($hotel_id); 
                    return; 
                }
                $stmt = $this->db_handle->prepare("SELECT * FROM staff WHERE (name LIKE ? OR email LIKE ?) AND hotel_id=?");
                $like = "%$keyword%";
                $stmt->bind_param("ssi", $like, $like, $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }

            // ✅ delete method
            public function deleteStaffById($staffId, $hotel_id) {
                $stmt = $this->db_handle->prepare("DELETE FROM staff WHERE staff_id=? AND hotel_id=?");
                $stmt->bind_param("si", $staffId, $hotel_id);
                if ($stmt->execute()) {
                    echo "<script>alert('Staff Deleted Successfully'); window.location.href='searchstaff.php';</script>";
                    exit;
                } else {
                    echo "<div class='alert alert-danger text-center mt-3'>Error deleting staff.</div>";
                }
                $stmt->close();
            }
        }

        $search = new SearchStaff();

        // ✅ Admin authentication for delete/edit
        if (isset($_POST['action']) && isset($_POST['admin_id']) && isset($_POST['admin_pass'])) {
            $adminEmail = "admin123@gmail.com";
            $adminPass  = "1234";

            if ($_POST['admin_id'] === $adminEmail && $_POST['admin_pass'] === $adminPass) {
                if ($_POST['action'] == "delete") {
                    $search->deleteStaffById($_POST['staff_id'], $hotel_id);
                } elseif ($_POST['action'] == "edit") {
                    echo "<script>window.location.href='updatestaff.php?staff_id=" . urlencode($_POST['staff_id']) . "';</script>";
                    exit;
                }
            } else {
                echo "<script>alert('Invalid Admin Credentials!');</script>";
            }
        }

        if (isset($_POST['b1'])) 
            $search->showAllStaff($hotel_id);
        elseif (isset($_POST['b2'])) 
            $search->searchStaff(trim($_POST['t1']), $hotel_id);
        else 
            $search->showAllStaff($hotel_id);
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
                <input type="hidden" name="staff_id" id="modalStaffId">
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
function openAuthModal(action, staffId) {
    document.getElementById('modalAction').value = action;
    document.getElementById('modalStaffId').value = staffId;
    var modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
}
</script>
</body>
</html>
