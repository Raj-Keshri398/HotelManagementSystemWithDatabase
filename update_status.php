<?php
session_start();
require_once 'connect.php';
if(!isset($_SESSION['email'])) die("Unauthorized");

if(isset($_POST['booking_id'], $_POST['status'])){
    $allowed = ['confirmed','cancelled'];
    $status = $_POST['status'];
    if(!in_array($status,$allowed)) die("Invalid status");

    $db = new Connect();
    $conn = $db->getDbHandle();

    // Get guest mobile number
    $stmt = $conn->prepare("SELECT name, mobile_no FROM online_booking WHERE booking_id=?");
    $stmt->bind_param("s", $_POST['booking_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows == 0) {
        echo "Guest not found";
        exit;
    }
    $guest = $res->fetch_assoc();
    $guestName = $guest['name'];
    $mobileNo  = $guest['mobile_no'];
    $stmt->close();

    // Update status
    $stmt = $conn->prepare("UPDATE online_booking SET status=? WHERE booking_id=?");
    $stmt->bind_param("ss", $status, $_POST['booking_id']);
    $stmt->execute();
    $stmt->close();

    // Send SMS (example using Fast2SMS API)
    $message = "Dear $guestName, your booking (ID: {$_POST['booking_id']}) has been $status.";
    
    // Replace below with your SMS API
    $apiKey = "YOUR_API_KEY"; 
    $senderId = "KSHTL"; // Example sender
    $url = "https://www.fast2sms.com/dev/bulkV2";

    $postData = [
        "route" => "v3",
        "sender_id" => $senderId,
        "message" => $message,
        "language" => "english",
        "flash" => 0,
        "numbers" => $mobileNo
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            "authorization: $apiKey",
            "Content-Type: application/json"
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    echo "success";
}
?>
