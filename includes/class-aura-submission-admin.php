<?php

class AURA_Submission_Admin {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_rating_metabox'));
        add_action('save_post', array($this, 'save_submission_ratings'));
        add_filter('manage_photo_submission_posts_columns', array($this, 'add_columns'));
        add_action('manage_photo_submission_posts_custom_column', array($this, 'populate_columns'), 10, 2);
        add_action('admin_head', array($this, 'add_thumbnail_styles'));
    }

    public function add_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['thumbnail'] = __('Photo', 'aura-photo-awards');
        $new_columns['title'] = $columns['title'];
        $new_columns['aura_total_rating'] = __('Total Rating', 'aura-photo-awards');
        return $new_columns;
    }

    public function populate_columns($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(50, 50));
                } else {
                    echo '—';
                }
                break;
            case 'aura_total_rating':
                $total = get_post_meta($post_id, '_aura_total_rating', true);
                echo esc_html($total ? $total : __('Not Rated', 'aura-photo-awards'));
                break;
        }
    }

    public function add_thumbnail_styles() {
        echo '<style>
            .column-thumbnail { width: 60px; }
            .column-thumbnail img { 
                width: 50px; 
                height: 50px; 
                object-fit: cover;
                border-radius: 4px;
            }
        </style>';
    }

    public function add_rating_metabox() {
        add_meta_box(
            'aura_submission_rating',
            __('Submission Ratings', 'aura-photo-awards'),
            array($this, 'render_rating_metabox'),
            'photo_submission',
            'side',
            'high'
        );
    }

    public function render_rating_metabox($post) {
        $criteria = array('light', 'pose', 'idea', 'emotion', 'colors');
        $ratings = array();

        foreach ($criteria as $criterion) {
            $ratings[$criterion] = get_post_meta($post->ID, "_aura_rating_{$criterion}", true);
        }

        echo '<div class="aura-rating-fields">';
        foreach ($criteria as $criterion) {
            $value = isset($ratings[$criterion]) ? intval($ratings[$criterion]) : 0;
            echo '<label for="aura-rating-' . esc_attr($criterion) . '">' . esc_html(ucfirst($criterion)) . ':</label>';
            echo '<select name="aura_rating_' . esc_attr($criterion) . '" id="aura-rating-' . esc_attr($criterion) . '">';
            echo '<option value="0">' . __('Select', 'aura-photo-awards') . '</option>';
            for ($i = 1; $i <= 5; $i++) {
                echo '<option value="' . esc_attr($i) . '" ' . selected($value, $i, false) . '>' . esc_html($i) . '</option>';
            }
            echo '</select><br>';
        }
        echo '</div>';

        wp_nonce_field('aura_save_ratings', 'aura_ratings_nonce');
    }

    public function save_submission_ratings($post_id) {
        if (!isset($_POST['aura_ratings_nonce']) || !wp_verify_nonce($_POST['aura_ratings_nonce'], 'aura_save_ratings')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ('photo_submission' !== get_post_type($post_id)) {
            return;
        }

        $criteria = array('light', 'pose', 'idea', 'emotion', 'colors');
        $total_rating = 0;

        foreach ($criteria as $criterion) {
            if (isset($_POST["aura_rating_{$criterion}"])) {
                $rating = intval($_POST["aura_rating_{$criterion}"]);
                if ($rating < 1 || $rating > 5) {
                    continue;
                }
                update_post_meta($post_id, "_aura_rating_{$criterion}", $rating);
                $total_rating += $rating;
            }
        }

        update_post_meta($post_id, '_aura_total_rating', $total_rating);
    }
}

new AURA_Submission_Admin();
