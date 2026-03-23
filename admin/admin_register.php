<?php
session_name("ADMIN_SESSION");
session_start();
require_once "../includes/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$username, $email, $password]);
        $_SESSION['admin_success'] = "Admin account created successfully!";
        header("Location: admin_login.php");
        exit();
    }
    catch (PDOException $e) {
        $_SESSION['admin_error'] = "Error: Username or Email already exists!";
        header("Location: admin_register.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register - QOZY</title>
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

        .register-container {
            background-color: var(--container-bg);
            padding: 40px 50px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        .register-container img {
            max-width: 130px;
            margin-bottom: 20px;
        }

        .register-container h2 {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .register-container p {
            color: var(--text-light);
            margin-bottom: 25px;
        }

        .input-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
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

        .login-link {
            display: block;
            margin-top: 20px;
            color: var(--primary-purple);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link:hover {
            color: var(--btn-hover);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="register-container">
        <img src="../assets/images/Logo-QOZY.png" alt="QOZY Logo">
        <h2>Admin Registration</h2>
        <p>Create a new admin account</p>

        <?php 
            if (isset($_SESSION['admin_error'])) {
                echo '<p class="error-message">' . htmlspecialchars($_SESSION['admin_error']) . '</p>';
                unset($_SESSION['admin_error']); 
            }
            if (isset($_SESSION['admin_success'])) {
                echo '<p class="success-message">' . htmlspecialchars($_SESSION['admin_success']) . '</p>';
                unset($_SESSION['admin_success']); 
            }
        ?>

        <form method="POST" id="registerForm">
            <div class="input-group">
                <label for="username"><i class="fa-solid fa-user"></i> Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="input-group">
                <label for="email"><i class="fa-solid fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" placeholder="e.g. a@gmail.com" required>
            </div>

            <div class="input-group">
                <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required>

            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <a href="admin_login.php" class="login-link">Already have an account? Login here</a>
    </main>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const user = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const pass = document.getElementById('password').value.trim();

            const userPattern = /^[a-zA-Z_ ]{3,20}$/;
            const emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
            const passPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/;

            let errors = [];

            if (!userPattern.test(user)) {
                errors.push("Username must be 3–20 characters long and contain only letters.");
            }

            if (!emailPattern.test(email)) {
                errors.push("Please enter a valid email address.");
            }

            if (!passPattern.test(pass)) {
                errors.push("Password must be at least 8 characters, with upper & lowercase letters, a number, and a special character.");
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join("\n"));
            }
        });
    </script>
</body>
</html>
