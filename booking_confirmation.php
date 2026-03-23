<?php
session_start();

// Redirect to homepage if no booking exists
if(!isset($_SESSION['last_booking'])) {
    header("Location: index.php");
    exit;
}

$booking = $_SESSION['last_booking'];

// Unset the booking_done flag to prevent revisiting checkout
unset($_SESSION['booking_done']);

// --- Dynamic Status Variables ---
$status = htmlspecialchars($booking['status'] ?? 'Pending');
$is_confirmed = strtolower($status) === 'confirmed';
$icon_class = $is_confirmed ? 'fas fa-check-circle' : 'fas fa-hourglass-half';
$icon_color = $is_confirmed ? 'var(--status-green)' : 'var(--status-pending)';
$main_title = $is_confirmed ? 'Booking Confirmed!' : 'Booking Pending Review';
$message_text = $is_confirmed 
    ? "Your reservation is confirmed. We look forward to hosting you!" 
    : "Your booking is currently pending. We will notify you once payment is fully processed or approved.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Confirmation - QOZY</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary-purple: #a999d1;
    --accent-pink: #ff69b4; /* Used for primary actions */
    --accent-blue: #a0e6ff;
    --text-dark: #1a1a1a;
    --text-light: #555;
    --background-light: #f4f4f9;
    --container-bg: #ffffff;
    --border-color: #eee;
    --btn-primary-bg: var(--accent-pink);
    --btn-primary-hover: #e058a2;
    --status-green: #28a745;
    --status-pending: #ff8c00; /* New color for pending status */
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.08);
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--background-light);
    margin: 0;
}

/* Header (Assuming includes/header.php handles this. Minimal styling for context) */
.main-header { 
    background-color: var(--container-bg); 
    padding: 15px 0; 
    box-shadow: var(--shadow-soft); 
    position: fixed; 
    width: 100%; 
    top: 0; 
    z-index: 1000;
}
.container { width: 90%; max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }

/* Page Wrapper */
.page-wrapper {
    display: flex;
    justify-content: center;
    align-items: flex-start; /* Aligned to the top */
    min-height: calc(100vh - 80px);
    padding: 40px 20px;
    box-sizing: border-box;
}

/* Confirmation Card */
.confirmation-container {
    background-color: var(--container-bg);
    padding: 30px;
    border-radius: 16px;
    box-shadow: var(--shadow-soft);
    max-width: 700px;
    width: 100%;
    text-align: center;
}

/* Header/Status Section */
.status-header {
    padding: 20px 0 30px;
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 30px;
}
.status-header i {
    font-size: 3.5rem;
    color: <?= $icon_color ?>; /* Use PHP variable for color */
    margin-bottom: 15px;
    animation: bounceIn 0.8s ease-out;
}
h1 {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
}
.status-message {
    font-size: 1.05rem;
    color: var(--text-light);
}

/* Booking Details Grid */
.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px 40px;
    text-align: left;
    margin-bottom: 30px;
}
.detail-item {
    padding: 10px 0;
    border-bottom: 1px dashed var(--border-color);
}
.detail-item:last-child { border-bottom: none; }
.detail-item span {
    display: block;
    font-size: 0.9rem;
    color: var(--text-light);
    font-weight: 400;
    margin-bottom: 2px;
}
.detail-item strong {
    font-size: 1.1rem;
    color: var(--text-dark);
    font-weight: 600;
}

/* Price and Final Status */
.price-final {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: var(--background-light);
    border-radius: 8px;
    margin-bottom: 30px;
}
.price-final p { margin: 0; }
.price-final .label { font-size: 1.1rem; font-weight: 600; }
.price-final .amount { font-size: 1.8rem; color: var(--accent-pink); font-weight: 700; }
.status-badge {
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
    color: white;
    background-color: <?= $icon_color ?>;
}

/* Footer / Action */
.action-area {
    padding-top: 20px;
}
.btn-home {
    background-color: var(--btn-primary-bg);
    color: white;
    padding: 12px 25px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
    transition: background-color 0.3s ease;
    min-width: 200px;
}
.btn-home:hover { 
    background-color: var(--btn-primary-hover); 
}

/* Redirect Timer */
.redirect-timer {
    margin-top: 15px;
    font-size: 0.9rem;
    color: var(--text-light);
}
.redirect-timer strong {
    color: var(--accent-pink);
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .confirmation-container {
        padding: 20px;
    }
    .details-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .price-final {
        flex-direction: column;
        align-items: flex-start;
    }
    .price-final .amount {
        margin-top: 5px;
    }
    .status-badge {
        align-self: flex-end;
    }
}

/* Animations */
@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.1); opacity: 1; }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); }
}
</style>
</head>
<body>
<?php include "includes/header.php" ?>
<main class="page-wrapper">
    <div class="confirmation-container">
        
        <div class="status-header">
            <i class="<?= $icon_class ?>"></i>
            <h1><?= $main_title ?></h1>
            <p class="status-message"><?= $message_text ?></p>
        </div>

        <h2>Booking Details</h2>
        <div class="details-grid">
            
            <div class="detail-item">
                <span>Property</span>
                <strong><?= htmlspecialchars($booking['property']) ?></strong>
            </div>
            
            <div class="detail-item">
                <span>Room Type</span>
                <strong><?= htmlspecialchars($booking['room']) ?></strong>
            </div>

            <div class="detail-item">
                <span>Check-in Date</span>
                <strong><?= htmlspecialchars($booking['checkin']) ?></strong>
            </div>

            <div class="detail-item">
                <span>Check-out Date</span>
                <strong><?= htmlspecialchars($booking['checkout']) ?></strong>
            </div>
            
            <div class="detail-item">
                <span>Guests / Rooms</span>
                <strong><?= htmlspecialchars($booking['guests']) ?> Guests / <?= htmlspecialchars($booking['number_of_rooms'] ?? 1) ?> Rooms</strong>
            </div>
            
            <div class="detail-item">
                <span>Nights Stay</span>
                <strong><?= htmlspecialchars($booking['nights']) ?> Nights</strong>
            </div>
            
        </div>
        
        <div class="price-final">
            <div>
                <p class="label">Total Amount Paid</p>
                <p class="amount">₹<?= htmlspecialchars(number_format($booking['amount_paid'], 2)) ?></p>
            </div>
            <span class="status-badge"><?= $status ?></span>
        </div>

        <div class="action-area">
            <a href="index.php" class="btn-home">Go to Homepage</a>
            <p class="redirect-timer">Redirecting in <strong id="countdown">10</strong> seconds...</p>
        </div>
        
    </div>
</main>

<script>
// --- Timer for Automatic Redirect ---
let countdown = 10;
const countdownElement = document.getElementById('countdown');

const timer = setInterval(() => {
    countdown--;
    countdownElement.textContent = countdown;
    
    if (countdown <= 0) {
        clearInterval(timer);
        window.location.replace("index.php");
    }
}, 1000);

// --- Prevent user from going back to checkout page (existing logic) ---
(function() {
    history.replaceState(null, null, location.href);
    history.pushState(null, null, location.href);

    window.addEventListener('popstate', function() {
        // Clear the countdown interval if user manually tries to navigate back
        clearInterval(timer); 
        location.replace("index.php");
    });
})();
</script>
</body>
</html>