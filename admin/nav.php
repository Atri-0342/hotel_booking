<style>
.admin-sidebar { flex:0 0 250px; background:#343a40; color:white; padding:20px; box-sizing:border-box; }
.admin-sidebar h2 { text-align:center; margin-bottom:30px; color:#a0e6ff; }
.admin-sidebar nav ul { list-style:none; padding:0; margin:0; }
.admin-sidebar nav li { margin-bottom:15px; }
.admin-sidebar nav a { color:#adb5bd; text-decoration:none; display:block; padding:10px 15px; border-radius:6px; transition:0.3s; }
.admin-sidebar nav a:hover, .admin-sidebar nav a.active { background:rgba(255,255,255,0.1); color:#fff; }
.admin-main-content { flex-grow:1; padding:30px; box-sizing:border-box; }
.admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
.admin-header h1 { margin:0; }
</style>
<aside class="admin-sidebar">
    <h2>QOZY Admin</h2>
    <nav>
        <ul>
            <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> User Management</a></li>
            <li><a href="properties.php"><i class="fas fa-hotel"></i> Listing Management</a></li>
            <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Booking Management</a></li>
            <li><a href="reviews.php"><i class="fas fa-star"></i> Review Management</a></li>
            <li><a href="offers.php"><i class="fas fa-tags"></i> Offers Management</a></li> <!-- Added -->
            <li><a href="support.php"><i class="fas fa-envelope"></i> Support Messages</a></li>
            <li><a href="amenities.php"><i class="fas fa-list"></i> Manage Amenities</a></li>
            <li><a href="activity_logs.php"><i class="fas fa-history"></i> User Activity Logs</a></li>
        </ul>
    </nav>
</aside>