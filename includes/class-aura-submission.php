<?php

class AURA_Submission {
    public function __construct() {
        add_shortcode('aura_submit_photo', array($this, 'render_submission_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_aura_submit_photo', array($this, 'handle_photo_submission'));
        add_action('wp_ajax_get_user_credits', array($this, 'ajax_get_user_credits'));
        add_action('init', array($this, 'register_post_type'));
    }

    public function register_post_type() {
        register_post_type('photo_submission', array(
            'labels' => array(
                'name' => __('Photo Submissions', 'aura-photo-awards'),
                'singular_name' => __('Photo Submission', 'aura-photo-awards')
            ),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'supports' => array('title', 'editor', 'thumbnail'),  // Added thumbnail support
            'show_in_menu' => false, // Menü wird entfernt
            'menu_icon' => 'dashicons-format-image'
        ));
    }

    public function enqueue_assets() {
        wp_enqueue_style('aura-submission', AURA_PLUGIN_URL . 'assets/css/submission.css', array(), AURA_VERSION);
        wp_enqueue_script('aura-submission', AURA_PLUGIN_URL . 'assets/js/submission.js', array('jquery'), AURA_VERSION, true);

        wp_localize_script('aura-submission', 'auraSubmission', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aura_photo_submission'),
            'maxFileSize' => '6', // MB
            'minFileSize' => '1', // MB
            'minDimension' => '2048',
            'maxDimension' => '4000',
            'messages' => array(
                'fileTooLarge' => __('File size must be less than 6MB', 'aura-photo-awards'),
                'fileTooSmall' => __('File size must be at least 1MB', 'aura-photo-awards'),
                'invalidFormat' => __('Only JPG/JPEG files are allowed', 'aura-photo-awards'),
                'dimensionTooSmall' => __('Image must be at least 2048px on the longest side', 'aura-photo-awards'),
                'dimensionTooLarge' => __('Image must not exceed 4000px on the longest side', 'aura-photo-awards'),
                'uploadSuccess' => __('Photo uploaded successfully!', 'aura-photo-awards'),
            )
        ));
    }

    public function render_submission_form() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to submit photos.', 'aura-photo-awards') . '</p>';
        }

        $template_path = AURA_PLUGIN_PATH . 'public/submission-form.php';

        if (!file_exists($template_path)) {
            error_log('AURA: Template not found at: ' . $template_path);
            return "Template not found at: $template_path";
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    public function handle_photo_submission() {
        try {
            if (!check_ajax_referer('aura_photo_submission', 'nonce', false)) {
                throw new Exception(__('Security check failed', 'aura-photo-awards'));
            }

            if (!is_user_logged_in()) {
                throw new Exception(__('Please log in to submit photos', 'aura-photo-awards'));
            }

            $user_id = get_current_user_id();
            $credits = (int) get_user_meta($user_id, 'aura_credits', true);

            if ($credits < 1) {
                throw new Exception(__('Insufficient credits', 'aura-photo-awards'));
            }

            if (empty($_POST['contest_id'])) {
                throw new Exception(__('No contest selected', 'aura-photo-awards'));
            }

            $contest_id = absint($_POST['contest_id']);
            $category = sanitize_text_field($_POST['category']);

            // Validate contest existence
            $contest = get_post($contest_id);
            if (!$contest || $contest->post_type !== 'contest' || $contest->post_status !== 'publish') {
                throw new Exception(__('Invalid contest selected', 'aura-photo-awards'));
            }

            // Handle file upload and create submission
            $file = $_FILES['photo'];
            $post_id = $this->create_submission($file, $user_id, $contest_id, $category);

            $this->deduct_credit($user_id);

            wp_send_json_success([
                'message' => __('Photo submitted successfully', 'aura-photo-awards'),
            ]);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    private function create_submission($file, $user_id, $contest_id, $category) {
        $min_size = 1 * 1024 * 1024; // 1MB
        $max_size = 6 * 1024 * 1024; // 6MB

        if ($file['size'] < $min_size || $file['size'] > $max_size) {
            throw new Exception(__('Invalid file size', 'aura-photo-awards'));
        }

        if (!$this->is_valid_image($file)) {
            throw new Exception(__('Invalid image format', 'aura-photo-awards'));
        }

        $upload_dir = wp_upload_dir();
        $filename = wp_unique_filename($upload_dir['path'], sanitize_file_name($file['name']));
        $filepath = $upload_dir['path'] . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception(__('Failed to save uploaded file', 'aura-photo-awards'));
        }

        // Resize image
        $image = wp_get_image_editor($filepath);
        if (is_wp_error($image)) {
            throw new Exception(__('Image processing failed', 'aura-photo-awards'));
        }

        $image->resize(2048, 2048, false);
        $image->set_quality(90);
        $image->save($filepath);

        // Create attachment
        $attachment_id = wp_insert_attachment(
            array(
                'post_mime_type' => $file['type'],
                'post_title'     => sanitize_file_name($file['name']),
                'post_status'    => 'inherit',
            ),
            $filepath
        );

        if (is_wp_error($attachment_id)) {
            throw new Exception(__('Failed to create attachment', 'aura-photo-awards'));
        }

        wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $filepath));

        // Create post
        $post_id = wp_insert_post(array(
            'post_title'   => sanitize_text_field($_POST['title'] ?? ''),
            'post_content' => sanitize_textarea_field($_POST['description'] ?? ''),
            'post_status'  => 'pending',
            'post_type'    => 'photo_submission',
            'post_author'  => $user_id,
            'meta_input'   => array(
                '_contest_id' => $contest_id,
                '_photo_category' => $category,
                '_photo_url' => wp_get_attachment_url($attachment_id),
            ),
        ));

        if (is_wp_error($post_id)) {
            throw new Exception(__('Failed to create submission', 'aura-photo-awards'));
        }

        // Set thumbnail
        if (function_exists('wp_set_post_thumbnail')) {
            wp_set_post_thumbnail($post_id, $attachment_id);
        } else {
            update_post_meta($post_id, '_thumbnail_id', $attachment_id);
        }

        return $post_id;
    }

    private function ensure_admin_functions() {
        if (!function_exists('wp_set_post_thumbnail')) {
            require_once ABSPATH . 'wp-admin/includes/post.php';
        }
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
    }

    public function ajax_get_user_credits() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('User not logged in', 'aura-photo-awards'));
            return;
        }

        $user_id = get_current_user_id();
        wp_cache_delete($user_id, 'user_meta');
        $credits = get_user_meta($user_id, 'aura_credits', true);
        wp_send_json_success(['credits' => intval($credits)]);
    }

    private function is_valid_image($file) {
        $type = wp_check_filetype($file['name']);
        return in_array($type['ext'], ['jpg', 'jpeg']);
    }

    private function deduct_credit($user_id) {
        $credits = (int) get_user_meta($user_id, 'aura_credits', true);
        $new_credits = max(0, $credits - 1);
        update_user_meta($user_id, 'aura_credits', $new_credits);
        wp_cache_delete($user_id, 'user_meta');
        error_log('AURA: Credit deducted for user ID: ' . $user_id . ' | New Credits: ' . $new_credits);
    }
}
