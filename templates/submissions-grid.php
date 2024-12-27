<div class="aura-submissions-grid">
    <?php foreach ($judged_photos as $photo): 
        $judge_data = unserialize($photo->judge_data);
        $thumbnail_id = get_post_thumbnail_id($photo->ID);
        $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'large');
        $ratings = $judge_data['ratings'];
        
        // Zusätzliche Benutzerdaten abrufen
        $user_id = get_post_meta($photo->ID, 'user_id', true); // Benutzer-ID aus den Foto-Metadaten
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);
        $country = get_user_meta($user_id, 'country', true);
    ?>
        <div class="submission-item">
            <div class="submission-image">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($photo->post_title); ?>">
                <span class="badge <?php echo strtolower($judge_data['badge']); ?>"><?php echo esc_html($judge_data['badge']); ?></span>
            </div>
            <div class="submission-details">
                <h3><?php echo esc_html($photo->post_title); ?></h3>
                <!-- Hinzugefügte Benutzerinformationen -->
                <p>Submitted by: <?php echo esc_html($first_name . ' ' . $last_name); ?></p>
                <p>Country: <?php echo esc_html($country); ?></p>
                <div class="ratings-grid">
                    <div class="rating-item">Light: <?php echo esc_html($ratings['light']); ?>/5</div>
                    <div class="rating-item">Pose: <?php echo esc_html($ratings['pose']); ?>/5</div>
                    <div class="rating-item">Idea: <?php echo esc_html($ratings['idea']); ?>/5</div>
                    <div class="rating-item">Emotion: <?php echo esc_html($ratings['emotion']); ?>/5</div>
                    <div class="rating-item">Colors: <?php echo esc_html($ratings['colors']); ?>/5</div>
                </div>
                <div class="total-points">Total Points: <?php echo esc_html($judge_data['jury_points']); ?></div>
                <div class="judge-date">Judged on: <?php echo date('F j, Y', strtotime($judge_data['date'])); ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.aura-submissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.submission-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

.submission-image {
    position: relative;
}

.submission-image img {
    width: 100%;
    height: auto;
}

.badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 10px;
    border-radius: 4px;
    color: white;
    font-weight: bold;
}

.badge.platinum { background: #E5E4E2; }
.badge.gold { background: #FFD700; }
.badge.silver { background: #C0C0C0; }
.badge.bronze { background: #CD7F32; }

.submission-details {
    padding: 15px;
}

.ratings-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin: 10px 0;
}

.total-points {
    font-size: 1.2em;
    font-weight: bold;
    margin: 10px 0;
}

.judge-date {
    color: #666;
    font-size: 0.9em;
}
</style>
