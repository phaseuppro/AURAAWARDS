<?php

class AURA_Submissions {

    public static function render_submissions_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'aura-photo-awards'));
        }

        // Fetch submissions
        $contest_filter = isset($_GET['contest_id']) ? absint($_GET['contest_id']) : 0;
        $meta_query = array();

        if ($contest_filter) {
            $meta_query[] = array(
                'key' => '_contest_id',
                'value' => $contest_filter,
                'compare' => '=',
            );
        }

        $args = array(
            'post_type'      => 'photo_submission',
            'posts_per_page' => -1,
            'post_status'    => array('pending', 'reviewed'),
            'meta_query'     => $meta_query,
        );
        $submissions = new WP_Query($args);

        // Fetch contests for dropdown filter
        $contests = get_posts(array(
            'post_type' => 'contest',
            'post_status' => 'publish',
            'numberposts' => -1,
        ));

        ?>
        <div class="wrap">
            <h1><?php _e('Submissions', 'aura-photo-awards'); ?></h1>

            <!-- Contest Filter -->
            <form method="get" action="">
                <input type="hidden" name="page" value="aura-submissions">
                <select name="contest_id">
                    <option value=""><?php _e('All Contests', 'aura-photo-awards'); ?></option>
                    <?php foreach ($contests as $contest): ?>
                        <option value="<?php echo esc_attr($contest->ID); ?>" <?php selected($contest_filter, $contest->ID); ?>>
                            <?php echo esc_html($contest->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button">Filter</button>
            </form>

            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th style="width: 60px"><?php _e('Photo', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Title', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Category', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Contest', 'aura-photo-awards'); ?></th>
                        <th><?php _e('User', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Status', 'aura-photo-awards'); ?></th>
                        <th><?php _e('Actions', 'aura-photo-awards'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($submissions->have_posts()) {
                        while ($submissions->have_posts()) {
                            $submissions->the_post();
                            $user_id = get_post_field('post_author', get_the_ID());
                            $status = get_post_status();
                            $contest_id = get_post_meta(get_the_ID(), '_contest_id', true);
                            $contest_title = $contest_id ? get_the_title($contest_id) : __('No Contest', 'aura-photo-awards');
                            ?>
                            <tr>
                                <td>
                                    <?php 
                                    if (has_post_thumbnail()) {
                                        echo get_the_post_thumbnail(get_the_ID(), array(50, 50), array('style' => 'width: 50px; height: 50px; object-fit: cover; border-radius: 4px;'));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?php the_title(); ?></td>
                                <td><?php echo esc_html(get_post_meta(get_the_ID(), '_photo_category', true)); ?></td>
                                <td><?php echo esc_html($contest_title); ?></td>
                                <td><?php echo esc_html(get_userdata($user_id)->display_name); ?></td>
                                <td><?php echo esc_html(ucfirst($status)); ?></td>
                                <td>
                                    <?php if ($status === 'pending') { ?>
                                        <a href="<?php echo admin_url('admin.php?page=aura-judging&submission_id=' . get_the_ID()); ?>" class="button"><?php _e('Judge', 'aura-photo-awards'); ?></a>
                                    <?php } else { ?>
                                        <span><?php _e('Reviewed', 'aura-photo-awards'); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="7"><?php _e('No submissions found.', 'aura-photo-awards'); ?></td>
                        </tr>
                        <?php
                    }
                    wp_reset_postdata();
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
