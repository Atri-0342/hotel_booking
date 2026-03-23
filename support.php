<?php
session_start();
require 'includes/db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$prefill_name = '';
$prefill_email = '';
$message = '';
$message_type = ''; // 'success' or 'error'

// --- 1. Handle Flash Messages ---
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- 2. Handle Retained Post Data (on error) ---
$retained_name = $_SESSION['post_data']['name'] ?? '';
$retained_email = $_SESSION['post_data']['email'] ?? '';
$retained_subject = $_SESSION['post_data']['subject'] ?? '';
$retained_message_text = $_SESSION['post_data']['message'] ?? '';
unset($_SESSION['post_data']);

// --- 3. Fetch user info and pre-fill fields if logged in ---
if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $logged_in_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($logged_in_user) {
            // Only pre-fill if no retained POST data (meaning the form was not just submitted with an error)
            if (!$retained_name) $prefill_name = $logged_in_user['full_name'];
            if (!$retained_email) $prefill_email = $logged_in_user['email'];
        }
    } catch (PDOException $e) {
        error_log("DB Error fetching user info: " . $e->getMessage());
    }
}

// Set form values priority: Retained POST > Logged-in User Data > Empty
$form_name = $retained_name ?: $prefill_name;
$form_email = $retained_email ?: $prefill_email;
$form_subject = $retained_subject;
$form_message = $retained_message_text;


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input (already done above, but re-collect based on post)
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');

    // Basic validation
    if (!$name || !$email || !$subject || !$message_text) {
        $_SESSION['message'] = "All fields are required!";
        $_SESSION['message_type'] = 'error';
        $_SESSION['post_data'] = $_POST; // Retain data
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format!";
        $_SESSION['message_type'] = 'error';
        $_SESSION['post_data'] = $_POST;
    } else {
        // Use prepared statement to prevent SQL injection and secure message storage
        // FIX: Removed 'user_id' from the query to resolve the "Unknown column" PDOException.
        $stmt = $pdo->prepare("INSERT INTO support_requests (user_name, email, subject, message) VALUES (?, ?, ?, ?)");
        
        // Execute the statement
        $success = $stmt->execute([$name, $email, $subject, $message_text]);

        if ($success) {
            $_SESSION['message'] = "Your support request has been submitted successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to submit your request. Please try again.";
            $_SESSION['message_type'] = 'error';
            $_SESSION['post_data'] = $_POST; // Retain data on DB error
        }
    }
    // Redirect to clear POST data and display session message
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Example FAQs (static)
$faqs = [
    ["question" => "How do I make a booking?", "answer" => "Go to the Properties page, select a property, and fill out the booking form."],
    ["question" => "Can I cancel my booking?", "answer" => "Yes, you can cancel your booking from the My Bookings page before your check-in date."],
    ["question" => "How do I reset my password?", "answer" => "Use the 'Forgot Password' link on the login page or your Profile page."]
];

