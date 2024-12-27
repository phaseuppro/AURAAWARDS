<?php 
$total_jury_points = 0; // Initialisiere die Gesamtpunkte
$leaderboard = []; // Array mit allen Jury-Punkten der Teilnehmer

// Berechne die Gesamtpunkte des aktuellen Nutzers
if (!empty($judged_photos)) { 
    foreach ($judged_photos as $photo) { 
        if (!empty($photo->judge_data)) {
            $judge_data = maybe_unserialize($photo->judge_data);
            $jury_points = isset($judge_data['jury_points']) ? (int) $judge_data['jury_points'] : 0;
            $total_jury_points += $jury_points;
        }
    }
}

// Beispiel-Daten für das Leaderboard (hier mit Platzhaltern, du solltest dies mit echten Daten füllen)
$leaderboard = [150, 120, 100, 200, 180, 220]; // Punkte anderer Teilnehmer
$leaderboard[] = $total_jury_points; // Füge die Punkte des aktuellen Nutzers hinzu

// Sortiere das Leaderboard absteigend
rsort($leaderboard);

// Finde die Position des Nutzers
$user_position = array_search($total_jury_points, $leaderboard) + 1;

?>

<!-- Anzeige der Gesamtpunkte und Rang -->
<div class="jury-points-summary">
    <p>Your total Jury Points: <?php echo esc_html($total_jury_points); ?></p>
    <p>Your current position on the Leaderboard: <?php echo esc_html($user_position); ?></p>
</div>

<!-- The Judged Photos Grid -->
<div class="aura-judged-photos-grid">
    <?php if (!empty($judged_photos)) { ?>
        <?php foreach ($judged_photos as $photo): 
            if (empty($photo->badged_image_id)) continue;
            $image_url = wp_get_attachment_url($photo->badged_image_id);
            if (!$image_url) continue;
        ?>
            <div class="judged-photo-item">
                <div class="photo-container">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($photo->post_title); ?>">
                </div>
                <?php if (!empty($photo->judge_data)) { 
                    $judge_data = maybe_unserialize($photo->judge_data); ?>
                    <div class="rating-details">
                        <?php if (!empty($judge_data['ratings'])) { ?>
                            <div class="ratings">
                                <?php foreach ($judge_data['ratings'] as $criterion => $rating): ?>
                                    <div class="rating-item">
                                        <span class="criterion"><?php echo esc_html(ucfirst($criterion)); ?></span>
                                        <span class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php } ?>
                        <?php if (!empty($judge_data['jury_points'])) { ?>
                            <div class="total-points">
                                Jury Points for this Submission: <?php echo esc_html($judge_data['jury_points']); ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
                <!-- Download Link for Submission -->
                <div class="download-link">
                    <a href="<?php echo esc_url($image_url); ?>" download="<?php echo esc_attr(basename($image_url)); ?>" class="button">
                        Download Submission
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php } else { ?>
        <p>No judged photos found.</p>
    <?php } ?>
</div>

<style>
/* General Summary Styles */
.jury-points-summary {
    margin: 10px 0; /* Weniger Abstand um den Container herum */
    padding: 10px; /* Reduziertes Innenabstand */
    font-size: 18px;
    font-weight: bold;
    color: #495057; /* Neutrale Schriftfarbe */
    background: #f9f9f9; /* Heller Hintergrund */
    border: 1px solid #ddd; /* Dezenter Rand */
    border-radius: 6px; /* Leichte Rundung */
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtiler Schatten */
    text-align: center; /* Zentrierter Text */
    line-height: 1.4; /* Reduzierte Zeilenhöhe */
}

.jury-points-summary p {
    margin: 5px 0; /* Weniger Abstand zwischen den Textzeilen */
}

/* Judged Photos Grid */
.aura-judged-photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Responsive grid */
    gap: 20px;
    padding: 20px;
}

/* Individual Judged Photo Item */
.judged-photo-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #f9f9f9; /* Light background */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Photo Container */
.photo-container {
    position: relative;
}

.photo-container img {
    width: 100%;
    height: auto;
    display: block;
}

/* Rating Details */
.rating-details {
    padding: 15px;
}

/* Total Jury Points */
.total-points {
    font-size: 16px;
    font-weight: bold;
    margin: 10px 0;
    color: #495057;
}

/* Rating Stars */
.ratings {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 15px 0;
}

.rating-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px solid #ddd;
}

.criterion {
    font-weight: bold;
    text-transform: capitalize;
}

.stars {
    font-size: 14px;
    color: #FFD700; /* Gold color for stars */
}

/* Filled Stars */
.star.filled {
    color: #FFD700; /* Gold */
}

.star {
    color: #ddd; /* Gray for empty stars */
}

/* Badge Styling */
.badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    color: #fff;
}

.badge.platinum { background: #e5e4e2; } /* Platinum */
.badge.gold { background: #FFD700; } /* Gold */
.badge.silver { background: #C0C0C0; } /* Silver */
.badge.bronze { background: #cd7f32; } /* Bronze */

/* Download Link */
.download-link {
    padding: 10px 15px;
    text-align: center;
    margin-top: 10px;
}

.download-link .button {
    display: flex; /* Flexbox für präzise Zentrierung */
    justify-content: center; /* Horizontale Zentrierung */
    align-items: center; /* Vertikale Zentrierung */
    width: 100%; /* Button über die gesamte Breite */
    padding: 10px 15px;
    background-color: #007bff; /* Button-Hintergrund */
    color: #fff; /* Weiße Schrift */
    text-decoration: none; /* Kein Unterstrich */
    border-radius: 5px; /* Abgerundete Ecken */
    font-size: 14px; /* Einheitliche Schriftgröße */
    transition: background-color 0.3s ease; /* Weicher Übergang */
    text-align: center; /* Zusätzliche Sicherheitsmaßnahme */
}

.download-link .button:hover {
    background-color: #0056b3; /* Dunklere Farbe beim Hover */
}

</style>
