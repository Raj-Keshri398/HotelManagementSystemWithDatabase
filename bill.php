<?php
session_start();
include 'connect.php';
include 'navbar.php';

// ✅ Extend connect class for database handle
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

// ✅ User authentication
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please login to manage invoices.'); window.location.href='login.php';</script>";
    exit;
}

// ✅ Initialize bill data
$bill = [
    "bill_id" => "",
    "booking_id" => "",
    "guest_name" => "",
    "room_no" => "",
    "room_charge" => "",
    "deposit_payment" => "",
    "service_charge" => "",
    "food_charge" => "",
    "discount" => "",
    "check_in" => "",
    "check_out" => "",
    "stay_duration" => "",
    "total_payment" => "",
    "cgst" => "",
    "sgst" => "",
    "final_amount" => "",
    "payment_method" => "",
    "transaction_no" => ""
];

// ✅ Reset form
if (isset($_POST['new'])) {
    foreach ($bill as $key => $value) $bill[$key] = "";
}

// ✅ Function to calculate final amounts
function calculateFinalAmount($total, $food, $service, $cgst, $sgst, $deposit, $discount) {
    $subtotal = $total + $food + $service;
    $cgst_amount = ($subtotal * $cgst) / 100;
    $sgst_amount = ($subtotal * $sgst) / 100;
    $gst_total   = $cgst_amount + $sgst_amount;
    $after_deposit = $subtotal + $gst_total - $deposit;
    $discount_amount = ($after_deposit * $discount) / 100;
    return [
        'cgst_amount' => $cgst_amount,
        'sgst_amount' => $sgst_amount,
        'final_amount' => $after_deposit - $discount_amount
    ];
}

