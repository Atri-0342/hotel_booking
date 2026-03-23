<?php
session_name("ADMIN_SESSION");
session_start();
require_once "../includes/db_connect.php";

// Redirect if admin is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM support_requests WHERE id=?");
    $stmt->execute([$delete_id]);
    header("Location: support.php?message=Support request deleted successfully!");
    exit();
}

// Handle Admin Email Response (no saving to DB)
if (isset($_POST['respond_id'], $_POST['admin_response'])) {
    $respond_id = (int)$_POST['respond_id'];
    $response_text = trim($_POST['admin_response']);

    if (!empty($response_text)) {
        // Fetch user email and name
        $stmt = $pdo->prepare("SELECT email, user_name, subject FROM support_requests WHERE id=?");
        $stmt->execute([$respond_id]);
        $request = $stmt->fetch();

        if ($request) {
            $to = $request['email'];
            $subject = "Response to your Support Request: " . $request['subject'];
            $message = "<p>Hi " . htmlspecialchars($request['user_name']) . ",</p>";
            $message .= "<p>Our support team responded to your request:</p>";
            $message .= "<blockquote>" . nl2br(htmlspecialchars($response_text)) . "</blockquote>";
            $message .= "<p>Thank you,<br>QOZY Support Team</p>";

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: support@qozy.com\r\n";

            if (@mail($to, $subject, $message, $headers)) {
                header("Location: support.php?message=Response sent successfully!");
                exit();
            } else {
                $error = "Failed to send email. Check server mail settings.";
            }
        }
    } else {
        $error = "Response cannot be empty.";
    }
}

// Fetch all support requests
try {
    $stmt = $pdo->query("SELECT id, user_name, email, subject, message, created_at FROM support_requests ORDER BY created_at DESC");
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching support requests: " . $e->getMessage());
}

$messageAlert = $_GET['message'] ?? ($error ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Support Requests</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root { 
    --primary-purple: #a999d1; 
    --text-dark: #333; 
    --background-light: #f4f4f9; 
    --border-color: #ddd; 
}

/* BASE LAYOUT */
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    background-color: var(--background-light); 
    margin:0; 
    display:flex; /* Essential for side-by-side layout (nav + main) */
    min-height:100vh; 
}
.admin-main-content { flex-grow:1; padding:30px; overflow-y:auto; box-sizing:border-box;}
.admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; flex-wrap: wrap;}
.admin-header h1 { margin:0; color: var(--text-dark); }
.admin-header .logout-btn { 
    background-color: var(--primary-purple); 
    color:white; 
    padding:8px 15px; 
    text-decoration:none; 
    border-radius:6px; 
    font-weight:600; 
    transition: background-color 0.3s; 
}
.admin-header .logout-btn:hover { background-color: #907fbf; }
.message-alert { color:green; margin-bottom:15px; }

/* Table Container (for scroll and aesthetics) */
.table-container {
    overflow-x: auto;
    background-color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.07);
    border-radius: 8px;
    margin-top: 20px;
}

