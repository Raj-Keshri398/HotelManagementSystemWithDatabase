<?php
session_start();
include 'connect.php';

// ✅ Check login
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please login first'); window.location.href='login.php';</script>";
    exit;
}

// ✅ Get current logged-in user's hotel_id & hotel_name
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
                        placeholder="Enter Role Title to search"
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
        class SearchRole extends Connect {

            public function __construct() {
                parent::__construct();
            }

            private function renderTable($result) {
				if (!$result || mysqli_num_rows($result) === 0) {
					echo "<div class='alert alert-warning text-center shadow-sm'>No role found.</div>";
					return;
				}

				// ✅ Table (only on md and above)
				echo "<div class='d-none d-md-block table-responsive shadow-sm rounded'>
						<table class='table table-striped table-hover table-bordered align-middle text-nowrap'>
						<thead class='table-dark text-center'>
							<tr>
								<th>Role ID</th>
								<th>Role Title</th>
								<th>Role Description</th>
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

				// ✅ Cards (only on small screens)
				echo "<div class='d-block d-md-none'>";
				foreach ($rows as $row) echo $this->renderCard($row);
				echo "</div>";
			}

            private function renderRow($row) {
                $roleId = (int)$row['role_id'];
                $roleTitle = htmlspecialchars($row['role_name']);
                $roleDesc = htmlspecialchars($row['role_description']);

                return "<tr>
                    <td>$roleId</td>
                    <td>$roleTitle</td>
                    <td>$roleDesc</td>
                    <td class='text-center'>
                        <button type='button' class='btn btn-sm btn-danger' onclick=\"openAuthModal('delete', '$roleId')\">
                            <i class='fa-solid fa-trash'></i>
                        </button>
                        <button type='button' class='btn btn-sm btn-primary ms-1' onclick=\"openAuthModal('edit', '$roleId')\">
                            <i class='fa-solid fa-pen-to-square'></i>
                        </button>
                    </td>
                </tr>";
            }

			// responsive code for mobile screen
			private function renderCard($row) {
				$roleId = (int)$row['role_id'];
				$roleTitle = htmlspecialchars($row['role_name']);
				$roleDesc = htmlspecialchars($row['role_description']);

				return "<div class='card shadow-sm mb-3'>
					<div class='card-body'>
						<p><strong>Role ID:</strong> $roleId</p>
						<p><strong>Role Title:</strong> $roleTitle</p>
						<p><strong>Description:</strong> $roleDesc</p>
						<button type='button' class='btn btn-sm btn-danger w-100 mt-2' onclick=\"openAuthModal('delete', '$roleId')\">
							<i class='fa-solid fa-trash'></i>
						</button>
						<button type='button' class='btn btn-sm btn-primary mt-2' onclick=\"openAuthModal('edit', '$roleId')\">
							<i class='fa-solid fa-pen-to-square'></i>
						</button>
					</div>
				</div>";
			}


            // ✅ Show all roles (only current hotel)
            public function showAllRoles($hotel_id) {
                $stmt = $this->db_handle->prepare("SELECT * FROM role WHERE hotel_id=? ORDER BY role_id ASC");
                $stmt->bind_param("i", $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }


            // ✅ Search role (only current hotel)
            public function searchRole($keyword, $hotel_id) {
                $keyword = trim($keyword);
                if ($keyword === '') { 
                    $this->showAllRoles($hotel_id); 
                    return; 
                }
                $stmt = $this->db_handle->prepare("SELECT * FROM role WHERE role_name LIKE ? AND hotel_id=?");
                $like = "%$keyword%";
                $stmt->bind_param("si", $like, $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }


            // ✅ Delete role
            public function deleteRoleById($roleId) {
                $stmt = $this->db_handle->prepare("DELETE FROM role WHERE role_id=?");
                $stmt->bind_param("i", $roleId);
                if ($stmt->execute()) {
                    echo "<script>alert('Role Deleted Successfully'); window.location.href='searchrole.php';</script>";
                    exit;
                } else {
                    echo "<div class='alert alert-danger text-center mt-3'>Error deleting role.</div>";
                }
                $stmt->close();
            }

        }

        $search = new SearchRole();

        // ✅ Admin authentication
        if (isset($_POST['action']) && isset($_POST['admin_id']) && isset($_POST['admin_pass'])) {
            $adminEmail = "admin123@gmail.com";
            $adminPass  = "1234";

            if ($_POST['admin_id'] === $adminEmail && $_POST['admin_pass'] === $adminPass) {
                if ($_POST['action'] == "delete") {
                    $search->deleteRoleById($_POST['role_id']);
                } elseif ($_POST['action'] == "edit") {
                    echo "<script>window.location.href='role.php?role_id=" . urlencode($_POST['role_id']) . "';</script>";
                    exit;
                }
            } else {
                echo "<script>alert('Invalid Admin Credentials!');</script>";
            }
        }
        if (isset($_POST['b1'])) 
            $search->showAllRoles($hotel_id);
        elseif (isset($_POST['b2'])) 
            $search->searchRole(trim($_POST['t1']), $hotel_id);
        else 
            $search->showAllRoles($hotel_id);

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
                <input type="hidden" name="role_id" id="modalRoleId">
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
function openAuthModal(action, roleId) {
    document.getElementById('modalAction').value = action;
    document.getElementById('modalRoleId').value = roleId;
    var modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
}
</script>
</body>
</html>
