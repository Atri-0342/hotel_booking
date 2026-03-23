<?php
session_name("ADMIN_SESSION");
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once "../includes/db_connect.php";

$message = '';
$alertType = '';

// ✅ Handle Add Offer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_offer'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = 'fas fa-calendar'; // default icon
    $image_url = null;

    // --- 1. Basic validation ---
    if (strlen($title) < 3 || strlen($title) > 255) {
        $message = "Title must be between 3 and 255 characters.";
        $alertType = "error";
    } elseif (strlen($description) < 10) {
        $message = "Description must be at least 10 characters long.";
        $alertType = "error";
    } else {
        // --- 2. Image upload handling ---
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "../uploads/offers/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExts)) {
                $message = "Invalid image format. Only JPG, PNG, GIF, WEBP allowed.";
                $alertType = "error";
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $message = "Image size should not exceed 2MB.";
                $alertType = "error";
            } else {
                $fileName = time() . "_" . preg_replace("/[^A-Za-z0-9_\.-]/", "_", $_FILES['image']['name']);
                $targetFile = $uploadDir . $fileName;

                $mime = mime_content_type($_FILES['image']['tmp_name']);
                if (strpos($mime, 'image/') !== 0) {
                    $message = "Uploaded file is not a valid image.";
                    $alertType = "error";
                } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $image_url = "uploads/offers/" . $fileName;
                } else {
                    $message = "Image upload failed.";
                    $alertType = "error";
                }
            }
        }

        // --- 3. External URL fallback ---
        if (!$image_url && !empty($_POST['image_url'])) {
            $url = trim($_POST['image_url']);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $image_url = $url;
            } else {
                $message = "Invalid image URL.";
                $alertType = "error";
            }
        }

        // --- 4. Insert if everything valid ---
        if (!$message) {
            $stmt = $pdo->prepare("INSERT INTO offers (title, description, icon, image_url) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $icon, $image_url])) {
                header("Location: offers.php?success=1");
                exit();
            } else {
                $message = "Failed to add offer due to database error.";
                $alertType = "error";
            }
        }
    }
}

// ✅ Handle Delete Offer
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmtImg = $pdo->prepare("SELECT image_url FROM offers WHERE id=?");
    $stmtImg->execute([$id]);
    $offerToDelete = $stmtImg->fetch();

    if ($offerToDelete && $offerToDelete['image_url'] && !preg_match('/^https?:\/\//', $offerToDelete['image_url'])) {
        $localPath = "../" . $offerToDelete['image_url'];
        if (file_exists($localPath)) unlink($localPath);
    }

    $stmt = $pdo->prepare("DELETE FROM offers WHERE id=?");
    $stmt->execute([$id]);
    header("Location: offers.php?deleted=1");
    exit();
}

// ✅ Success / deleted messages
if (isset($_GET['success'])) {
    $message = "Offer added successfully!";
    $alertType = "success";
}
if (isset($_GET['deleted'])) {
    $message = "Offer deleted successfully!";
    $alertType = "success";
}

$offers = $pdo->query("SELECT * FROM offers ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Offers Management - QOZY Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --primary-purple: #a999d1;
    --accent-green: #28a745;
    --error-red: #dc3545;
    --background-light: #f4f4f9;
    --text-dark: #333;
    --border-color: #ddd;
    --container-bg: #fff;
}

/* --- BASE LAYOUT --- */
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin:0; 
    background:var(--background-light); 
    display:flex; 
    min-height:100vh; 
}

