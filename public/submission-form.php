<?php
if (!defined('ABSPATH')) exit;

$user_credits = get_user_meta(get_current_user_id(), 'aura_credits', true);

// Get active contests
function get_active_contests() {
    $current_time = current_time('Y-m-d');
    $args = array(
        'post_type' => 'contest',
        'meta_query' => array(
            array(
                'key' => 'start_date',
                'value' => $current_time,
                'compare' => '<=',
                'type' => 'DATE',
            ),
            array(
                'key' => 'end_date',
                'value' => $current_time,
                'compare' => '>=',
                'type' => 'DATE',
            ),
        ),
    );
    return get_posts($args);
}
$contests = get_active_contests();
?>

<div class="aura-submission-form">
    <?php if (!is_user_logged_in()): ?>
        <p><?php echo wp_kses_post(__('Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to submit photos.', 'aura-photo-awards')); ?></p>
    <?php elseif ($user_credits < 1): ?>
        <div class="aura-credits-info">
            <?php echo wp_kses_post(__('You need credits to submit photos. <a href="' . get_permalink(get_option('aura_shop_page')) . '">Purchase credits</a>', 'aura-photo-awards')); ?>
        </div>
    <?php else: ?>
        <div class="aura-credits-info">
            <?php printf(esc_html__('Available Credits: %d', 'aura-photo-awards'), intval($user_credits)); ?>
        </div>
        
        <form id="aura-submission-form" enctype="multipart/form-data">
            <?php wp_nonce_field('aura_photo_submission', 'nonce'); ?>

            <!-- Contest Selection -->
            <div class="aura-form-group">
                <label for="contest_id"><?php esc_html_e('Select Contest', 'aura-photo-awards'); ?></label>
                <select id="contest_id" name="contest_id" required>
                    <option value=""><?php esc_html_e('Select a Contest', 'aura-photo-awards'); ?></option>
                    <?php foreach ($contests as $contest): ?>
                        <option value="<?php echo $contest->ID; ?>"><?php echo esc_html($contest->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="aura-form-group">
                <label for="photo-upload"><?php esc_html_e('Select Photo', 'aura-photo-awards'); ?></label>
                <input type="file" id="photo-upload" name="photo" accept=".jpg,.jpeg" required>
                <div class="file-requirements">
                    <small><?php esc_html_e('Image Requirements:', 'aura-photo-awards'); ?></small>
                    <ul>
                        <li><?php esc_html_e('Format: JPG/JPEG only', 'aura-photo-awards'); ?></li>
                        <li><?php esc_html_e('Size: 1MB to 6MB', 'aura-photo-awards'); ?></li>
                        <li><?php esc_html_e('Dimensions: min 2048px to max 4000px on longest side', 'aura-photo-awards'); ?></li>
                    </ul>
                </div>
            </div>

            <!-- Preview Image Container -->
            <div class="aura-form-group aura-submission-preview">
                <label><?php esc_html_e('Photo Preview:', 'aura-photo-awards'); ?></label>
                <img id="preview-image" src="" alt="<?php esc_attr_e('Preview', 'aura-photo-awards'); ?>" style="max-height: 100px; display: none; margin-top: 10px;">
            </div>
            
            <div class="aura-form-group">
                <label for="category"><?php esc_html_e('Category', 'aura-photo-awards'); ?></label>
                <select id="category" name="category" required>
                    <option value=""><?php esc_html_e('Select Category', 'aura-photo-awards'); ?></option>
                    <option value="maternity"><?php esc_html_e('Maternity', 'aura-photo-awards'); ?></option>
                    <option value="newborn"><?php esc_html_e('Newborn', 'aura-photo-awards'); ?></option>
                    <option value="children"><?php esc_html_e('Children', 'aura-photo-awards'); ?></option>
                    <option value="family"><?php esc_html_e('Family', 'aura-photo-awards'); ?></option>
                    <option value="ai-infused"><?php esc_html_e('AI-Infused', 'aura-photo-awards'); ?></option>
                    <option value="sitter"><?php esc_html_e('Sitter', 'aura-photo-awards'); ?></option>
                    <option value="siblings"><?php esc_html_e('Siblings', 'aura-photo-awards'); ?></option>
                    <option value="cakesmash & bathtub"><?php esc_html_e('Cakesmash & Bathtub', 'aura-photo-awards'); ?></option>
                </select>
            </div>
            
            <div id="aura-submission-messages"></div>
            <div class="aura-form-group">
                <div class="upload-progress" style="display: none;">
                    <div class="progress-bar"></div>
                </div>
            </div>
            
            <button type="submit" class="aura-submit-btn"><?php esc_html_e('Submit Photo', 'aura-photo-awards'); ?></button>
        </form>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    const fileInput = $('#photo-upload');
    const previewImage = $('#preview-image');

    fileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                previewImage.attr('src', event.target.result).show();
            };
            reader.readAsDataURL(file);
        } else {
            previewImage.hide();
        }
    });
});
</script>
