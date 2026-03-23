<?php
session_start();
require 'includes/db_connect.php';

// Admin email
$admin_email = "raja2002chakraborty@gmail.com";

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Guest');

// Handle POST booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $property_id = (int)($_POST['property_id'] ?? 0);
    $room_id = (int)($_POST['room_id'] ?? 0);
    $room_price = floatval($_POST['room_price'] ?? 0);
    $number_of_rooms = max(1,(int)($_POST['number_of_rooms'] ?? 1));
    $checkin = $_POST['checkin'] ?? date('Y-m-d');
    $checkout = $_POST['checkout'] ?? date('Y-m-d', strtotime($checkin . ' +1 day'));
    $guests = max(1,(int)($_POST['guests'] ?? 1));
    $payment_type = $_POST['payment_type'] ?? 'full'; // full or partial
    $property_title = htmlspecialchars($_POST['property_title'] ?? 'Property');
    $room_name = htmlspecialchars($_POST['room_name'] ?? 'Room');

    if ($property_id <= 0 || $room_id <= 0 || $room_price <= 0) die("Invalid booking details.");

    $nights = max(round((strtotime($checkout) - strtotime($checkin)) / (60*60*24)), 1);
    $totalPrice = $room_price * $number_of_rooms * $nights;
    $serviceFee = round($totalPrice * 0.05,2);
    $taxes = round($totalPrice * 0.12,2);
    $finalAmount = $totalPrice + $serviceFee + $taxes;

    // Determine partial/full payment
    $deposit = ($payment_type === 'partial') ? 1000 : $finalAmount;
    $status = ($payment_type === 'full') ? 'Confirmed' : 'Pending';

    // Insert booking
    $stmt = $pdo->prepare("
        INSERT INTO bookings 
        (user_id, property_id, room_id, checkin_date, checkout_date, guests, rooms_count, total_price, amount_paid, payment_type, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$user_id, $property_id, $room_id, $checkin, $checkout, $guests, $number_of_rooms, $finalAmount, $deposit, $payment_type, $status])) {

        $booking_id = $pdo->lastInsertId();
        
        $log = $pdo->prepare("
    INSERT INTO activity_logs (user_id, activity, ip_address)
    VALUES (?, ?, ?)
");

$activity = "Booked Property: $property_title, Room: $room_name (Booking ID: $booking_id)";
$ip = $_SERVER['REMOTE_ADDR'];

$log->execute([$user_id, $activity, $ip]);

        $_SESSION['last_booking'] = [
            'booking_id' => $booking_id,
            'property' => $property_title,
            'room' => $room_name,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'guests' => $guests,
            'nights' => $nights,
            'number_of_rooms' => $number_of_rooms,
            'total_price' => $finalAmount,
            'amount_paid' => $deposit,
            'status' => $status,
            'payment_type' => $payment_type
        ];

        $_SESSION['booking_done'] = true;

        // Send email to admin
        $subject = "New Booking Received - QOZY";
        $message = "
            <h2>Booking Details</h2>
            <p><strong>User:</strong> {$user_name}</p>
            <p><strong>Property:</strong> {$property_title}</p>
            <p><strong>Room:</strong> {$room_name}</p>
            <p><strong>Number of Rooms:</strong> {$number_of_rooms}</p>
            <p><strong>Check-in:</strong> {$checkin}</p>
            <p><strong>Check-out:</strong> {$checkout}</p>
            <p><strong>Guests:</strong> {$guests}</p>
            <p><strong>Nights:</strong> {$nights}</p>
            <p><strong>Total Price:</strong> ₹" . number_format($finalAmount,2) . "</p>
            <p><strong>Amount Paid:</strong> ₹" . number_format($deposit,2) . "</p>
            <p><strong>Status:</strong> {$status}</p>
            <p><strong>Payment Type:</strong> {$payment_type}</p>
        ";
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@qozy.com\r\n";
        @mail($admin_email, $subject, $message, $headers);

        header("Location: booking_confirmation.php");
        exit;
    } else {
        die("Booking failed. Try again.");
    }
}

// GET request: Fetch property info
$property_id = (int)($_GET['property_id'] ?? 0);
$room_id = (int)($_GET['room_id'] ?? 0);
$room_price = floatval($_GET['room_price'] ?? 0);
$room_name = htmlspecialchars($_GET['room_name'] ?? 'Default Room');
$number_of_rooms = max(1, (int)($_GET['number_of_rooms'] ?? 1));

if ($property_id <= 0) die("Invalid property.");

$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();
if (!$property) die("Property not found.");

$checkin = $_GET['checkin'] ?? date('Y-m-d');
$checkout = $_GET['checkout'] ?? date('Y-m-d', strtotime($checkin . ' +1 day'));
$guests = max(1, (int)($_GET['guests'] ?? 1));
$nights = max(round((strtotime($checkout) - strtotime($checkin)) / (60*60*24)), 1);