// Contact info
$contact = [
    "Phone" => "+91 1234567890",
    "Email" => "support@qozy.com",
    "Address" => "123, Main Street, Your City, India"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support / Contact Us - QOZY</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root {
    --primary-color: #a999d1;
    --accent-color: #ffc0cb;
    --text-dark: #333;
    --text-light: #555;
    --bg-light: #f4f4f9;
    --container-bg: #fff;
    --btn-bg: #a999d1;
    --btn-hover: #8c82c2;

    /* Message styles */
    --success-bg: #d4edda;
    --success-text: #155724;
    --error-bg: #f8d7da;
    --error-text: #721c24;
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0;
    background: var(--bg-light);
}
/* Header */
.main-header {
    background: var(--container-bg); padding: 10px 0; position: fixed; top:0; left:0; width:100%;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index:1000;
}
.container { width:90%; max-width:1200px; margin:0 auto; display:flex; justify-content:space-between; align-items:center; }
.logo-link img { max-height:50px; }
.main-nav { display:flex; gap:15px; align-items:center; }
.nav-link { text-decoration:none; color: var(--text-dark); font-weight:600; padding:8px 12px; border-radius:6px; transition:0.3s; }
.nav-link:hover { background:#eee; }
.btn-nav { background: var(--btn-bg); color:white; }
.btn-nav:hover { background: var(--btn-hover); }

/* Page wrapper */
.page-wrapper { width:90%; max-width:1000px; margin:30px auto 40px; padding:20px; }

/* Layout for FAQ + Contact */
.info-section { display:flex; gap:20px; flex-wrap: wrap; }
.faq-container { flex:1 1 300px; background: var(--container-bg); border-radius:10px; padding:20px; box-shadow:0 5px 15px rgba(0,0,0,0.05);}
.contact-container { flex:1 1 250px; background: var(--container-bg); border-radius:10px; padding:20px; box-shadow:0 5px 15px rgba(0,0,0,0.05);}
h1 { text-align:center; margin-bottom:20px; color: var(--text-dark); }
h2 { color: var(--primary-color); margin-top:0; }
.faq-item { border-bottom:1px solid #ddd; padding:10px 0; }
.faq-question { font-weight:600; cursor:pointer; position:relative; padding-right:20px; }
.faq-question::after { content: '+'; position:absolute; right:0; font-weight:bold; }
.faq-question.active::after { content: '-'; }
.faq-answer { display:none; margin-top:5px; color: var(--text-dark); }

/* Form and message styling */
.f { margin-top:30px; background: var(--container-bg); padding:30px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.05);}
label { display:block; margin-bottom:5px; font-weight:600; color: var(--text-dark);}
input, textarea { width:100%; padding:10px; margin-bottom:15px; border-radius:6px; border:1px solid #ddd; font-size:1rem; }
textarea { resize: vertical; min-height:100px; }
button { padding:12px; width:100%; background: var(--btn-bg); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; transition:0.3s; }
button:hover { background: var(--btn-hover); }

/* Message box */
.message-box { 
    text-align:center; 
    margin-bottom:20px; 
    padding: 15px; 
    border-radius: 8px; 
    font-weight: 600; 
}
.message-box.success { 
    background-color: var(--success-bg); 
    color: var(--success-text); 
    border: 1px solid #c3e6cb;
}
.message-box.error { 
    background-color: var(--error-bg); 
    color: var(--error-text); 
    border: 1px solid #f5c6cb;
}

.contact-container p { margin:8px 0; font-weight:500; }
</style>
</head>
<body>

<?php include "includes/header.php" ?>

<div class="page-wrapper">
    <h1>Support / Contact Us</h1>

    <div class="info-section">
        <!-- FAQ -->
        <div class="faq-container">
            <h2>Frequently Asked Questions</h2>
            <div class="faq">
                <?php foreach($faqs as $f): ?>
                    <div class="faq-item">
                        <div class="faq-question"><?= htmlspecialchars($f['question']) ?></div>
                        <div class="faq-answer"><?= htmlspecialchars($f['answer']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="contact-container">
            <h2>Contact Info</h2>
            <?php foreach($contact as $key => $val): ?>
                <p><strong><?= $key ?>:</strong> <?= htmlspecialchars($val) ?></p>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Support Form -->
    <?php if($message): ?>
        <p class="message-box <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>
    <form class="f" method="post">
        <h2>Send us a message</h2>

        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" required value="<?= htmlspecialchars($form_name) ?>">

        <label for="email">Email</label>
        <input type="email" name="email" id="email" required value="<?= htmlspecialchars($form_email) ?>">

        <label for="subject">Subject</label>
        <input type="text" name="subject" id="subject" required value="<?= htmlspecialchars($form_subject) ?>">

        <label for="message">Message</label>
        <textarea name="message" id="message" required><?= htmlspecialchars($form_message) ?></textarea>

        <button type="submit">Submit Request</button>
    </form>
</div>

<script>
// Toggle FAQ answers
document.querySelectorAll('.faq-question').forEach(q => {
    q.addEventListener('click', () => {
        q.classList.toggle('active');
        const ans = q.nextElementSibling;
        ans.style.display = ans.style.display === 'block' ? 'none' : 'block';
    });
});
</script>

</body>
</html>