<?php
session_name("ADMIN_SESSION");
session_start();
require_once "../includes/db_connect.php";

// Redirect if admin is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Optional: check if user exists
    $stmtCheck = $pdo->prepare("SELECT full_name FROM users WHERE id=?");
    $stmtCheck->execute([$delete_id]);
    $userToDelete = $stmtCheck->fetch();
    
    if ($userToDelete) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$delete_id]);
        $message = "User '{$userToDelete['full_name']}' deleted successfully!";
        // Redirect to avoid re-submission on refresh
        header("Location: users.php?message=" . urlencode($message));
        exit();
    } else {
        $message = "User not found.";
    }
}

// Show message from redirect
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Fetch recent 6 users
try {
    $stmt = $pdo->query("SELECT id, full_name, email, city, phone_number, created_at FROM users ORDER BY created_at DESC LIMIT 6");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching users: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - User Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --primary-purple: #a999d1;
    --accent-blue: #a0e6ff;
    --text-dark: #333;
    --text-light: #555;
    --background-light: #f4f4f9;
    --container-bg: #ffffff;
    --border-color: #ddd;
    --sidebar-bg: #343a40;
    --sidebar-link: #adb5bd;
    --sidebar-link-hover: #ffffff;
}
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--background-light); margin: 0; display: flex; min-height: 100vh; }

/* Existing Layout */
.admin-main-content { flex-grow: 1; padding: 30px; overflow-y: auto; box-sizing: border-box;}
.admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.admin-header h1 { margin: 0; color: var(--text-dark); }
.admin-header .logout-btn { background-color: var(--primary-purple); color: white; padding: 8px 15px; text-decoration: none; border-radius: 6px; font-weight: 600; transition: background-color 0.3s; }
.admin-header .logout-btn:hover { background-color: #907fbf; }

/* Table Styling */
.admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.07); border-radius: 8px; overflow: hidden; }
.admin-table th, .admin-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
.admin-table th { background-color: var(--background-light); font-weight: 600; color: var(--text-dark); }
.admin-table tbody tr:hover { background-color: #f8f9fa; }

/* Action Buttons */
.action-btn { 
    padding: 5px 10px; 
    border: none; 
    border-radius: 4px; 
    color: white; 
    cursor: pointer; 
    margin-right: 5px; 
    font-size: 0.9em; 
    text-decoration: none; 
    display: inline-flex;
    align-items: center;
    gap: 5px; /* space between icon and text */
    margin-bottom: 4px;
}
.btn-edit { background-color: #ffc107; }
.btn-delete { background-color: #dc3545; }
.message { color: green; margin-bottom: 15px; }

/* The following styles replace the existing mobile fix for a more robust responsive table */
@media (max-width: 768px) {
    /* Adjust main content padding for smaller screens */
    .admin-main-content {
        padding: 15px;
    }

    /* Hide table header (optional, but good for card view) */
    .admin-table thead {
        border: none;
        clip: rect(0 0 0 0);
        height: 1px;
        margin: -1px;
        overflow: hidden;
        padding: 0;
        position: absolute;
        width: 1px;
    }

    .admin-table {
        border: 0;
    }
    
    .admin-table tr {
        border: 1px solid var(--border-color);
        display: block;
        margin-bottom: .625em; /* Space between "cards" */
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .admin-table td {
        border-bottom: 1px solid var(--border-color);
        display: block;
        text-align: right;
        font-size: .8em;
        /* Padding adjustment for better look */
        padding: 10px 15px; 
    }
    
    /* Display column header using the data-label trick */
    .admin-table td::before {
        /* Set the data-label attribute in HTML, but we'll use a standard list here for simplicity */
        content: attr(data-label);
        float: left;
        font-weight: bold;
        text-transform: uppercase;
        color: var(--text-dark);
        margin-right: 1em;
    }
    
    /* Remove the bottom border for the last cell in the "card" */
    .admin-table tr td:last-child {
        border-bottom: 0;
        text-align: left; /* Center or left align the action buttons */
    }

    /* Style for Action Buttons on mobile */
    .action-btn {
        display: block; /* Make buttons take full width of the cell */
        width: 100%;
        text-align: center;
        margin: 5px 0;
    }
}

</style>
</head>
<body>

<?php include "nav.php"?>

<main class="admin-main-content">
<header class="admin-header">
    <h1>User Management</h1>
    <a href="admin_logout.php" class="logout-btn">Logout</a>
</header>

<?php if(isset($message)) echo "<p class='message'>{$message}</p>"; ?>

<table class="admin-table">
    <thead>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Joined</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if(count($users) > 0): ?>
        <?php foreach($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['phone_number']) ?></td>
                <td><?= htmlspecialchars($u['city']) ?></td>
                <td><?= date('d-m-Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $u['id'] ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                    <a href="?delete_id=<?= $u['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($u['full_name']) ?>?');"><i class="fas fa-trash"></i> Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7">No users found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

</main>
</body>
</html>