.admin-main-content { flex:1; padding:30px; }
.admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
.admin-header h1 { margin:0; color:var(--text-dark); }
.logout-btn { 
    background:var(--primary-purple); 
    color:#fff; 
    padding:8px 15px; 
    border-radius:6px; 
    text-decoration:none; 
    font-weight:600; 
    transition:0.3s; 
}
.logout-btn:hover { background:#907fbf; }

/* --- ALERTS --- */
.alert { padding:12px 15px; border-radius:6px; margin-bottom:20px; font-weight:500; }
.alert.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.alert.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

/* --- FORM --- */
.form-container { 
    background:var(--container-bg); 
    padding:20px; 
    border-radius:10px; 
    box-shadow:0 4px 15px rgba(0,0,0,0.07); 
    margin-bottom:30px; 
}
.form-container label {
    display: block;
    font-weight: 600;
    margin-top: 10px;
    margin-bottom: 5px;
    color: var(--text-dark);
}
.form-container input:not([type="file"]), .form-container textarea { 
    width:100%; 
    padding:10px; 
    margin-bottom:10px; 
    border-radius:6px; 
    border:1px solid var(--border-color); 
    box-sizing: border-box;
}
.form-container button { 
    background:var(--primary-purple); 
    color:#fff; 
    border:none; 
    padding:10px 15px; 
    border-radius:6px; 
    cursor:pointer; 
    font-weight:600; 
    transition:0.3s; 
}
.form-container button:hover { background:#907fbf; }

/* --- TABLE WRAPPER (For scroll) --- */
.table-wrapper {
    overflow-x: auto;
    background:var(--container-bg); 
    border-radius:10px; 
    box-shadow:0 4px 15px rgba(0,0,0,0.07); 
}

/* --- TABLE STYLES --- */
table { 
    width:100%; 
    min-width: 750px; /* Forces scroll on intermediate screens */
    border-collapse:collapse; 
    background:var(--container-bg); 
}
th, td { padding:12px 10px; text-align:left; border-bottom:1px solid var(--border-color); }
th { background:var(--background-light); }
img.offer-img { width:80px; height:auto; border-radius:5px; object-fit: cover; }

/* Action Button Styling (Desktop/Tablet) */
.action-btn-delete {
    background: var(--error-red);
    color: #fff;
    padding: 6px 10px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background 0.3s;
}
.action-btn-delete:hover {
    background: #c82333;
}

/* --- RESPONSIVE / MOBILE CARD VIEW --- */
@media (max-width:768px) {
    .admin-main-content { padding: 15px; }

    /* Hide table wrapper aesthetics on mobile */
    .table-wrapper {
        box-shadow: none;
        background: transparent;
        border-radius: 0;
    }
    
    /* Table Card View */
    table { 
        min-width: unset; 
        border: 0;
    }
    table, thead, tbody, th, td, tr { display:block; width:100%; }
    thead { display:none; }
    tr { 
        margin-bottom:15px; 
        background:#fff; 
        border-radius:8px; 
        box-shadow:0 2px 5px rgba(0,0,0,0.05); 
        padding:10px; 
    }
    td { 
        border:none; 
        display:flex; 
        justify-content:space-between; 
        padding:8px 10px;
        text-align: right; 
    }
    td::before { 
        content: attr(data-label); 
        font-weight:600; 
        color:var(--text-dark); 
        text-align: left;
        flex-basis: 40%;
    }
    
    /* Ensure action button is centered and full width in its cell */
    tr td:last-child {
        justify-content: center;
        padding-top: 15px;
    }
    .action-btn-delete {
        width: 100%;
        justify-content: center;
    }

    img.offer-img {
        max-width: 100%;
        height: auto;
        width: auto;
        max-height: 100px;
    }
}
</style>
</head>
<body>
<?php include "nav.php"; ?>

<main class="admin-main-content">
    <header class="admin-header">
        <h1>Manage Offers</h1>
        <a href="admin_logout.php" class="logout-btn">Logout</a>
    </header>

    <?php if ($message): ?>
        <div class="alert <?= $alertType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <section class="form-container">
        <h3>Add New Offer</h3>
        <form method="POST" enctype="multipart/form-data" novalidate>
            <label for="title">Title</label>
            <input type="text" name="title" id="title" maxlength="255" required>

            <label for="description">Description</label>
            <textarea name="description" id="description" rows="3" minlength="10" required></textarea>

            <label for="image">Upload Image (Max 2MB, optional)</label>
            <input type="file" name="image" id="image" accept="image/jpeg, image/png, image/gif, image/webp">

            <label for="image_url">Or Image URL (optional)</label>
            <input type="url" name="image_url" id="image_url" placeholder="https://example.com/image.jpg">

            <button type="submit" name="add_offer"><i class="fas fa-plus"></i> Add Offer</button>
        </form>
    </section>

    <section>
        <h3>Existing Offers</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Icon</th>
                        <th>Image</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($offers): foreach ($offers as $offer): ?>
                    <tr>
                        <td data-label="ID"><?= $offer['id'] ?></td>
                        <td data-label="Title"><?= htmlspecialchars(substr($offer['title'], 0, 30)) . (strlen($offer['title']) > 30 ? '...' : '') ?></td>
                        <td data-label="Description"><?= htmlspecialchars(substr($offer['description'], 0, 50)) . (strlen($offer['description']) > 50 ? '...' : '') ?></td>
                        <td data-label="Icon"><i class="<?= htmlspecialchars($offer['icon']) ?>"></i></td>
                        <td data-label="Image">
                            <?php if ($offer['image_url']):
                                $imgSrc = preg_match('/^https?:\/\//', $offer['image_url']) ? $offer['image_url'] : "../" . $offer['image_url']; ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Offer image" class="offer-img">
                            <?php endif; ?>
                        </td>
                        <td data-label="Created"><?= date('Y-m-d', strtotime($offer['created_at'])) ?></td>
                        <td data-label="Actions">
                            <a href="?delete_id=<?= $offer['id'] ?>" onclick="return confirm('Delete this offer?')" class="action-btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="7" style="text-align:center;">No offers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>