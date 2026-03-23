<?php
session_start();
require 'includes/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['id'] ?? null;
$message = '';

// Fetch booking details
if(!$booking_id){
    die("Booking ID not specified.");
}

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id=? AND user_id=?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch();

if(!$booking){
    die("Booking not found or you don't have permission to edit this booking.");
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $guests = $_POST['guests'] ?? '';

    // Simple validation
    if( !$guests){
        $message = "All fields are required!";
    
    } else {
        $stmt = $pdo->prepare("UPDATE bookings SET guests=? WHERE id=? AND user_id=?");
        if($stmt->execute([$guests, $booking_id, $user_id])){
            $pdo->prepare("INSERT INTO activity_logs (user_id, activity, ip_address) VALUES (?, ?, ?)")
    ->execute([$user_id, "Cancelled booking ID: $booking_id", $_SERVER['REMOTE_ADDR']]);
            $_SESSION['message'] = "Booking updated successfully!";
            header("Location: bookings.php");
            exit;
        } else {
            $message = "Failed to update booking. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Booking - QOZY</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f4f9; padding:50px; text-align:center; }
.form-container { background:white; padding:30px; border-radius:10px; max-width:400px; margin:auto; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
input { width:100%; padding:10px; margin-bottom:15px; border-radius:5px; border:1px solid #ddd; }
button { padding:12px; width:100%; background:#a999d1; color:white; border:none; border-radius:5px; cursor:pointer; font-weight:600; }
button:hover { background:#8c82c2; }
.message { margin-bottom:15px; color:red; font-weight:600; }
a.back-link { display:block; margin-top:15px; text-decoration:none; color:#333; }
a.back-link:hover { text-decoration:underline; }
</style>
</head>
<body>

<div class="form-container">

    <?php if($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post">

        <label>Guests</label>
        <input type="number" name="guests" value="<?= htmlspecialchars($booking['guests']) ?>" min="1" required>

        <button type="submit">Update Booking</button>
    </form>

    <a class="back-link" href="bookings.php">Back to My Bookings</a>
</div>

</body>
</html>
