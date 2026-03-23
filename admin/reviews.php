<?php
session_name("ADMIN_SESSION");
session_start();
require_once "../includes/db_connect.php";

// Redirect if admin not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle approve, reject, delete actions
if (isset($_GET['action'], $_GET['id'])) {
    $review_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE reviews SET status='Approved' WHERE id=?");
        $stmt->execute([$review_id]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE reviews SET status='Rejected' WHERE id=?");
        $stmt->execute([$review_id]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id=?");
        $stmt->execute([$review_id]);
    }

    header("Location: reviews.php");
    exit();
}

// Fetch all reviews with property title
$stmt = $pdo->query("
    SELECT r.id, r.name, r.rating, r.comment, r.status, r.review_date, p.title AS property
    FROM reviews r
    LEFT JOIN properties p ON r.property_id = p.id
    ORDER BY r.review_date DESC
");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Review Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>

:root {
    --primary-purple: #a999d1;
    --text-dark: #333;
    --background-light: #f4f4f9;
    --border-color: #ddd;
    --sidebar-bg: #343a40;
    --sidebar-link: #adb5bd;
    --sidebar-link-hover: #ffffff;
}
/* Base Layout: Always side-by-side (row direction) */
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    background-color: var(--background-light); 
    margin:0; 
    display:flex; /* Essential for side-by-side layout */
    min-height:100vh; 
}

/* Main Content and Header Styles */
.admin-main-content { 
    flex-grow:1; 
    padding:30px; 
    overflow-y:auto; 
    box-sizing:border-box;
}
.admin-header { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    margin-bottom:30px; 
    flex-wrap: wrap;
}
.admin-header h1 { margin:0; color: var(--text-dark); }
.admin-header .logout-btn { 
    background-color: var(--primary-purple); 
    color:white; 
    padding:8px 15px; 
    text-decoration:none; 
    border-radius:6px; 
    font-weight:600; 
    transition: background-color 0.3s; 
}
.admin-header .logout-btn:hover { background-color: #907fbf; }

/* Table Styles (Desktop/Intermediate) */
.admin-table { 
    width:100%; 
    min-width: 900px; /* Ensures table forces scroll if necessary */
    border-collapse:collapse; 
    margin-top:20px; 
    background-color:white; 
    box-shadow:0 4px 15px rgba(0,0,0,0.07); 
    border-radius:8px; 
    overflow-x: auto; /* Enable horizontal scrolling */
    overflow-y: hidden;
}
.admin-table th, .admin-table td { 
    padding:12px 15px; 
    text-align:left; 
    border-bottom:1px solid var(--border-color); 
}
.admin-table th { 
    background-color: var(--background-light); 
    font-weight:600; 
    color: var(--text-dark); 
}
.admin-table tbody tr:hover { 
    background-color:#f8f9fa; 
}

/* Action Button Container FIX (Prevents overlap on desktop/tablet) */
.admin-table tbody tr td:last-child {
    display: flex;
    flex-wrap: wrap; /* Allows buttons to wrap to a new line */
    gap: 5px; /* Spacing between buttons */
}

/* Action Button Styles */
.action-btn { 
    margin-bottom: 2px; 
    padding:5px 10px; 
    border:none; 
    border-radius:4px; 
    color:white; 
    cursor:pointer; 
    font-size:0.9em; 
    text-decoration:none; 
    display: inline-flex;
    align-items: center;
    gap: 4px; 
    white-space: nowrap;
}
.btn-approve { background-color:#28a745; }
.btn-reject { background-color:#dc3545; }
.btn-delete { background-color:#6c757d; }
.status-Pending { color: #ffc107; font-weight:600; }
.status-Approved { color: #28a745; font-weight:600; }
.status-Rejected { color: #dc3545; font-weight:600; }

/* ------------------------------------- */
/* RESPONSIVE DESIGN (Mobile Card View Activation) */
/* ------------------------------------- */

@media (max-width: 768px) { 
    /* **CRITICAL FIX:** Body layout is NOT changed to column, 
       so content always stays beside the nav/sidebar. */
       
    .admin-main-content { padding: 15px; }
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    /* Hide the 900px body column rule if it exists */
    @media (max-width:900px) { 
        .admin-sidebar { flex:0 0 auto; width:100%; }
        /* The body flex-direction:column rule is explicitly avoided here */
    }

    /* Table Card View Overrides */
    .admin-table {
        min-width: unset; 
        border: 0;
        overflow-x: hidden;
        box-shadow: none;
        margin-top: 15px;
    }

    /* Hiding table header */
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
    .admin-table tr {
        border: 1px solid var(--border-color);
        display: block;
        margin-bottom: 15px; 
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        background-color: #fff;
    }
    .admin-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid var(--border-color);
        text-align: right;
    }
    /* Mobile labels from data-label attributes */
    .admin-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-dark);
        text-align: left;
        flex-basis: 40%;
    }

    /* Action Cell: Force vertical stack on mobile */
    .admin-table tr td:last-child {
        border-bottom: none;
        flex-direction: column; 
        align-items: stretch;
        gap: 8px; 
        padding-top: 10px;
        flex-wrap: nowrap;
    }

    /* Action Button Fix (Stacking) */
    .action-btn {
        display: flex;
        width: 100%; 
        text-align: center;
        justify-content: center;
        margin: 0 !important; /* Clear existing margins */
    }
}

</style>
</head>
<body>
<?php include "nav.php"?>



<main class="admin-main-content">
<header class="admin-header">
    <h1>Review Management</h1>
    <a href="admin_logout.php" class="logout-btn">Logout</a>
</header>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>User Name</th>
            <th>Property</th>
            <th>Rating</th>
            <th>Comment</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if(count($reviews) > 0): ?>
            <?php foreach($reviews as $rev): ?>
            <tr>
                <td><?= $rev['id'] ?></td>
                <td><?= htmlspecialchars($rev['name']) ?></td>
                <td><?= htmlspecialchars($rev['property']) ?></td>
                <td><?= $rev['rating'] ?> <i class="fas fa-star" style="color:#ffc107;"></i></td>
                <td><?= htmlspecialchars(substr($rev['comment'],0,50)) ?><?= strlen($rev['comment'])>50?'...':'' ?></td>
                <td class="status-<?= $rev['status'] ?>"><?= $rev['status'] ?></td>
                <td>
                    <?php if($rev['status'] === 'Pending'): ?>
                        <a href="?action=approve&id=<?= $rev['id'] ?>" class="action-btn btn-approve">Approve</a>
                        <a href="?action=reject&id=<?= $rev['id'] ?>" class="action-btn btn-reject">Reject</a>
                    <?php endif; ?>
                    <a href="?action=delete&id=<?= $rev['id'] ?>" onclick="return confirm('Delete this review?');" class="action-btn btn-delete">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">No reviews found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</main>
</body>
</html>
