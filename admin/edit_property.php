<?php
session_name("ADMIN_SESSION");
session_start();
require_once "../includes/db_connect.php";

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Validate property ID
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: properties.php?message=" . urlencode("Invalid property ID"));
    exit();
}

$property_id = (int) $_GET['id'];

// Fetch property data
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id=?");
$stmt->execute([$property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header("Location: properties.php?message=" . urlencode("Property not found!"));
    exit();
}

// Fetch amenities
$amenities = $pdo->query("SELECT * FROM amenities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch selected amenities
$stmt = $pdo->prepare("SELECT amenity_id FROM property_amenities WHERE property_id=?");
$stmt->execute([$property_id]);
$selected_amenities = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'amenity_id');

// Fetch rooms
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE property_id=?");
$stmt->execute([$property_id]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect inputs
    $title = trim($_POST['title']);
    $city = trim($_POST['city']);
    $type = trim($_POST['property_type']);
    $description = trim($_POST['description']);
    $rating = floatval($_POST['rating'] ?? 0);
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $nearest_airport = trim($_POST['nearest_airport'] ?? '');
    $nearest_landmark = trim($_POST['nearest_landmark'] ?? '');
    $nearest_tourist_spot = trim($_POST['nearest_tourist_spot'] ?? '');
    $selectedAmenities = $_POST['amenities'] ?? [];

    $main_image_url = $property['main_image_url'];

    // Validation
    if ($title === '' || strlen($title) < 3) $errors[] = "Title must be at least 3 characters.";
    if ($city === '') $errors[] = "City is required.";
    if ($type === '') $errors[] = "Property type is required.";
    if ($description === '' || strlen($description) < 10) $errors[] = "Description must be at least 10 characters.";
    if ($rating < 0 || $rating > 5) $errors[] = "Rating must be between 0 and 5.";
    if (!is_numeric($latitude) || !is_numeric($longitude)) $errors[] = "Latitude and Longitude must be numeric.";

    // Handle main image upload
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['main_image']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Invalid image format (JPG, PNG, or GIF only).";
        } else {
            $uploadDir = "../uploads/properties/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newFileName = time() . "_" . basename($_FILES['main_image']['name']);
            $targetFile = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $targetFile)) {
                $main_image_url = "uploads/properties/" . $newFileName;
            } else {
                $errors[] = "Failed to upload main image.";
            }
        }
    }

    // Update if no errors
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE properties 
            SET title=?, city=?, property_type=?, description=?, rating=?, latitude=?, longitude=?, 
                nearest_airport=?, nearest_landmark=?, nearest_tourist_spot=?, main_image_url=? 
            WHERE id=?
        ");
        $stmt->execute([
            $title, $city, $type, $description, $rating, $latitude, $longitude,
            $nearest_airport, $nearest_landmark, $nearest_tourist_spot, $main_image_url, $property_id
        ]);

        // Update amenities
        $pdo->prepare("DELETE FROM property_amenities WHERE property_id=?")->execute([$property_id]);
        if (!empty($selectedAmenities)) {
            $stmtAmenity = $pdo->prepare("INSERT INTO property_amenities (property_id, amenity_id) VALUES (?, ?)");
            foreach ($selectedAmenities as $a) {
                $stmtAmenity->execute([$property_id, $a]);
            }
        }

        // Update or add rooms
        $roomPrices = [];
        if (isset($_POST['rooms'])) {
            $rooms = $_POST['rooms'];
            $pdo->prepare("DELETE FROM rooms WHERE property_id=?")->execute([$property_id]);
            $stmtRoom = $pdo->prepare("INSERT INTO rooms (property_id, room_name, room_description, price_per_night, capacity, available) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($rooms as $r) {
                if (!empty($r['room_name']) && $r['price_per_night'] > 0) {
                    $stmtRoom->execute([
                        $property_id,
                        $r['room_name'],
                        $r['room_description'] ?? '',
                        $r['price_per_night'],
                        $r['capacity'] ?? 2,
                        isset($r['available']) ? 1 : 0
                    ]);
                    $roomPrices[] = floatval($r['price_per_night']);
                }
            }
        }

        // Update base price
        if (!empty($roomPrices)) {
            $lowestPrice = min($roomPrices);
            $pdo->prepare("UPDATE properties SET price_per_night=? WHERE id=?")->execute([$lowestPrice, $property_id]);
        }

        $message = "<div style='color:green; font-weight:600;'>✅ Property updated successfully!</div>";
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
<title>Edit Property - Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { font-family:'Segoe UI',sans-serif; background:#f4f4f9; margin:0; padding:30px; }
form { background:white; padding:20px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.1); max-width:800px; margin:auto; }
input, textarea, select { width:100%; padding:10px; margin-bottom:12px; border-radius:6px; border:1px solid #ccc; font-size:1rem; }
button { background:#a999d1; color:white; border:none; padding:10px 20px; border-radius:6px; font-size:1rem; cursor:pointer; }
button:hover { background:#907fbf; }
.room-group { border:1px solid #ddd; padding:15px; border-radius:8px; margin-bottom:15px; background:#f9f9fc; }
.add-room-btn { background:#28a745; color:white; padding:8px 14px; border-radius:6px; border:none; cursor:pointer; margin-bottom:15px; }
.add-room-btn:hover { background:#218838; }
</style>
</head>
<body>

<h2 style="text-align:center;">Edit Property</h2>
<?= $message ?>

<form action="" method="POST" enctype="multipart/form-data">
    <input type="text" name="title" value="<?= htmlspecialchars($property['title']) ?>" placeholder="Title" required>
    <input type="text" name="city" value="<?= htmlspecialchars($property['city']) ?>" placeholder="City" required>
    <input type="text" name="property_type" value="<?= htmlspecialchars($property['property_type']) ?>" placeholder="Property Type" required>
    <input type="number" step="0.1" name="rating" value="<?= htmlspecialchars($property['rating']) ?>" max="5" placeholder="Rating (0-5)">
    <textarea name="description" rows="4"><?= htmlspecialchars($property['description']) ?></textarea>

    <input type="text" name="latitude" value="<?= htmlspecialchars($property['latitude']) ?>" placeholder="Latitude" required>
    <input type="text" name="longitude" value="<?= htmlspecialchars($property['longitude']) ?>" placeholder="Longitude" required>

    <input type="text" name="nearest_airport" value="<?= htmlspecialchars($property['nearest_airport']) ?>" placeholder="Nearest Airport">
    <input type="text" name="nearest_landmark" value="<?= htmlspecialchars($property['nearest_landmark']) ?>" placeholder="Nearest Landmark">
    <input type="text" name="nearest_tourist_spot" value="<?= htmlspecialchars($property['nearest_tourist_spot']) ?>" placeholder="Nearest Tourist Spot">

    <label>Main Image:</label>
    <?php if (!empty($property['main_image_url'])): ?>
        <img src="../<?= htmlspecialchars($property['main_image_url']) ?>" width="150">
    <?php endif; ?>
    <input type="file" name="main_image" accept="image/*">

    <h3>Amenities:</h3>
    <?php foreach($amenities as $a): ?>
        <label><input type="checkbox" name="amenities[]" value="<?= $a['id'] ?>" <?= in_array($a['id'], $selected_amenities) ? 'checked' : '' ?>> <?= htmlspecialchars($a['name']) ?></label><br>
    <?php endforeach; ?>

    <hr>
    <h2>🛏 Rooms</h2>
    <div id="rooms-container">
        <?php foreach ($rooms as $i => $r): ?>
        <div class="room-group">
            <input type="text" name="rooms[<?= $i ?>][room_name]" value="<?= htmlspecialchars($r['room_name']) ?>" placeholder="Room Name" required>
            <textarea name="rooms[<?= $i ?>][room_description]" placeholder="Room Description"><?= htmlspecialchars($r['room_description']) ?></textarea>
            <input type="number" step="0.01" name="rooms[<?= $i ?>][price_per_night]" value="<?= htmlspecialchars($r['price_per_night']) ?>" placeholder="Price per night" required>
            <input type="number" name="rooms[<?= $i ?>][capacity]" value="<?= htmlspecialchars($r['capacity']) ?>" placeholder="Capacity" min="1">
            <label><input type="checkbox" name="rooms[<?= $i ?>][available]" <?= $r['available'] ? 'checked' : '' ?>> Available</label>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="add-room-btn" id="addRoomBtn">+ Add Another Room</button>

    <br><br>
    <button type="submit"><i class="fas fa-save"></i> Update Property</button>
    <button type="button" onclick="window.location.href='properties.php'" style="background:#6c757d;">Cancel</button>
</form>

<script>
let roomIndex = <?= count($rooms) ?>;
document.getElementById('addRoomBtn').addEventListener('click', () => {
    const container = document.getElementById('rooms-container');
    const div = document.createElement('div');
    div.className = 'room-group';
    div.innerHTML = `
        <input type="text" name="rooms[${roomIndex}][room_name]" placeholder="Room Name" required>
        <textarea name="rooms[${roomIndex}][room_description]" placeholder="Room Description"></textarea>
        <input type="number" step="0.01" name="rooms[${roomIndex}][price_per_night]" placeholder="Price per night" required>
        <input type="number" name="rooms[${roomIndex}][capacity]" placeholder="Capacity" min="1" value="2">
        <label><input type="checkbox" name="rooms[${roomIndex}][available]" checked> Available</label>
    `;
    container.appendChild(div);
    roomIndex++;
});
</script>
</body>
</html>
