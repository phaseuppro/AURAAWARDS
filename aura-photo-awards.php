<?php 
/**
 * Plugin Name: AURA Photo Awards
 * Description: Photography contest platform with jury evaluation system
 * Version: 1.2.0
 * Author: Your Name
 * Text Domain: aura-photo-awards
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AURA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AURA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AURA_VERSION', '1.2.0');
define('AURA_BADGES_PATH', AURA_PLUGIN_PATH . 'assets/badges/');
define('AURA_BADGES_URL', AURA_PLUGIN_URL . 'assets/badges/');

// Add shortcodes
add_action('init', 'aura_register_shortcodes');

function aura_register_shortcodes() {
    add_shortcode('aura_submit_photo', array(new AURA_Submission(), 'render_submission_form'));
    add_shortcode('aura_modern_gallery', 'aura_render_modern_gallery'); // Sicherstellen, dass die Galerie-Shortcode-Registrierung erfolgt
    add_shortcode('aura_leaderboard', 'aura_render_leaderboard'); // Shortcode for Leaderboard
}

// Include the activator class first
require_once AURA_PLUGIN_PATH . 'includes/class-aura-activator.php';

// Plugin activation hook
register_activation_hook(__FILE__, 'aura_activate_plugin');

function aura_activate_plugin() {
    // Create custom tables
    AURA_Activator::create_tables();
    
    // Create upload directory
    $upload_dir = wp_upload_dir();
    $aura_dir = $upload_dir['basedir'] . '/aura-photos';
    if (!file_exists($aura_dir)) {
        wp_mkdir_p($aura_dir);
    }
    
    // Set default options
    if (!get_option('aura_credits_price')) {
        add_option('aura_credits_price', array(
            '1' => 10,
            '3' => 20,
            '5' => 30,
            '10' => 50
        ));
    }
}

// Plugin initialization
add_action('plugins_loaded', 'aura_init_plugin');

function aura_init_plugin() {
    // Load text domain
    load_plugin_textdomain('aura-photo-awards', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize components
    require_once AURA_PLUGIN_PATH . 'includes/class-aura-loader.php';
    require_once AURA_PLUGIN_PATH . 'includes/class-aura-judging.php';

    // Initialize WooCommerce integration if WooCommerce is active
    if (class_exists('WooCommerce')) {
        require_once AURA_PLUGIN_PATH . 'includes/class-aura-woocommerce.php';
        require_once AURA_PLUGIN_PATH . 'includes/class-aura-account-tabs.php';
        new AURA_WooCommerce();
        new AURA_Account_Tabs();
    }
}
function aura_handle_delete_contest() {
    // Sicherheitsprüfung
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to access this page.', 'aura-photo-awards'));
    }

    // Contest-ID abrufen
    $contest_id = isset($_GET['contest_id']) ? absint($_GET['contest_id']) : 0;
    if (!$contest_id || get_post_type($contest_id) !== 'contest') {
        wp_die(__('Invalid contest ID.', 'aura-photo-awards'));
    }

    // Contest löschen
    wp_delete_post($contest_id, true);

    // Weiterleitung nach dem Löschen
    wp_redirect(admin_url('admin.php?page=aura-manage-contests'));
    exit;
}

// Register custom post status
add_action('init', 'aura_register_post_statuses');

function aura_register_post_statuses() {
    register_post_status('reviewed', array(
        'label'                     => _x('Reviewed', 'post status', 'aura-photo-awards'),
        'public'                    => true, // Sichtbarkeit für Gäste sicherstellen
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Reviewed <span class="count">(%s)</span>', 'Reviewed <span class="count">(%s)</span>', 'aura-photo-awards')
    ));
}

// Add Admin Menu
add_action('admin_menu', 'aura_register_admin_menu');

function aura_register_admin_menu() {
    add_menu_page(
        __('AURA Photo Awards', 'aura-photo-awards'),
        __('AURA Photo Awards', 'aura-photo-awards'),
        'manage_options',
        'aura-photo-awards',
        null,
        'dashicons-camera',
        25
    );

    add_submenu_page(
        'aura-photo-awards',
        __('Manage User Credits', 'aura-photo-awards'),
        __('Manage Credits', 'aura-photo-awards'),
        'manage_options',
        'aura-manage-credits',
        array('AURA_Contest', 'render_manage_credits_page')
    );

    add_submenu_page(
        'aura-photo-awards',
        __('Create Contest', 'aura-photo-awards'),
        __('Create Contest', 'aura-photo-awards'),
        'manage_options',
        'aura-create-contest',
        array('AURA_Contest', 'render_create_contest_page')
    );

    add_submenu_page(
        'aura-photo-awards',
        __('Submissions', 'aura-photo-awards'),
        __('Submissions', 'aura-photo-awards'),
        'manage_options',
        'aura-submissions',
        array('AURA_Submissions', 'render_submissions_page')
    );

    add_submenu_page(
        'aura-photo-awards',
        __('Judging', 'aura-photo-awards'),
        __('Judging', 'aura-photo-awards'),
        'manage_options',
        'aura-judging',
        array('AURA_Judging', 'render_judging_page')
    );

    
    add_submenu_page(
    'aura-photo-awards',
    __('Manage Contests', 'aura-photo-awards'),
    __('Manage Contests', 'aura-photo-awards'),
    'manage_options',
    'aura-manage-contests',
    array('AURA_Contest', 'render_manage_contests_page')
    
    
    
);

    add_submenu_page(
        null, // Keine sichtbare Seite im Menü
        __('Edit Contest', 'aura-photo-awards'),
        __('Edit Contest', 'aura-photo-awards'),
        'manage_options',
        'edit_contest',
        array('AURA_Contest', 'aura_render_edit_contest_page')
    );

add_submenu_page(
    null, // Keine sichtbare Seite im Menü
    __('Delete Contest', 'aura-photo-awards'),
    __('Delete Contest', 'aura-photo-awards'),
    'manage_options',
    'delete_contest',
    'aura_handle_delete_contest'
);
}


// Hook into user registration to give new users 3 free credits
add_action('user_register', 'aura_give_free_credits_to_new_users', 10, 1);

function aura_give_free_credits_to_new_users($user_id) {
    // Assign 3 free credits to new users
    update_user_meta($user_id, 'aura_credits', 3);
}

// Hook into backend user creation to give new users 3 free credits
add_action('profile_update', 'aura_give_free_credits_on_admin_user_creation', 10, 2);

function aura_give_free_credits_on_admin_user_creation($user_id, $old_user_data) {
    // Check if the user doesn't already have the 'aura_credits' meta
    if (!metadata_exists('user', $user_id, 'aura_credits')) {
        update_user_meta($user_id, 'aura_credits', 3);
    }
}

// Shortcode for Leaderboard
function aura_render_leaderboard() {
    global $wpdb;
    $users = $wpdb->get_results(
        "SELECT u.ID AS user_id, u.display_name, COALESCE(SUM(pm.meta_value), 0) AS total_points
         FROM {$wpdb->users} u
         LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_aura_jury_points'
         WHERE p.post_status IN ('reviewed', 'publish')
         GROUP BY u.ID
         ORDER BY total_points DESC
         LIMIT 10",
        ARRAY_A
    );

    ob_start();
    ?>
    <div class="aura-leaderboard">
        <h3>Leaderboard</h3>
        <table>
            <thead>
                <tr>
                    <th style="background-color: #ff591f; color: #fff;">Rank</th>
                    <th style="background-color: #ff591f; color: #fff;">Photographer</th>
                    <th style="background-color: #ff591f; color: #fff;">Country</th>
                    <th style="background-color: #ff591f; color: #fff;">Jury Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php $rank = 1; foreach ($users as $user): 
                        $first_name = get_user_meta($user['user_id'], 'first_name', true);
                        $last_name = get_user_meta($user['user_id'], 'last_name', true);
                        $country = get_user_meta($user['user_id'], 'billing_country', true);
                        if (class_exists('WooCommerce') && function_exists('wc_get_countries')) {
                            $countries = wc_get_countries();
                            $country = isset($countries[$country]) ? $countries[$country] : $country;
                        }
                        $full_name = trim($first_name . ' ' . $last_name);
                        if (empty($full_name)) {
                            $full_name = $user['display_name'];
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($rank++); ?></td>
                            <td><?php echo esc_html($full_name); ?></td>
                            <td><?php echo esc_html($country); ?></td>
                            <td><?php echo esc_html($user['total_points']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <style>
        .aura-leaderboard {
            margin: 20px 0;
        }
        .aura-leaderboard table {
            width: 100%;
            border-collapse: collapse;
        }
        .aura-leaderboard th, .aura-leaderboard td {
            border: 1px solid #ddd;
            padding: 8px;
        }
    </style>
    <?php
    return ob_get_clean();
}

// Shortcode für moderne Galerie mit Overlay
function aura_render_modern_gallery($atts) {
    $atts = shortcode_atts(
        array('contest_id' => 0),
        $atts,
        'aura_modern_gallery'
    );
    
    $contest_id = intval($atts['contest_id']);
    
    $args = array(
        'post_type' => 'photo_submission',
        'posts_per_page' => -1,
        'post_status' => 'reviewed',
        'meta_query' => array(),
        'no_found_rows' => true, // Beschleunigt die Query
        'update_post_meta_cache' => false, // Kein Meta-Cache erforderlich
        'update_post_term_cache' => false, // Kein Taxonomie-Cache erforderlich
    );

    if ($contest_id > 0) {
        $args['meta_query'][] = array(
            'key'     => '_contest_id',
            'value'   => $contest_id,
            'compare' => '='
        );
    }
    
    $query = new WP_Query($args);

    ob_start();
    ?>
    <div class="aura-modern-gallery">
    <?php 
    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            $image_id = get_post_meta(get_the_ID(), '_badged_image_id', true);
            $image_url = wp_get_attachment_url($image_id);
            $badge = get_post_meta(get_the_ID(), '_aura_badge', true) ?: 'N/A';
            $jury_points = get_post_meta(get_the_ID(), '_aura_jury_points', true) ?: 0;

            $user_id = get_post_field('post_author', get_the_ID());
            $first_name = get_user_meta($user_id, 'first_name', true);
            $last_name = get_user_meta($user_id, 'last_name', true);
            $country = get_user_meta($user_id, 'billing_country', true);

            // Dynamische Rand-Steuerung
            $has_border = false; // Ändere auf true, um den Rand hinzuzufügen

            // Bildmetadaten abrufen, um die Orientierung zu bestimmen
            $image_metadata = wp_get_attachment_metadata($image_id);
            $orientation = ($image_metadata['width'] > $image_metadata['height']) ? 'landscape' : 'portrait';

            // Dynamische Zoomklasse
            $zoom_class = 'zoom-in'; // Ändere auf 'zoom-out', um das Bild zu verkleinern
            ?>
            <div class="gallery-item <?php echo $has_border ? 'has-border' : ''; ?> <?php echo $orientation; ?> <?php echo $zoom_class; ?>">
                <div class="image-wrapper">
                    <a href="<?php echo esc_url($image_url); ?>" data-fancybox="gallery">
                        <img src="<?php echo esc_url($image_url); ?>" 
                             alt="<?php the_title(); ?>" 
                             class="<?php echo $orientation; ?>">
                    </a>
                    <div class="overlay">
                        <p class="user-info badge-info">Badge: <?php echo esc_html($badge); ?></p>
                        <p class="user-info jury-points">Jury Points: <?php echo esc_html($jury_points); ?></p>
                        <p class="user-info photographer">Photographer: <?php echo esc_html($first_name . ' ' . $last_name); ?></p>
                        <p class="user-info country">Country: <?php echo esc_html($country); ?></p>
                    </div>
                </div>
            </div>
            <?php
        endwhile;
    else :
        ?>
        <p>No judged submissions found.</p>
    <?php endif; ?>
</div>
<style>
    .aura-modern-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Grid-Layout */
    gap: 10px; /* Gleicher Abstand zwischen den Bildern */
    padding: 10px; /* Abstand zwischen dem Grid und dem Rand des Containers */
    box-sizing: border-box; /* Sorgt dafür, dass das Padding korrekt berechnet wird */
}

.gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 0px;
    background-color: transparent; /* Standard ohne Hintergrundfarbe */
    aspect-ratio: 1 / 1; /* 1:1-Verhältnis */
}

/* Klasse für Rand */
.gallery-item.has-border {
    background-color: #ff591f; /* Hintergrundfarbe, wenn Rand aktiv ist */
    border: 2px solid #ff591f; /* Optionaler Rand */
}

.gallery-item .image-wrapper {
    width: 100%;
    height: 100%;
    display: flex; /* Zentriert Bild im Container */
    align-items: center;
    justify-content: center;
    overflow: hidden; /* Verhindert Überlauf */
}

/* Allgemeines Styling für Bilder */
.gallery-item img {
    object-fit: cover; /* Füllt den Container */
    object-position: center; /* Zentriert das Bild */
    transform-origin: center; /* Standardmäßig Fokus auf Zentrum */
    transition: transform 0.3s ease-in-out; /* Weicher Übergang beim Zoomen */
}

/* Zoom für Landscape-Bilder */
.gallery-item.landscape.zoom-in img {
    transform: scale(1.5); /* Vergrößert Landscape-Bilder */
}

.gallery-item.landscape.zoom-out img {
    transform: scale(0.0); /* Verkleinert Landscape-Bilder */
}

/* Zoom für Portrait-Bilder */
.gallery-item.portrait.zoom-in img {
    transform: scale(1.0); /* Vergrößert Portrait-Bilder */
}

