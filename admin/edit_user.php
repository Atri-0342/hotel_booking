<?php
session_name("ADMIN_SESSION");
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id = $_GET['id'];

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) die("User not found.");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $city = trim($_POST['city']);

    $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, city=? WHERE id=?");
    $stmt->execute([$name, $email, $phone, $city, $id]);
    $success = "User updated successfully!";
    // Refresh data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

form {
    background: white;
    padding: 30px;
    border-radius: 10px;
    max-width: 450px;
    width: 100%;
    box-shadow: 0 4px 15px rgba(0,0,0,0.07);
}

label {
    display: block;
    margin-top: 10px;
    font-weight: 600;
}

input {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
}

button {
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    margin-top: 15px;
    cursor: pointer;
    font-weight: 600;
}

.save-btn { background-color: #28a745; color: white; }
.cancel-btn { background-color: #dc3545; color: white; text-decoration: none; display: inline-block; padding: 8px 15px; margin-left: 10px; }
.success { color: green; margin-bottom: 10px; text-align: center; }
.error { color: #dc3545; margin-bottom: 10px; text-align: center; }
.error-field { border-color: #dc3545; box-shadow: 0 0 3px #dc3545; }

h1 {
    text-align: center;
    margin-bottom: 20px;
}
</style>
</head>
<body>

<form method="POST" id="editUserForm" novalidate>
    <h1>Edit User</h1>

    <?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>

    <div class="error" id="errorMsg"></div>

    <label for="full_name">Name:</label>
    <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

    <label for="email">Email:</label>
    <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label for="phone_number">Phone:</label>
    <input type="text" name="phone_number" id="phone_number" value="<?= htmlspecialchars($user['phone_number']) ?>">

    <label for="city">City:</label>
    <input type="text" name="city" id="city" value="<?= htmlspecialchars($user['city']) ?>">

    <button type="submit" class="save-btn">Save Changes</button>
    <a href="users.php" class="cancel-btn">Cancel</a>
</form>

<script>
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault(); // prevent form from submitting initially

    const name = document.getElementById('full_name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone_number');
    const city = document.getElementById('city');
    const errorDiv = document.getElementById('errorMsg');
    let errors = [];

    // Clear previous styles and messages
    errorDiv.textContent = '';
    [name, email, phone, city].forEach(input => input.classList.remove('error-field'));

    // Regex patterns
    const namePattern = /^[A-Za-z\s]{3,50}$/;
    const emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
    const phonePattern = /^\d{10,15}$/;
    const cityPattern = /^[A-Za-z\s]*$/;

    // Validate fields
    if (!namePattern.test(name.value.trim())) {
        errors.push("Name must be 3–50 letters only.");
        name.classList.add('error-field');
    }

    if (!emailPattern.test(email.value.trim())) {
        errors.push("Please enter a valid email address.");
        email.classList.add('error-field');
    }

    if (phone.value.trim() && !phonePattern.test(phone.value.trim())) {
        errors.push("Phone must be 10–15 digits only.");
        phone.classList.add('error-field');
    }

    if (city.value.trim() && !cityPattern.test(city.value.trim())) {
        errors.push("City can only contain letters and spaces.");
        city.classList.add('error-field');
    }

    // Show error or submit
    if (errors.length > 0) {
        errorDiv.innerHTML = errors.join("<br>");
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        this.submit(); // Submit form if valid
    }
});
</script>
</body>
</html>
