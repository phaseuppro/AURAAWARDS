<?php
class AURA_Loader {
    protected $actions;
    protected $filters;

    public function __construct() {
        $this->actions = array();
        $this->filters = array();

        // Load required files
        $this->load_dependencies();

        // Initialize components
        $this->init_components();
    }

    private function load_dependencies() {
        // Core functionality
        require_once AURA_PLUGIN_PATH . 'includes/class-aura-submission.php';
        require_once AURA_PLUGIN_PATH . 'includes/class-aura-submission-admin.php';
        require_once AURA_PLUGIN_PATH . 'includes/class-aura-judging.php';
        require_once AURA_PLUGIN_PATH . 'includes/class-aura-contest.php';
        require_once AURA_PLUGIN_PATH . 'includes/class-aura-submissions.php';
    }

    private function init_components() {
        // Initialize submission handling
        new AURA_Submission();

        // Initialize admin functionalities
        new AURA_Submission_Admin();

        // Initialize judging system
        new AURA_Judging();
    }
}

// Initialize the loader
new AURA_Loader();