.gallery-item.portrait.zoom-out img {
    transform: scale(0.0); /* Verkleinert Portrait-Bilder */
}

.overlay {
    position: absolute;
    bottom: 5px; /* Verschiebt das Overlay 10px nach oben */
    left: 10px; /* Verschiebt das Overlay 10px nach rechts */
    width: calc(100% - 20px); /* Passt die Breite an, um den Rand auszugleichen */
    height: 38%; /* Overlay bleibt 1/3 des Bildes */
    padding: 10px;
    background: rgba(0, 0, 0, 0.7); /* Halbtransparentes Schwarz */
    color: #fff;
    box-sizing: border-box;
    text-align: left;
}

.overlay .user-info {
    margin: 4px 0; /* Abstand zwischen den Zeilen */
    font-size: 14px; /* Schriftgröße der Benutzerinformationen */
    line-height: 1.4; /* Optimale Zeilenhöhe */
    position: relative; /* Ermöglicht Verschiebung mit top/left/right/bottom */
    top: 0; /* Standardwert */
    left: 0; /* Standardwert */
}

.overlay .user-info.move-up {
    top: -10px; /* Verschiebt den Text 10px nach oben */
}

.overlay .user-info.move-down {
    top: 10px; /* Verschiebt den Text 10px nach unten */
}

.overlay .user-info.move-left {
    left: -10px; /* Verschiebt den Text 10px nach links */
}

.overlay .user-info.move-right {
    left: 10px; /* Verschiebt den Text 10px nach rechts */
}

.overlay .badge-info {
    font-weight: bold; /* Badge-Information fett darstellen */
    color: #ff591f; /* Goldene Schriftfarbe für Badge */
}

.overlay .jury-points {
    font-size: 14px; /* Etwas kleinere Schriftgröße */
}

.overlay .photographer, .overlay .country {
    font-size: 14px; /* Etwas kleinere Schriftgröße */
    font-weight: normal;
}

</style>

    <?php
    wp_reset_postdata(); // Query zurücksetzen
    return ob_get_clean();
}

// Shortcode registrieren
add_action('init', function() {
    add_shortcode('aura_modern_gallery', 'aura_render_modern_gallery');
});
// Verhindern, dass Cache-Plugins Shortcodes blockieren
add_filter('do_shortcode_tag', function($output, $tag) {
    if ($tag === 'aura_modern_gallery') {
        return do_shortcode($output); // Erneut ausführen
    }
    return $output;
}, 10, 2);