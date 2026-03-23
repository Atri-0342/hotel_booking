<?php
session_name("ADMIN_SESSION");
session_start();

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Clear session cookie (if set)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear any additional admin cookies if needed
// Example: if you store "admin_token" or "remember_me"
setcookie("admin_token", '', time() - 3600, "/");
setcookie("remember_me", '', time() - 3600, "/");

// Redirect to admin login page
header("Location: admin_login.php");
exit();
?>
