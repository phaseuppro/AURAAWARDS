<?php
$user_id = get_current_user_id();
$submissions = get_posts(array(
    'post_type' => 'photo_submission',
    'author' => $user_id,
    'posts_per_page' => -1
));
?>
<div class="aura-submissions-grid">
    <?php if ($submissions): ?>
        <?php foreach($submissions as $submission): ?>
            <div class="submission-item">
                <img src="<?php echo esc_url($photo->badge_image_url); ?>" alt="<?php echo esc_attr($photo->post_title); ?>">
                <div class="submission-details">
                    <h3><?php echo get_the_title($submission->ID); ?></h3>
                    <?php if($ratings = get_post_meta($submission->ID, '_aura_ratings', true)): ?>
                        <div class="ratings-display">
                            <?php foreach($ratings as $criterion => $score): ?>
                                <div class="rating-row">
                                    <span><?php echo ucfirst($criterion); ?>:</span>
                                    <?php echo str_repeat('★', $score) . str_repeat('☆', 5-$score); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No submissions found.</p>
    <?php endif; ?>
</div>
