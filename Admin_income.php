<?php
// income.php
require_once 'connect.php';

class ConnectFixed extends connect {
    public function getConnection() { return $this->db_handle; }
}
$db = new ConnectFixed();
$conn = $db->getConnection();

if (!$conn) {
    echo "<div class='alert alert-danger'>Database connection failed.</div>";
    return;
}

// Helper: safe scalar query (query fail ho to 0)
function scalar($conn, $sql) {
    $res = @$conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_row();
    return $row ? (float)$row[0] : 0;
}
function count_rows($conn, $table) {
    $res = @$conn->query("SELECT COUNT(*) FROM `$table`");
    if (!$res) return 0;
    $row = $res->fetch_row();
    return $row ? (int)$row[0] : 0;
}

// --- KPIs ---
// Total Income (all time)
$total_income   = scalar($conn, "SELECT COALESCE(SUM(amount),0) FROM payments");
// Total Expenses (all time)
$total_expenses = scalar($conn, "SELECT COALESCE(SUM(amount),0) FROM expenses");
// Total Guests
$total_guests   = count_rows($conn, "guest");
// Total Staff
$total_staff    = count_rows($conn, "employee");

// Today
$today_income   = scalar($conn, "SELECT COALESCE(SUM(amount),0) FROM payments  WHERE DATE(payment_date) = CURDATE()");
$today_expenses = scalar($conn, "SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE(expense_date) = CURDATE()");
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0"><i class="fas fa-chart-line me-2"></i>Income Dashboard</h3>
    <span class="text-muted small"><?= date('d M Y') ?></span>
</div>

<div class="row g-3">
    <!-- Total Income -->
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 display-6"><i class="fas fa-sack-dollar"></i></div>
                <div>
                    <div class="text-muted small">Total Income</div>
                    <div class="h4 mb-0">₹<?= number_format($total_income, 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Guests -->
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 display-6"><i class="fas fa-user-friends"></i></div>
                <div>
                    <div class="text-muted small">Total Guests</div>
                    <div class="h4 mb-0"><?= number_format($total_guests) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Expenses -->
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 display-6"><i class="fas fa-money-bill-wave"></i></div>
                <div>
                    <div class="text-muted small">Total Expenses</div>
                    <div class="h4 mb-0">₹<?= number_format($total_expenses, 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Staff -->
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 display-6"><i class="fas fa-user-tie"></i></div>
                <div>
                    <div class="text-muted small">Total Staff</div>
                    <div class="h4 mb-0"><?= number_format($total_staff) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today Income -->
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 display-6"><i class="fas fa-arrow-trend-up"></i></div>
                <div>
                    <div class="text-muted small">Today Income</div>
                    <div class="h4 mb-0">₹<?= number_format($today_income, 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today Expenses -->
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 display-6"><i class="fas fa-arrow-trend-down"></i></div>
                <div>
                    <div class="text-muted small">Today Expenses</div>
                    <div class="h4 mb-0">₹<?= number_format($today_expenses, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Thoda polish */
    .card .display-6 { opacity: .85; }
    .card { border-radius: 12px; }
</style>
