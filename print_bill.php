<?php 
session_start();
include 'connect.php'; // Database connection
include 'navbar.php';

if (!isset($_SESSION['email'])) {
    die("Unauthorized access - Please login first");
}

$bill_id = $_GET['bill_id'] ?? '';
if (!$bill_id) die("Bill ID is required.");

$conn = (new connect())->getDbHandle();
$stmt = $conn->prepare("SELECT * FROM bill WHERE bill_id=?");
$stmt->bind_param("s", $bill_id);
$stmt->execute();
$result = $stmt->get_result();
$bill = $result->fetch_assoc();
if (!$bill) die("Bill not found.");

// ---------------- STAY DURATION CALCULATION ----------------
$checkIn  = new DateTime($bill['check_in']);
$checkOut = new DateTime($bill['check_out']);
$diff = $checkOut->getTimestamp() - $checkIn->getTimestamp();
$totalMinutes = floor($diff / 60);
$totalHours   = $totalMinutes / 60;
$days  = floor($totalHours / 24);
$hours = floor($totalHours % 24);
$mins  = $totalMinutes % 60;
$stayDuration = $days." days ".$hours." hrs ".$mins." mins";

// ---------------- PAYMENT CALCULATION ----------------
$roomPerHour = $bill['room_charge'] / 24;
$roomTotal   = $roomPerHour * $totalHours;
$taxable     = $roomTotal + $bill['food_charge'] + $bill['service_charge'];
$cgst        = $taxable * ($bill['cgst']/100);
$sgst        = $taxable * ($bill['sgst']/100);
$deposit     = $bill['deposit_payment'];

// ✅ Discount as Percentage
$discountPercent = isset($bill['discount']) ? $bill['discount'] : 0;
$discount        = ($taxable * $discountPercent) / 100;

$finalAmount = $taxable + $cgst + $sgst - $deposit - $discount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice <?= htmlspecialchars($bill['bill_id']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
.invoice-box { 
    max-width: 900px; 
    margin: 50px auto; 
    padding: 30px; 
    border: 1px solid #eee; 
    background: #fff; 
    box-shadow: 0 0 10px rgba(0,0,0,0.15); 
    position: relative;
}

/* ✅ Watermark Table Overlay */
.watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-12deg);
    font-size: 120px;
    color: rgba(0,0,0,0.09);
    pointer-events: none;
    z-index: 100;
    white-space: nowrap;
    text-align: center;
}

/* Ensure main content is above watermark */
.invoice-content {
    position: relative;
    z-index: 10;
}

.table th, .table td { vertical-align: middle !important; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.btn-print { margin-top: 20px; }
@media print { .btn-print { display: none; } }
</style>
</head>
<body>

<div class="invoice-box">
    <!-- Watermark -->
    <div class="watermark"><?= htmlspecialchars($bill['hotel_name']) ?></div>

    <div class="invoice-content">
        <div class="row mb-4">
            <div class="col-6">
                <h2><?= htmlspecialchars($bill['hotel_name']) ?></h2>
                <p>Hotel ID: <?= htmlspecialchars($bill['hotel_id']) ?></p>
            </div>
            <div class="col-6 text-end">
                <h1>INVOICE</h1>
                <p><strong>Invoice No:</strong> <?= htmlspecialchars($bill['bill_id']) ?><br>
                   <strong>Date:</strong> <?= date("d-m-Y H:i", strtotime($bill['created_at'])) ?></p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <p><strong>Guest Name:</strong> <?= htmlspecialchars($bill['guest_name']) ?><br>
                   <strong>Booking ID:</strong> <?= htmlspecialchars($bill['booking_id']) ?><br>
                   <strong>Room No:</strong> <?= htmlspecialchars($bill['room_no']) ?></p>
            </div>
            <div class="col-6 text-end">
                <p><strong>Check-In:</strong> <?= date("d-m-Y H:i", strtotime($bill['check_in'])) ?><br>
                   <strong>Check-Out:</strong> <?= date("d-m-Y H:i", strtotime($bill['check_out'])) ?><br>
                   <strong>Stay Duration:</strong> <?= $stayDuration ?></p>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Room Charge (<?= round($totalHours,2) ?> hrs)</td>
                    <td class="text-right"><?= number_format($roomTotal,2) ?></td>
                </tr>
                <tr>
                    <td>Service Charge</td>
                    <td class="text-right"><?= number_format($bill['service_charge'],2) ?></td>
                </tr>
                <tr>
                    <td>Food Charge</td>
                    <td class="text-right"><?= number_format($bill['food_charge'],2) ?></td>
                </tr>
                <tr>
                    <td>Deposit Paid</td>
                    <td class="text-right">-<?= number_format($deposit,2) ?></td>
                </tr>
                <tr>
                    <td>CGST (<?= $bill['cgst'] ?>%)</td>
                    <td class="text-right"><?= number_format($cgst,2) ?></td>
                </tr>
                <tr>
                    <td>SGST (<?= $bill['sgst'] ?>%)</td>
                    <td class="text-right"><?= number_format($sgst,2) ?></td>
                </tr>
                <?php if($discountPercent>0): ?>
                <tr>
                    <td>Discount (<?= $discountPercent ?>%)</td>
                    <td class="text-right">-<?= number_format($discount,2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="table-success">
                    <th>Final Amount</th>
                    <th class="text-right"><?= number_format($finalAmount,2) ?></th>
                </tr>
            </tbody>
        </table>

        <p><strong>Payment Method:</strong> <?= htmlspecialchars($bill['payment_method']) ?><br>
           <strong>Transaction No:</strong> <?= htmlspecialchars($bill['transaction_no']) ?></p>

        <div class="text-center">
            <button onclick="window.print()" class="btn btn-primary btn-print">Print Invoice</button>
        </div>
    </div>
</div>

</body>
</html>
