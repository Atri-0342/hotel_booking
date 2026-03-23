<?php
session_name("ADMIN_SESSION");
session_start();
require_once "../includes/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['admin_error'] = "Invalid username or password!";
        header("Location: admin_login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - QOZY</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-purple: #a999d1;
            --accent-pink: #ffc0cb;
            --accent-blue: #a0e6ff;
            --text-dark: #333;
            --text-light: #555;
            --background-light: #f4f4f9;
            --container-bg: #ffffff;
            --border-color: #ddd;
            --btn-hover: #907fbf;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: var(--container-bg);
            padding: 40px 50px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container img {
             max-width: 130px;
             margin-bottom: 20px;
        }
        .login-container h2 {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        .login-container p {
            color: var(--text-light);
            margin-bottom: 30px;
        }
        .input-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-light);
        }
        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        .input-group input:focus {
            outline: none;
            border-color: var(--primary-purple);
            box-shadow: 0 0 4px rgba(169, 153, 209, 0.4);
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: var(--primary-purple);
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: var(--btn-hover);
        }
        .error-message {
             color: #dc3545;
             margin-top: 15px;
             font-weight: 600;
        }
        .success-message {
            color: #28a745;
            margin-top: 15px;
            font-weight: 600;
        }
        .register-link {
            display: block;
            margin-top: 20px;
            color: var(--primary-purple);
            text-decoration: none;
            font-weight: 600;
        }
        .register-link:hover {
            color: var(--btn-hover);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <section class="login-container">
        <img src="../assets/images/Logo-QOZY.png" alt="QOZY Logo"> 
        <h2>Admin Panel Login</h2>
        <p>Please enter your credentials</p>

        <?php 
            if (isset($_SESSION['logout_msg'])) {
                echo '<p class="success-message" style="color: var(--primary-purple);">' . htmlspecialchars($_SESSION['logout_msg']) . '</p>';
                unset($_SESSION['logout_msg']);
            }

            if (isset($_SESSION['admin_error'])) {
                echo '<p class="error-message" style="color: var(--primary-purple);">' . htmlspecialchars($_SESSION['admin_error']) . '</p>';
                unset($_SESSION['admin_error']); 
            }
        ?>

        <form action="admin_login.php" method="POST">
            <div class="input-group">
                <label for="username"><i class="fa-solid fa-user"></i> Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <a href="admin_register.php" class="register-link">Don’t have an account? Register here</a>
    </section>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const user = document.getElementById('username').value.trim();
            const pass = document.getElementById('password').value.trim();

            if (!user || !pass) {
                alert('Please enter both username and password.');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
