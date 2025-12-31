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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
body { background-color: #f8f9fa; }
.navbar-space { margin-top: 80px; }
.search-form input { height: 45px; }
</style>
</head>
<body>

<div class="container navbar-space">

<!-- Search Form -->
<div class="row g-3 align-items-center mb-4">
    <div class="col">
        <form method="post" class="row g-2 search-form" autocomplete="off">
            <div class="col-12 col-md">
                <input type="text" name="t1" class="form-control shadow-sm" placeholder="Enter Invoice No or Booking ID" value="<?= isset($_POST['b2']) ? htmlspecialchars($_POST['t1']) : ''; ?>">
            </div>
            <div class="col-6 col-md-auto">
                <input type="date" name="from_date" class="form-control shadow-sm" value="<?= isset($_POST['b2']) ? htmlspecialchars($_POST['from_date']) : ''; ?>">
            </div>
            <div class="col-6 col-md-auto">
                <input type="date" name="to_date" class="form-control shadow-sm" value="<?= isset($_POST['b2']) ? htmlspecialchars($_POST['to_date']) : ''; ?>">
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
class SearchBill extends Connect {
    public function __construct() {
        parent::__construct();
    }

    private function renderTable($result) {
        if (!$result || mysqli_num_rows($result) === 0) {
            echo "<div class='alert alert-warning text-center shadow-sm'>No invoice found.</div>";
            return;
        }

        echo "<div class='d-none d-md-block table-responsive shadow-sm rounded'>
            <table class='table table-striped table-hover table-bordered align-middle text-nowrap'>
            <thead class='table-dark text-center'>
            <tr>
                <th>Invoice No</th>
                <th>Booking ID</th>
                <th>Guest</th>
                <th>Room No</th>
                <th>Room Charge</th>
                <th>Deposit</th>
                <th>Service</th>
                <th>Food</th>
                <th>CGST</th>
                <th>SGST</th>
                <th>Discount</th>
                <th>Final Amount</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Stay</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Transaction No</th>
                <th>Download</th>
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

        echo "<div class='d-block d-md-none'>";
        foreach ($rows as $row) echo $this->renderCard($row);
        echo "</div>";
    }

    private function renderRow($row) {
        $invoiceNo = htmlspecialchars($row['bill_id']);
        return "<tr>
            <td>{$invoiceNo}</td>
            <td>".htmlspecialchars($row['booking_id'])."</td>
            <td>".htmlspecialchars($row['guest_name'])."</td>
            <td>".htmlspecialchars($row['room_no'])."</td>
            <td>".htmlspecialchars($row['room_charge'])."</td>
            <td>".htmlspecialchars($row['deposit_payment'])."</td>
            <td>".htmlspecialchars($row['service_charge'])."</td>
            <td>".htmlspecialchars($row['food_charge'])."</td>
            <td>".htmlspecialchars($row['cgst'])."</td>
            <td>".htmlspecialchars($row['sgst'])."</td>
            <td>".htmlspecialchars($row['discount'])."</td>
            <td>".htmlspecialchars($row['final_amount'])."</td>
            <td>".htmlspecialchars($row['check_in'])."</td>
            <td>".htmlspecialchars($row['check_out'])."</td>
            <td>".htmlspecialchars($row['stay_duration'])."</td>
            <td>".htmlspecialchars($row['total_payment'])."</td>
            <td>".htmlspecialchars($row['payment_method'])."</td>
            <td>".htmlspecialchars($row['transaction_no'])."</td>
            <td class='text-center'>
                <a href='bill_pdf.php?id=$invoiceNo' class='btn btn-sm btn-danger' target='_blank'>
                    <i class='fa-solid fa-file-pdf'></i> PDF
                </a>
            </td>
            <td class='text-center'>
                <button type='button' class='btn btn-sm btn-danger' onclick=\"openAuthModal('delete', '$invoiceNo')\">
                    <i class='fa-solid fa-trash'></i>
                </button>
            </td>
        </tr>";
    }

    private function renderCard($row) {
        $invoiceNo = htmlspecialchars($row['bill_id']);
        return "<div class='card shadow-sm mb-3'>
            <div class='card-body'>
                <p><strong>Invoice No:</strong> {$invoiceNo}</p>
                <p><strong>Booking ID:</strong> ".htmlspecialchars($row['booking_id'])."</p>
                <p><strong>Guest:</strong> ".htmlspecialchars($row['guest_name'])."</p>
                <p><strong>Room No:</strong> ".htmlspecialchars($row['room_no'])."</p>
                <p><strong>Room Charge:</strong> ".htmlspecialchars($row['room_charge'])."</p>
                <p><strong>Deposit:</strong> ".htmlspecialchars($row['deposit_payment'])."</p>
                <p><strong>Service:</strong> ".htmlspecialchars($row['service_charge'])."</p>
                <p><strong>Food:</strong> ".htmlspecialchars($row['food_charge'])."</p>
                <p><strong>CGST:</strong> ".htmlspecialchars($row['cgst'])."</p>
                <p><strong>SGST:</strong> ".htmlspecialchars($row['sgst'])."</p>
                <p><strong>Discount:</strong> ".htmlspecialchars($row['discount'])."</p>
                <p><strong>Final Amount:</strong> ".htmlspecialchars($row['final_amount'])."</p>
                <p><strong>Check-In:</strong> ".htmlspecialchars($row['check_in'])."</p>
                <p><strong>Check-Out:</strong> ".htmlspecialchars($row['check_out'])."</p>
                <p><strong>Stay:</strong> ".htmlspecialchars($row['stay_duration'])."</p>
                <p><strong>Total:</strong> ".htmlspecialchars($row['total_payment'])."</p>
                <p><strong>Payment:</strong> ".htmlspecialchars($row['payment_method'])."</p>
                <p><strong>Transaction No:</strong> ".htmlspecialchars($row['transaction_no'])."</p>
                <p><a href='bill_pdf.php?id=$invoiceNo' class='btn btn-sm btn-danger w-100 mt-2' target='_blank'>
                    <i class='fa-solid fa-file-pdf'></i> PDF
                </a></p>
                <button type='button' class='btn btn-sm btn-danger w-100 mt-2' onclick=\"openAuthModal('delete', '$invoiceNo')\">
                    <i class='fa-solid fa-trash'></i> Delete
                </button>
            </div>
        </div>";
    }

    public function showAllInvoices($hotel_id) {
        $stmt = $this->db_handle->prepare("SELECT * FROM bill WHERE hotel_id=? ORDER BY bill_id ASC");
        $stmt->bind_param("s", $hotel_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $this->renderTable($res);
        $stmt->close();
    }

    public function searchInvoice($keyword, $hotel_id, $fromDate = '', $toDate = '') {
        $keyword = trim($keyword);
        $conditions = ["hotel_id=?"];
        $params = [$hotel_id];
        $types = "s";

        if ($keyword !== '') {
            $conditions[] = "(bill_id LIKE ? OR booking_id LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $types .= "ss";
        }

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
        $sql = "SELECT * FROM bill WHERE $where ORDER BY bill_id ASC";

        $stmt = $this->db_handle->prepare($sql);
        if (!$stmt) die("Prepare failed (searchInvoice): " . $this->db_handle->error);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $this->renderTable($res);
        $stmt->close();
    }

    public function deleteInvoice($invoiceNo, $hotel_id) {
        $stmt = $this->db_handle->prepare("DELETE FROM bill WHERE bill_id=? AND hotel_id=?");
        $stmt->bind_param("ss", $invoiceNo, $hotel_id);
        if ($stmt->execute()) {
            echo "<script>alert('Invoice Deleted Successfully'); window.location.href='searchbill1.php';</script>";
            exit;
        } else {
            echo "<div class='alert alert-danger text-center mt-3'>Error deleting invoice.</div>";
        }
        $stmt->close();
    }
}

$search = new SearchBill();

if (isset($_POST['action'], $_POST['admin_id'], $_POST['admin_pass'])) {
    $adminEmail = "admin123@gmail.com";
    $adminPass  = "1234";
    if ($_POST['admin_id'] === $adminEmail && $_POST['admin_pass'] === $adminPass) {
        if ($_POST['action'] == "delete") {
            $search->deleteInvoice($_POST['invoice_no'], $hotel_id);
        }
    } else {
        echo "<script>alert('Invalid Admin Credentials!');</script>";
    }
}

if (isset($_POST['b1'])) $search->showAllInvoices($hotel_id);
elseif (isset($_POST['b2'])) $search->searchInvoice(trim($_POST['t1']), $hotel_id, $_POST['from_date'] ?? '', $_POST['to_date'] ?? '');
else $search->showAllInvoices($hotel_id);
?>

<!-- Admin Modal -->
<div class="modal fade" id="authModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Admin Authentication</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" id="modalAction">
            <input type="hidden" name="invoice_no" id="modalInvoiceNo">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAuthModal(action, invoiceNo) {
    document.getElementById('modalAction').value = action;
    document.getElementById('modalInvoiceNo').value = invoiceNo;
    new bootstrap.Modal(document.getElementById('authModal')).show();
}
</script>
</body>
</html>
