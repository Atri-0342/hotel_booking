<?php
session_name("ADMIN_SESSION");
session_start();

// Redirect if admin is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once "../includes/db_connect.php";

// Handle Approve or Cancel actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE bookings SET status='Confirmed' WHERE id=?");
        $stmt->execute([$id]);
    } elseif ($action === 'cancel') {
        $stmt = $pdo->prepare("UPDATE bookings SET status='Cancelled' WHERE id=?");
        $stmt->execute([$id]);
    }

    header("Location: bookings.php");
    exit();
}

// Fetch recent 6 bookings with user & property info
try {
    $stmt = $pdo->query("
        SELECT b.id, u.full_name as user, u.email, u.phone_number as phone,
               p.title as property, p.city as property_city, b.checkin_date, b.checkout_date,
               b.guests, b.total_price, b.status
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN properties p ON b.property_id = p.id
        ORDER BY b.created_at DESC
        LIMIT 6
    ");
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching bookings: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Booking Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --primary-purple: #a999d1;
    --accent-blue: #a0e6ff;
    --text-dark: #333;
    --background-light: #f4f4f9;
    --border-color: #ddd;
}
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--background-light); margin:0; display:flex; min-height:100vh; }

.admin-main-content { flex-grow:1; padding:30px; overflow-y:auto; box-sizing:border-box;}
.admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
.admin-header h1 { margin:0; color: var(--text-dark); }
.admin-header .logout-btn { background-color: var(--primary-purple); color:white; padding:8px 15px; text-decoration:none; border-radius:6px; font-weight:600; transition:0.3s; }
.admin-header .logout-btn:hover { background-color:#907fbf; }
.admin-table { width:100%; border-collapse:collapse; margin-top:15px; background-color:#fff; box-shadow:0 4px 15px rgba(0,0,0,0.07); border-radius:8px; overflow:hidden; }
.admin-table th, .admin-table td { padding:12px 15px; text-align:left; border-bottom:1px solid var(--border-color); }
.admin-table th { background-color: var(--background-light); font-weight:600; color: var(--text-dark); }
.admin-table tbody tr:hover { background-color:#f8f9fa; }

/* --- FIX START --- */
.admin-table tbody tr td:last-child {
    /* Make the action cell a flex container to manage buttons */
    display: flex;
    flex-wrap: wrap; /* Allow buttons to wrap to the next line if space is tight */
    gap: 5px; /* Add gap for spacing between buttons */
}

.action-btn { 
    padding:5px 10px; 
    border:none; 
    border-radius:4px; 
    color:white; 
    cursor:pointer; 
    /* Remove horizontal margin, using gap on parent instead */
    /* margin-right:5px; */ 
    font-size:0.9em; 
    /* Ensure buttons are displayed as flex items */
    display: inline-flex; 
    align-items: center;
    gap: 4px; /* Space between icon and text (if text is added) */
    text-decoration: none; /* Ensure links look consistent */
}
/* --- FIX END --- */

.btn-view { background-color:#17a2b8; }
.btn-approve { background-color:#28a745; }
.btn-cancel { background-color:#dc3545; }
.search-input { width:100%; padding:10px; margin-bottom:10px; border:1px solid var(--border-color); border-radius:6px; }

/* Modal */
.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5); }
.modal-content { background-color:#fff; margin:10% auto; padding:20px; border-radius:10px; width:90%; max-width:500px; position:relative; }
.close-modal { position:absolute; top:10px; right:15px; font-size:1.5rem; cursor:pointer; color:#333; }
.modal h2 { margin-top:0; margin-bottom:15px; color:var(--primary-purple); }
.modal p { margin:5px 0; }
.status-badge { padding:3px 8px; border-radius:6px; color:white; font-weight:600; font-size:0.9em; }
.status-Pending { background:#ffc107; }
.status-Confirmed { background:#28a745; }
.status-Cancelled { background:#dc3545; }
@media (max-width: 900px) {
    /* 1. Main Content Padding */
    .admin-main-content { padding: 15px; }
    
    /* 2. Header Stacking */
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    /* 3. Table Structure (Card View) */
    .table-container {
        overflow-x: hidden; /* Disable scroll for card view */
        box-shadow: none; 
    }

    .admin-table {
        min-width: unset; 
        border: 0;
    }

    /* Hiding table header for card view */
    .admin-table thead {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        padding: 0;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }

    /* Styling for each row/card */
    .admin-table tr {
        border: 1px solid var(--border-color);
        display: block;
        margin-bottom: 15px; 
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        background-color: #fff;
    }

    /* Styling for each cell/line item */
    .admin-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid var(--border-color);
        text-align: right;
    }

    /* Creating the column label using data-label attribute */
    .admin-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-dark);
        text-align: left;
        flex-basis: 40%;
    }
    
    /* Action cell: stack buttons vertically and space them */
    .admin-table tr td:last-child {
        border-bottom: none;
        flex-direction: column; 
        align-items: stretch;
        gap: 8px; /* Vertical space between the stacked buttons */
        padding-top: 10px;
        /* Re-adding display:flex and flex-wrap:wrap from the desktop fix, 
           but overriding direction to column */
        display: flex; 
        flex-wrap: nowrap;
    }

    /* Action Button Fix (Stacking) */
    .action-btn {
        display: flex;
        width: 100%; /* Make buttons full width */
        text-align: center;
        justify-content: center;
        margin: 0 !important; /* Crucial: clear all margins and rely on parent gap */
    }
}
</style>
</head>
<body>

<?php include "nav.php"?>

<main class="admin-main-content">
<header class="admin-header">
    <h1>Booking Management</h1>
    <a href="admin_logout.php" class="logout-btn">Logout</a>
</header>

<input type="text" class="search-input" placeholder="Search bookings..." id="bookingSearch">

<table class="admin-table" id="bookingsTable">
    <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Property</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if(count($bookings) > 0): ?>
        <?php foreach($bookings as $b): ?>
            <tr>
                <td><?= $b['id'] ?></td>
                <td><?= htmlspecialchars($b['user']) ?></td>
                <td><?= htmlspecialchars($b['property']) ?></td>
                <td><?= $b['checkin_date'] ?></td>
                <td><?= $b['checkout_date'] ?></td>
                <td><span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
                <td>
                    <button class="action-btn btn-view"
                        data-user="<?= htmlspecialchars($b['user']) ?>"
                        data-email="<?= htmlspecialchars($b['email']) ?>"
                        data-phone="<?= htmlspecialchars($b['phone']) ?>"
                        data-property="<?= htmlspecialchars($b['property']) ?>"
                        data-city="<?= htmlspecialchars($b['property_city']) ?>"
                        data-checkin="<?= $b['checkin_date'] ?>"
                        data-checkout="<?= $b['checkout_date'] ?>"
                        data-guests="<?= $b['guests'] ?>"
                        data-price="<?= $b['total_price'] ?>"
                        data-status="<?= $b['status'] ?>"
                    ><i class="fas fa-eye"></i></button>

                    <?php if($b['status'] === 'Pending'): ?>
                        <a href="?action=approve&id=<?= $b['id'] ?>" class="action-btn btn-approve" onclick="return confirm('Approve this booking?');"><i class="fas fa-check"></i></a>
                        <a href="?action=cancel&id=<?= $b['id'] ?>" class="action-btn btn-cancel" onclick="return confirm('Cancel this booking?');"><i class="fas fa-times"></i></a>
                    <?php endif; ?>

                    <?php if($b['status'] === 'Confirmed'): ?>
                        <a href="?action=cancel&id=<?= $b['id'] ?>" class="action-btn btn-cancel" onclick="return confirm('Cancel this booking?');"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7">No bookings found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</main>

<div class="modal" id="bookingModal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Booking Details</h2>
        <p><strong>User:</strong> <span id="modalUser"></span></p>
        <p><strong>Email:</strong> <span id="modalEmail"></span></p>
        <p><strong>Phone:</strong> <span id="modalPhone"></span></p>
        <p><strong>Property:</strong> <span id="modalProperty"></span></p>
        <p><strong>City:</strong> <span id="modalCity"></span></p>
        <p><strong>Check-in:</strong> <span id="modalCheckin"></span></p>
        <p><strong>Check-out:</strong> <span id="modalCheckout"></span></p>
        <p><strong>Guests:</strong> <span id="modalGuests"></span></p>
        <p><strong>Total Price:</strong> ₹<span id="modalPrice"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
    </div>
</div>

<script>
// Search bookings
document.getElementById('bookingSearch').addEventListener('keyup', function() {
    const value = this.value.toLowerCase();
    document.querySelectorAll('#bookingsTable tbody tr').forEach(row => {
        row.style.display = Array.from(row.cells).some(cell => row.textContent.toLowerCase().includes(value)) ? '' : 'none';
    });
});

// Booking Modal
const modal = document.getElementById('bookingModal');
const modalFields = {
    user: document.getElementById('modalUser'),
    email: document.getElementById('modalEmail'),
    phone: document.getElementById('modalPhone'),
    property: document.getElementById('modalProperty'),
    city: document.getElementById('modalCity'),
    checkin: document.getElementById('modalCheckin'),
    checkout: document.getElementById('modalCheckout'),
    guests: document.getElementById('modalGuests'),
    price: document.getElementById('modalPrice'),
    status: document.getElementById('modalStatus')
};

document.querySelectorAll('.btn-view').forEach(btn => {
    btn.addEventListener('click', () => {
        modalFields.user.textContent = btn.dataset.user;
        modalFields.email.textContent = btn.dataset.email;
        modalFields.phone.textContent = btn.dataset.phone;
        modalFields.property.textContent = btn.dataset.property;
        modalFields.city.textContent = btn.dataset.city;
        modalFields.checkin.textContent = btn.dataset.checkin;
        modalFields.checkout.textContent = btn.dataset.checkout;
        modalFields.guests.textContent = btn.dataset.guests;
        modalFields.price.textContent = btn.dataset.price;
        // Also update the status badge class in the modal
        const statusText = btn.dataset.status;
        modalFields.status.textContent = statusText;
        modalFields.status.className = 'status-badge status-' + statusText;

        modal.style.display = 'block';
    });
});

document.querySelector('.close-modal').addEventListener('click', () => modal.style.display='none');
window.addEventListener('click', e => { if(e.target==modal) modal.style.display='none'; });
</script>
</body>
</html>