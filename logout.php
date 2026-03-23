<?php
// Start session before destroying
session_start();
require 'includes/db_connect.php'; // include PDO connection

if (isset($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $activity = "User Logged Out";

    // Insert logout activity log
    $log = $pdo->prepare("
        INSERT INTO activity_logs (user_id, activity, ip_address)
        VALUES (?, ?, ?)
    ");
    $log->execute([$user_id, $activity, $ip]);
}
// --- Step 1: Unset all session variables ---
$_SESSION = [];

// --- Step 2: If session uses cookies, delete them securely ---
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// --- Step 3: If you set any custom cookies (e.g., remember_me), remove them too ---
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');  // Expire the cookie
}

// --- Step 4: Destroy the session completely ---
session_destroy();

// --- Step 5: Redirect user back to login ---
header("Location: login.php");
exit();
?>
