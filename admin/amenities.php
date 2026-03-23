<?php
session_name("ADMIN_SESSION");
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once "../includes/db_connect.php";

$messageAlert = "";

// ✅ Handle Add Amenity
if (isset($_POST['add_amenity'])) {
    $amenity_name = trim($_POST['amenity_name']);

    // 🔒 Server-side Validation
    if (empty($amenity_name)) {
        $messageAlert = "Amenity name cannot be empty!";
    } elseif (!preg_match("/^[A-Za-z\s\-]{2,50}$/", $amenity_name)) {
        $messageAlert = "Amenity name must be 2–50 characters and contain only letters, spaces, or hyphens.";
    } else {
        // ✅ Check if amenity already exists
        $check = $pdo->prepare("SELECT COUNT(*) FROM amenities WHERE name = ?");
        $check->execute([$amenity_name]);
        if ($check->fetchColumn() > 0) {
            $messageAlert = "This amenity already exists!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO amenities (name) VALUES (?)");
            $stmt->execute([$amenity_name]);
            header("Location: amenities.php?message=Amenity added successfully!");
            exit();
        }
    }
}

// ✅ Handle Delete Amenity
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM amenities WHERE id=?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: amenities.php?message=Amenity deleted successfully!");
    exit();
}

// ✅ Fetch all amenities
$amenities = $pdo->query("SELECT * FROM amenities ORDER BY name ASC")->fetchAll();
$messageAlert = $messageAlert ?: ($_GET['message'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Amenities - Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* --- ROOT VARIABLES --- */
:root {
    --primary-purple: #a999d1;
    --text-dark: #333;
    --background-light: #f4f4f9;
    --border-color: #ddd;
}

/* --- BASE LAYOUT --- */
body { 
    font-family: 'Segoe UI', sans-serif; 
    background: var(--background-light); 
    margin:0; 
    display:flex; 
    min-height:100vh; 
}

/* --- SIDEBAR (Assuming nav.php generates this) --- */
.admin-sidebar { 
    flex: 0 0 250px; /* Kept from your original code */
    background:#343a40; 
    color:white; 
    padding:20px; 
    box-sizing:border-box; 
}
.admin-sidebar h2 { text-align:center; margin-bottom:30px; color:#a0e6ff; }
.admin-sidebar nav ul { list-style:none; padding:0; margin:0; }
.admin-sidebar nav li { margin-bottom:15px; }
.admin-sidebar nav a { color:#adb5bd; text-decoration:none; display:block; padding:10px 15px; border-radius:6px; transition:0.3s; }
.admin-sidebar nav a:hover, .admin-sidebar nav a.active { background:rgba(255,255,255,0.1); color:#fff; }

/* --- MAIN CONTENT --- */
.admin-main-content { 
    flex-grow:1; 
    padding:30px; 
    box-sizing:border-box; 
}
.admin-header { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    margin-bottom:30px; 
    flex-wrap: wrap; /* Allows wrap on small screens */
}
.admin-header h1 { margin:0; color: var(--text-dark); }
.logout-btn { 
    background: var(--primary-purple); 
    color:white; 
    padding:8px 15px; 
    border-radius:6px; 
    text-decoration:none; 
    font-weight:600; 
}
.logout-btn:hover { background:#907fbf; }

/* --- FORM & MESSAGES --- */
.message-alert { color:#28a745; margin-bottom:15px; font-weight:600; }
.message-error { color:#dc3545; margin-bottom:15px; font-weight:600; }
.amenity-form input[type=text] { 
    padding:10px; 
    width:250px; 
    border-radius:6px; 
    border:1px solid #ccc; 
    margin-right: 10px;
}
.amenity-form button { 
    padding:10px 15px; 
    border:none; 
    border-radius:6px; 
    background:#28a745; 
    color:white; 
    cursor:pointer;
    font-weight: 600;
}
.amenity-form button:hover { background:#218838; }


/* --- TABLE STRUCTURE (Desktop/Intermediate) --- */
.table-container {
    overflow-x: auto; /* Enable horizontal scroll */
    background:white; 
    border-radius:8px; 
    overflow:hidden; 
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    margin-top: 20px;
}

.amenities-table { 
    width:100%; 
    min-width: 400px; /* Minimum width to force scroll */
    border-collapse:collapse; 
}
.amenities-table th, .amenities-table td { 
    padding:12px; 
    text-align:left; 
    border-bottom:1px solid var(--border-color); 
}
.amenities-table th { 
    background: var(--background-light); 
    font-weight:600; 
}
.amenities-table tbody tr:last-child td {
    border-bottom: none;
}

/* Delete Button Style */
.delete-btn { 
    background:#dc3545; 
    color:white; 
    padding:5px 10px; 
    border:none; 
    border-radius:4px; 
    cursor:pointer; 
    text-decoration:none; 
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9em;
}
.delete-btn:hover { background:#c82333; }


/* ------------------------------------- */
/* RESPONSIVE DESIGN (Mobile Card View Activation) */
/* ------------------------------------- */

@media (max-width: 768px) { 
    /* Main Layout */
    .admin-main-content { padding: 15px; }
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    /* Form Stacking */
    .amenity-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .amenity-form input[type=text] {
        width: 100%;
        margin-right: 0;
    }

    /* Table Card View Overrides */
    .table-container {
        overflow-x: hidden;
        box-shadow: none;
        background: transparent;
    }
    .amenities-table {
        min-width: unset; 
        border: 0;
    }

    /* Hiding table header */
    .amenities-table thead {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        padding: 0;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }
    .amenities-table tr {
        border: 1px solid var(--border-color);
        display: block;
        margin-bottom: 15px; 
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        background-color: #fff;
    }
    .amenities-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid var(--border-color);
        text-align: right;
    }
    /* Mobile labels from data-label attributes */
    .amenities-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-dark);
        text-align: left;
        flex-basis: 40%;
    }
    .amenities-table tr td:last-child {
        border-bottom: none;
        justify-content: center; /* Center the single button */
    }
    .delete-btn {
        width: 100%; /* Make button full width */
        justify-content: center;
    }
}
</style>
</head>
<body>

<?php include "nav.php"?>

<main class="admin-main-content">
<header class="admin-header">
    <h1>Manage Amenities</h1>
    <a href="admin_logout.php" class="logout-btn">Logout</a>
</header>

<?php if($messageAlert): ?>
    <p class="<?= str_contains(strtolower($messageAlert), 'success') ? 'message-alert' : 'message-error' ?>">
        <?= htmlspecialchars($messageAlert) ?>
    </p>
<?php endif; ?>

<form method="POST" class="amenity-form" onsubmit="return validateAmenityForm();">
    <input type="text" id="amenity_name" name="amenity_name" placeholder="New Amenity Name" 
           required pattern="[A-Za-z\s\-]{2,50}" 
           title="Only letters, spaces, and hyphens (2–50 characters)">
    <button type="submit" name="add_amenity"><i class="fas fa-plus"></i> Add Amenity</button>
</form>

<div class="table-container">
    <table class="amenities-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
<?php 
$counter = 1;
foreach($amenities as $amenity): ?>
    <tr>
        <td data-label="#"><?= $counter++ ?></td>
        <td data-label="Name"><?= htmlspecialchars($amenity['name']) ?></td>
        <td data-label="Action">
            <a href="?delete_id=<?= $amenity['id'] ?>" class="delete-btn" onclick="return confirm('Delete this amenity?');">
                <i class="fas fa-trash"></i> Delete
            </a>
        </td>
    </tr>
<?php endforeach; ?>
        </tbody>
    </table>
</div>

</main>

<script>
function validateAmenityForm() {
    const input = document.getElementById("amenity_name");
    const value = input.value.trim();
    // JS pattern matches the PHP server-side pattern: letters, spaces, hyphens
    const pattern = /^[A-Za-z\s\-]{2,50}$/; 

    if (!value) {
        alert("Amenity name cannot be empty!");
        input.focus();
        return false;
    }

    if (!pattern.test(value)) {
        alert("Amenity name must be 2–50 characters and contain only letters, spaces, or hyphens.");
        input.focus();
        return false;
    }

    return true;
}
</script>

</body>
</html>