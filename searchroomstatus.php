<?php
session_start();
include 'connect.php';

// ✅ Agar login nahi hai to redirect
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
                        placeholder="Enter Room ID to search"
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
        class SearchRoomStatus extends Connect {

            public function __construct() {
                parent::__construct();
            }

            private function renderTable($result) {
                if (!$result || mysqli_num_rows($result) === 0) {
                    echo "<div class='alert alert-warning text-center shadow-sm'>No room status found.</div>";
                    return;
                }

                // Desktop table
                echo "<div class='d-none d-md-block table-responsive shadow-sm rounded'>
                        <table class='table table-striped table-hover table-bordered align-middle text-nowrap'>
                        <thead class='table-dark text-center'>
                            <tr>
                                <th>Status ID</th>
                                <th>Room ID</th>
                                <th>Staff ID</th>
                                <th>Cleaning Date/Time</th>
                                <th>Cleaning Status</th>
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
                $statusId = (int)$row['status_id'];
                return "<tr>
                    <td class='text-center'>".htmlspecialchars($row['status_id'])."</td>
                    <td>".htmlspecialchars($row['room_id'])."</td>
                    <td>".htmlspecialchars($row['staff_id'])."</td>
                    <td>".htmlspecialchars($row['cleaning_date_time'])."</td>
                    <td>".htmlspecialchars($row['cleaning_status'])."</td>
                    <td class='text-center'>
                        <button type='button' class='btn btn-sm btn-danger' onclick=\"openAuthModal('delete', '$statusId')\">
                            <i class='fa-solid fa-trash'></i>
                        </button>
                    </td>
                </tr>";
            }

            private function renderCard($row) {
                $statusId = (int)$row['status_id'];
                return "<div class='card shadow-sm mb-3'>
                    <div class='card-body'>
                        <p><strong>Status ID:</strong> ".htmlspecialchars($row['status_id'])."</p>
                        <p><strong>Room ID:</strong> ".htmlspecialchars($row['room_id'])."</p>
                        <p><strong>Staff ID:</strong> ".htmlspecialchars($row['staff_id'])."</p>
                        <p><strong>Cleaning Date/Time:</strong> ".htmlspecialchars($row['cleaning_date_time'])."</p>
                        <p><strong>Cleaning Status:</strong> ".htmlspecialchars($row['cleaning_status'])."</p>
                        <button type='button' class='btn btn-sm btn-danger w-100 mt-2' onclick=\"openAuthModal('delete', '$statusId')\">
                            <i class='fa-solid fa-trash'></i>
                        </button>
                    </div>
                </div>";
            }

            // ✅ Show all statuses
            public function showAllStatuses($hotel_id) {
                $stmt = $this->db_handle->prepare("SELECT * FROM room_status WHERE hotel_id=? ORDER BY status_id DESC");
                $stmt->bind_param("i", $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }

            public function searchStatus($keyword, $hotel_id) {
                $keyword = trim($keyword);
                if ($keyword === '') { 
                    $this->showAllStatuses($hotel_id); 
                    return; 
                }
                $stmt = $this->db_handle->prepare("SELECT * FROM room_status WHERE room_id LIKE ? AND hotel_id=?");
                $like = "%$keyword%";
                $stmt->bind_param("si", $like, $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }

            // ✅ delete method
            public function deleteStatusById($statusId, $hotel_id) {
                $stmt = $this->db_handle->prepare("DELETE FROM room_status WHERE status_id=? AND hotel_id=?");
                $stmt->bind_param("ii", $statusId, $hotel_id);
                if ($stmt->execute()) {
                    echo "<script>alert('Room Status Deleted Successfully'); window.location.href='searchroomstatus.php';</script>";
                    exit;
                } else {
                    echo "<div class='alert alert-danger text-center mt-3'>Error deleting room status.</div>";
                }
                $stmt->close();
            }
        }

        $search = new SearchRoomStatus();

        // ✅ Admin authentication
        if (isset($_POST['action']) && isset($_POST['admin_id']) && isset($_POST['admin_pass'])) {
            $adminEmail = "admin123@gmail.com";
            $adminPass  = "1234";

            if ($_POST['admin_id'] === $adminEmail && $_POST['admin_pass'] === $adminPass) {
                if ($_POST['action'] == "delete") {
                    $search->deleteStatusById($_POST['status_id'], $hotel_id);
                }
            } else {
                echo "<script>alert('Invalid Admin Credentials!');</script>";
            }
        }

        if (isset($_POST['b1'])) 
            $search->showAllStatuses($hotel_id);
        elseif (isset($_POST['b2'])) 
            $search->searchStatus(trim($_POST['t1']), $hotel_id);
        else 
            $search->showAllStatuses($hotel_id);
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
                <input type="hidden" name="status_id" id="modalStatusId">
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
function openAuthModal(action, statusId) {
    document.getElementById('modalAction').value = action;
    document.getElementById('modalStatusId').value = statusId;
    var modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
}
</script>
</body>
</html>