$basePrice = $room_price * $number_of_rooms * $nights;
$serviceFee = round($basePrice * 0.05,2);
$taxes = round($basePrice * 0.12,2);
$totalPrice = $basePrice + $serviceFee + $taxes;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - QOZY</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; background:#f4f4f9; margin:0; }
.page-wrapper { width:90%; max-width:1000px; margin:30px auto; }
h1 { text-align:center; margin-bottom:30px; color:#333; }
.checkout-layout { display:flex; gap:40px; flex-wrap:wrap; }
.booking-summary, .payment-options { background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05); flex:1; }
.booking-summary h2, .payment-options h2 { font-size:1.4rem; margin-top:0; margin-bottom:20px; }
.summary-property { display:flex; gap:15px; align-items:center; margin-bottom:20px; }
.summary-property img { width:100px; height:80px; object-fit:cover; border-radius:8px; background:#f4f4f9; }
.price-breakdown p { display:flex; justify-content:space-between; margin:10px 0; font-size:0.95rem; color:#333; }
.price-breakdown hr { border-top:dashed; margin:15px 0; }
.cancellation-policy { font-size:0.9rem; color:#28a745; margin-top:15px; }
.payment-method { border:1px solid #ddd; border-radius:8px; padding:15px; margin-bottom:15px; display:flex; align-items:flex-start; cursor:pointer; }
.payment-method:hover { border-color:#a999d1; background:#fafafa; }
.payment-method input[type="radio"] { margin-right:15px; margin-top:5px; accent-color:#a999d1; }
.payment-method label { cursor:pointer; line-height:1.4; font-size:0.95rem; color:#333; }
.payment-select { width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; margin-top:10px; margin-bottom:20px; font-size:1rem; }
.btn-confirm-pay { width:100%; padding:14px; background:#ff69b4; color:white; font-weight:700; cursor:pointer; border-radius:8px; border:none; transition:0.3s; }
.btn-confirm-pay:hover { background:#e058a2; }
@media (min-width:769px){ 
    .page-wrapper {
        margin: 30px auto;
        padding: 0;
    }
    h1 {
        font-size: 2.2rem; /* Restore desktop size */
        margin-bottom: 30px;
    }
    .checkout-layout{
        flex-direction:row; /* Switch back to two columns */
        gap: 40px;
    }
    .booking-summary, .payment-options {
        padding: 25px; /* Restore desktop padding */
        flex: 1;
    }
    .booking-summary h2, .payment-options h2 {
        font-size: 1.4rem; /* Restore desktop size */
        margin-bottom: 20px;
        padding-bottom: 10px;
    }
    .summary-property img {
        width: 100px; /* Restore desktop image size */
        height: 80px;
        border-radius: 8px;
    }
    .summary-property div h3 {
        font-size: 1.1rem;
    }
    .summary-property div p {
        font-size: 0.9rem;
    }
    .price-breakdown p {
        font-size: 0.95rem;
    }
    .payment-method {
        padding: 15px;
        margin-bottom: 15px;
    }
    .payment-method input[type="radio"] {
        margin-right: 15px;
    }
    .payment-method label {
        font-size: 0.95rem;
    }
}
</style>
</head>
<body>
<header><?php include "includes/header.php"; ?></header>
<main class="page-wrapper checkout-container">
<h1>Confirm and Pay</h1>
<div class="checkout-layout">
    <section class="booking-summary">
        <h2>Your Trip</h2>
        <div class="summary-property">
            <img src="<?= htmlspecialchars($property['main_image_url'] ?? 'assets/images/placeholder.jpg') ?>" alt="Property">
            <div>
                <h3><?= htmlspecialchars($property['title']) ?></h3>
                <p>Room: <?= $room_name ?></p>
                <p>Number of Rooms: <?= $number_of_rooms ?></p>
                <p>Dates: <?= $checkin ?> to <?= $checkout ?></p>
                <p>Guests: <?= $guests ?></p>
            </div>
        </div>
        <hr>
        <h2>Price Details</h2>
        <div class="price-breakdown">
            <p><span>₹<?= number_format($room_price) ?> x <?= $nights ?> nights x <?= $number_of_rooms ?> rooms</span> <span>₹<?= number_format($basePrice) ?></span></p>
            <p><span>Service Fee</span> <span>₹<?= number_format($serviceFee) ?></span></p>
            <p><span>Taxes (12%)</span> <span>₹<?= number_format($taxes) ?></span></p>
            <hr>
            <p><strong>Total Amount</strong> <strong>₹<?= number_format($totalPrice) ?></strong></p>
        </div>
        <p class="cancellation-policy"><i class="fas fa-check-circle"></i> Free cancellation within 48 hours</p>
    </section>

    <section class="payment-options">
        <h2>Choose Payment Method</h2>
        <form method="post" id="booking-form">
            <input type="hidden" name="property_id" value="<?= $property_id ?>">
            <input type="hidden" name="property_title" value="<?= htmlspecialchars($property['title']) ?>">
            <input type="hidden" name="room_name" value="<?= $room_name ?>">
            <input type="hidden" name="room_id" value="<?= $room_id ?>">
            <input type="hidden" name="checkin" value="<?= $checkin ?>">
            <input type="hidden" name="checkout" value="<?= $checkout ?>">
            <input type="hidden" name="guests" value="<?= $guests ?>">
            <input type="hidden" name="room_price" value="<?= $room_price ?>">
            <input type="hidden" name="number_of_rooms" value="<?= $number_of_rooms ?>">

            <div class="payment-method">
                <input type="radio" id="pay-full" name="payment_type" value="full" checked>
                <label for="pay-full"><strong>Pay in Full</strong><br>Pay the total now and confirm instantly.</label>
            </div>
            <div class="payment-method">
                <input type="radio" id="pay-partial" name="payment_type" value="partial">
                <label for="pay-partial"><strong>Book Now, Pay ₹1000</strong><br>Pay a small deposit now and the rest at check-in.</label>
            </div>
            <br><br>
            <hr>
            <br><br><br>

            <button type="button" class="btn-confirm-pay" id="confirm-payment-btn">Confirm and Pay (Simulated)</button>
        </form>
    </section>
</div>
</main>

<script>
document.getElementById('confirm-payment-btn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Processing Payment...';
    setTimeout(() => {
            document.getElementById('booking-form').submit();
    }, 1200);
});
</script>
</body>
</html>
