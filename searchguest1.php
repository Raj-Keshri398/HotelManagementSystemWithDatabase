<?php
session_start();
include 'connect.php';
include 'navbar.php';

// ✅ Agar login nahi hai to login page pe redirect karo
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

// ✅ Agar hotel not found to login page bhej do
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

        /* ---- Image styles: box shape, not round ---- */
        .table-img, .comp-img {
            object-fit: cover;
            border-radius: 6px;        
            cursor: pointer;
            transition: transform .12s ease, box-shadow .12s ease;
            display: inline-block;
            vertical-align: middle;
        }
        .table-img { width: 80px; height: 60px; }
        .comp-img  { width: 60px; height: 60px; }

        .table-img:hover, .comp-img:hover {
            transform: scale(1.06);
            box-shadow: 0 8px 20px rgba(0,0,0,0.18);
        }

        /* Card guest image */
        .card-guest-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            transition: transform .12s ease, box-shadow .12s ease;
        }
        .card-guest-img:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 26px rgba(0,0,0,0.18);
        }

        td img { display: block; }
        #modalImage {
            width: 100%;
            height: auto;
            max-height: calc(100vh - 120px);
            object-fit: contain;
        }
    </style>
</head>
<body>

<div class="container navbar-space">

    <!-- ✅ Top bar with search, date range and refresh -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col">
            <form method="post" class="row g-2 search-form" autocomplete="off">
                
                <!-- Guest ID -->
                <div class="col-12 col-md">
                    <input type="text" name="t1" class="form-control shadow-sm"
                        placeholder="Enter Guest ID to search"
                        value="<?php echo isset($_POST['b2']) ? htmlspecialchars($_POST['t1']) : ''; ?>">
                </div>

                <!-- From Date -->
                <div class="col-6 col-md-auto">
                    <input type="date" name="from_date" class="form-control shadow-sm"
                        value="<?php echo isset($_POST['b2']) ? htmlspecialchars($_POST['from_date']) : ''; ?>">
                </div>

                <!-- To Date -->
                <div class="col-6 col-md-auto">
                    <input type="date" name="to_date" class="form-control shadow-sm"
                        value="<?php echo isset($_POST['b2']) ? htmlspecialchars($_POST['to_date']) : ''; ?>">
                </div>

                <!-- Search button -->
                <div class="col-6 col-md-auto">
                    <button type="submit" name="b2" class="btn btn-dark w-100" title="Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>

                <!-- Refresh button -->
                <div class="col-6 col-md-auto">
                    <button type="submit" name="b1" class="btn btn-success w-100" title="Show All Guests">
                        <i class="fa-solid fa-arrows-rotate"></i> Show All
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php
        // ✅ Guest search class
        class SearchGuest extends Connect {

            public function __construct() {
                parent::__construct();
            }

            // ✅ Table render function
            private function renderTable($result) {
                if (!$result || mysqli_num_rows($result) === 0) {
                    echo "<div class='alert alert-warning text-center shadow-sm'>No guest found.</div>";
                    return;
                }

                // ✅ Desktop table
                echo "<div class='d-none d-md-block table-responsive shadow-sm rounded'>
                        <table class='table table-striped table-hover table-bordered align-middle text-nowrap'>
                        <thead class='table-dark text-center'>
                            <tr>
                                <th>ID Type</th>
                                <th>Guest ID</th>
                                <th>Guest Image</th>
                                <th>Title</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>DOB</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>City</th>
                                <th>Country</th>
                                <th>Companions</th>
                                <th>Companions Image</th>
                                <th>Time</th>
                                <th>Download</th> <!-- ✅ New column -->
                            </tr>
                        </thead>
                        <tbody>";

                $rows = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $rows[] = $row;
                    echo $this->renderRow($row);
                }
                echo "</tbody></table></div>";

                // ✅ Mobile cards
                echo "<div class='d-block d-md-none'>";
                foreach ($rows as $row) echo $this->renderCard($row);
                echo "</div>";
            }

            // ✅ Each row render
            private function renderRow($row) {
                $guestIdNo = htmlspecialchars($row['guest_id_no'] ?? '');

                // ✅ Guest Photo
                if (!empty($row['guest_photo'])) {
                    $gfile = htmlspecialchars($row['guest_photo']);
                    $guestPhoto = "<img src='uploads/guests/{$gfile}' data-full='uploads/guests/{$gfile}' class='table-img previewable' alt='Guest' onerror=\"this.onerror=null;this.src='';\">";
                } else {
                    $guestPhoto = "No Image";
                }

                // ✅ Companion Photo
                $companionPhoto = "No Image";
                if (!empty($row['companion_photos'])) {
                    $photos = array_filter(array_map('trim', explode(',', $row['companion_photos'])));
                    if (count($photos) > 0) {
                        $companionPhoto = "";
                        foreach ($photos as $p) {
                            $pf = htmlspecialchars($p);
                            $companionPhoto .= "<img src='uploads/companions/{$pf}' data-full='uploads/companions/{$pf}' class='comp-img previewable me-1 mb-1' alt='Companion' onerror=\"this.onerror=null;this.src='';\">";
                        }
                    }
                }

                // ✅ Full row return with PDF download button
                return "<tr>
                    <td>".htmlspecialchars($row['id_type'] ?? '')."</td>
                    <td>$guestIdNo</td>
                    <td>$guestPhoto</td>
                    <td>".htmlspecialchars($row['title'] ?? '')."</td>
                    <td>".htmlspecialchars($row['name'] ?? '')."</td>
                    <td>".htmlspecialchars($row['gender'] ?? '')."</td>
                    <td>".htmlspecialchars($row['dob'] ?? '')."</td>
                    <td>".htmlspecialchars($row['phone_no'] ?? '')."</td>
                    <td>".htmlspecialchars($row['email'] ?? '')."</td>
                    <td>".htmlspecialchars($row['address'] ?? '')."</td>
                    <td>".htmlspecialchars($row['city'] ?? '')."</td>
                    <td>".htmlspecialchars($row['country'] ?? '')."</td>
                    <td>".htmlspecialchars($row['companions'] ?? '')."</td>
                    <td>$companionPhoto</td>
                    <td>".htmlspecialchars($row['created_at'] ?? '')."</td>
                    <td class='text-center'>
                        <a href='guest_pdf.php?id=$guestIdNo' class='btn btn-sm btn-danger' target='_blank'>
                            <i class='fa-solid fa-file-pdf'></i> PDF
                        </a>
                    </td>
                </tr>";
            }

            // ✅ Mobile card view
            private function renderCard($row) {
                $guestIdNo = htmlspecialchars($row['guest_id_no'] ?? '');

                if (!empty($row['guest_photo'])) {
                    $gfile = htmlspecialchars($row['guest_photo']);
                    $guestPhoto = "<img src='uploads/guests/{$gfile}' data-full='uploads/guests/{$gfile}' class='card-guest-img previewable mb-2' alt='Guest' onerror=\"this.onerror=null;this.src='';\">";
                } else {
                    $guestPhoto = "<div class='text-center p-5 bg-light'>No Image</div>";
                }

                $companionPhoto = "No Image";
                if (!empty($row['companion_photos'])) {
                    $photos = array_filter(array_map('trim', explode(',', $row['companion_photos'])));
                    if (count($photos) > 0) {
                        $companionPhoto = "";
                        foreach ($photos as $p) {
                            $pf = htmlspecialchars($p);
                            $companionPhoto .= "<img src='uploads/companions/{$pf}' data-full='uploads/companions/{$pf}' class='comp-img previewable me-1 mb-1' alt='Companion' onerror=\"this.onerror=null;this.src='';\">";
                        }
                    }
                }

                return "<div class='card shadow-sm mb-3'>
                    <div class='card-body'>
                        <p><strong>ID Type:</strong> ".htmlspecialchars($row['id_type'] ?? '')."</p>
                        <p><strong>Guest ID:</strong> $guestIdNo</p>
                        <p><strong>Guest Image:</strong><br>$guestPhoto</p>
                        <p><strong>Title:</strong> ".htmlspecialchars($row['title'] ?? '')."</p>
                        <p><strong>Name:</strong> ".htmlspecialchars($row['name'] ?? '')."</p>
                        <p><strong>Gender:</strong> ".htmlspecialchars($row['gender'] ?? '')."</p>
                        <p><strong>DOB:</strong> ".htmlspecialchars($row['dob'] ?? '')."</p>
                        <p><strong>Phone:</strong> ".htmlspecialchars($row['phone_no'] ?? '')."</p>
                        <p><strong>Email:</strong> ".htmlspecialchars($row['email'] ?? '')."</p>
                        <p><strong>Address:</strong> ".htmlspecialchars($row['address'] ?? '')."</p>
                        <p><strong>City:</strong> ".htmlspecialchars($row['city'] ?? '')."</p>
                        <p><strong>Country:</strong> ".htmlspecialchars($row['country'] ?? '')."</p>
                        <p><strong>Companions:</strong> ".htmlspecialchars($row['companions'] ?? '')."</p>
                        <p><strong>Companion Image:</strong><br>$companionPhoto</p>
                        <p><strong>Time:</strong> ".htmlspecialchars($row['created_at'] ?? '')."</p>
                        <p><a href='guest_pdf.php?id=$guestIdNo' class='btn btn-sm btn-danger' target='_blank'><i class='fa-solid fa-file-pdf'></i> PDF</a></p>
                    </div>
                </div>";
            }

            // ✅ Show all guests by hotel_id
            public function showAllGuests($hotel_id) {
                $stmt = $this->db_handle->prepare("SELECT * FROM guest WHERE hotel_id=? ORDER BY guest_id_no ASC");
                if (!$stmt) die("Prepare failed (showAllGuests): " . $this->db_handle->error);
                $stmt->bind_param("i", $hotel_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }

            // ✅ Search guest by guest_id_no + date range
            public function searchGuest($keyword, $hotel_id, $fromDate = '', $toDate = '') {
                $keyword = trim($keyword);
                $conditions = ["hotel_id=?"];
                $params = [$hotel_id];
                $types = "i";

                // Guest ID condition
                if ($keyword !== '') {
                    $conditions[] = "guest_id_no LIKE ?";
                    $params[] = "%$keyword%";
                    $types .= "s";
                }

                // Date range condition
                if (!empty($fromDate) && !empty($toDate)) {
                    $conditions[] = "DATE(created_at) BETWEEN ? AND ?";
                    $params[] = $fromDate;
                    $params[] = $toDate;
                    $types .= "ss";
                } elseif (!empty($fromDate)) {
                    $conditions[] = "DATE(created_at) >= ?";
                    $params[] = $fromDate;
                    $types .= "s";
                } elseif (!empty($toDate)) {
                    $conditions[] = "DATE(created_at) <= ?";
                    $params[] = $toDate;
                    $types .= "s";
                }

                $where = implode(" AND ", $conditions);
                $sql = "SELECT * FROM guest WHERE $where ORDER BY guest_id_no ASC";

                $stmt = $this->db_handle->prepare($sql);
                if (!$stmt) die("Prepare failed (searchGuest): " . $this->db_handle->error);

                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                $this->renderTable($res);
                $stmt->close();
            }

            // ✅ Delete guest
            public function deleteGuest($guestId, $hotel_id) {
                $stmt = $this->db_handle->prepare("DELETE FROM guest WHERE guest_id_no=? AND hotel_id=?");
                if (!$stmt) die("Prepare failed (deleteGuest): " . $this->db_handle->error);
                $stmt->bind_param("si", $guestId, $hotel_id);
                if ($stmt->execute()) {
                    echo "<script>alert('Record Deleted Successfully'); window.location.href='searchguest1.php';</script>";
                    exit;
                } else {
                    echo "<div class='alert alert-danger text-center mt-3'>Error deleting record.</div>";
                }
                $stmt->close();
            }
        }

        // ✅ Create object and handle actions
        $search = new SearchGuest();

        if (isset($_POST['b1'])) {
            // ✅ click show all button working showallfuction and refresh the page and empty the text and date range box
            $search->showAllGuests($hotel_id);
        } elseif (isset($_POST['b2'])) {
            // ✅ click the search button showing conditional data
            $search->searchGuest(
                trim($_POST['t1']),
                $hotel_id,
                $_POST['from_date'] ?? '',
                $_POST['to_date'] ?? ''
            );
        } else {
            // ✅ Page load default show all data
            $search->showAllGuests($hotel_id);
        }

    ?>
</div>

<!-- ✅ Image preview modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body p-0">
        <img id="modalImage" src="" alt="Preview">
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ✅ Modal me image preview
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('imageModal');
    var bsModal = new bootstrap.Modal(modalEl);

    document.querySelectorAll('.previewable').forEach(function(img) {
        img.addEventListener('click', function() {
            var src = this.getAttribute('data-full') || this.src;
            document.getElementById('modalImage').src = src;
            bsModal.show();
        });
    });
});
</script>

</body>
</html>
