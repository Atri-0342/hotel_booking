<?php
session_start();

// Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // NOTE: Ensure 'includes/db_connect.php' properly initializes a PDO object named $pdo
    require_once 'includes/db_connect.php';

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Email and password are required.";
        header("Location: login.php");
        exit();
    }

    try {
        // Prepare statement to retrieve user data
        $stmt = $pdo->prepare("SELECT id, full_name, email, password FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            // Successful login: Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];

            $log = $pdo->prepare("
        INSERT INTO activity_logs (user_id, activity, ip_address)
        VALUES (?, ?, ?)
    ");

    $activity = "User Logged In";
    $ip = $_SERVER['REMOTE_ADDR'];

    $log->execute([$user['id'], $activity, $ip]);
    
            header("Location: index.php"); // Redirect to dashboard/home
            exit();
        } else {
            // Invalid credentials
            $_SESSION['error_message'] = "Invalid email or password.";
            header("Location: login.php");
            exit();
        }

    } catch (PDOException $e) {
        // Database error handling
        error_log("Login Error: " . $e->getMessage()); // Log error for developers
        $_SESSION['error_message'] = "A server error occurred. Please try again.";
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to QOZY</title>
    <style>
/* --- Root Variables from QOZY Logo --- */
:root {
    --primary-purple: #a999d1;
    --accent-pink: #ffc0cb;
    --accent-blue: #a0e6ff;
    --text-dark: #333;
    --text-light: #555;
    --background-light: #f4f4f9;
    --container-bg: #ffffff;
    --border-color: #ddd;
    --btn-primary-bg: var(--accent-pink);
    --btn-primary-hover: #f0b4bf;
    --status-red: #dc3545;
}

/* Base and Layout Fix */
html, body.registration-page {
    margin: 0;
    padding: 0;
    height: 100%;
    overflow: hidden; /* Forcefully hides scrollbars on desktop for full-screen layout */
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--background-light);
    margin: 0;
}

/* --- Main Layout Wrapper --- */
.registration-page-wrapper {
    display: flex;
    width: 100%;
    height: 100%; /* Use 100% to fill the body */
}

/* --- Form Container (Left Side) --- */
.registration-page-wrapper .form-container {
    flex: 0 0 500px;
    height: 100%;
    max-width: 500px;
    padding: 1vh 60px; /* Dynamic padding */
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-sizing: border-box;
}

.logo-container {
    margin-top: 10px;
    text-align: center;
    margin-bottom: 0.4vh; /* Dynamic margin */
}
.logo-container img {
    max-width: 120px;
}

.form-title {
    color: var(--accent-blue);
    text-align: center;
}

.form-container p {
    color: var(--text-light);
    margin-bottom: 0.4vh; /* Dynamic margin */
    text-align: center;
}

/* Input Group Styling */
.input-group {
    text-align: left;
    margin-bottom: 1vh; /* Dynamic margin */
}
.input-group label {
    display: block;
    margin-bottom: 2px;
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--text-light);
}
.input-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    box-sizing: border-box;
    font-size: 1rem;
}
.input-group input:focus { 
    outline: none; 
    border-color: var(--primary-purple); 
}

/* Alert/Message Styling */
.alert {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    font-size: 0.95rem;
}
.alert.error {
    background-color: #f8d7da;
    color: var(--status-red);
    border: 1px solid #f5c6cb;
}
.alert.success {
    background-color: #d4edda;
    color: var(--status-green);
    border: 1px solid #c3e6cb;
}

/* Button and Footer */
.btn { 
    width: 100%; 
    padding: 12px; 
    border: none; 
    border-radius: 8px; 
    background-color: var(--btn-primary-bg); 
    color: white; 
    font-size: 1.1rem; 
    font-weight: 600; 
    cursor: pointer; 
    transition: background-color 0.3s ease; 
    margin-top: 1vh;
}
.btn:hover { 
    background-color: var(--btn-primary-hover); 
}
.form-footer {
    text-align: center;
    margin-top: 2vh; /* Dynamic margin */
}
.form-footer a { 
    color: var(--primary-purple); 
    text-decoration: none; 
    font-weight: 600; 
}

/* --- Visual Section (Right Side) --- */
.registration-visual-section {
    position: relative;
    flex-grow: 1;
    background-color: #333;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.registration-visual-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.4);
    z-index: 2;
}
.promo-text {
    position: relative;
    color: white;
    font-size: 2.8rem;
    font-weight: 700;
    text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.7);
    z-index: 3;
    text-align: center;
    padding: 0 20px;
}
.registration-promo-video {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    min-width: 100%;
    min-height: 100%;
    width: auto;
    height: auto;
    object-fit: cover;
    z-index: 1;
}

/* --- Responsive Adjustments --- */
@media (max-width: 992px) {
    html, body.registration-page {
        overflow: auto; /* Re-enable scroll on mobile */
    }
    .registration-page-wrapper { 
        flex-direction: column; 
        height: auto; 
        min-height: 100vh; 
    }
    .registration-page-wrapper .form-container { 
        flex: auto; 
        max-width: 100%; 
        border-radius: 12px; 
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); 
        margin: 20px; /* Adjusted margin for better mobile look */
        height: auto; 
    }
    .registration-visual-section { 
        width: calc(100% - 40px); 
        height: 300px; 
        border-radius: 12px; 
        margin: 20px; 
        order: -1; 
    }
    .promo-text { 
        font-size: 2rem; 
    }
}
    </style>

    <script>
        // Simple client-side validation for required fields
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            form.addEventListener('submit', (event) => {
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();

                // Clear any PHP session messages initially
                const existingAlert = document.querySelector('.alert');
                if (existingAlert) {
                    existingAlert.remove();
                }

                if (email === '' || password === '') {
                    alert('Email and password are required.');
                    event.preventDefault();
                }
            });
        });
    </script>
</head>
<body class="registration-page">
<main class="registration-page-wrapper">

    <section class="form-container">
        <figure class="logo-container">
            <img src="assets/images/Logo-QOZY.png" alt="QOZY Logo">
        </figure>

        <h2 class="form-title">Welcome Back!</h2>
        <p>Login to continue your journey.</p>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert success">
                <p><?= htmlspecialchars($_SESSION['success_message']); ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert error">
                <p><?= htmlspecialchars($_SESSION['error_message']); ?></p>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <form action="login.php" method="POST" id="loginForm">
            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <footer class="form-footer">
            <p>Don't have an account? <a href="register.php">Sign up here</a></p>
        </footer>
    </section>

    <aside class="registration-visual-section">
        <h2 class="promo-text">Have A Nice Vacation!</h2>
        <video autoplay loop muted playsinline class="registration-promo-video">
            <source src="assets/videos/login-promo1.mp4" type="video/mp4">
        </video>
    </aside>
</main>
</body>
</html>