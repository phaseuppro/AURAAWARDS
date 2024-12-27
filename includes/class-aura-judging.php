<?php
class AURA_Judging {
    private $badges = [
        'platinum' => 'platinum-badge.png',
        'gold' => 'gold-badge.png',
        'silver' => 'silver-badge.png',
        'bronze' => 'bronze-badge.png',
        'participant' => 'participant-badge.png'
    ];

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_aura_load_thumbnails', array($this, 'load_thumbnails'));
        add_action('wp_ajax_aura_save_judgment', array($this, 'save_judgment'));
        add_action('wp_ajax_aura_reject_submission', array($this, 'reject_submission'));
        
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'aura-photo-awards_page_aura-judging') {
            return;
        }

        wp_enqueue_style('aura-judging', AURA_PLUGIN_URL . 'assets/css/judging.css', array(), AURA_VERSION);
        wp_enqueue_script('aura-judging', AURA_PLUGIN_URL . 'assets/js/judging.js', array('jquery'), AURA_VERSION, true);

        wp_localize_script('aura-judging', 'auraJudging', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aura_judging_nonce'),
            'badgesUrl' => AURA_BADGES_URL
        ));
    }



    public static function render_judging_page() {
        ?>
        <div class="aura-judging-dashboard">
            <div class="aura-judging-filters">
                <label for="contest-filter"><?php _e('Filter by Contest', 'aura-photo-awards'); ?></label>
                <select id="contest-filter">
                    <option value="all"><?php _e('All Contests', 'aura-photo-awards'); ?></option>
                    <?php
                    $contests = get_posts(array(
                        'post_type' => 'contest',
                        'post_status' => 'publish',
                        'numberposts' => -1
                    ));
                    foreach ($contests as $contest) {
                        echo '<option value="' . esc_attr($contest->ID) . '">' . esc_html($contest->post_title) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="aura-judging-thumbnails">
                <h3><?php _e('Pending Submissions', 'aura-photo-awards'); ?></h3>
                <div id="thumbnail-list"></div>
            </div>
            <div class="aura-judging-main">
                <h3><?php _e('Selected Photo', 'aura-photo-awards'); ?></h3>
                <div id="selected-photo"></div>
            </div>
            <div class="aura-judging-controls">
                <h3><?php _e('Judging Panel', 'aura-photo-awards'); ?></h3>
                <button id="reject-submission" class="reject-button"><?php _e('Reject Submission', 'aura-photo-awards'); ?></button>
                <div id="rating-panel">
                    <?php
                    $criteria = array('light', 'pose', 'idea', 'emotion', 'colors');
                    foreach ($criteria as $criterion) {
                        echo '<div style="display: flex; align-items: center; justify-content: space-between;">';
                        echo '<label>' . esc_html(ucfirst($criterion)) . ':</label>';
                        echo '<div class="rating-stars" data-criterion="' . esc_attr($criterion) . '">';
                        for ($i = 1; $i <= 5; $i++) {
                            echo '<span class="rating-star" data-value="' . $i . '">☆</span>';
                        }
                        echo '</div></div>';
                    }
                    ?>
                </div>
                <div id="jury-summary" class="jury-points-badge">
                    <p id="jury-points"><?php _e('Jury Points: 0', 'aura-photo-awards'); ?></p>
                    <p id="badge-result"><?php _e('Assigned Badge: Participant', 'aura-photo-awards'); ?></p>
                </div>
                <div id="badge-panel">
                    <h4><?php _e('Badge Position', 'aura-photo-awards'); ?></h4>
                    <button data-position="top-left"><?php _e('Top Left', 'aura-photo-awards'); ?></button>
                    <button data-position="top-right"><?php _e('Top Right', 'aura-photo-awards'); ?></button>
                    <button data-position="bottom-left"><?php _e('Bottom Left', 'aura-photo-awards'); ?></button>
                    <button data-position="bottom-right"><?php _e('Bottom Right', 'aura-photo-awards'); ?></button>
                </div>
                <button id="judge-save"><?php _e('Judge & Save', 'aura-photo-awards'); ?></button>
            </div>
        </div>
        <?php
    }

    public function load_thumbnails() {
 
        check_ajax_referer('aura_judging_nonce', 'nonce');
        $contest_filter = isset($_POST['contest_id']) ? absint($_POST['contest_id']) : 0;

        $args = array(
            'post_type' => 'photo_submission',
            'posts_per_page' => -1,
            'post_status' => 'pending',
        );

        if ($contest_filter) {
            $args['meta_query'] = array(
                array(
                    'key' => '_contest_id',
                    'value' => $contest_filter,
                    'compare' => '='
                )
            );
        }

        $query = new WP_Query($args);
        $thumbnails = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $thumbnails[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                    'fullsize' => get_the_post_thumbnail_url(get_the_ID(), 'full'),
                );
            }
        }
        wp_send_json_success($thumbnails);
    }

    public function save_judgment() {
    check_ajax_referer('aura_judging_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to perform this action.', 'aura-photo-awards'));
    }

    $post_id = intval($_POST['post_id']);
    $judge_id = get_current_user_id();

    if (!$post_id || get_post_type($post_id) !== 'photo_submission') {
        wp_send_json_error(__('Invalid photo submission.', 'aura-photo-awards'));
    }

    $ratings = isset($_POST['ratings']) ? $_POST['ratings'] : array();
    $total_stars = 0;

    foreach ($ratings as $criterion => $value) {
        if (!in_array($criterion, array('light', 'pose', 'idea', 'emotion', 'colors')) || !is_numeric($value) || $value < 1 || $value > 5) {
            wp_send_json_error(__('Invalid rating value.', 'aura-photo-awards'));
        }
        $total_stars += intval($value);
        update_post_meta($post_id, "_aura_rating_{$criterion}", intval($value));
    }

    $jury_points = $total_stars * 4;
    $badge = $this->get_badge_type($jury_points);
    $badge_position = sanitize_text_field($_POST['position']);

    // Save judge-specific meta
    update_post_meta($post_id, '_aura_judge_' . $judge_id, array(
        'ratings' => $ratings,
        'jury_points' => $jury_points,
        'badge' => $badge,
        'date' => current_time('mysql')
    ));

    // Get original image
    $attachment_id = get_post_thumbnail_id($post_id);
    $original_path = get_attached_file($attachment_id);
    $badge_path = AURA_BADGES_PATH . $this->badges[strtolower($badge)];

    // Create and save badged image
    $badged_image = $this->apply_badge($original_path, $badge_path, $badge_position);
    $upload_dir = wp_upload_dir();
    $badged_filename = 'badged-' . basename($original_path);
    $badged_path = $upload_dir['path'] . '/' . $badged_filename;
    
    imagepng($badged_image, $badged_path);
    imagedestroy($badged_image);

    // Create attachment for badged image
    $badged_attachment = array(
        'post_mime_type' => 'image/png',
        'post_title' => 'Badged ' . get_the_title($post_id),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $badged_attach_id = wp_insert_attachment($badged_attachment, $badged_path, $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $badged_attach_data = wp_generate_attachment_metadata($badged_attach_id, $badged_path);
    wp_update_attachment_metadata($badged_attach_id, $badged_attach_data);

    // Update post meta
    update_post_meta($post_id, '_aura_jury_points', $jury_points);
    update_post_meta($post_id, '_aura_badge', $badge);
    update_post_meta($post_id, '_aura_badge_position', $badge_position);
    update_post_meta($post_id, '_original_image_id', $attachment_id);
    update_post_meta($post_id, '_badged_image_id', $badged_attach_id);

    wp_update_post(array(
        'ID' => $post_id,
        'post_status' => 'reviewed',
    ));

    wp_send_json_success(array(
        'message' => __('Judgment saved successfully.', 'aura-photo-awards'),
        'jury_points' => $jury_points,
        'badge' => $badge,
    ));
}


    private function apply_badge($image_path, $badge_path, $position) {
    // Verify source image exists
    if (!file_exists($image_path)) {
        throw new Exception('Source image not found: ' . $image_path);
    }
    
    // Verify badge exists
    if (!file_exists($badge_path)) {
        throw new Exception('Badge image not found: ' . $badge_path);
    }

    $image = imagecreatefromstring(file_get_contents($image_path));
    $badge = imagecreatefrompng($badge_path);
    
    if (!$image || !$badge) {
        throw new Exception('Failed to create image resources');
    }

    $img_width = imagesx($image);
    $img_height = imagesy($image);
    $badge_width = imagesx($badge);
    $badge_height = imagesy($badge);
        
        switch($position) {
            case 'top-left':
                $x = 20;
                $y = 20;
                break;
            case 'top-right':
                $x = $img_width - $badge_width - 20;
                $y = 20;
                break;
            case 'bottom-left':
                $x = 20;
                $y = $img_height - $badge_height - 20;
                break;
            case 'bottom-right':
                $x = $img_width - $badge_width - 20;
                $y = $img_height - $badge_height - 20;
                break;
        }
        
        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagecopy($image, $badge, $x, $y, 0, 0, $badge_width, $badge_height);
        imagedestroy($badge);
        
        return $image;
    }

    private function get_badge_type($points) {
        if ($points >= 90) return 'Platinum';
        if ($points >= 70) return 'Gold';
        if ($points >= 50) return 'Silver';
        if ($points >= 30) return 'Bronze';
        return 'Participant';
    }

    public function reject_submission() {
        check_ajax_referer('aura_judging_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'aura-photo-awards'));
        }

        $post_id = intval($_POST['post_id']);
        if (!$post_id || get_post_type($post_id) !== 'photo_submission') {
            wp_send_json_error(__('Invalid photo submission.', 'aura-photo-awards'));
        }

        $user_id = get_post_field('post_author', $post_id);
        $credits = (int) get_user_meta($user_id, 'aura_credits', true);
        update_user_meta($user_id, 'aura_credits', $credits + 1);

        wp_delete_post($post_id, true);

        wp_send_json_success(__('Submission rejected successfully.', 'aura-photo-awards'));
    }
}
