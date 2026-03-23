<?php
session_start();
require 'includes/db_connect.php'; // PDO connection

// Theme colors (for consistency)
$primary_purple = '#a999d1';
$background_light = '#f4f4f9';

// 1. Get Offer ID
$offer_id = $_GET['id'] ?? null;

if (!$offer_id || !is_numeric($offer_id)) {
    // Redirect or display an error if ID is missing or invalid
    header("Location: index.php"); 
    exit("Invalid offer ID.");
}

// 2. Fetch Offer Details
try {
    $stmt = $pdo->prepare("SELECT title, description, icon, image_url FROM offers WHERE id = ?");
    $stmt->execute([$offer_id]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        exit("Error: Offer not found.");
    }
} catch (PDOException $e) {
    error_log("DB Error fetching offer: " . $e->getMessage());
    exit("An error occurred while retrieving the offer.");
}

// Security: Sanitize fetched data
$offer_title = htmlspecialchars($offer['title']);
$offer_description = htmlspecialchars($offer['description']);
$offer_icon = htmlspecialchars($offer['icon']);
$offer_image_url = htmlspecialchars($offer['image_url'] ?? 'placeholder.jpg'); // Use a fallback image

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QOZY Offer: <?= $offer_title ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { 
    margin: 0; 
    font-family: 'Segoe UI', sans-serif; 
    background: <?= $background_light ?>; 
}
main {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.offer-header {
    text-align: center;
    padding: 20px 0;
}
.offer-header i {
    font-size: 3rem;
    color: <?= $primary_purple ?>;
    margin-bottom: 10px;
}
.offer-header h1 {
    color: #333;
    font-size: 2.2rem;
    margin: 10px 0;
}
.offer-image {
    width: 100%;
    height: 300px;
    background: url('<?= $offer_image_url ?>') center/cover no-repeat;
    border-radius: 8px;
    margin-bottom: 20px;
}
.offer-content {
    line-height: 1.6;
    color: #555;
}
.back-link {
    display: block;
    margin-top: 30px;
    text-align: center;
    color: <?= $primary_purple ?>;
    text-decoration: none;
    font-weight: 600;
}
.back-link:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<main>
    <div class="offer-header">
        <i class="<?= $offer_icon ?>"></i>
        <h1><?= $offer_title ?></h1>
    </div>

    <div class="offer-image" role="img" aria-label="Image related to the offer: <?= $offer_title ?>"></div>

    <section class="offer-content">
        <p><?= nl2br($offer_description) ?></p>
        
        <h3>Terms & Conditions:</h3>
        <p>This offer is subject to availability and may be withdrawn at any time. Check the property details for applicability.</p>
    </section>

    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</main>

</body>
</html>