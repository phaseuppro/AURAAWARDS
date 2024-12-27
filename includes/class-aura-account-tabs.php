<?php
class AURA_Account_Tabs {
    public function __construct() {
        add_filter('woocommerce_account_menu_items', array($this, 'add_custom_tabs'));
        add_action('init', array($this, 'add_endpoints'));
        add_action('woocommerce_account_aura-credits_endpoint', array($this, 'credits_content'));
        add_action('woocommerce_account_aura-judged_endpoint', array($this, 'judged_content'));
        add_action('woocommerce_account_participate_endpoint', array($this, 'submissions_content'));
        add_filter('query_vars', array($this, 'add_query_vars'));

        error_log('AURA Account Tabs Initialized');
    }

    public function add_custom_tabs($items) {
        error_log('Adding Custom Tabs');
        $new_items = array();
        foreach ($items as $key => $item) {
            $new_items[$key] = $item;
            if ($key === 'dashboard') {
                $new_items['aura-credits'] = 'Credits';
                $new_items['aura-judged'] = 'Judged Photos';
                $new_items['participate'] = 'Participate';
            }
        }
        return $new_items;
    }

    public function add_endpoints() {
        error_log('Adding Endpoints');
        add_rewrite_endpoint('aura-credits', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('aura-judged', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('participate', EP_ROOT | EP_PAGES);

        flush_rewrite_rules(); // Erzwungenes Aktualisieren der Regeln

        if (get_option('aura_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('aura_flush_rewrite_rules');
        }
    }

    public function add_query_vars($vars) {
        error_log('Adding Query Vars');
        $vars[] = 'aura-credits';
        $vars[] = 'aura-judged';
        $vars[] = 'participate';
        return $vars;
    }

    public function credits_content() {
        error_log('Rendering Credits Content');
        $user_id = get_current_user_id();
        $available_credits = get_user_meta($user_id, 'aura_credits', true);
        include AURA_PLUGIN_PATH . 'templates/myaccount/credits.php';
    }

    public function judged_content() {
        error_log('Rendering Judged Photos Content');
        $user_id = get_current_user_id();
        global $wpdb;

        $judged_photos = $wpdb->get_results($wpdb->prepare("
    SELECT p.*, 
           pm_badge.meta_value as badge_type,
           pm_badged_img.meta_value as badged_image_id,
           pm_judge.meta_value as judge_data
    FROM {$wpdb->posts} p 
    LEFT JOIN {$wpdb->postmeta} pm_badge ON p.ID = pm_badge.post_id AND pm_badge.meta_key = '_aura_badge'
    LEFT JOIN {$wpdb->postmeta} pm_badged_img ON p.ID = pm_badged_img.post_id AND pm_badged_img.meta_key = '_badged_image_id'
    LEFT JOIN {$wpdb->postmeta} pm_judge ON p.ID = pm_judge.post_id AND pm_judge.meta_key = '_aura_judge_1'
    WHERE p.post_type = 'photo_submission'
    AND p.post_status = 'reviewed'
    AND p.post_author = %d
    ORDER BY p.post_date DESC
", $user_id));

        error_log('Judged Photos: ' . print_r($judged_photos, true));
        include AURA_PLUGIN_PATH . 'templates/myaccount/judged-photos-grid.php';
    }

    public function submissions_content() {
        error_log('Rendering Submissions Content');
        error_log('Submissions endpoint accessed successfully.');
        if (file_exists(AURA_PLUGIN_PATH . 'public/submission-form.php')) {
            error_log('Submission form found.');
            include AURA_PLUGIN_PATH . 'public/submission-form.php';
        } else {
            error_log('Submission form NOT found. Path: ' . AURA_PLUGIN_PATH . 'public/submission-form.php');
        }
    }
}