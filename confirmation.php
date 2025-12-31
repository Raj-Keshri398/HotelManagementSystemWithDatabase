<?php
// Agar booking_id URL me nahi hai to homepage bhej do
if (!isset($_GET['booking_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = htmlspecialchars($_GET['booking_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container text-center mt-5">
    <div class="card shadow p-5">
        <h1 class="text-success mb-3">🎉 Booking Successful!</h1>
        <p class="fs-5">Your Booking ID is:</p>
        <h2 class="fw-bold text-primary"><?php echo $booking_id; ?></h2>
        <p class="mt-4">Please save this ID for future reference.</p>
        <a href="index.php" class="btn btn-primary mt-3">Go to Home</a>
    </div>
</div>

</body>
</html>
