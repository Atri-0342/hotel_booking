<?php
session_start();
require 'includes/db_connect.php'; // Database connection

$isLoggedIn = isset($_SESSION['user_id']);
$user_name = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']) : 'Guest';

// --- Get Property ID ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid property ID.");
}
$property_id = (int)$_GET['id'];
// --- Fetch Property ---
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();
if (!$property) die("Property not found.");

// --- Fetch Images ---
$stmt = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ?");
$stmt->execute([$property_id]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

// --- Fetch Property Videos ---
$stmt = $pdo->prepare("SELECT video_url, video_type FROM property_videos WHERE property_id = ?");
$stmt->execute([$property_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch Amenities ---
$stmt = $pdo->prepare("
    SELECT a.name 
    FROM amenities a 
    JOIN property_amenities pa ON a.id = pa.amenity_id 
    WHERE pa.property_id = ?
");
$stmt->execute([$property_id]);
$amenities = $stmt->fetchAll(PDO::FETCH_COLUMN);

// --- Fetch Menu ---
$stmt = $pdo->prepare("SELECT item_name, description, price FROM menus WHERE property_id = ? AND available = 1");
$stmt->execute([$property_id]);
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch Rooms ---
$stmt = $pdo->prepare("SELECT id, room_name, room_description, price_per_night, capacity 
                       FROM rooms WHERE property_id = ? AND available = 1");
$stmt->execute([$property_id]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch Reviews ---
$stmt = $pdo->prepare("SELECT name, rating, comment, review_date FROM reviews WHERE property_id = ? ORDER BY review_date DESC");
$stmt->execute([$property_id]);
$reviews = $stmt->fetchAll();
$review_count = count($reviews);

$avg_rating=(float)$property['rating'];
// --- Calculate average rating ---
$avg_rating = ($review_count > 0)
    ? round(array_sum(array_column($reviews, 'rating')) / $review_count, 1)
    : (float)$property['rating'];

// --- Handle review submission ---
if($isLoggedIn && isset($_POST['submit_review'])) {
    $rating = (int)$_POST['review_rating'];
    $comment = trim($_POST['review_comment']);
    $name = $user_name;
    $review_date = date('Y-m-d');

    if($rating >= 1 && $rating <= 5 && !empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO reviews (property_id, name, rating, comment, review_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$property_id, $name, $rating, $comment, $review_date]);

        // Update average rating in properties table
        $stmt = $pdo->prepare("UPDATE properties 
                               SET rating = (SELECT ROUND(AVG(rating),1) FROM reviews WHERE property_id = ?) 
                               WHERE id = ?");
        $stmt->execute([$property_id, $property_id]);

        header("Location: property_details.php?id=" . $property_id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($property['title']) ?> - QOZY</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* --- CSS --- */
html, body { height: auto; overflow-y: auto; }
body.details-page-body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; margin: 0; }
.property-details-container { width:90%; max-width:1100px; margin:40px auto; padding-top:20px; }
.property-header h1 { font-size:2.5rem; margin-bottom:10px; color:#333; }
.property-meta { color:#666; font-size:1rem; margin-bottom:20px; }
.property-gallery { border-radius:12px; overflow:hidden; margin-bottom:30px; box-shadow:0 8px 25px rgba(0,0,0,0.1); }
.property-gallery #mainDisplay { width:100%; height:500px; background:#000; }
.property-gallery #mainDisplay img,
.property-gallery #mainDisplay video { width:100%; height:500px; object-fit:cover; display:block; }

.gallery-thumbnails { 
    display:grid; 
    grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); 
    gap:10px; 
    margin-top:10px; 
}

.gallery-thumbnails img { 
    width:100%; 
    height:80px; 
    border-radius:8px; 
    object-fit:cover; 
    cursor:pointer; 
    opacity:0.7; 
    transition:0.3s; 
}

.gallery-thumbnails img:hover, 
.gallery-thumbnails img.active { 
    opacity:1; 
    transform:scale(1.05); 
}

.video-thumb {
    position: relative;
    cursor: pointer;
    width: 100%;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
}

.video-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.7;
    transition: 0.3s;
}

.video-thumb:hover img,
.video-thumb.active img {
    opacity: 1;
    transform: scale(1.05);
}

.video-thumb i.fa-play {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 24px;
    pointer-events: none;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.details-layout { display:flex; gap:50px; align-items:flex-start; flex-wrap:wrap; }
.details-main-content { flex:1; }
.details-main-content section { margin-bottom:30px; border-bottom:1px solid #ddd; padding-bottom:20px; }
.details-main-content h2 { font-size:1.5rem; color:#333; margin-bottom:15px; }
#amenities-list { list-style:none; padding:0; display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:10px; }
#amenities-list li i { color:#6a0dad; margin-right:10px; }
.booking-box { flex:0 0 340px; background:#fff; padding:25px; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.1); position:sticky; top:20px; align-self:start; transition: transform 0.3s ease, box-shadow 0.3s ease; }
.booking-box:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
.booking-box strong { font-size:1.6rem; color:#6a0dad; margin-bottom:10px; display:block; }
.booking-box p { margin:0 0 10px 0; font-size:1rem; color:#555; }
.booking-box form { display:flex; flex-direction:column; gap:15px; }
.booking-box label { font-weight:500; font-size:0.95rem; color:#333; }
.booking-box input[type="date"], .booking-box input[type="number"], .booking-box select { padding:10px 12px; border:1px solid #ccc; border-radius:6px; font-size:1rem; width:100%; box-sizing:border-box; transition:border 0.3s, box-shadow 0.3s; }
.booking-box input[type="date"]:focus, .booking-box input[type="number"]:focus, .booking-box select:focus { outline:none; border-color:#6a0dad; box-shadow:0 0 6px rgba(106,13,173,0.3); }
.btn-book { padding:12px; background:#ff69b4; color:#fff; border:none; border-radius:6px; font-size:1rem; font-weight:600; cursor:pointer; transition: background 0.3s, transform 0.2s; }
.btn-book:hover { background:#e058a2; transform:translateY(-2px); }
.review-item { border:1px solid #ddd; padding:15px; border-radius:8px; margin-bottom:15px; background:#fff; }
.review-header { display:flex; justify-content:space-between; margin-bottom:10px; }
.review-rating i { color:#ff69b4; }
.review-form { margin-top:20px; }
.review-form label { font-weight:500; display:block; margin-bottom:5px; }
.review-form select, .review-form textarea { width:100%; padding:10px; margin-bottom:10px; border-radius:6px; border:1px solid #ccc; }
@media (max-width:768px){ .details-layout{flex-direction:column;} .booking-box{width:100%; position:relative; top:0; margin-top:20px;} }
</style>
</head>
<body class="details-page-body">
<?php require "includes/header.php"; ?>

<main class="property-details-container">
    <!-- Property Header -->
    <section class="property-header">
        <h1><?= htmlspecialchars($property['title']) ?></h1>
        <div class="property-meta">
            <i class="fas fa-star"></i> <?= htmlspecialchars($avg_rating) ?> · 
            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['city']) ?> · 
            <?= htmlspecialchars($property['property_type']) ?>
        </div>
    </section>

    <!-- Property Gallery -->
<section class="property-gallery">
    <div id="mainDisplay" style="position:relative;">
        <img id="mainImage" src="<?= htmlspecialchars($images[0] ?? $property['main_image_url']) ?>" 
             alt="<?= htmlspecialchars($property['title']) ?>" 
             style="width:100%; height:500px; object-fit:cover;">
        <!-- Map icon -->
        <div id="map-icon" title="View on Map" 
             style="position:absolute; top:15px; right:15px; background:rgba(255,255,255,0.9); 
                    border-radius:50%; padding:10px; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.2); 
                    transition:background 0.2s; z-index:10;">
            <i class="fas fa-map-marker-alt" style="color:#6a0dad; font-size:20px;"></i>
        </div>
    </div>
    <div class="gallery-thumbnails">
        <?php foreach($images as $index => $img): ?>
            <img class="<?= $index === 0 ? 'active' : '' ?>" 
                 src="<?= htmlspecialchars($img) ?>" 
                 data-type="image" 
                 data-url="<?= htmlspecialchars($img) ?>" 
                 alt="Thumbnail <?= $index+1 ?>" 
                 onclick="showMedia(this)">
        <?php endforeach; ?>
        <?php foreach($videos as $index => $vid): ?>
    <div class="video-thumb" onclick="showMedia(this)">
        <img src="assets/thumb/video_placeholder.jpg" 
             data-type="video"
             data-url="<?= htmlspecialchars($vid['video_url']) ?>"
             alt="Video Thumbnail <?= $index+1 ?>">
        <i class="fas fa-play" 
           style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); color:white; font-size:20px; pointer-events:none;"></i>
    </div>
<?php endforeach; ?>

    </div>
</section>


    <div class="details-layout">
        <div class="details-main-content">
            <!-- About -->
            <section>
                <h2>About this space</h2>
                <p><?= nl2br(htmlspecialchars($property['description'])) ?></p>
            </section>

            <!-- Amenities -->
            <section>
                <h2>Amenities</h2>
                <ul id="amenities-list">
                    <?php if(!empty($amenities)): foreach($amenities as $a): ?>
                        <li><i class="fas fa-check-circle"></i><?= htmlspecialchars($a) ?></li>
                    <?php endforeach; else: ?>
                        <li>No amenities listed.</li>
                    <?php endif; ?>
                </ul>
            </section>

            <!-- Menu -->
            <section>
                <h2>Menu (Optional Food Service)</h2>
                <?php if(!empty($menu_items)): ?>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f4f4f9;">
                                <th style="padding:8px; text-align:left;">Item</th>
                                <th style="padding:8px; text-align:left;">Description</th>
                                <th style="padding:8px; text-align:right;">Price (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($menu_items as $item): ?>
                                <tr>
                                    <td style="padding:8px;"><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td style="padding:8px;"><?= htmlspecialchars($item['description']) ?></td>
                                    <td style="padding:8px; text-align:right;"><?= number_format($item['price'],2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No food service available for this property.</p>
                <?php endif; ?>
            </section>

            <!-- Reviews -->
            <section>
                <h2>Reviews (<?= $review_count ?>)</h2>
                <?php if($review_count>0): 
                    foreach($reviews as $r): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <strong><?= htmlspecialchars($r['name']) ?></strong>
                            <span><?= date("F j, Y", strtotime($r['review_date'])) ?></span>
                        </div>
                        <div class="review-rating">
                            <?php for($i=1;$i<=5;$i++): ?>
                                <i class="fa-star <?= $i <= $r['rating'] ? 'fas' : 'far' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                    </div>
                <?php endforeach; else: ?>
                    <p>No reviews yet.</p>
                <?php endif; ?>

                <h2>Write a Review</h2>
                <?php if($isLoggedIn): ?>
                    <form action="" method="post" class="review-form">
                        <label>Rating</label>
                        <select name="review_rating" required>
                            <option value="">Select rating</option>
                            <?php for($i=5;$i>=1;$i--): ?>
                                <option value="<?= $i ?>"><?= $i ?> Star<?= $i>1?'s':'' ?></option>
                            <?php endfor; ?>
                        </select>
                        <label>Comment</label>
                        <textarea name="review_comment" rows="4" placeholder="Write your review..." required></textarea>
                        <button type="submit" name="submit_review" class="btn-book">Submit Review</button>
                    </form>
                <?php else: ?>
                    <p>Please <a href="login.php">login</a> to write a review.</p>
                <?php endif; ?>
            </section>
        </div>
        <!-- Booking Box -->
        <aside class="booking-box">
            <p><strong id="roomPrice">₹<?= number_format($rooms[0]['price_per_night'],2) ?></strong> / night</p>
            <p><i class="fas fa-star"></i> <?= htmlspecialchars($avg_rating) ?></p>
            <form action="checkout.php" method="get" id="bookingForm">
                <input type="hidden" name="property_id" value="<?= $property_id ?>">
                <label>Room Type</label>
                <select name="room_id" id="roomType" required>
                    <?php foreach($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>" data-price="<?= $room['price_per_night'] ?>" data-name="<?= htmlspecialchars($room['room_name']) ?>">
                            <?= htmlspecialchars($room['room_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="room_name" id="roomNameInput" value="<?= htmlspecialchars($rooms[0]['room_name']) ?>">
                <input type="hidden" name="room_price" id="roomPriceInput" value="<?= $rooms[0]['price_per_night'] ?>">
                <label>Number of Rooms</label>
                <input type="number" name="number_of_rooms" id="numRooms" min="1" value="1" required>
                <label>Check-in</label>
                <input type="date" name="checkin" id="checkin" required>
                <label>Check-out</label>
                <input type="date" name="checkout" id="checkout" required>
                <label>Guests per Room</label>
                <input type="number" name="guests" min="1" value="1" required>
                <button type="submit" class="btn-book">Request to Book</button>
            </form>
        </aside>
    </div>
</main>

<script>
window.addEventListener('load', function() {
    // --- Date setup ---
    const today = new Date().toISOString().split('T')[0];
    const checkin = document.getElementById('checkin');
    const checkout = document.getElementById('checkout');
    checkin.min = checkout.min = today;
    checkin.value = today;
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    checkout.value = tomorrow.toISOString().split('T')[0];
    checkin.addEventListener('change', function() {
        const minDate = new Date(checkin.value);
        minDate.setDate(minDate.getDate() + 1);
        checkout.min = minDate.toISOString().split('T')[0];
        if (new Date(checkout.value) <= new Date(checkin.value))
            checkout.value = checkout.min;
    });

    // --- Room selection & total price ---
    const roomType = document.getElementById('roomType');
    const roomPrice = document.getElementById('roomPrice');
    const roomPriceInput = document.getElementById('roomPriceInput');
    const numRooms = document.getElementById('numRooms');
    const roomNameInput = document.getElementById('roomNameInput');

    function updatePrice() {
        if (!roomType || !roomType.selectedOptions.length) return;
        const opt = roomType.selectedOptions[0];
        if (!opt) return;

        const price = parseFloat(opt.dataset.price) || 0;
        const name = opt.dataset.name || '';
        const total = price * parseInt(numRooms.value || 1);
        roomPrice.innerText = '₹' + total.toFixed(2);
        roomPriceInput.value = price;
        roomNameInput.value = name;
    }
    if (roomType) {
        roomType.addEventListener('change', updatePrice);
        numRooms.addEventListener('input', updatePrice);
        updatePrice();
    }

    // --- Gallery ---
    const mainDisplay = document.getElementById('mainDisplay');
    const thumbnails = document.querySelectorAll('.gallery-thumbnails img, .gallery-thumbnails .video-thumb');

    window.showMedia = function(element) {
        let type, url;
        if (element.tagName === 'IMG') {
            type = element.dataset.type;
            url = element.dataset.url;
        } else if (element.classList.contains('video-thumb')) {
            const img = element.querySelector('img');
            if (!img) return;
            type = img.dataset.type;
            url = img.dataset.url;
        }

        if (!url) return;

        if (type === 'image') {
            mainDisplay.innerHTML = `<img src="${url}" style="width:100%;height:500px;object-fit:cover;">`;
        } else if (type === 'video') {
            mainDisplay.innerHTML = `<video controls autoplay style="width:100%;height:500px;object-fit:cover;">
                                        <source src="${url}" type="video/mp4">
                                        Your browser does not support video.
                                     </video>`;
        }

        thumbnails.forEach(t => t.classList.remove('active'));
        if (element.tagName === 'IMG') {
            element.classList.add('active');
        } else {
            const img = element.querySelector('img');
            if (img) img.classList.add('active');
        }
    };

    if (thumbnails.length > 0) {
        const firstThumb = thumbnails[0];
        if (firstThumb.tagName === 'IMG') firstThumb.classList.add('active');
    }
    // --- Fullscreen map ---
const mapIcon = document.getElementById('map-icon');
const mapPopupFullscreen = document.getElementById('map-popup-fullscreen');
const closeMapFullscreen = document.getElementById('close-map-fullscreen');
let mapFullscreen, markerFullscreen;

mapIcon.addEventListener('click', function() {
    const lat = <?= $property['latitude'] ?: 0 ?>;
    const lng = <?= $property['longitude'] ?: 0 ?>;
    const title = "<?= htmlspecialchars($property['title'], ENT_QUOTES) ?>";
    const city = "<?= htmlspecialchars($property['city'], ENT_QUOTES) ?>";

    mapPopupFullscreen.style.display = 'block';

    setTimeout(() => {
        if (!mapFullscreen) {
            mapFullscreen = L.map('leaflet-map-fullscreen').setView([lat,lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19}).addTo(mapFullscreen);
        } else {
            mapFullscreen.setView([lat,lng], 14);
            if(markerFullscreen) markerFullscreen.remove();
            mapFullscreen.invalidateSize();
        }

        markerFullscreen = L.marker([lat,lng]).addTo(mapFullscreen)
            .bindPopup(`<strong>${title}</strong><br>${city}`)
            .openPopup();
    }, 50);
});

closeMapFullscreen.addEventListener('click', () => { mapPopupFullscreen.style.display = 'none'; });
document.addEventListener('keydown', e => { if(e.key==='Escape') mapPopupFullscreen.style.display='none'; });

});
</script>
<div id="map-popup-fullscreen" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:10000; background:#fff;">
    <button id="close-map-fullscreen" style="position:absolute; top:15px; left:15px; z-index:10001; padding:8px 12px; border:none; border-radius:5px; cursor:pointer; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.2);">← Back</button>
    <div id="leaflet-map-fullscreen" style="width:100%; height:100%;"></div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

</body>
</html>
