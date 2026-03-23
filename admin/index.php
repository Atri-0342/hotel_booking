<?php
session_name("ADMIN_SESSION");
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once "../includes/db_connect.php";

// Fetch dashboard stats
try {
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_properties = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
    $total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $total_revenue = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status='Confirmed'")->fetchColumn();

    // Recent 6 users
    $stmt = $pdo->query("SELECT id, full_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 6");
    $recent_users = $stmt->fetchAll();

    // Recent 6 bookings
    $stmt = $pdo->query("SELECT b.id, u.full_name, p.title, b.checkin_date, b.checkout_date, b.status 
                         FROM bookings b 
                         JOIN users u ON b.user_id = u.id 
                         JOIN properties p ON b.property_id = p.id 
                         ORDER BY b.created_at DESC 
                         LIMIT 6");
    $recent_bookings = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - QOZY</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    :root {
        --primary-purple: #a999d1;
        --accent-blue: #a0e6ff;
        --text-dark: #333;
        --text-light: #555;
        --background-light: #f4f4f9;
        --container-bg: #ffffff;
        --border-color: #ddd;
        --sidebar-bg: #343a40;
        --sidebar-link: #adb5bd;
        --sidebar-link-hover: #ffffff;
    }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--background-light); margin: 0; display: flex; min-height: 100vh; }

    .admin-main-content { flex-grow: 1; padding: 30px; overflow-y: auto; box-sizing: border-box; }
    .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .admin-header h1 { margin: 0; color: var(--text-dark); }
    .admin-header .logout-btn { background-color: var(--primary-purple); color: white; padding: 8px 15px; text-decoration: none; border-radius: 6px; font-weight: 600; transition: background-color 0.3s; }
    .admin-header .logout-btn:hover { background-color: #907fbf; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; }
    .stat-card { background-color: var(--container-bg); padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); text-align: center; }
    .stat-card i { font-size: 2.5rem; color: var(--primary-purple); margin-bottom: 15px; }
    .stat-card .stat-number { font-size: 2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
    .stat-card .stat-label { font-size: 1rem; color: var(--text-light); }
    .recent-activity { display: flex; gap: 20px; margin-top: 40px; flex-wrap: wrap; }
    .table-container { background-color: white; padding: 15px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); flex: 1 1 48%; min-width: 300px; }
    .table-container h3 { margin-top: 0; margin-bottom: 10px; font-size: 1.2rem; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 8px; border-bottom: 1px solid var(--border-color); }
    th { background-color: var(--background-light); font-weight: 600; }
    tr:hover { background-color: #f8f9fa; }
    .search-input { width: 100%; padding: 6px 10px; margin-bottom: 10px; border-radius: 6px; border: 1px solid var(--border-color); }
</style>
</head>
<body>
    
<?php include "nav.php"?>

<main class="admin-main-content">
<header class="admin-header">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></h1>
    <a href="admin_logout.php" class="logout-btn">Logout</a>
</header>

<section class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <div class="stat-number"><?php echo $total_users; ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-hotel"></i>
        <div class="stat-number"><?php echo $total_properties; ?></div>
        <div class="stat-label">Total Properties</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-check"></i>
        <div class="stat-number"><?php echo $total_bookings; ?></div>
        <div class="stat-label">Total Bookings</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-wallet"></i>
        <div class="stat-number">₹<?php echo number_format($total_revenue, 0, '.', ','); ?></div>
        <div class="stat-label">Estimated Revenue</div>
    </div>
</section>

<section class="recent-activity" style="display: flex; gap: 20px; margin-top: 40px;">
    <div class="table-container" style="flex: 1; min-width: 400px;">
        <h3>Recent Users</h3>
        <input type="text" class="search-input" placeholder="Search Users..." onkeyup="filterTable('users-table', this.value)">
        <div style="overflow-x:auto;">
            <table id="users-table">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Joined</th></tr>
                </thead>
                <tbody>
                <?php foreach($recent_users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['created_at']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-container" style="flex: 1; min-width: 400px;">
        <h3>Recent Bookings</h3>
        <input type="text" class="search-input" placeholder="Search Bookings..." onkeyup="filterTable('bookings-table', this.value)">
        <div style="overflow-x:auto;">
            <table id="bookings-table">
                <thead>
                    <tr><th>ID</th><th>User</th><th>Property</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach($recent_bookings as $booking): ?>
                    <tr>
                        <td><?php echo $booking['id']; ?></td>
                        <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['title']); ?></td>
                        <td><?php echo $booking['checkin_date']; ?></td>
                        <td><?php echo $booking['checkout_date']; ?></td>
                        <td><?php echo $booking['status']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


<script>
function filterTable(tableId, searchValue) {
    const filter = searchValue.toLowerCase();
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);
    rows.forEach(row => {
        row.style.display = Array.from(row.cells).some(cell => 
            cell.textContent.toLowerCase().includes(filter)
        ) ? '' : 'none';
    });
}
</script>
</main>
</body>
</html>
