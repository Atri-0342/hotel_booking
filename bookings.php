<?php
session_start();
require 'includes/db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Cancel Booking (Using POST for security, preventing CSRF)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])){
    $cancel_id = $_POST['cancel_booking_id'];

    // Input validation (ensure ID is numeric)
    if (is_numeric($cancel_id)) {
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status='Cancelled' WHERE id=? AND user_id=?");
        $stmt->execute([$cancel_id, $user_id]);
        $_SESSION['message'] = "Booking cancelled successfully!";
    } else {
        $_SESSION['message'] = "Invalid booking ID for cancellation.";
    }

    // Redirect to prevent form resubmission
    header("Location: bookings.php");
    exit;
}

// Fetch bookings for the user (excluding cancelled) with property details
// Selecting all columns from 'bookings' (b.*) and specific columns from 'properties' (p)
$stmt = $pdo->prepare("
    SELECT b.*, p.title AS property_title, p.main_image_url
    FROM bookings b
    JOIN properties p ON b.property_id = p.id
    WHERE b.user_id = ? AND b.status != 'Cancelled'
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC); // Ensure associative array for JSON encoding
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings - QOZY</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --primary-color: #a999d1;
    --accent-color: #ffc0cb;
    --text-dark: #333;
    --bg-light: #f4f4f9;
    --btn-edit: #4CAF50;
    --btn-edit-hover: #45a049;
    --btn-cancel: #f44336;
    --btn-cancel-hover: #e53935;
    --btn-view: #3b82f6; /* Blue for View Details */
    --btn-view-hover: #2563eb;
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0; 
    background: var(--bg-light);
}

/* Header styles assumed to be in includes/header.php */
.main-header {
    background: #fff; padding: 10px 0; position: fixed; top:0; left:0; width:100%;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index:1000;
}

.page-wrapper { width:90%; max-width:1200px; margin:20px auto; }
h1 { text-align: center; color: var(--text-dark); margin-bottom: 25px;}

