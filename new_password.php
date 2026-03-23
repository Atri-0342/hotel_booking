<?php
session_start();
require 'includes/db_connect.php';

$message = '';

// Use session to get user ID
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if(!$new_password || !$confirm_password){
        $message = "Both fields are required!";
    } elseif($new_password !== $confirm_password){
        $message = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if($stmt->execute([$hashed_password, $user_id])){
            header("location:profile.php");
        } else {
            $message = "Failed to update password. Try again.";
        }
    }
}

$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if(!$user) die("User not found.");
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - QOZY</title>
<style>
body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding:50px; text-align:center; }
.form-container { background:white; padding:30px; border-radius:10px; max-width:400px; margin:auto; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
input { width:100%; padding:10px; margin-bottom:15px; border-radius:5px; border:1px solid #ddd; }
button { padding:12px; width:100%; background:#a999d1; color:white; border:none; border-radius:5px; cursor:pointer; font-weight:600; }
button:hover { background:#8c82c2; }
.message { margin-bottom:15px; color:green; }
</style>
</head>
<body>

<div class="form-container">
    <h2>Reset Password</h2>
    <p>For account: <strong><?= htmlspecialchars($user['email']) ?></strong></p>

    <?php if($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit">Update Password</button>
    </form>

    <p style="margin-top:15px;"><a href="profile.php">Back to Profile</a></p>
</div>

</body>
</html>
