<?php

if ( ! defined('ABSPATH') ) exit;

class SR_Frontend_Router {

    public function __construct() {
        add_action('template_redirect', [ $this, 'intercept_panel_url' ]);
    }

    public function intercept_panel_url() {

        if ( ! is_page('panel-reklamy') ) return;

        if ( ! is_user_logged_in() ) {
            auth_redirect();
            exit;
        }

        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'dashboard';

        // Główna ramka HTML panelu (layout)
        include SR_PLUGIN_DIR . 'includes/views/frontend-panel.php';

        exit;
    }
}