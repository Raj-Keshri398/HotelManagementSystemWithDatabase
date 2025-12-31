<?php
// ✅ Session start karte hain
session_start();
include 'connect.php'; // Database connection file include

// ✅ Agar user login nahi hai to stop kar do
if (!isset($_SESSION['email'])) {
    die("❌ Unauthorized access - Please login first.");
}

// ✅ Agar URL me guest id nahi aayi hai to stop kar do
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Invalid request - Guest ID not provided.");
}

// ✅ URL se guest id le lo
$guestId = $_GET['id'];

// ✅ Database connection create
$db = new Connect();
$conn = $db->getDbHandle();

// ✅ Guest ki details nikalna
$stmt = $conn->prepare("SELECT * FROM guest WHERE guest_id_no=?");
$stmt->bind_param("s", $guestId);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Agar guest nahi mila to error
if ($result->num_rows === 0) {
    die("❌ Guest not found in database.");
}
$guest = $result->fetch_assoc();
$stmt->close();

// ✅ FPDF library include karo
require("fpdf/fpdf.php");

// ✅ PDF object create
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont("Arial","B",16);

// ✅ Heading
$pdf->Cell(0,10,"Guest Details",0,1,"C");
$pdf->Ln(5);

// ✅ Normal font set
$pdf->SetFont("Arial","",12);

// ✅ Guest details loop
foreach ($guest as $key => $value) {
    if (empty($value)) continue; // agar empty hai to skip karo

    // ✅ Guest Photo
    if ($key === "guest_photo") {
        $pdf->Cell(50,40,"Guest Photo",1,0,"C");

        $imagePath = __DIR__ . "/uploads/guests/" . $value;
        if (!empty($value) && file_exists($imagePath)) {
            $x = $pdf->GetX(); 
            $y = $pdf->GetY();
            $pdf->Image($imagePath, $x+5, $y+5, 50, 30); // Image inside right cell
            $pdf->Cell(140,40,"",1,1);
        } else {
            $pdf->Cell(140,40,"No Image Found",1,1,"C");
        }
        continue;
    }

    // ✅ Companion Photos
    if ($key === "companion_photos") {
        $pdf->Cell(50,40,"Companions Photo",1,0,"C");

        $photos = array_filter(array_map('trim', explode(',', $value)));
        if (!empty($photos)) {
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $offset = 5;
            foreach ($photos as $p) {
                $filePath = __DIR__ . "/uploads/companions/" . $p;
                if (file_exists($filePath)) {
                    $pdf->Image($filePath, $x+$offset, $y+5, 25, 25);
                    $offset += 30; // next image ka gap
                }
            }
            $pdf->Cell(140,40,"",1,1);
        } else {
            $pdf->Cell(140,40,"No Images",1,1,"C");
        }
        continue;
    }

    // ✅ Baaki sab normal text fields as table
    $pdf->Cell(50,10,ucwords(str_replace("_"," ",$key)),1,0); // field name
    $pdf->Cell(140,10,$value,1,1); // field value
}

// ✅ PDF download force kar do (Guest_XXX.pdf ke naam se)
$pdf->Output("D","Guest_$guestId.pdf");
exit;
?>
