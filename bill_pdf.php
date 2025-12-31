<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['email'])) {
    die("Unauthorized access");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invoice ID is required.");
}

$billId = $_GET['id'];
$conn = (new connect())->getDbHandle();

// Fetch bill details
$stmt = $conn->prepare("SELECT b.*, h.hotel_name FROM bill b JOIN hotel h ON b.hotel_id = h.hotel_id WHERE b.bill_id=?");
$stmt->bind_param("s", $billId);
$stmt->execute();
$result = $stmt->get_result();
$bill = $result->fetch_assoc();
$stmt->close();

if (!$bill) die("Invoice not found.");

// ---------------- STAY DURATION ----------------
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
$cgst        = $taxable * 0.09;
$sgst        = $taxable * 0.09;
$deposit     = $bill['deposit_payment'];
$discount    = isset($bill['discount']) ? $bill['discount'] : 0;
$finalAmount = $taxable + $cgst + $sgst - $deposit - $discount;

// ---------------- PDF GENERATION ----------------
require("fpdf/fpdf.php");

$pdf = new FPDF();
$pdf->AddPage();

// HEADER
$pdf->SetFont("Arial","B",18);
$pdf->Cell(0,10, $bill['hotel_name'],0,1,"L");
$pdf->Ln(5);

$pdf->SetFont("Arial","B",16);
$pdf->Cell(0,10,"INVOICE",0,1,"R");
$pdf->SetFont("Arial","",12);
$pdf->Cell(0,6,"Hotel ID: ".$bill['hotel_id'],0,1,"R");
$pdf->SetFont("Arial","",12);
$pdf->Cell(0,6,"Invoice ID: ".$bill['bill_id'],0,1,"R");
$pdf->Cell(0,6,"Date: ".date("d-m-Y H:i", strtotime($bill['created_at'])),0,1,"R");
$pdf->Ln(5);

// GUEST DETAILS
$pdf->SetFont("Arial","B",12);
$pdf->Cell(0,6,"Guest Details:",0,1);
$pdf->SetFont("Arial","",12);
$pdf->Cell(50,6,"Guest Name:",0,0);
$pdf->Cell(0,6,$bill['guest_name'],0,1);
$pdf->Cell(50,6,"Booking ID:",0,0);
$pdf->Cell(0,6,$bill['booking_id'],0,1);
$pdf->Cell(50,6,"Room No:",0,0);
$pdf->Cell(0,6,$bill['room_no'],0,1);
$pdf->Cell(50,6,"Check-In:",0,0);
$pdf->Cell(0,6,$bill['check_in'],0,1);
$pdf->Cell(50,6,"Check-Out:",0,0);
$pdf->Cell(0,6,$bill['check_out'],0,1);
$pdf->Cell(50,6,"Stay Duration:",0,0);
$pdf->Cell(0,6,$stayDuration,0,1);
$pdf->Ln(5);

// PAYMENT TABLE
$pdf->SetFont("Arial","B",12);
$pdf->Cell(140,8,"Description",1,0,"L");
$pdf->Cell(50,8,"Amount (₹)",1,1,"R");
$pdf->SetFont("Arial","",12);

// Room Charge
$pdf->Cell(140,8,"Room Charge (".round($totalHours,2)." hrs)",1,0);
$pdf->Cell(50,8,number_format($roomTotal,2),1,1,"R");

// Service Charge
$pdf->Cell(140,8,"Service Charge",1,0);
$pdf->Cell(50,8,number_format($bill['service_charge'],2),1,1,"R");

// Food Charge
$pdf->Cell(140,8,"Food Charge",1,0);
$pdf->Cell(50,8,number_format($bill['food_charge'],2),1,1,"R");

// Deposit
$pdf->Cell(140,8,"Deposit Paid",1,0);
$pdf->Cell(50,8,"-".number_format($deposit,2),1,1,"R");

// CGST
$pdf->Cell(140,8,"CGST (9%)",1,0);
$pdf->Cell(50,8,number_format($cgst,2),1,1,"R");

// SGST
$pdf->Cell(140,8,"SGST (9%)",1,0);
$pdf->Cell(50,8,number_format($sgst,2),1,1,"R");

// Discount
if($discount>0){
    $pdf->Cell(140,8,"Discount",1,0);
    $pdf->Cell(50,8,"-".number_format($discount,2),1,1,"R");
}

// Final Amount
$pdf->SetFont("Arial","B",12);
$pdf->Cell(140,10,"Final Amount",1,0);
$pdf->Cell(50,10,number_format($finalAmount,2),1,1,"R");
$pdf->Ln(5);

// Payment Method
$pdf->SetFont("Arial","",12);
$pdf->Cell(0,6,"Payment Method: ".$bill['payment_method'],0,1);
$pdf->Cell(0,6,"Transaction No: ".$bill['transaction_no'],0,1);

// Footer
$pdf->Ln(5);
$pdf->SetFont("Arial","I",10);
$pdf->Cell(0,6,"Thank you for staying with us!",0,1,"C");

$pdf->Output("D","Invoice_".$billId.".pdf");
exit;
?>
