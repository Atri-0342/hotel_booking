<?php
session_start();
require_once 'includes/db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = preg_replace('/\D/', '', $_POST['phone_number']); 
    $password = $_POST['password'];
    $dob = $_POST['dob'];
    $city = trim($_POST['city']);

    if (empty($full_name) || empty($email) || empty($phone_number) || empty($password) || empty($dob) || empty($city)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        header('Location: register.php');
        exit();
    }

    try {
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetchColumn() > 0) {
            $_SESSION['error_message'] = "This email is already registered. Please login.";
            header('Location: register.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $_SESSION['error_message'] = "A database error occurred during registration check.";
        header('Location: register.php');
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone_number, password, dob, city) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone_number, $password_hash, $dob, $city]);

        $_SESSION['success_message'] = "Registration successful! Please login.";

        $user_id = $pdo->lastInsertId();

// Log user registration activity
$log = $pdo->prepare("
    INSERT INTO activity_logs (user_id, activity, ip_address)
    VALUES (?, ?, ?)
");

$activity = "User Registered";
$log->execute([$user_id, $activity, $_SERVER['REMOTE_ADDR']]);

        header('Location: login.php');
        exit();

    } catch (PDOException $e) {
        error_log("Registration Database Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Registration failed due to a database error.";
        header('Location: register.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - QOZY</title>
<link rel="stylesheet" href="assets/css/style.css"> 
<style>
.password-strength {
    margin-top: 5px;
    height: 8px;
    width: 100%;
    background: #ddd;
    border-radius: 4px;
    overflow: hidden;
    display: none;
}
.password-strength-fill { height: 100%; width: 0%; transition: width 0.3s ease, background 0.3s ease; }
.strength-weak { background: red; width: 33%; }
.strength-medium { background: orange; width: 66%; }
.strength-strong { background: limegreen; width: 100%; }
.error-msg { color: #cc0000; font-size: 0.9em; margin-top: 5px; }
.message { padding: 10px; margin-bottom: 15px; border-radius: 5px; font-weight: bold; }
.message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>
</head>
<body class="registration-page">
<main class="registration-page-wrapper">
    <section class="form-container">
        <div class="logo-container">
            <img src="assets/images/Logo-QOZY.png" alt="QOZY Logo">
        </div>
        <p>Get started with a cozy stay!</p>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message message-success"><?php echo $_SESSION['success_message']; ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="message message-error"><?php echo $_SESSION['error_message']; ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <form id="registerForm" method="POST" novalidate>
            <div class="input-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                <div class="error-msg" id="nameError"></div>
            </div>

            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <div class="error-msg" id="emailError"></div>
            </div>

            <div class="input-group">
                <label for="phone_number">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" required maxlength="10" pattern="\d{10}" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                <div class="error-msg" id="phoneError"></div>
            </div>

            <div class="input-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>">
                <div class="error-msg" id="dobError"></div>
            </div>

            <div class="input-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" required placeholder="Enter your city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                <div class="error-msg" id="cityError"></div>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="password-strength" id="passwordStrength"><div class="password-strength-fill"></div></div>
                <div class="error-msg" id="passwordError"></div>
            </div>

            <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <div class="error-msg" id="confirmError"></div>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <div class="form-footer">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </section>

    <section class="registration-visual-section">
        <h2 class="promo-text">Have A Nice Vacation!</h2>
        <video autoplay loop muted playsinline class="registration-promo-video">
            <source src="assets/videos/registration-promo.mp4" type="video/mp4">
        </video>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const nameInput = document.getElementById('full_name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone_number');
    const dobInput = document.getElementById('dob');
    const cityInput = document.getElementById('city');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('passwordStrength');
    const strengthFill = strengthBar.querySelector('.password-strength-fill');

    const calculatePasswordStrength = (val) => {
        let strength = 0;
        if (val.length >= 8) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[a-z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[!@#$%^&*(),.?":{}|<>]/.test(val)) strength++;
        return strength;
    };

    const updateStrengthMeter = () => {
        const val = passwordInput.value;
        const strength = calculatePasswordStrength(val);
        strengthBar.style.display = val ? 'block' : 'none';
        strengthFill.className = 'password-strength-fill';
        if (strength <= 2) strengthFill.classList.add('strength-weak');
        else if (strength === 3) strengthFill.classList.add('strength-medium');
        else strengthFill.classList.add('strength-strong');
    };

    passwordInput.addEventListener('input', updateStrengthMeter);

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        let valid = true;
        document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');

        if (!/^[A-Za-z\s]+$/.test(nameInput.value.trim())) {
            document.getElementById('nameError').textContent = "Full Name must contain only letters and spaces.";
            valid = false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
            document.getElementById('emailError').textContent = "Invalid email format.";
            valid = false;
        }
        if (!/^\d{10}$/.test(phoneInput.value.trim())) {
            document.getElementById('phoneError').textContent = "Phone must be exactly 10 digits.";
            valid = false;
        }
        if (!dobInput.value) {
            document.getElementById('dobError').textContent = "Please select your date of birth.";
            valid = false;
        }
        if (!/^[A-Za-z\s]+$/.test(cityInput.value.trim())) {
            document.getElementById('cityError').textContent = "City must contain only letters.";
            valid = false;
        }

        const passVal = passwordInput.value;
        const confirmVal = confirmInput.value;
        let passErrors = [];
        if (passVal.length < 8) passErrors.push("At least 8 characters");
        if (!/[A-Z]/.test(passVal)) passErrors.push("One uppercase");
        if (!/[a-z]/.test(passVal)) passErrors.push("One lowercase");
        if (!/[0-9]/.test(passVal)) passErrors.push("One number");
        if (!/[!@#$%^&*(),.?\":{}|<>]/.test(passVal)) passErrors.push("One special character");

        if (passErrors.length > 0) {
            document.getElementById('passwordError').textContent = "Password requires: " + passErrors.join(', ') + ".";
            valid = false;
        }
        if (passVal !== confirmVal) {
            document.getElementById('confirmError').textContent = "Passwords do not match.";
            valid = false;
        }
        if (valid) form.submit();
    });

    updateStrengthMeter();
});
</script>
</body>
</html>
