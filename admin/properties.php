<?php
session_name("ADMIN_SESSION");
session_start();
require_once "../includes/db_connect.php";

// Redirect if admin not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// ✅ Handle delete property
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM properties WHERE id=?");
    $stmt->execute([$delete_id]);
    header("Location: properties.php?message=Property deleted successfully!");
    exit();
}

// ✅ Fetch properties
$stmt = $pdo->query("SELECT id, title, city, property_type, price_per_night FROM properties ORDER BY id DESC");
$properties = $stmt->fetchAll();

$messageAlert = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Property Listings</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --primary-purple: #a999d1;
    --accent-green: #28a745;
    --accent-blue: #a0e6ff;
    --text-dark: #333;
    --background-light: #f4f4f9;
    --border-color: #ddd;
    --sidebar-bg: #343a40;
    --sidebar-link: #adb5bd;
    --sidebar-link-hover: #ffffff;
}

/* GLOBAL */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--background-light);
    margin: 0;
    display: flex; /* Keep side-by-side layout */
    min-height: 100vh;
    /* Do NOT set flex-direction: row; here, Flexbox default is row */
}

/* MAIN CONTENT */
main.admin-main-content {
    flex-grow: 1;
    padding: 30px;
    box-sizing: border-box;
    overflow-y: auto;
    /* Ensure main content is always visible next to the nav/sidebar */
}

/* --- HEADER AND ACTIONS (No Change) --- */

header.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 10px;
}
header.admin-header h1 {
    margin: 0;
    color: var(--text-dark);
    font-size: 1.8rem;
}
header.admin-header .actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.logout-btn {
    background-color: var(--primary-purple);
    color: white;
    padding: 8px 15px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: background-color 0.3s;
}
.logout-btn:hover {
    background-color: #907fbf;
}
.add-btn {
    background-color: var(--accent-green);
}
.add-btn:hover {
    background-color: #218838;
}

/* ALERT MESSAGE */
.message-alert {
    color: green;
    margin-bottom: 15px;
    font-weight: 500;
}

/* TABLE STYLES (Desktop/Tablet) */
section.table-section {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
    overflow-x: auto; /* Keep this for horizontal scrolling if needed */
    -webkit-overflow-scrolling: touch;
}

table.admin-table {
    min-width: 600px; 
    width: 100%;
    border-collapse: collapse;
}
.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}
.admin-table th {
    background-color: var(--background-light);
    font-weight: 600;
    color: var(--text-dark);
}
.admin-table tbody tr:hover {
    background-color: #f8f9fa;
}

/* ACTION BUTTONS (No Change) */
.action-btn {
    padding: 6px 10px;
    border: none;
    border-radius: 4px;
    color: white;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom:2px;
    font-size: 0.9em;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}
.btn-edit { background-color: #ffc107; color: #222; }
.btn-edit:hover { background-color: #e0a800; color: white; }
.btn-delete { background-color: #dc3545; }
.btn-delete:hover { background-color: #c82333; }

/* ------------------------------------- */
/* RESPONSIVE DESIGN (Modifying the 900px breakpoint) */
/* ------------------------------------- */

@media (max-width: 900px) {
    /* 1. Layout Fix: DO NOT change flex-direction on body */
    /* body { flex-direction: column; } <--- REMOVED/OVERRIDDEN */
    main.admin-main-content { padding: 20px 15px; }

    /* 2. Header Fix (Stacking and Spacing) */
    header.admin-header { 
        flex-direction: column; 
        align-items: flex-start;
        width: 100%;
    }
    header.admin-header .actions {
        width: 100%;
        justify-content: space-between;
        margin-top: 10px;
    }
    .logout-btn, .add-btn {
        flex-grow: 1;
        text-align: center;
        justify-content: center;
    }

    /* 3. Card View Table Activation */
    table.admin-table {
        min-width: unset; 
        border: 0;
    }
    .admin-table thead {
        /* Hides the table header row */
        border: none;
        clip: rect(0 0 0 0);
        height: 1px;
        margin: -1px;
        overflow: hidden;
        padding: 0;
        position: absolute;
        width: 1px;
    }
    
    .admin-table tr {
        border: 1px solid var(--border-color);
        display: block;
        margin-bottom: 15px; 
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .admin-table td {
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        text-align: right; 
        font-size: 1em;
    }
    
    .admin-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-dark);
        text-align: left;
        flex-basis: 40%;
    }

    .admin-table tr td:last-child {
        border-bottom: none;
        flex-direction: column; 
        align-items: stretch;
        gap: 8px;
        padding-top: 15px; 
    }

    /* 4. Action Button Fix */
    .action-btn {
        display: flex;
        width: 100%;
        text-align: center;
        justify-content: center;
        margin: 0; 
    }
}
</style>
</head>

<body>
<?php include "nav.php"; ?>

<main class="admin-main-content">
    <header class="admin-header">
        <h1>Manage Properties</h1>
        <div class="actions">
            <a href="add_property.php" class="logout-btn add-btn">
                <i class="fas fa-plus"></i> Add Property
            </a>
            <a href="admin_logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <?php if ($messageAlert): ?>
        <p class="message-alert"><?= htmlspecialchars($messageAlert) ?></p>
    <?php endif; ?>

    <section class="table-section" aria-labelledby="property-list">
        <h2 id="property-list" style="display:none;">Property List</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>City</th>
                    <th>Type</th>
                    <th>Price/Night</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($properties) > 0): ?>
                    <?php foreach ($properties as $prop): ?>
                        <tr>
                            <td data-label="ID"><?= $prop['id'] ?></td>
                            <td data-label="Title"><?= htmlspecialchars($prop['title']) ?></td>
                            <td data-label="City"><?= htmlspecialchars($prop['city']) ?></td>
                            <td data-label="Type"><?= htmlspecialchars($prop['property_type']) ?></td>
                            <td data-label="Price/Night">₹<?= number_format($prop['price_per_night'], 2) ?></td>
                            <td data-label="Actions">
                                <a href="edit_property.php?id=<?= $prop['id'] ?>" class="action-btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete_id=<?= $prop['id'] ?>" class="action-btn btn-delete"
                                    onclick="return confirm('Are you sure you want to delete this property?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No properties found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>