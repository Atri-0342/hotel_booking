<?php
session_start();
require 'includes/db_connect.php'; // PDO connection

// Check login
$isLoggedIn = isset($_SESSION['user_id']);
$user_name = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : 'Guest';
$user_city = '';

if ($isLoggedIn) {
    // Fetch user's city
    $stmt = $pdo->prepare("SELECT city FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_city = $row ? $row['city'] : '';
}

// Theme colors
$primary_purple = '#a999d1';
$accent_pink = '#ffc0cb';
$text_dark = '#fff';
$background_light = '#f4f4f9';

// Fetch offers from DB
$stmt = $pdo->query("SELECT * FROM offers ORDER BY id DESC");
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch properties near user's city (limit 8)
if ($user_city) {
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE city=? ORDER BY id DESC LIMIT 8");
    $stmt->execute([$user_city]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT * FROM properties ORDER BY id DESC LIMIT 8");
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add first image to each property
foreach ($properties as &$prop) {
    $stmtImg = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id=? LIMIT 1");
    $stmtImg->execute([$prop['id']]);
    $img = $stmtImg->fetch();
    // Assuming property images stored locally are prefixed with '../' in the DB
    $prop['image'] = $img ? $img['image_url'] : $prop['main_image_url'];
}
unset($prop);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QOZY Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* --- BASE & UTILITIES --- */
body { 
    margin:0; 
    font-family:'Segoe UI', sans-serif; 
    background: <?= $background_light ?>; 
}
main {
    padding-bottom: 30px; /* Space above footer */
}

/* --- WELCOME / HERO SECTION --- */
.welcome-box { 
    /* Combined background and overlay for better control */
    background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat; 
    color:<?= $text_dark ?>; 
    text-align:center; 
    padding:60px 20px; 
    border-radius:12px; 
    margin:20px auto; 
    max-width:1100px; 
    position:relative; 
    box-shadow:0 2px 10px rgba(0,0,0,0.3);
}
.welcome-box h1 {
    font-size:2.5rem; /* Increased size for impact */
    margin-bottom:10px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}
.welcome-box p {
    font-size:1.1rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}

/* --- SEARCH FORM --- */
.hero-section { margin-top:30px; }
.search-form { 
    display:flex; 
    justify-content:center; 
    gap:10px;
    max-width: 650px;
    margin: 20px auto 0;
}
.search-form input[type=text] { 
    flex-grow: 1; /* Allows input to take up most space */
    min-width: 150px;
    padding:15px 20px; 
    border-radius:8px; 
    border:1px solid #ccc; 
    font-size:1rem; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.search-form button { 
    flex-shrink: 0;
    padding:15px 30px; 
    font-size:1rem; 
    border-radius:8px; 
    border:none; 
    background:#a0e6ff; 
    color: #333;
    cursor:pointer; 
    display:flex; 
    align-items:center; 
    gap:8px; 
    font-weight: 600;
    transition: background 0.3s, transform 0.2s; 
}
.search-form button:hover { 
    background:#78d4ff; 
    transform:scale(1.02); /* Less aggressive scale */
}

/* --- OFFERS SECTION --- */
.offers-section { width:95%; max-width:1200px; margin:40px auto; }
.offers-section h2 { color: <?= $primary_purple ?>; margin-bottom:20px; text-align:center; font-size:2rem; }
.offers-container { 
    display:flex; 
    gap:20px; 
    overflow-x:auto; /* Horizontal scroll */
    padding-bottom:10px; 
    scroll-snap-type: x mandatory; /* Smoother scrolling experience */
}
.offer-card { 
    flex:0 0 260px; /* Fixed width on desktop/tablet */
    height:200px; 
    border-radius:16px; 
    padding:10px 20px; 
    display:flex; 
    flex-direction:column; 
    justify-content: center; /* Center content vertically */
    align-items:center; 
    text-align:center; 
    transition:0.3s; 
    position:relative; 
    background-size:cover; 
    background-position:center; 
    box-shadow:0 4px 12px rgba(0,0,0,0.2);
    text-decoration: none;
    scroll-snap-align: start;
}
.offer-card::after { 
    content:''; 
    position:absolute; 
    top:0; 
    left:0; 
    right:0; 
    bottom:0; 
    background:linear-gradient(to top, rgba(0,0,0,0.7), rgba(0,0,0,0.2)); /* Darker gradient for text contrast */
    border-radius:16px; 
    z-index: 0;
}
.offer-card figure {
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1; /* Ensure figure content is above overlay */
}
.offer-card i, .offer-card h4, .offer-card p{
    position:relative; 
    z-index:1; 
    color:white;
}
.offer-card i{ font-size:2rem; margin-bottom:10px; }
.offer-card h4{ margin:5px 0; font-size:1.2rem; font-weight:700; }
.offer-card p{ font-size:0.9rem; margin: 0; }
.offer-card:hover { 
    transform:translateY(-4px); 
    box-shadow:0 10px 20px rgba(0,0,0,0.4); 
}

/* --- PROPERTIES GRID --- */
.properties-section { width:95%; max-width:1200px; margin:40px auto; }
.properties-section h2 { text-align:center; color:<?= $primary_purple ?>; margin-bottom:20px; font-size:2rem; }
.properties-grid { 
    display:grid; 
    grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); /* Adjusted min width */
    gap:20px; 
}
.property-card { 
    background:#fff; 
    border-radius:8px; 
    overflow:hidden; 
    box-shadow:0 4px 12px rgba(0,0,0,0.1); 
    transition:0.2s; 
}
.property-card:hover { 
    transform:translateY(-4px); 
    box-shadow:0 6px 16px rgba(0,0,0,0.15); 
}
.property-card img { 
    width:100%; 
    height:180px; /* Slightly taller images */
    object-fit:cover; 
}
.property-card .card-content { padding:15px; }
.property-card h3{margin:0 0 8px;font-size:1.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
.property-card p{margin:4px 0;color:#555;font-size:0.9rem;}
.property-card .rating i{color:#f5a623;}
.property-card-link{text-decoration:none;color:inherit; display:block;}

/* --- FOOTER --- */
footer { text-align:center; padding:25px; color:#555; margin-top:30px; font-size:0.95rem; background: #e0e0e0; }
footer a { color: <?= $primary_purple ?>; text-decoration:none; font-weight:600; }
footer a:hover { text-decoration:underline; }

/* --- RESPONSIVE MEDIA QUERIES --- */
@media(max-width:768px){ 
    .search-form { 
        flex-direction: column;
        gap: 15px;
        padding: 0 20px;
    }
    .search-form input[type=text]{ 
        /* REMOVED: justify-content: center; (Not applicable) */
        padding: 15px 20px; /* Reset padding to match desktop for consistency */
    } 
    .search-form button {
        justify-content: center;
        padding: 12px 20px;
    }
    .welcome-box h1 {
        font-size: 2rem;
    }
    /* Smaller offer cards on smaller screens for more visibility */
    .offer-card{ 
        flex:0 0 220px; 
        height:180px; 
    }
}
@media(max-width:480px){ 
    .properties-grid {
        grid-template-columns: repeat(auto-fill, minmax(100%, 1fr)); /* Single column on smallest screens */
    }
    .offer-card{ 
        flex:0 0 180px; /* Even smaller cards */
        height:160px; 
        padding: 10px;
    }
    .offer-card h4{ 
        font-size:1.1rem; 
    }
    .offer-card i {
        font-size: 1.5rem;
    }
}
</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <header class="welcome-box"> 
        <h1>Welcome, <?= $user_name ?>!</h1>
        <p>Explore properties near you and check our latest offers.</p>

        <div class="hero-section">
            <form action="search_results.php" method="GET" class="search-form" role="search">
                <label for="search_query" class="sr-only">Search properties, city, amenities...</label>
                <input type="text" id="search_query" name="query" placeholder="Search properties, city, amenities..." required>
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
    </header> 
    <section class="offers-section">
        <h2>Available Offers</h2>
        <div class="offers-container">
            <?php foreach($offers as $offer): ?>
                <?php 
                    $offer_id = htmlspecialchars($offer['id'] ?? '');
                    // Use a default path if the image_url is local but doesn't have the parent directory prefix
                    $raw_image_url = $offer['image_url'] ?? '';
                    $offer_image_url = (strpos($raw_image_url, 'http') === 0 || empty($raw_image_url)) ? $raw_image_url : '../' . $raw_image_url;

                    $offer_icon = htmlspecialchars($offer['icon'] ?? '');
                    $offer_title = htmlspecialchars($offer['title'] ?? '');
                    $offer_description = htmlspecialchars(substr($offer['description'] ?? '', 0, 40)) . (strlen($offer['description'] ?? '') > 40 ? '...' : '');
                ?>
                <a href="off.php?id=<?= $offer_id ?>" class="offer-card" style="background:url('<?= $offer_image_url ?: 'assets/images/default_offer_bg.jpg' ?>') center/cover no-repeat;">
                    <figure> 
                        <i class="<?= $offer_icon ?>"></i>
                        <figcaption>
                            <h4><?= $offer_title ?></h4>
                            <p><?= $offer_description ?></p>
                        </figcaption>
                    </figure>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="properties-section">
        <h2>Properties near <?= htmlspecialchars($user_city ?: 'you') ?></h2>
        <div class="properties-grid">
            <?php foreach($properties as $prop): ?>
                <?php
                    $prop_id = htmlspecialchars($prop['id'] ?? '');
                    // Ensure local images are correctly prefixed
                    $prop['image'];
                    $prop_title = htmlspecialchars($prop['title'] ?? '');
                    $prop_rating = htmlspecialchars($prop['rating'] ?? 'N/A');
                    $prop_city = htmlspecialchars($prop['city'] ?? '');
                    $prop_type = htmlspecialchars($prop['property_type'] ?? '');
                    $prop_price = number_format($prop['price_per_night'] ?? 0);
                ?>
                <a href="property_details.php?id=<?= $prop_id ?>" class="property-card-link">
                    <article class="property-card"> 
                        <img src="<?=$prop['image'] ?>" alt="<?= $prop_title ?>">
                        <div class="card-content">
                            <div class="rating"><i class="fas fa-star"></i> <?= $prop_rating ?></div>
                            <h3><?= $prop_title ?></h3>
                            <p><?= $prop_city ?> - <?= $prop_type ?></p>
                            <p>₹<?= $prop_price ?> / night</p>
                        </div>
                    </article>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer>
<p>&copy; <?= date('Y') ?> QOZY. All rights reserved. | <a href="index.php">Home</a></p>
</footer>

</body>
</html>