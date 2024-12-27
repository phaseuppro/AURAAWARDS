<?php
class AURA_Activator {
    
    public static function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . "aura_contests";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name text NOT NULL,
            description text NOT NULL,
            start_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            end_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Submissions table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aura_submissions (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) NOT NULL,
            contest_id BIGINT(20) NOT NULL DEFAULT 0,
            photo_url VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            title VARCHAR(100),
            description TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Scores table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aura_scores (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            submission_id BIGINT(20) NOT NULL,
            light_score TINYINT(1),
            pose_score TINYINT(1),
            idea_score TINYINT(1),
            emotion_score TINYINT(1),
            colors_score TINYINT(1),
            total_score DECIMAL(5,2),
            badge VARCHAR(20),
            badge_position VARCHAR(20),
            evaluated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Credits meta table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aura_user_credits (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) NOT NULL,
            credits INT NOT NULL DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        foreach ($sql as $query) {
            dbDelta($query);
            if ($wpdb->last_error) {
                error_log("AURA Plugin Table Creation Error: " . $wpdb->last_error);
            }
        }
    }

    public static function update_tables() {
        global $wpdb;

        // Add contest_id to submissions table
        $wpdb->query("ALTER TABLE {$wpdb->prefix}aura_submissions ADD COLUMN contest_id BIGINT(20) NOT NULL DEFAULT 0 AFTER user_id");

        // Add is_active to contests table
        $wpdb->query("ALTER TABLE {$wpdb->prefix}aura_contests ADD COLUMN is_active BOOLEAN DEFAULT 1 AFTER end_date");
    }
}

register_activation_hook(__FILE__, ['AURA_Activator', 'create_tables']);
register_activation_hook(__FILE__, ['AURA_Activator', 'update_tables']);
