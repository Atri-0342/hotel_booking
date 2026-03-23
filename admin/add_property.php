<?php
session_name("ADMIN_SESSION");
session_start();
require_once "../includes/db_connect.php";

// Redirect if admin not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$message = '';
$errors = [];

//  Fetch available amenities
$amenities = $pdo->query("SELECT * FROM amenities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['submit'])) {
    //  Sanitize & validate inputs
    $title = trim($_POST['title'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $type = trim($_POST['property_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rating = floatval($_POST['rating'] ?? 0);
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $nearest_airport = trim($_POST['nearest_airport'] ?? '');
    $nearest_landmark = trim($_POST['nearest_landmark'] ?? '');
    $nearest_tourist_spot = trim($_POST['nearest_tourist_spot'] ?? '');
    $selectedAmenities = $_POST['amenities'] ?? [];

    //  Validation
    if ($title === '' || strlen($title) < 3) $errors[] = "Property title must be at least 3 characters.";
    if ($city === '') $errors[] = "City name is required.";
    if ($type === '') $errors[] = "Property type is required.";
    if ($rating < 0 || $rating > 5) $errors[] = "Rating must be between 0 and 5.";
    if (!is_numeric($latitude) || !is_numeric($longitude)) $errors[] = "Latitude and Longitude must be numeric.";

    //  Validate main image
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['main_image']['tmp_name']);
        if (!in_array($fileType, $allowed)) {
            $errors[] = "Main image must be JPG, PNG, or GIF.";
        } elseif ($_FILES['main_image']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Main image must be under 2MB.";
        }
    } else {
        $errors[] = "Main image is required.";
    }

    // 🎥 Validate videos (optional)
    if (isset($_FILES['videos'])) {
        foreach ($_FILES['videos']['tmp_name'] as $i => $tmpName) {
            if ($_FILES['videos']['error'][$i] == 0 && $_FILES['videos']['size'][$i] > 10 * 1024 * 1024) {
                $errors[] = "Each video must be under 10MB.";
            }
        }
    }

    //  Stop if errors
    if (empty($errors)) {
        //  Insert property with temporary base price = 0
        $stmt = $pdo->prepare("
            INSERT INTO properties 
            (title, city, property_type, price_per_night, description, rating, max_guests, latitude, longitude, nearest_airport, nearest_landmark, nearest_tourist_spot)
            VALUES (?, ?, ?, 0, ?, ?, 2, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $city, $type, $description, $rating, $latitude, $longitude, $nearest_airport, $nearest_landmark, $nearest_tourist_spot]);
        $property_id = $pdo->lastInsertId();

        //  Upload directory
        $targetDir = "../uploads/properties/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        // 🌆 Main image
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
            $ext = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
            $fileName = "prop_{$property_id}_main." . strtolower($ext);
            $targetFile = $targetDir . $fileName;
            move_uploaded_file($_FILES['main_image']['tmp_name'], $targetFile);
            $stmt = $pdo->prepare("UPDATE properties SET main_image_url=? WHERE id=?");
            $stmt->execute(["uploads/properties/" . $fileName, $property_id]);
        }

        //  Additional images
        if (isset($_FILES['images'])) {
            foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
                if ($_FILES['images']['error'][$i] == 0) {
                    $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $fileName = "prop_{$property_id}_img_{$i}." . strtolower($ext);
                    move_uploaded_file($tmpName, $targetDir . $fileName);
                    $stmt = $pdo->prepare("INSERT INTO property_images (property_id, image_url) VALUES (?, ?)");
                    $stmt->execute([$property_id, "uploads/properties/" . $fileName]);
                }
            }
        }

        // 🎥 Videos
        if (isset($_FILES['videos'])) {
            foreach ($_FILES['videos']['tmp_name'] as $i => $tmpName) {
                if ($_FILES['videos']['error'][$i] == 0) {
                    $ext = pathinfo($_FILES['videos']['name'][$i], PATHINFO_EXTENSION);
                    $fileName = "prop_{$property_id}_vid_{$i}." . strtolower($ext);
                    move_uploaded_file($tmpName, $targetDir . $fileName);
                    $stmt = $pdo->prepare("INSERT INTO property_videos (property_id, video_url, video_type) VALUES (?, ?, ?)");
                    $stmt->execute([$property_id, "uploads/properties/" . $fileName, $ext]);
                }
            }
        }

        // 🏷️ Amenities
        if (!empty($selectedAmenities)) {
            $stmtAmenity = $pdo->prepare("INSERT INTO property_amenities (property_id, amenity_id) VALUES (?, ?)");
            foreach ($selectedAmenities as $a) {
                $stmtAmenity->execute([$property_id, $a]);
            }
        }

        // 🛏️ Rooms
        $roomPrices = [];
        if (isset($_POST['rooms'])) {
            $rooms = $_POST['rooms'];
            $stmtRoom = $pdo->prepare("
                INSERT INTO rooms (property_id, room_name, room_description, price_per_night, capacity, available) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($rooms as $r) {
                if (!empty($r['room_name']) && $r['price_per_night'] > 0) {
                    $stmtRoom->execute([
                        $property_id,
                        $r['room_name'],
                        $r['room_description'] ?? '',
                        $r['price_per_night'],
                        $r['capacity'],
                        isset($r['available']) ? 1 : 0
                    ]);
                    $roomPrices[] = floatval($r['price_per_night']);
                }
            }

            // Update property with lowest room price
            if (!empty($roomPrices)) {
                $lowestPrice = min($roomPrices);
                $stmt = $pdo->prepare("UPDATE properties SET price_per_night=? WHERE id=?");
                $stmt->execute([$lowestPrice, $property_id]);
            }
        }

        $message = "✅ Property and rooms added successfully!";
    } else {
        $message = "<div style='color:red; font-weight:600;'>" . implode("<br>", $errors) . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Property & Rooms - Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { font-family:'Segoe UI',sans-serif; background:#f4f4f9; margin:0; padding:0; display:flex; }
.admin-main-content { flex-grow:1; padding:30px; box-sizing:border-box; }
form { background:white; padding:20px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.1); max-width:800px; margin:auto; }
form input, form select, form textarea { width:100%; padding:10px; margin-bottom:12px; border-radius:6px; border:1px solid #ccc; font-size:1rem; }
form button { background:#a999d1; color:white; border:none; padding:10px 20px; border-radius:6px; font-size:1rem; cursor:pointer; }
form button:hover { background:#907fbf; }
.message-alert { margin-bottom:15px; text-align:center; font-weight:600; }
.room-group { border:1px solid #ddd; padding:15px; border-radius:8px; margin-bottom:15px; background:#f9f9fc; }
.add-room-btn { background:#28a745; color:white; padding:8px 14px; border-radius:6px; border:none; cursor:pointer; margin-bottom:15px; }
.add-room-btn:hover { background:#218838; }
</style>
</head>
<body>
<?php include "nav.php"; ?>

<main class="admin-main-content">
<?php if($message) echo "<div class='message-alert'>{$message}</div>"; ?>

<form id="propertyForm" action="" method="POST" enctype="multipart/form-data">
    <h2>Add New Property</h2>

    <input type="text" name="title" placeholder="Property Title" required minlength="3">
    <input type="text" name="city" placeholder="City" required>
    <input type="text" name="property_type" placeholder="Property Type" required>
    <input type="number" step="0.1" max="5" name="rating" placeholder="Rating (0-5)" required>
    <textarea name="description" placeholder="Description" rows="4" minlength="10"></textarea>

    <label>Main Image:</label>
    <input type="file" name="main_image" accept="image/" required>

    <label>Additional Images:</label>
    <input type="file" name="images[]" accept="image/*" multiple>

    <label>Videos:</label>
    <input type="file" name="videos[]" accept="video/*" multiple>

    <input type="text" name="latitude" placeholder="Latitude" required pattern="^-?[0-9]*\.?[0-9]+$">
    <input type="text" name="longitude" placeholder="Longitude" required pattern="^-?[0-9]*\.?[0-9]+$">

    <input type="text" name="nearest_airport" placeholder="Nearest Airport">
    <input type="text" name="nearest_landmark" placeholder="Nearest Landmark">
    <input type="text" name="nearest_tourist_spot" placeholder="Nearest Tourist Spot">

    <h3>Amenities:</h3>
    <div class="amenities-box">
        <?php foreach($amenities as $a): ?>
        <label style="display:inline-block; margin-right:15px;">
            <?= htmlspecialchars($a['name']) ?><input type="checkbox" name="amenities[]" value="<?= $a['id'] ?>"> 
        </label>
        <?php endforeach; ?>
    </div>

    <hr>
    <h2>🛏 Add Rooms</h2>
    <div id="rooms-container">
        <div class="room-group">
            <input type="text" name="rooms[0][room_name]" placeholder="Room Name" required>
            <textarea name="rooms[0][room_description]" placeholder="Room Description"></textarea>
            <input type="number" step="0.01" name="rooms[0][price_per_night]" placeholder="Price per night" required min="1">
            <input type="number" name="rooms[0][capacity]" placeholder="Capacity (e.g. 2)" value="2" min="1">
            <label><input type="checkbox" name="rooms[0][available]" checked> Available</label>
        </div>
    </div>
    <button type="button" class="add-room-btn" id="addRoomBtn">+ Add Another Room</button>

    <br><br>
    <button type="submit" name="submit"><i class="fas fa-plus"></i> Add Property & Rooms</button>
</form>
</main>

<script>
let roomIndex = 1;
document.getElementById('addRoomBtn').addEventListener('click', () => {
    const container = document.getElementById('rooms-container');
    const div = document.createElement('div');
    div.className = 'room-group';
    div.innerHTML = `
        <input type="text" name="rooms[${roomIndex}][room_name]" placeholder="Room Name" required>
        <textarea name="rooms[${roomIndex}][room_description]" placeholder="Room Description"></textarea>
        <input type="number" step="0.01" name="rooms[${roomIndex}][price_per_night]" placeholder="Price per night" required min="1">
        <input type="number" name="rooms[${roomIndex}][capacity]" placeholder="Capacity (e.g. 2)" value="2" min="1">
        <label><input type="checkbox" name="rooms[${roomIndex}][available]" checked> Available</label>
    `;
    container.appendChild(div);
    roomIndex++;
});
</script>
</body>
</html>
