<?php
require 'includes/db_connect.php';
session_start();
// --- Helper to render stars ---
function renderStars($rating) {
    if($rating == 0) return "<span style='font-size:0.85rem; color:#555;'>No reviews yet</span>";
    // truncate to nearest 0.5 downward
    $rating = floor($rating * 2) / 2;
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) == 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    $html = '';
    // Use font-awesome classes directly for styling consistency
    for ($i = 0; $i < $fullStars; $i++) $html .= '<i class="fas fa-star"></i>';
    if ($halfStar) $html .= '<i class="fas fa-star-half-alt"></i>';
    for ($i = 0; $i < $emptyStars; $i++) $html .= '<i class="far fa-star"></i>';

    $html .= " <span style='font-size:0.85rem; color:#555;'>".number_format($rating,1)."/5</span>";
    return $html;
}
$query = $_GET['query'] ?? '';
// Fetch all property types and amenities for filters
$propertyTypes = $pdo->query("SELECT DISTINCT property_type FROM properties")->fetchAll(PDO::FETCH_COLUMN);
$amenities = $pdo->query("SELECT name FROM amenities ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$properties = [];
$selectedTypes = [];
$selectedAmenities = [];

// Only run search query if a query string is provided
if($query && strlen(trim($query)) > 0) {
    $searchQuery = strtolower(trim($query));
    $keywords = preg_split('/\s+/', $searchQuery);
    $stopwords = ['in','at','the','a','an','with','for','to','and','or','of','on','is','are','be'];
    $keywords = array_filter($keywords, fn($word) => !in_array(strtolower($word), $stopwords));
    $keywords = array_values($keywords);
 
    if (count($keywords) > 0) {
        $conditions = [];
        $params = [];

        foreach($keywords as $k => $keyword) {
            $searchTerm = "%{$keyword}%";
            $conditions[] = "(
                p.title LIKE :title{$k} OR 
                p.description LIKE :desc{$k} OR 
                p.property_type LIKE :type{$k} OR 
                p.city LIKE :city{$k} OR 
                a.name LIKE :amenity{$k} OR
                p.nearest_airport LIKE :airport{$k} OR
                p.nearest_landmark LIKE :landmark{$k} OR
                p.nearest_tourist_spot LIKE :tourist{$k}
            )";
            $params["title{$k}"] = $searchTerm;
            $params["desc{$k}"] = $searchTerm;
            $params["type{$k}"] = $searchTerm;
            $params["city{$k}"] = $searchTerm;
            $params["amenity{$k}"] = $searchTerm;
            $params["airport{$k}"] = $searchTerm;
            $params["landmark{$k}"] = $searchTerm;
            $params["tourist{$k}"] = $searchTerm;

            // Pre-select filters based on keywords for better initial filtering experience
            foreach($propertyTypes as $type) {
                if(stripos($type, $keyword) !== false) $selectedTypes[] = $type;
            }
            foreach($amenities as $amenity) {
                if(stripos($amenity, $keyword) !== false) $selectedAmenities[] = $amenity;
            }
        }

        $selectedTypes = array_unique($selectedTypes);
        $selectedAmenities = array_unique($selectedAmenities);

        $whereClause = implode(' OR ', $conditions);

        $sql = "
SELECT p.id, p.title, p.description, p.city, p.property_type, 
       p.price_per_night, p.max_guests, p.latitude, p.longitude,
       COALESCE(AVG(r.rating), p.rating, 0) AS rating, 
       p.main_image_url,
       GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR ',') AS amenities_list
FROM properties p
LEFT JOIN property_amenities pa ON p.id = pa.property_id
LEFT JOIN amenities a ON pa.amenity_id = a.id
LEFT JOIN reviews r ON p.id = r.property_id AND r.status='Approved'
WHERE {$whereClause}
GROUP BY p.id
ORDER BY rating DESC, p.price_per_night ASC
";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($properties as &$prop) {
                // Ensure correct image URL is set
                $prop['image'] = $prop['main_image_url']; 
                $prop['amenities'] = $prop['amenities_list'] ? explode(',', $prop['amenities_list']) : [];
            }
            unset($prop);

        } catch (PDOException $e) {
            error_log("Search query error: " . $e->getMessage());
            $properties = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Results - QOZY</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<style>
:root {
    --primary-color: #a999d1;
    --accent-color: #ffc0cb;
    --text-dark: #333;
    --bg-light: #f4f4f9;
    --star-color: #f5a623;
}
body, html { margin:0; padding:0; font-family:'Segoe UI', sans-serif; background:var(--bg-light); }
.page-wrapper { 
    display:flex; 
    min-height:100vh; 
    padding:20px; 
    box-sizing:border-box; 
    padding-top: 100px; /* Space for fixed header */
}
header { 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100%; 
    z-index: 1000; 
    /* Assuming header.php provides necessary styling */
}
aside.filters-sidebar { 
    width:280px; 
    background:#fff; 
    padding:20px; 
    border-radius:8px; 
    box-shadow:0 4px 12px rgba(0,0,0,0.05); 
    height:fit-content; 
    position: sticky; /* Sticky sidebar */
    top: 90px;
}
main.results-container { flex:1; margin-left:20px; overflow-x:hidden; }
h2 { margin-bottom:20px; color:var(--text-dark); }
h3 { margin-bottom:20px; color:var(--primary-color); }
.search-query { background:#fff; padding:15px 20px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
.search-query strong { color:var(--primary-color); }
.filter-group { 
    margin-bottom:20px; 
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}
.filter-group:last-child { border-bottom: none; }
.filter-group h4 { margin-bottom:10px; color:var(--text-dark); font-size:1rem; font-weight: 600; }
.filter-group input[type=range] { 
    width:100%; 
    cursor:pointer; 
    accent-color: var(--primary-color); /* Style the slider thumb */
}
.filter-group label { 
    display:flex; 
    align-items: center;
    margin:6px 0; 
    cursor:pointer; 
    font-size:0.9rem; 
    color: #555;
}
.filter-group input[type=checkbox],
.filter-group input[type=radio] {
    margin-right: 8px;
    accent-color: var(--primary-color);
}
#price-value { display:block; margin-top:5px; color:#666; font-size:0.9rem; font-weight: 500; }
.properties-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(210px,1fr)); gap:20px; }
.property-card { 
    background:#fff; 
    border-radius:12px; 
    overflow:hidden; 
    box-shadow:0 6px 16px rgba(0,0,0,0.1); 
    transition:transform 0.2s, box-shadow 0.2s; 
    position:relative; 
}
.property-card:hover { transform:translateY(-5px); box-shadow:0 10px 20px rgba(0,0,0,0.15); }
.property-card img { width:100%; height:180px; object-fit:cover; display:block; }
.property-card .card-content { padding:15px; }
.property-card h3 { margin:0 0 8px; font-size:1.2rem; color:var(--text-dark); }
.property-card p { margin:4px 0; color:#555; font-size:0.95rem; }
.property-card .rating { margin-bottom:8px; color:var(--star-color); font-size: 0.9rem; }
.property-card .amenities-tags { margin-top:10px; display:flex; flex-wrap:wrap; gap:6px; }
.property-card .amenities-tags span { 
    font-size:0.75rem; 
    background:var(--accent-color); 
    color:#555;
    padding:4px 10px; 
    border-radius:15px; 
    font-weight: 500;
}
.property-card-link { text-decoration:none; color:inherit; display:block; }
.no-results { grid-column:1/-1; text-align:center; padding:60px 20px; color:#666; background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
.no-results i { font-size:3.5rem; color:#ddd; margin-bottom:20px; }
.no-results h3 { color:var(--text-dark); margin-bottom:10px; }
.no-results p { color:#888; font-size:0.95rem; }

/* Map Icon Button */
.map-icon { 
    position:absolute; 
    bottom:10px; 
    right:10px; 
    background:var(--primary-color); 
    color:#fff; 
    border-radius:50%; 
    padding:10px; 
    cursor:pointer; 
    transition:background 0.2s; 
    z-index:10; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    border: none;
}
.map-icon:hover { background:#8e7bbd; }

/* Fullscreen Map Modal */
#map-popup-fullscreen { 
    display:none; 
    position:fixed; 
    top:0; 
    left:0; 
    width:100vw; 
    height:100vh; 
    z-index:10000; 
    background:#fff; 
}
#close-map-fullscreen { 
    position:absolute; 
    top:20px; 
    right:20px; /* Moved to right for better placement */
    background:var(--primary-color); 
    color:#fff;
    border:none; 
    padding:10px 15px; 
    font-size:1rem; 
    border-radius:8px; 
    cursor:pointer; 
    z-index:10001; 
    box-shadow:0 4px 10px rgba(0,0,0,0.3); 
    transition:background 0.2s; 
}
#close-map-fullscreen:hover { background:#8e7bbd; }


/* Responsive adjustments */
@media (max-width: 992px) {
    aside.filters-sidebar {
        width: 100%;
        margin-bottom: 20px;
        position: static; /* Disable sticky on mobile */
        max-width: none;
    }
    main.results-container { 
        margin-left: 0; 
        width: 100%;
    }
    .page-wrapper {
        flex-direction: column; 
        padding-top: 80px;
    }
}
@media (max-width: 480px) {
    .properties-grid {
        grid-template-columns: 1fr; /* Stack cards on very small screens */
    }
    .property-card img {
        height: 180px;
    }
}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="page-wrapper">
<aside class="filters-sidebar" aria-label="Search Filters">
    <h3><i class="fas fa-filter"></i> Filter Results</h3>
    <form id="filter-form">
        <section class="filter-group">
            <h4>Price per night (Max)</h4>
            <input type="range" id="price-filter" min="1000" max="30000" step="1000" value="10000">
            <span id="price-value">Max: ₹30,000</span>
        </section>
        
        <section class="filter-group">
            <h4>Property Type</h4>
            <div id="property-type-filters">
                <?php foreach($propertyTypes as $type): ?>
                    <label>
                        <input type="checkbox" class="type-filter" value="<?= htmlspecialchars($type) ?>" 
                        <?= in_array($type, $selectedTypes) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($type) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
        
        <section class="filter-group">
            <h4>Rating</h4>
            <label><input type="radio" name="rating-filter" class="rating-filter" value="0" checked> All</label>
            <label><input type="radio" name="rating-filter" class="rating-filter" value="4"> 4+ stars</label>
            <label><input type="radio" name="rating-filter" class="rating-filter" value="4.5"> 4.5+ stars</label>
            <label><input type="radio" name="rating-filter" class="rating-filter" value="5"> 5 stars</label>
        </section>
        
        <section class="filter-group">
            <h4>Amenities</h4>
            <div id="amenities-filters">
                <?php foreach($amenities as $amenity): ?>
                    <label>
                        <input type="checkbox" class="amenity-filter" value="<?= htmlspecialchars($amenity) ?>" 
                        <?= in_array($amenity, $selectedAmenities) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($amenity) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
    </form>
</aside>

<main class="results-container">
    <?php if($query): ?>
        <div class="search-query">
            <i class="fas fa-search"></i> Searched for: <strong><?= htmlspecialchars($query) ?></strong>
        </div>
    <?php endif; ?>
    
    <h2 id="results-heading" aria-live="polite">Showing Properties (<?= count($properties) ?>)</h2>
    <section class="properties-grid" id="properties-grid">
        <!-- Properties rendered here by JS or initial PHP -->
        <?php if(count($properties) > 0): ?>
            <?php foreach($properties as $prop): ?>
            <a href="property_details.php?id=<?= $prop['id'] ?>" class="property-card-link">
                <article class="property-card" 
                    data-price="<?= $prop['price_per_night'] ?>" 
                    data-type="<?= htmlspecialchars($prop['property_type']) ?>" 
                    data-amenities='<?= htmlspecialchars(json_encode($prop['amenities']), ENT_QUOTES, 'UTF-8') ?>'
                    data-rating="<?= $prop['rating'] ?>"
                    data-lat="<?= $prop['latitude'] ?>"
                    data-lng="<?= $prop['longitude'] ?>"
                    data-title="<?= htmlspecialchars($prop['title']) ?>"
                    data-city="<?= htmlspecialchars($prop['city']) ?>"
                    data-id="<?= $prop['id'] ?>">
                    <img src="<?= htmlspecialchars($prop['image']) ?>" 
                         onerror="this.onerror=null;this.src='https://placehold.co/280x200/A999D1/ffffff?text=QOZY+Property';" 
                         alt="<?= htmlspecialchars($prop['title']) ?>">
                    
                    <!-- Map Icon Button -->
                    <button type="button" class="map-icon" title="View on map" data-id="<?= $prop['id'] ?>">
                        <i class="fas fa-map-marker-alt"></i>
                    </button>
                    
                    <div class="card-content">
                        <div class="rating"><?= renderStars($prop['rating']) ?></div>
                        <h3><?= htmlspecialchars($prop['title']) ?></h3>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($prop['city']) ?></p>
                        <p><strong>₹<?= number_format($prop['price_per_night']) ?></strong> / night</p>
                        <?php if(!empty($prop['amenities'])): ?>
                        <div class="amenities-tags">
                            <?php foreach(array_slice($prop['amenities'], 0, 3) as $amenity): ?>
                                <span><?= htmlspecialchars($amenity) ?></span>
                            <?php endforeach; ?>
                            <?php if(count($prop['amenities']) > 3): ?>
                                <span>+<?= count($prop['amenities']) - 3 ?> more</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </article>
            </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No properties found</h3>
                <p>Try searching with different keywords or adjust your filters.</p>
            </div>
        <?php endif; ?>
    </section>
</main>
</div>

<!-- Fullscreen Map Modal -->
<div id="map-popup-fullscreen" role="dialog" aria-modal="true" aria-labelledby="map-title">
    <h2 id="map-title" style="position: absolute; left: -9999px;">Property Location Map</h2>
    <button id="close-map-fullscreen" aria-label="Close Map">&times;</button>
    <div id="leaflet-map-fullscreen" style="width:100%; height:100%;"></div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const priceFilter = document.getElementById('price-filter');
    const priceValue = document.getElementById('price-value');
    const filterForm = document.getElementById('filter-form');
    const propertiesGrid = document.getElementById('properties-grid');
    const resultsHeading = document.getElementById('results-heading');
    // PHP variable passed to JS
    const allPropertiesData = <?= json_encode($properties, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP); ?>;
    
    // Convert amenities_list string to actual array for easy filtering
    const allProperties = allPropertiesData.map(p => ({
        ...p,
        amenities: p.amenities_list ? p.amenities_list.split(',') : [],
        rating: parseFloat(p.rating)
    }));

    function getStarsHTML(rating) {
        rating = Math.round(rating * 2) / 2;
        let fullStars = Math.floor(rating);
        let halfStar = (rating - fullStars) === 0.5 ? 1 : 0;
        let emptyStars = 5 - fullStars - halfStar;
        let html = '';
        const starColor = 'var(--star-color)';

        for (let i = 0; i < fullStars; i++) html += `<i class="fas fa-star" style="color:${starColor};"></i>`;
        if (halfStar) html += `<i class="fas fa-star-half-alt" style="color:${starColor};"></i>`;
        for (let i = 0; i < emptyStars; i++) html += `<i class="far fa-star" style="color:${starColor}; opacity: 0.5;"></i>`;
        
        html += ` <span style="font-size:0.85rem; color:#555;">${rating.toFixed(1)}/5</span>`;
        return html;
    }

    function renderProperties(props) {
        propertiesGrid.innerHTML = '';
        resultsHeading.textContent = `Showing Properties (${props.length})`;
        
        if (props.length === 0) {
            propertiesGrid.innerHTML = `<div class="no-results" role="alert">
                <i class="fas fa-filter"></i>
                <h3>No properties match your filters</h3>
                <p>Try adjusting your filter settings</p>
            </div>`;
            return;
        }

        props.forEach(p => {
            let amenityTags = '';
            const amenitiesArray = p.amenities || []; // Ensure it's an array
           
            if(amenitiesArray.length > 0){
                const displayAmenities = amenitiesArray.slice(0,3);
                amenityTags = displayAmenities.map(a => `<span>${a}</span>`).join('');
                if(amenitiesArray.length > 3) amenityTags += `<span>+${amenitiesArray.length - 3} more</span>`;
            }

            const cardHtml = `<a href="property_details.php?id=${p.id}" class="property-card-link">
                <article class="property-card" 
                    data-lat="${p.latitude}" 
                    data-lng="${p.longitude}" 
                    data-title="${p.title}" 
                    data-city="${p.city}" 
                    data-id="${p.id}" 
                    data-type="${p.property_type}" 
                    data-price="${p.price_per_night}" 
                    data-amenities='${JSON.stringify(amenitiesArray)}'
                    data-rating="${p.rating}">
                    
                    <img src="${p.main_image_url}" 
                         onerror="this.onerror=null;this.src='https://placehold.co/280x200/A999D1/ffffff?text=QOZY+Property';" 
                         alt="${p.title}">
                    
                    <button type="button" class="map-icon" title="View on map" data-id="${p.id}"><i class="fas fa-map-marker-alt"></i></button>
                    
                    <div class="card-content">
                        <div class="rating">${getStarsHTML(p.rating)}</div>
                        <h3>${p.title}</h3>
                        <p><i class="fas fa-map-marker-alt"></i> ${p.city}</p>
                        <p><strong>₹${parseInt(p.price_per_night).toLocaleString('en-IN')}</strong> / night</p>
                        ${amenityTags ? `<div class="amenities-tags">${amenityTags}</div>` : ''}
                    </div>
                </article>
            </a>`;
            propertiesGrid.insertAdjacentHTML('beforeend', cardHtml);
        });
    }

    /**
     * Main filtering logic executed on any input change.
     */
    function applyFilters(){
        const maxPrice = parseInt(priceFilter.value);
        
        const selectedTypes = Array.from(document.querySelectorAll('.type-filter'))
                                .filter(cb => cb.checked).map(cb => cb.value);
        
        const selectedAmenities = Array.from(document.querySelectorAll('.amenity-filter'))
                                .filter(cb => cb.checked).map(cb => cb.value);
        
        const minRating = parseFloat(document.querySelector('.rating-filter:checked').value);

        const filtered = allProperties.filter(p => {
            const priceMatch = p.price_per_night <= maxPrice;
            const typeMatch = selectedTypes.length === 0 || selectedTypes.includes(p.property_type);
            const ratingMatch = p.rating >= minRating;
            
            // Check if ALL selected amenities are present in the property's amenities list
            const amenityMatch = selectedAmenities.length === 0 || selectedAmenities.every(a => p.amenities && p.amenities.includes(a));
            
            return priceMatch && typeMatch && amenityMatch && ratingMatch;
        });
        
        renderProperties(filtered);
    }

    // --- Event Listeners for Filters ---
    priceFilter.addEventListener('input', () => { 
        priceValue.textContent = `Max: ₹${parseInt(priceFilter.value).toLocaleString('en-IN')}`; 
        applyFilters(); 
    });
    
    filterForm.addEventListener('change', applyFilters);
    // --- Fullscreen map logic (Leaflet) ---
    const mapPopupFullscreen = document.getElementById('map-popup-fullscreen');
    const closeMapFullscreen = document.getElementById('close-map-fullscreen');
    let mapFullscreen = null; 

    // Event delegation for map icon button clicks
    document.addEventListener('click', function(e){
        const mapIcon = e.target.closest('.map-icon');
        if(mapIcon){
            e.preventDefault();
            e.stopPropagation();

            // Find the property data associated with the clicked card
            const propertyId = mapIcon.dataset.id;
            const property = allProperties.find(p => p.id == propertyId);

            if (!property || !property.latitude || !property.longitude) {
                console.error("Property or coordinates not found.");
                return;
            }

            const lat = parseFloat(property.latitude);
            const lng = parseFloat(property.longitude);
            const title = property.title;
            const city = property.city;

            mapPopupFullscreen.style.display = 'block';

            // Give the map container a moment to become visible before initialization/resizing
            setTimeout(() => {
                if(!mapFullscreen){
                    // Initialize map if it doesn't exist
                    mapFullscreen = L.map('leaflet-map-fullscreen').setView([lat, lng], 14);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    }).addTo(mapFullscreen);
                } else {
                    // Invalidate size and set view if map already exists
                    mapFullscreen.invalidateSize();
                    mapFullscreen.setView([lat, lng], 14);
                }

                // Clear previous markers
                mapFullscreen.eachLayer(layer => {
                    if (layer instanceof L.Marker) {
                        mapFullscreen.removeLayer(layer);
                    }
                });

                // Add new marker
                L.marker([lat, lng]).addTo(mapFullscreen)
                    .bindPopup(`<strong>${title}</strong><br>${city}<br><a href="property_details.php?id=${property.id}">View Details</a>`)
                    .openPopup();
            }, 50);
        }
    });

    closeMapFullscreen.addEventListener('click', () => { 
        mapPopupFullscreen.style.display = 'none'; 
    });
    // Close map on Escape key
    document.addEventListener('keydown', e => { 
        if(e.key === 'Escape' && mapPopupFullscreen.style.display === 'block') {
            mapPopupFullscreen.style.display = 'none'; 
        }
    });
    // Initial run to apply filters based on keywords (pre-checked filters)
    applyFilters(); 
});
</script>
</body>
</html>