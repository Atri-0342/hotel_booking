<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$user_name = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : 'Guest';

// Theme colors
$primary_purple = '#a999d1';
$accent_pink = '#ffc0cb';
?>
<style>
/* --- ROOT VARIABLES (Moved from PHP to CSS for cleaner usage) --- */
:root {
    --primary-purple: <?= $primary_purple ?>;
    --accent-pink: <?= $accent_pink ?>;
    --text-color: #333;
    --background-color: #fff;
    --logout-bg: #e58eb5;
    --shadow-color: rgba(0,0,0,0.05);
}

/* --- HEADER CONTAINER --- */
header {
    background: var(--background-color);
    padding: 10px 0;
    box-shadow: 0 2px 8px var(--shadow-color);
}
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 95%;
    max-width: 1200px;
    margin: auto;
}
.logo img {
    width: 140px;
    height: 50px;
    border-radius: 10px;
    object-fit: contain;
    background-color: white;
}

/* --- ACTION AREA (Logged In/Out) --- */
.header-actions {
    display: flex;
    align-items: center;
    gap: 20px;
}

/* --- LOGGED-IN NAVIGATION ICONS --- */
.nav-icon {
    width: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    text-decoration: none; /* In case it's wrapped in an <a> tag later */
}
.nav-icon i {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--primary-purple);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}
.nav-icon:hover i {
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}
.nav-icon p {
    margin: 5px 0 0 0;
    font-size: 0.75rem;
    text-align: center;
    color: var(--text-color);
}

/* --- LOGOUT BUTTON --- */
.logout-btn {
    background: var(--logout-bg);
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.logout-btn:hover {
    background: #d47e9e; /* Slightly darker pink on hover */
}

/* --- LOGGED-OUT LINKS --- */
.header-actions a {
    display: inline-block; 
    padding: 8px 16px; 
    text-decoration: none; 
    border-radius: 6px; 
    font-weight: 600;
}
.header-actions a[href="login.php"],
.header-actions a[href="support.php"] {
    background: #a0e6ff; /* Light blue for secondary actions */
    color: var(--text-color);
}
.header-actions a[href="register.php"] {
    background: var(--accent-pink); 
    color: white;
}

/* ------------------------------------- */
/* RESPONSIVENESS (Mobile View) */
/* ------------------------------------- */
@media (max-width: 600px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        padding: 0 10px;
    }
    .logo {
        width: 100%;
        text-align: center;
    }
    .header-actions {
        width: 100%;
        gap: 10px;
        flex-wrap: wrap; /* Allows icons/buttons to wrap */
        justify-content: center; /* Center the actions */
    }
    .nav-icon {
        width: 60px; /* Make icons slightly smaller */
    }
    .nav-icon i {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    .nav-icon p {
        font-size: 0.7rem;
    }

    /* Stack or space out login/register buttons */
    .header-actions a {
        flex-grow: 1; /* Make login/register links fill space */
        text-align: center;
        padding: 10px 8px;
        margin-left: 0 !important;
    }

    /* Make logout button full width if needed */
    .header-actions form {
        width: 100%;
    }
    .logout-btn {
        width: 100%;
        padding: 10px;
    }
}
</style>
<header>
    <div class="dashboard-header">
        <div class="logo">
            <a href="index.php">
                <img src="assets/images/Logo-QOZY.png" alt="QOZY">
            </a>
        </div>

        <div class="header-actions">
            <?php if($isLoggedIn): ?>
                <div class="nav-icon" onclick="window.location.href='index.php'">
                    <i class="fas fa-home"></i>
                    <p>Dashboard</p>
                </div>
                <div class="nav-icon" onclick="window.location.href='profile.php'">
                    <i class="fas fa-user-circle"></i>
                    <p>Profile</p>
                </div>

                <div class="nav-icon" onclick="window.location.href='bookings.php'">
                    <i class="fas fa-calendar-check"></i>
                    <p>My Bookings</p>
                </div>

                <div class="nav-icon" onclick="window.location.href='support.php'">
                    <i class="fas fa-headset"></i>
                    <p>Support</p>
                </div>

                <form action="logout.php" method="post">
                    <button class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
                <a href="support.php">Support</a>
            <?php endif; ?>
        </div>
    </div>
</header>