// ✅ Save bill
if (isset($_POST['save'])) {
    $hotel_id = $_SESSION['hotel_id'];
    $hotel_name = $_SESSION['hotel_name'];

    // Generate bill_id like INV000123
    $invoice_id = "INV" . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Calculate final amount
    $calc = calculateFinalAmount(
        $_POST['total_payment'],
        $_POST['food_charge'],
        $_POST['service_charge'],
        $_POST['cgst'],
        $_POST['sgst'],
        $_POST['deposit_payment'],
        $_POST['discount']
    );

    $stmt = $conn->prepare("INSERT INTO bill 
    (bill_id, hotel_id, hotel_name, booking_id, guest_name, room_no, room_charge, deposit_payment, service_charge, food_charge, discount, check_in, check_out, stay_duration, total_payment, cgst, sgst, final_amount, payment_method, transaction_no) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssssssdddsssdddssss",
        $invoice_id,
        $hotel_id,
        $hotel_name,
        $_POST['booking_id'],
        $_POST['guest_name'],
        $_POST['room_no'],
        $_POST['room_charge'],
        $_POST['deposit_payment'],
        $_POST['service_charge'],
        $_POST['food_charge'],
        $_POST['discount'],
        $_POST['check_in'],
        $_POST['check_out'],
        $_POST['stay_duration'],
        $_POST['total_payment'],
        $_POST['cgst'],
        $_POST['sgst'],
        $calc['final_amount'],
        $_POST['payment_method'],
        $_POST['transaction_no']
    );

    if ($stmt->execute()) {
        // ✅ Update checkout in booking
        $updateBooking = $conn->prepare("UPDATE booking SET check_out=? WHERE booking_id=?");
        $updateBooking->bind_param("ss", $_POST['check_out'], $_POST['booking_id']);
        $updateBooking->execute();
        
        // ✅ Update room availability
        $updateRoom = $conn->prepare("UPDATE room 
                                    SET booking_status='Available', cleaning_status='Uncleaned' 
                                    WHERE room_id=?");
        $updateRoom->bind_param("i", $_POST['room_id']);
        $updateRoom->execute();

        // ✅ Redirect to print bill page
        echo "<script>alert('Invoice saved successfully!'); window.location.href='print_bill.php?bill_id=$invoice_id';</script>";
        exit;
    } else {
        echo "<script>alert('Error: ".$stmt->error."');</script>";
    }
}

// ✅ Update bill
if (isset($_POST['update'])) {
    $hotel_id = $_SESSION['hotel_id'];
    $hotel_name = $_SESSION['hotel_name'];

    $calc = calculateFinalAmount(
        $_POST['total_payment'],
        $_POST['food_charge'],
        $_POST['service_charge'],
        $_POST['cgst'],
        $_POST['sgst'],
        $_POST['deposit_payment'],
        $_POST['discount']
    );
    
    $stmt = $conn->prepare("UPDATE bill SET 
    hotel_id=?, hotel_name=?, booking_id=?, guest_name=?, room_no=?, room_charge=?, deposit_payment=?, service_charge=?, food_charge=?, discount=?, check_in=?, check_out=?, stay_duration=?, total_payment=?, cgst=?, sgst=?, final_amount=?, payment_method=?, transaction_no=? 
    WHERE bill_id=?");
    $stmt->bind_param(
        "ssssssdddsssdddsssss",
        $hotel_id,
        $hotel_name,
        $_POST['booking_id'],
        $_POST['guest_name'],
        $_POST['room_no'],
        $_POST['room_charge'],
        $_POST['deposit_payment'],
        $_POST['service_charge'],
        $_POST['food_charge'],
        $_POST['discount'],
        $_POST['check_in'],
        $_POST['check_out'],
        $_POST['stay_duration'],
        $_POST['total_payment'],
        $_POST['cgst'],
        $_POST['sgst'],
        $calc['final_amount'],
        $_POST['payment_method'],
        $_POST['transaction_no'],
        $_POST['bill_id']
    );

    if ($stmt->execute()) {
        echo "<script>alert('Invoice updated successfully!'); window.location.href='bill.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error updating: ".$stmt->error."');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.form-container { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin: 50px auto; max-width: 900px; }
.form-label { font-size: 14px; font-weight: 500; }
.form-control, .form-select { font-size: 14px; padding: 6px 10px; }
h2 { font-size: 22px; margin-bottom: 15px; }
.btn { padding: 4px 12px; font-size: 14px; }
</style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Invoice Management</h2>
        <form method="POST" class="row g-3" novalidate>
            
            <input type="hidden" name="bill_id" value="<?= htmlspecialchars($bill['bill_id']) ?>">
            <input type="hidden" id="room_id" name="room_id">
            
            <!-- ✅ Booking Dropdown -->
            <div class="col-md-6">
                <label class="form-label">Booking</label>
                <select name="booking_id" id="booking_id" class="form-select" required>
                    <option value="">-- Select Booking --</option>
                    <?php
                    $hotel_id = $_SESSION['hotel_id'];
                    $bookings = $conn->prepare("SELECT b.booking_id, b.guest_id, b.room_id, r.room_no, b.room_charge, b.deposit_payment, b.check_in 
                                                FROM booking b 
                                                JOIN room r ON b.room_id = r.room_id 
                                                WHERE b.check_out IS NULL AND b.hotel_id=?");
                    $bookings->bind_param("s", $hotel_id);
                    $bookings->execute();
                    $bookings = $bookings->get_result();
                    while ($b = $bookings->fetch_assoc()) {
                        $sel = ($bill['booking_id'] == $b['booking_id']) ? "selected" : "";
                        echo "<option value='{$b['booking_id']}' 
                                data-guest='{$b['guest_id']}' 
                                data-room-id='{$b['room_id']}' 
                                data-room-no='{$b['room_no']}' 
                                data-charge='{$b['room_charge']}' 
                                data-deposit='{$b['deposit_payment']}' 
                                data-checkin='{$b['check_in']}'
                                $sel>Booking {$b['booking_id']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- ✅ Guest Name -->
            <div class="col-md-6">
                <label class="form-label">Guest Name</label>
                <input type="text" id="guest_name" name="guest_name" class="form-control" value="<?= htmlspecialchars($bill['guest_name']) ?>" readonly>
            </div>

            <!-- ✅ Room Details -->
            <div class="col-md-6">
                <label class="form-label">Room Number</label>
                <input type="text" id="room_no" name="room_no" class="form-control" value="<?= htmlspecialchars($bill['room_no']) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">Room Charge (Per Day)</label>
                <input type="number" step="0.01" id="room_charge" name="room_charge" class="form-control" value="<?= htmlspecialchars($bill['room_charge']) ?>" readonly>
            </div>

            <!-- ✅ Check-in/Check-out -->
            <div class="col-md-6">
                <label class="form-label">Check-In</label>
                <input type="text" id="check_in" name="check_in" class="form-control" value="<?= htmlspecialchars($bill['check_in']) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">Check-Out</label>
                <input type="datetime-local" name="check_out" id="check_out" class="form-control" value="<?= htmlspecialchars($bill['check_out']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Stay Duration</label>
                <input type="text" name="stay_duration" id="stay_duration" class="form-control" value="<?= htmlspecialchars($bill['stay_duration']) ?>" readonly>
            </div>

            <!-- ✅ Charges -->
            <div class="col-md-6">
                <label class="form-label">Service Charge</label>
                <input type="number" step="0.01" name="service_charge" class="form-control" value="<?= htmlspecialchars($bill['service_charge']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Food Charge</label>
                <input type="number" step="0.01" name="food_charge" class="form-control" value="<?= htmlspecialchars($bill['food_charge']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Total Payment (Room only)</label>
                <input type="number" step="0.01" name="total_payment" class="form-control" value="<?= htmlspecialchars($bill['total_payment']) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">CGST (%)</label>
                <input type="number" step="0.01" name="cgst" class="form-control" value="<?= htmlspecialchars($bill['cgst']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">SGST (%)</label>
                <input type="number" step="0.01" name="sgst" class="form-control" value="<?= htmlspecialchars($bill['sgst']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Deposit</label>
                <input type="number" step="0.01" id="deposit_payment" name="deposit_payment" class="form-control" value="<?= htmlspecialchars($bill['deposit_payment']) ?>" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label">Discount (%)</label>
                <input type="number" step="0.01" name="discount" class="form-control" value="<?= htmlspecialchars($bill['discount']) ?>">
            </div>


            <div class="col-md-6">
                <label class="form-label">Final Amount</label>
                <input type="number" step="0.01" name="final_amount" class="form-control" value="<?= htmlspecialchars($bill['final_amount']) ?>" readonly>
            </div>

            <!-- ✅ Payment Method -->
            <div class="col-md-6">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-select">
                    <option value="">-- Select Method --</option>
                    <option value="Cash" <?= $bill['payment_method']=="Cash"?"selected":"" ?>>Cash</option>
                    <option value="Card" <?= $bill['payment_method']=="Card"?"selected":"" ?>>Card</option>
                    <option value="UPI" <?= $bill['payment_method']=="UPI"?"selected":"" ?>>UPI</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Transaction No</label>
                <input type="text" name="transaction_no" class="form-control" value="<?= htmlspecialchars($bill['transaction_no']) ?>">
            </div>

            <!-- ✅ Buttons -->
            <div class="col-12 text-center mt-3">
                <button type="submit" name="save" class="btn btn-primary">Save</button>
                <button type="submit" name="update" class="btn btn-warning">Update</button>
                <button type="submit" name="new" class="btn btn-secondary">New</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('booking_id').addEventListener('change', function(){
    let opt = this.options[this.selectedIndex];
    document.getElementById('guest_name').value = opt.getAttribute('data-guest') || '';
    document.getElementById('room_no').value = opt.getAttribute('data-room-no') || '';
    document.getElementById('room_id').value = opt.getAttribute('data-room-id') || '';
    document.getElementById('room_charge').value = opt.getAttribute('data-charge') || '';
    document.getElementById('deposit_payment').value = opt.getAttribute('data-deposit') || '';
    document.getElementById('check_in').value = opt.getAttribute('data-checkin') || '';
});

function calculatePayment() {
    let checkIn = document.getElementById("check_in").value;
    let checkOut = document.getElementById("check_out").value;
    let roomChargePerDay = parseFloat(document.getElementById("room_charge").value) || 0;
    let foodCharge = parseFloat(document.querySelector("input[name='food_charge']").value) || 0;
    let serviceCharge = parseFloat(document.querySelector("input[name='service_charge']").value) || 0;
    let deposit = parseFloat(document.getElementById("deposit_payment").value) || 0;
    let cgst = parseFloat(document.querySelector("input[name='cgst']").value) || 0;
    let sgst = parseFloat(document.querySelector("input[name='sgst']").value) || 0;
    let discount = parseFloat(document.querySelector("input[name='discount']").value) || 0;

    if (checkIn && checkOut) {
        let inDate = new Date(checkIn);
        let outDate = new Date(checkOut);
        let diffMs = outDate - inDate; 
        let totalHours = diffMs / (1000 * 60 * 60);

        let days = Math.floor(totalHours / 24);
        let hours = Math.floor(totalHours % 24);
        let mins = Math.floor((totalHours*60) % 60);

        document.getElementById("stay_duration").value =
            days + " days " + hours + " hrs " + mins + " mins";

        let roomPerHour = roomChargePerDay / 24;
        let roomTotal = roomPerHour * totalHours;

        let subtotal = roomTotal + foodCharge + serviceCharge;
        let cgstAmount = (subtotal * cgst) / 100;
        let sgstAmount = (subtotal * sgst) / 100;

        let afterDeposit = (subtotal + cgstAmount + sgstAmount) - deposit;
        let discountAmount = (subtotal * discount) / 100;
        let finalAmount = afterDeposit - discountAmount;

        document.querySelector("input[name='total_payment']").value = roomTotal.toFixed(2);
        document.querySelector("input[name='final_amount']").value = finalAmount.toFixed(2);
    }
}

document.getElementById("check_out").addEventListener("change", calculatePayment);
document.querySelector("input[name='food_charge']").addEventListener("input", calculatePayment);
document.querySelector("input[name='service_charge']").addEventListener("input", calculatePayment);
document.querySelector("input[name='cgst']").addEventListener("input", calculatePayment);
document.querySelector("input[name='sgst']").addEventListener("input", calculatePayment);
document.querySelector("input[name='discount']").addEventListener("input", calculatePayment);
</script>

</body>
</html>
