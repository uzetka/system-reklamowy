<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Panel {

    public function __construct() {
        add_action( 'template_redirect', [ $this, 'handle_panel_page' ] );
        add_action( 'rest_api_init',    [ $this, 'register_rest_routes' ] );
    }

    /**
     * Główna obsługa strony /panel-reklamy
     */
    public function handle_panel_page() {
        if ( ! is_page( 'panel-reklamy' ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            auth_redirect();
            exit;
        }

        // Routing widoku
        $view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'dashboard';

        // Tutaj wkleisz HTML layoutu z dotychczasowego snippetu #10:
        // <html>…sidebar…main…sr_front_render_view($view)…</html>

        status_header( 200 );
        nocache_headers();

        $current_user = wp_get_current_user();
        $base_url     = get_permalink();

        // ⬇⬇⬇ WKLEJ TU body HTML z Twojego snippetu #10 (bez <?php add_action... etc.) ⬇⬇⬇
        include SR_PLUGIN_DIR . 'includes/views/frontend-panel.php';
        // ⬆⬆⬆ (zrobimy za chwilę plik frontend-panel.php)
        exit;
    }

    /**
     * REST dla frontu (nip-lookup, kontrahent-find) – przeniesione ze snippetów.
     */
    public function register_rest_routes() {
        // tu przeniesiemy definicje /sr/v1/nip-lookup i /sr/v1/kontrahent-find,
        // które dziś są w snippetach – na razie możemy je zostawić tam, ale docelowo tu.
    }
}