/* TABLE STYLES (Desktop/Intermediate) */
.admin-table { 
    width:100%; 
    min-width: 700px; /* Forces horizontal scroll if screen is narrower */
    border-collapse:collapse; 
    background-color:white; 
}
.admin-table th, .admin-table td { padding:12px 15px; text-align:left; border-bottom:1px solid var(--border-color); }
.admin-table th { background-color: var(--background-light); font-weight:600; color: var(--text-dark); }
.admin-table tbody tr:hover { background-color:#f8f9fa; }

/* ACTION BUTTON CONTAINER FIX (Prevents overlap) */
.admin-table tbody tr td:last-child {
    display: flex;
    flex-wrap: wrap; /* Allows buttons to wrap to a new line */
    gap: 5px; /* Spacing between buttons */
}

/* ACTION BUTTON STYLES */
.action-btn { 
    padding:5px 10px; 
    border:none; 
    border-radius:4px; 
    color:white; 
    cursor:pointer; 
    font-size:0.9em; 
    text-decoration:none; 
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}
.btn-view { background-color:#17a2b8; }
.btn-delete { background-color:#dc3545; }

/* Modal styles (unchanged) */
.modal { display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color: rgba(0,0,0,0.5); }
.modal-content { background-color:white; margin:10% auto; padding:20px; border-radius:8px; width:90%; max-width:600px; position:relative; }
.close-btn { position:absolute; top:10px; right:15px; font-size:1.5rem; font-weight:bold; color:#333; cursor:pointer; }
.modal-content h3 { margin-top:0; }
textarea { width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; margin-top:10px; box-sizing: border-box;}
.send-btn { margin-top:10px; padding:10px 15px; background:#17a2b8; color:white; border:none; border-radius:6px; cursor:pointer; }


/* ------------------------------------- */
/* RESPONSIVE DESIGN (Mobile Card View Activation) */
/* ------------------------------------- */

@media (max-width: 768px) { 
    /* Body remains side-by-side */
    .admin-main-content { padding: 15px; }
    
    /* Table Card View Overrides */
    .table-container {
        overflow-x: hidden;
        box-shadow: none;
        border-radius: 0;
        margin-top: 15px;
        background-color: transparent;
    }
    
    .admin-table {
        min-width: unset; 
        border: 0;
    }

    /* Hiding table header */
    .admin-table thead {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        padding: 0;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }
    .admin-table tr {
        border: 1px solid var(--border-color);
        display: block;
        margin-bottom: 15px; 
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        background-color: #fff;
    }
    .admin-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid var(--border-color);
        text-align: right;
    }
    /* Mobile labels from data-label attributes */
    .admin-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-dark);
        text-align: left;
        flex-basis: 40%;
    }

    /* Action Cell: Force vertical stack on mobile */
    .admin-table tr td:last-child {
        border-bottom: none;
        flex-direction: column; 
        align-items: stretch;
        gap: 8px; 
        padding-top: 10px;
        flex-wrap: nowrap;
    }

    /* Action Button Fix (Stacking) */
    .action-btn {
        display: flex;
        width: 100%; 
        text-align: center;
        justify-content: center;
        margin: 0 !important; 
    }
}
</style>
</head>
<body>

<?php include "nav.php" ?>

<main class="admin-main-content">
<header class="admin-header">
    <h1>Support Requests</h1>
    <a href="admin_logout.php" class="logout-btn">Logout</a>
</header>

<?php if($messageAlert) echo "<p class='message-alert'>{$messageAlert}</p>"; ?>

<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th><th>User Name</th><th>Email</th><th>Subject</th><th>Created At</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($requests) > 0): ?>
                <?php foreach($requests as $req): ?>
                    <tr>
                        <td data-label="ID"><?= $req['id'] ?></td>
                        <td data-label="User Name"><?= htmlspecialchars($req['user_name']) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($req['email']) ?></td>
                        <td data-label="Subject"><?= htmlspecialchars($req['subject']) ?></td>
                        <td data-label="Created At"><?= date('d-m-Y H:i', strtotime($req['created_at'])) ?></td>
                        <td data-label="Actions">
                            <button class="action-btn btn-view" onclick="openModal('<?= htmlspecialchars(addslashes($req['subject'])) ?>', '<?= htmlspecialchars(addslashes($req['message'])) ?>', <?= $req['id'] ?>)"><i class="fas fa-eye"></i> View/Respond</button>
                            <a href="?delete_id=<?= $req['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this request?');"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No support requests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</main>

<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 id="modalSubject"></h3>
        <p><strong>Message:</strong></p>
        <p id="modalMessage" style="white-space: pre-wrap; background:#f0f0f0; padding:10px; border-radius:4px;"></p>

        <form method="post" id="responseForm">
            <input type="hidden" name="respond_id" id="respondId">
            <h4>Send Email Response</h4>
            <textarea name="admin_response" id="adminResponse" rows="4" placeholder="Type your response here..." required></textarea>
            <button type="submit" class="send-btn">Send Response</button>
        </form>
    </div>
</div>

<script>
function openModal(subject, message, id){
    document.getElementById('modalSubject').textContent = subject;
    document.getElementById('modalMessage').textContent = message;
    document.getElementById('respondId').value = id;
    document.getElementById('adminResponse').value = '';
    document.getElementById('viewModal').style.display = 'block';
}

function closeModal(){
    document.getElementById('viewModal').style.display = 'none';
}

window.onclick = function(event){
    const modal = document.getElementById('viewModal');
    if(event.target == modal){
        modal.style.display = "none";
    }
}
</script>

</body>
</html>