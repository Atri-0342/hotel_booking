<?php
session_start();
require 'includes/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle profile update form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Sanitize and get post data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $dob = trim($_POST['dob'] ?? '');

    // Server-side validation (Still critical for security)
    if(empty($name) || empty($email) || empty($dob)){
        $message = "Name, email, and date of birth are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
    } else {
        // Update profile
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, city=?, dob=? WHERE id=?");
        
        // Ensure data is properly bound and sanitized for the DB
        if($stmt->execute([$name, $email, $phone, $city, $dob, $user_id])){
            $message = "Profile updated successfully!";
            $_SESSION['user_name'] = $name; // update session name
        } else {
            $message = "Failed to update profile. Try again.";
        }
    }
}

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if(!$user){
    die("User not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Profile - QOZY</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --primary-purple: #a999d1;
    --accent-pink: #ffc0cb;
    --accent-blue: #a0e6ff;
    --text-dark: #333;
    --text-light: #555;
    --background-light: #f4f4f9;
    --container-bg: #fff;
    --border-color: #ddd;
    --btn-primary-bg: var(--accent-pink);
    --btn-primary-hover: #f0b4bf;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0;
    background-color: var(--background-light);
}

/* Header (Assuming includes/header.php contains a header) */
.main-header {
    background-color: var(--container-bg);
    padding: 10px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: fixed;
    width: 100%;
    top: 0;
    left: 0;
    z-index: 1000;
}
.container { width: 90%; max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
.logo-link img { max-height: 50px; }
.main-nav { display: flex; gap: 15px; align-items: center; }
.nav-link { text-decoration: none; color: var(--text-dark); font-weight: 600; padding: 8px 12px; border-radius: 6px; transition: background-color 0.3s; }
.nav-link:hover { background-color: var(--background-light); }
.btn-nav { background-color: var(--btn-primary-bg); color: white; }
.btn-nav:hover { background-color: var(--btn-primary-hover); }

/* Page wrapper */
.page-wrapper { width: 90%; max-width: 600px; margin: 30px auto; padding:20px; }
h1 { text-align: center; margin-bottom: 30px; color: var(--text-dark); }

/* Profile Form */
.profile-form { background-color: var(--container-bg); padding:30px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.05);}
.profile-form label { display:block; margin-bottom:5px; font-weight:600; color: var(--text-dark);}
.profile-form input { width:100%; padding:10px; margin-bottom:15px; border:1px solid var(--border-color); border-radius:6px; font-size:1rem;}
.btn-update { background-color: var(--btn-primary-bg); color:white; padding:12px; width:100%; font-weight:600; border:none; border-radius:6px; cursor:pointer; transition:0.3s; }
.btn-update:hover { background-color: var(--btn-primary-hover); }

.message { text-align:center; margin-bottom:15px; padding: 10px; border-radius: 6px; font-weight: 600; }
.success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
.error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }

.change-password-link {
    display:block;
    text-align:center;
    margin-top:20px;
    font-weight:600;
    color: var(--primary-purple);
    text-decoration:none;
}
.change-password-link:hover {
    text-decoration:underline;
}
</style>
</head>
<body>

<?php include "includes/header.php" ?>

<main class="page-wrapper">
    <h1>My Profile</h1>
    <!-- Updated message element to allow JS manipulation -->
    <div id="statusMessage" class="message 
        <?php if ($message) { echo strpos($message, 'success') !== false ? 'success-message' : 'error-message'; } ?>">
        <?= htmlspecialchars($message) ?>
    </div>

    <form class="profile-form" method="post" onsubmit="return validateProfileForm()">
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label for="phone">Phone</label>
        <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($user['phone_number']) ?>">

        <label for="city">City</label>
        <input type="text" name="city" id="city" value="<?= htmlspecialchars($user['city']) ?>">

        <label for="dob">Date of Birth</label>
        <input type="date" name="dob" id="dob" value="<?= htmlspecialchars($user['dob']) ?>" required>

        <button type="submit" class="btn-update">Update Profile</button>
    </form>

    <a href="new_password.php" class="change-password-link">Change Password</a>
</main>

<script>
    /**
     * Clears and displays a validation message.
     * @param {string} text - The message to display.
     * @param {string} type - 'success' or 'error'.
     */
    function displayMessage(text, type) {
        const msgDiv = document.getElementById('statusMessage');
        msgDiv.textContent = text;
        msgDiv.className = 'message'; // Reset classes
        if (text) {
            msgDiv.classList.add(type === 'success' ? 'success-message' : 'error-message');
        }
    }

    /**
     * Client-side validation for the profile update form.
     * @returns {boolean} - true if validation passes, false otherwise.
     */
    function validateProfileForm() {
        // Get values and trim whitespace
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const city = document.getElementById('city').value.trim();
        const dob = document.getElementById('dob').value.trim();

        // Clear any previous messages
        displayMessage('', ''); 

        // 1. Name validation (Required)
        if (name === '') {
            displayMessage('Full Name is required.', 'error');
            return false;
        }

        // 2. Email validation (Required & Format)
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email === '') {
            displayMessage('Email is required.', 'error');
            return false;
        }
        if (!emailPattern.test(email)) {
            displayMessage('Please enter a valid email address.', 'error');
            return false;
        }
        
        // 3. Phone validation (Optional, but checks format if provided)
        // Checks for 7-15 digits, allowing spaces, hyphens, and parentheses, and optional '+' at start.
        const phonePattern = /^\+?[\d\s-()]{10,15}$/; 
        if (phone !== '' && !phonePattern.test(phone)) {
            displayMessage('Please enter a valid phone number (7-15 digits, only numbers, spaces, hyphens, or parentheses allowed).', 'error');
            return false;
        }
        
        // 4. City validation (Optional, but checks format if provided)
        // Allows letters, spaces, and hyphens (for multi-word cities)
        const cityPattern = /^[A-Za-z\s-]+$/;
        if (city !== '' && !cityPattern.test(city)) {
            displayMessage('City name can only contain letters, spaces, and hyphens.', 'error');
            return false;
        }

        // 5. Date of Birth validation (Required)
        if (dob === '') {
            displayMessage('Date of Birth is required.', 'error');
            return false;
        }
        
        // Validation passed
        return true;
    }
</script>

</body>
</html>