.bookings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(280px,1fr));
    gap: 20px;
}
.booking-card {
    background: #fff;
    border-radius: 12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s;
}
.booking-card:hover { transform: translateY(-5px); }
.booking-card img { width:100%; height:180px; object-fit:cover; cursor:pointer; }
.booking-content { padding:15px; flex-grow:1; }
.booking-content h3 { margin:0 0 10px 0; color: var(--text-dark); }
.booking-content p { margin:5px 0; color: #555; font-size:0.95rem; }
.status { font-weight:600; margin-top:5px; }
.booking-buttons { display:flex; flex-direction: column; gap:10px; margin-top:15px; }

/* General Button Styles */
.booking-buttons a,
.booking-buttons button {
    padding:10px;
    border-radius:6px;
    text-align:center;
    color:white;
    text-decoration:none;
    font-weight:600;
    cursor:pointer;
    border: none;
    flex: 1;
}

/* Styles for View button */
.btn-view { background: var(--btn-view); }
.btn-view:hover { background: var(--btn-view-hover); }

/* Styles for the Edit link */
.btn-edit { background: var(--btn-edit); }
.btn-edit:hover { background: var(--btn-edit-hover); }

.btn-cancel {
    width: 100%;
    padding: 13px 15px;
    background: var(--btn-cancel); 
    transition: background 0.2s;
}
.btn-cancel:hover { 
    background: var(--btn-cancel-hover); 
}

.message { text-align:center; color:green; margin-bottom:15px; font-weight:600; padding: 10px; background: #e6ffe6; border-radius: 6px;}


/* --- Modal Styles --- */
.modal {
    display: none; /* Hidden by default */
    position: fixed; 
    z-index: 2000; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}
.modal-content {
    background-color: #fefefe;
    margin: 10% auto; /* 10% from the top and centered */
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    width: 90%; 
    max-width: 500px; 
    position: relative;
    animation: fadeIn 0.3s;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.modal-content h2 { color: var(--primary-color); margin-top: 0; border-bottom: 2px solid var(--accent-color); padding-bottom: 10px; }
#modalDetails p { margin: 8px 0; font-size: 1rem; }
#modalDetails strong { display: inline-block; width: 150px; color: var(--text-dark); }

.close-button {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 20px;
}
.close-button:hover,
.close-button:focus {
    color: var(--text-dark);
    text-decoration: none;
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    justify-content: flex-end;
}
.modal-actions .btn-modal-action {
    padding: 10px 15px;
    border-radius: 6px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    border: none;
    cursor: pointer;
    flex: 1;
}
.modal-cancel-form {
    flex: 1;
}

/* Responsive adjustments */
@media (min-width: 600px) {
    .booking-buttons {
        flex-direction: row;
        gap: 10px;
    }
}
</style>
</head>
<body>

<?php include "includes/header.php" ?>

<main class="page-wrapper">
    <h1>My Bookings</h1>
    <?php if(isset($_SESSION['message'])): ?>
        <p class="message"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
    <?php endif; ?>

    <section class="bookings-grid">
        <?php if($bookings): ?>
            <?php foreach($bookings as $b): 
                // Prepare the full booking data as a JSON string for the JavaScript function
                // Using htmlspecialchars to correctly escape the JSON string for the onclick attribute
                $booking_json = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');
            ?>
                <article class="booking-card">
                    <a href="property_details.php?id=<?= $b['property_id'] ?>">
                        <img src="<?= htmlspecialchars($b['main_image_url']) ?>" onerror="this.onerror=null;this.src='https://placehold.co/180x180/E0E0E0/333?text=QOZY+Property';" alt="<?= htmlspecialchars($b['property_title']) ?>">
                    </a>
                    <div class="booking-content">
                        <h3><?= htmlspecialchars($b['property_title']) ?></h3>
                        <p><strong>Check-in:</strong> <?= $b['checkin_date'] ?></p>
                        <p><strong>Check-out:</strong> <?= $b['checkout_date'] ?></p>
                        <p class="status"><strong>Status:</strong> <?= $b['status'] ?></p>
                        
                        <div class="booking-buttons">
                            <!-- New View Details button -->
                            <button type="button" class="btn-view" onclick='showModal(<?= $booking_json; ?>)'>
                                View Details
                            </button>
                            
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No active bookings found.</p>
        <?php endif; ?>
    </section>
</main>

<!-- --- Booking Details Modal --- -->
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeModal()">&times;</span>
        <h2>Booking Details</h2>
        <div id="modalDetails">
            <!-- Details populated by JavaScript -->
        </div>
        
        <div class="modal-actions">
            <a id="modalEditBtn" href="#" class="btn-modal-action btn-edit">Edit Booking</a>
            <form method="POST" action="bookings.php" class="modal-cancel-form">
                <input type="hidden" name="cancel_booking_id" id="modalCancelId">
                <button type="submit" style="padding: 13px 15px;" class="btn-cancel btn-modal-action">Cancel Booking</button>
            </form>
        </div>
    </div>
</div>


<script>
    const modal = document.getElementById('bookingModal');
    const modalDetails = document.getElementById('modalDetails');
    const modalCancelId = document.getElementById('modalCancelId');
    const modalEditBtn = document.getElementById('modalEditBtn');
    const propertyTitleElement = document.getElementById('propertyTitle');

    /**
     * Helper function to format currency for display.
     */
    function formatCurrency(amount) {
        // Assuming INR (₹) based on the PHP code
        return `₹${parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
    }

    /**
     * Shows the modal and populates it with booking data.
     * @param {Object} booking - The booking object fetched from PHP.
     */
    function showModal(booking) {
        // Build the HTML content for the modal
        modalDetails.innerHTML = `
            <p><strong>Property:</strong> ${booking.property_title}</p>
            <p><strong>Booking ID:</strong> ${booking.id}</p>
            <p><strong>Check-in:</strong> ${booking.checkin_date}</p>
            <p><strong>Check-out:</strong> ${booking.checkout_date}</p>
            <p><strong>Total Guests:</strong> ${booking.guests}</p>
            <p><strong>Room Type ID:</strong> ${booking.room_id}</p>
            <p><strong>Rooms Count:</strong> ${booking.rooms_count}</p>
            <p><strong>Status:</strong> <span style="font-weight: 700; color: ${booking.status === 'Confirmed' ? '#4CAF50' : '#f44336'};">${booking.status}</span></p>
            <p><strong>Payment Type:</strong> ${booking.payment_type.charAt(0).toUpperCase() + booking.payment_type.slice(1)}</p>
            <p><strong>Total Price:</strong> ${formatCurrency(booking.total_price)}</p>
            <p><strong>Amount Paid:</strong> ${formatCurrency(booking.amount_paid)}</p>
            <p><strong>Booked On:</strong> ${new Date(booking.created_at).toLocaleDateString()}</p>
        `;

        // Update the form action and edit button link inside the modal
        modalCancelId.value = booking.id;
        modalEditBtn.href = `edit_booking.php?id=${booking.id}`;

        // Disable cancel button if already cancelled
        const cancelButton = document.querySelector('#bookingModal .btn-cancel');
        if (booking.status === 'Cancelled') {
            cancelButton.textContent = 'Already Cancelled';
            cancelButton.disabled = true;
            cancelButton.style.opacity = '0.7';
            modalEditBtn.style.display = 'none'; // Hide edit button if cancelled
        } else {
            cancelButton.textContent = 'Cancel Booking';
            cancelButton.disabled = false;
            cancelButton.style.opacity = '1';
            modalEditBtn.style.display = 'block';
        }

        // Show the modal
        modal.style.display = 'block';
    }

    /**
     * Hides the modal.
     */
    function closeModal() {
        modal.style.display = 'none';
    }

    // Close the modal if the user clicks anywhere outside of it
    window.onclick = function(event) {
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

</body>
</html>