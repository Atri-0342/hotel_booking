<?php
session_name("ADMIN_SESSION");
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once "../includes/db_connect.php";

// Fetch logs
$stmt = $pdo->query("
    SELECT a.id, u.full_name, a.activity, a.ip_address, a.created_at
    FROM activity_logs a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 100
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Activity Logs - QOZY Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; }
.container { padding: 30px; }
h1 { color: #333; margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background-color: #eee; }
tr:hover { background-color: #f9f9f9; }
.search-input { margin-bottom: 10px; padding: 8px; width: 300px; border-radius: 5px; border: 1px solid #ccc; }
</style>
</head>
<body>
<section class="container">
    <h1><i class="fas fa-history"></i> User Activity Logs</h1>
    <input type="text" class="search-input" placeholder="Search logs..." onkeyup="filterLogs(this.value)">
    <table id="logsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Activity</th>
                <th>IP Address</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($logs as $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td><?= htmlspecialchars($log['full_name']) ?></td>
                    <td><?= htmlspecialchars($log['activity']) ?></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    <td><?= $log['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
function filterLogs(value) {
    const rows = document.querySelectorAll("#logsTable tbody tr");
    const filter = value.toLowerCase();
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
}
</script>
</body>
</html>
