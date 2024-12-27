<?php
class AURA_Contest {
    public static function render_create_contest_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'aura-photo-awards'));
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('aura_create_contest')) {
            $title = sanitize_text_field($_POST['contest_title']);
            $description = sanitize_textarea_field($_POST['contest_description']);
            $start_date = sanitize_text_field($_POST['start_date']);
            $end_date = sanitize_text_field($_POST['end_date']);

            // Validate dates
            if (strtotime($start_date) > strtotime($end_date)) {
                echo '<div class="error"><p>' . __('Start date cannot be after end date.', 'aura-photo-awards') . '</p></div>';
            } else {
                $post_id = wp_insert_post(array(
                    'post_title'   => $title,
                    'post_content' => $description,
                    'post_status'  => 'publish',
                    'post_type'    => 'contest',
                    'meta_input'   => array(
                        'start_date' => $start_date,
                        'end_date'   => $end_date,
                    ),
                ));

                if (is_wp_error($post_id)) {
                    echo '<div class="error"><p>' . __('Error creating contest.', 'aura-photo-awards') . '</p></div>';
                } else {
                    echo '<div class="updated"><p>' . __('Contest created successfully.', 'aura-photo-awards') . '</p></div>';
                }
            }
        }

        // Render the form
        ?>
        <div class="wrap">
            <h1><?php _e('Create Contest', 'aura-photo-awards'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('aura_create_contest'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="contest_title"><?php _e('Contest Title', 'aura-photo-awards'); ?></label></th>
                        <td><input type="text" id="contest_title" name="contest_title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="contest_description"><?php _e('Description', 'aura-photo-awards'); ?></label></th>
                        <td><textarea id="contest_description" name="contest_description" class="large-text" rows="5" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="start_date"><?php _e('Start Date', 'aura-photo-awards'); ?></label></th>
                        <td><input type="date" id="start_date" name="start_date" required></td>
                    </tr>
                    <tr>
                        <th><label for="end_date"><?php _e('End Date', 'aura-photo-awards'); ?></label></th>
                        <td><input type="date" id="end_date" name="end_date" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Create Contest', 'aura-photo-awards'); ?></button>
                </p>
            </form>
        </div>
        <?php
    }

    public static function render_manage_contests_page() {
        $contests = get_posts(array(
            'post_type' => 'contest',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));

        global $wpdb;

        ?>
        <div class="wrap">
            <h1><?php _e('Manage Contests', 'aura-photo-awards'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Title', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Start Date', 'aura-photo-awards'); ?></th>
                        <th><?php _e('End Date', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Submissions', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Shortcode', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Actions', 'aura-photo-awards'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($contests)) : ?>
                        <?php foreach ($contests as $contest) : 
                            $contest_id = $contest->ID;

                            // Submissions zählen (aktualisierte Abfrage)
                            $submission_count = $wpdb->get_var(
                                $wpdb->prepare(
                                    "SELECT COUNT(p.ID) 
                                     FROM {$wpdb->prefix}posts AS p
                                     LEFT JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
                                     WHERE p.post_type = 'photo_submission' 
                                     AND p.post_status IN ('publish', 'reviewed') 
                                     AND pm.meta_key = '_contest_id' 
                                     AND pm.meta_value = %d",
                                    $contest_id
                                )
                            );

                            // Debugging für die Query
                            error_log("Contest ID: $contest_id - Submissions Count: $submission_count");
                            error_log("Submission Query: " . $wpdb->last_query);
                            ?>
                            <tr>
                                <td><?php echo esc_html($contest_id); ?></td>
                                <td><?php echo esc_html($contest->post_title); ?></td>
                                <td><?php echo esc_html(get_post_meta($contest_id, 'start_date', true)); ?></td>
                                <td><?php echo esc_html(get_post_meta($contest_id, 'end_date', true)); ?></td>
                                <td><?php echo intval($submission_count); ?></td>
                                <td>[aura_modern_gallery contest_id="<?php echo esc_html($contest_id); ?>"]</td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=edit_contest&contest_id=' . $contest_id); ?>" class="button"><?php _e('Edit', 'aura-photo-awards'); ?></a>
                                    <a href="<?php echo admin_url('admin.php?page=delete_contest&contest_id=' . $contest_id); ?>" class="button delete-contest" onclick="return confirm('<?php _e('Are you sure you want to delete this contest?', 'aura-photo-awards'); ?>')"><?php _e('Delete', 'aura-photo-awards'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7"><?php _e('No contests found.', 'aura-photo-awards'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

public static function aura_render_edit_contest_page() {
        // Sicherheitsprüfung
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'aura-photo-awards'));
        }

        // Contest ID abrufen
        $contest_id = isset($_GET['contest_id']) ? absint($_GET['contest_id']) : 0;
        if (!$contest_id || get_post_type($contest_id) !== 'contest') {
            wp_die(__('Invalid contest ID.', 'aura-photo-awards'));
        }

        $contest = get_post($contest_id);
        if (!$contest) {
            wp_die(__('Contest not found.', 'aura-photo-awards'));
        }

        $start_date = get_post_meta($contest_id, 'start_date', true);
        $end_date = get_post_meta($contest_id, 'end_date', true);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('edit_contest_nonce')) {
            $title = sanitize_text_field($_POST['contest_title']);
            $description = sanitize_textarea_field($_POST['contest_description']);
            $start_date = sanitize_text_field($_POST['start_date']);
            $end_date = sanitize_text_field($_POST['end_date']);

            if (strtotime($start_date) > strtotime($end_date)) {
                echo '<div class="error"><p>' . __('Start date cannot be after end date.', 'aura-photo-awards') . '</p></div>';
            } else {
                wp_update_post(array(
                    'ID' => $contest_id,
                    'post_title' => $title,
                    'post_content' => $description,
                ));

                update_post_meta($contest_id, 'start_date', $start_date);
                update_post_meta($contest_id, 'end_date', $end_date);

                echo '<div class="updated"><p>' . __('Contest updated successfully.', 'aura-photo-awards') . '</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Edit Contest', 'aura-photo-awards'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('edit_contest_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="contest_title"><?php _e('Contest Title', 'aura-photo-awards'); ?></label></th>
                        <td><input type="text" id="contest_title" name="contest_title" class="regular-text" value="<?php echo esc_attr($contest->post_title); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="contest_description"><?php _e('Description', 'aura-photo-awards'); ?></label></th>
                        <td><textarea id="contest_description" name="contest_description" class="large-text" rows="5" required><?php echo esc_textarea($contest->post_content); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="start_date"><?php _e('Start Date', 'aura-photo-awards'); ?></label></th>
                        <td><input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="end_date"><?php _e('End Date', 'aura-photo-awards'); ?></label></th>
                        <td><input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Update Contest', 'aura-photo-awards'); ?></button>
                </p>
            </form>
        </div>
        <?php
    }

    // Add Contest ID to Admin Table
    public static function add_contest_id_column($columns) {
        $columns['contest_id'] = __('Contest ID', 'aura-photo-awards');
        return $columns;
    }

    public static function display_contest_id_column($column, $post_id) {
        if ($column === 'contest_id') {
            echo esc_html($post_id);
        }
    }

    public static function init_hooks() {
        // Registrierung der bestehenden Hooks
        add_filter('manage_contest_posts_columns', array('AURA_Contest', 'add_contest_id_column'));
        add_action('manage_contest_posts_custom_column', array('AURA_Contest', 'display_contest_id_column'), 10, 2);

        // Registrierung des neuen Menüs für "Manage Credits"
        add_action('admin_menu', array('AURA_Contest', 'add_manage_credits_menu'));
    }

    public static function add_manage_credits_menu() {
        add_submenu_page(
            'aura-photo-awards', // Parent Menu
            __('Manage Credits', 'aura-photo-awards'),
            __('Manage Credits', 'aura-photo-awards'),
            'manage_options',
            'aura-manage-credits',
            array('AURA_Contest', 'render_manage_credits_page')
        );
    }

   public static function render_manage_credits_page() {
    // Berechtigungsprüfung
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to access this page.', 'aura-photo-awards'));
    }

    // Verarbeitung von Formularaktionen
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['credits_action'], $_POST['credits_amount'])) {
        $user_id = absint($_POST['user_id']);
        $action = sanitize_text_field($_POST['credits_action']);
        $amount = absint($_POST['credits_amount']);

        $current_credits = (int) get_user_meta($user_id, 'aura_credits', true);

        if ($action === 'add') {
            $new_credits = $current_credits + $amount;
        } elseif ($action === 'remove') {
            $new_credits = max(0, $current_credits - $amount);
        }

        update_user_meta($user_id, 'aura_credits', $new_credits);

        echo '<div class="updated"><p>' . __('Credits updated successfully.', 'aura-photo-awards') . '</p></div>';
    }

    // Benutzerliste anzeigen
    $users = get_users(array('meta_key' => 'aura_credits'));

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Manage Credits', 'aura-photo-awards') . '</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>' . esc_html__('User', 'aura-photo-awards') . '</th><th>' . esc_html__('Credits', 'aura-photo-awards') . '</th><th>' . esc_html__('Actions', 'aura-photo-awards') . '</th></tr></thead>';
    echo '<tbody>';

    foreach ($users as $user) {
        $credits = get_user_meta($user->ID, 'aura_credits', true);
        echo '<tr>';
        echo '<td>' . esc_html($user->display_name) . '</td>';
        echo '<td>' . intval($credits) . '</td>';
        echo '<td>';
        echo '<form method="post">';
        wp_nonce_field('update_credits_action', '_wpnonce');
        echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
        echo '<select name="credits_action"><option value="add">' . esc_html__('Add', 'aura-photo-awards') . '</option><option value="remove">' . esc_html__('Remove', 'aura-photo-awards') . '</option></select>';
        echo '<input type="number" name="credits_amount" min="1" required>';
        echo '<button type="submit" class="button">' . esc_html__('Update', 'aura-photo-awards') . '</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

    public function get_all_contests() {
        global $wpdb;

        // Check if the aura_contests table has data
        $table_name = $wpdb->prefix . "aura_contests";
        $query = "SELECT * FROM $table_name";
        $results = $wpdb->get_results($query, ARRAY_A);

        if (!empty($results)) {
            error_log('Contests from database: ' . print_r($results, true));
            return $results;
        }

        // If no data in table, check custom post type
        $args = array(
            'post_type' => 'contest',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );
        $query = new WP_Query($args);
        $contests = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $contests[] = array(
                    'id' => get_the_ID(),
                    'name' => get_the_title(),
                    'description' => get_the_content(),
                    'start_date' => get_post_meta(get_the_ID(), 'start_date', true),
                    'end_date' => get_post_meta(get_the_ID(), 'end_date', true),
                );
            }
        }
        wp_reset_postdata();

        error_log('Contests from post type: ' . print_r($contests, true));
        return $contests;
    }
}
