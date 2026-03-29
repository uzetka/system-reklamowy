<?php
/**
 * Router frontowego panelu /panel-reklamy.
 *
 * Zadania:
 * - Przechwycenie URL strony "Panel reklamy" (slug: panel-reklamy)
 * - Sprawdzenie logowania użytkownika
 * - Obsługa POST:
 *      - kontrahenci (SR_Frontend_Kontrahenci)
 *      - ustawienia / cennik RADIO (SR_Frontend_Settings)
 * - Wyrenderowanie widoku includes/views/frontend-panel.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Router {

    /**
     * Konstruktor.
     *
     * Rejestruje hook template_redirect z niskim priorytetem (1),
     * żeby przejąć obsługę zanim inne wtyczki/snippety.
     */
    public function __construct() {
        add_action( 'template_redirect', array( $this, 'intercept_panel_url' ), 1 );
    }

    /**
     * Przechwycenie żądania dla strony /panel-reklamy.
     */
    public function intercept_panel_url() {

        // 1) Nie nachodzimy na REST API ani inne specjalne konteksty.
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }

        // 2) Reagujemy tylko na stronę o slugu "panel-reklamy".
        if ( ! is_page( 'panel-reklamy' ) ) {
            return;
        }

        // 3) Wymagane logowanie – jak w pierwotnym snippecie.
        if ( ! is_user_logged_in() ) {
            // standardowy WP redirect na wp-login.php + powrót.
            auth_redirect();
            exit;
        }

        // 🔹 4) Obsługa POST dla KONTRAHENTÓW – wersja klasowa.
        if ( class_exists( 'SR_Frontend_Kontrahenci' ) ) {
            $kontrahenci = new SR_Frontend_Kontrahenci();
            $kontrahenci->handle_post();
        }

        // 🔹 5) Obsługa POST dla Ustawień (Cennik RADIO) – wersja klasowa.
        if ( class_exists( 'SR_Frontend_Settings' ) ) {
            $settings = new SR_Frontend_Settings();
            $settings->handle_post();
        }

        // 🔹 6) Router wewnątrz panelu – parametr "view".
        $view = isset( $_GET['view'] )
            ? sanitize_key( wp_unslash( $_GET['view'] ) )
            : 'dashboard';

        /** @var string $view */
        $view = $view;

        // 🔹 7) Główna ramka HTML panelu (layout).
        // Ten plik korzysta z sr_front_render_view( $view ) oraz sr_front_get_view_title( $view ).
        include SR_PLUGIN_DIR . 'includes/views/frontend-panel.php';

        // 8) Kończymy obsługę – inne hooki template_redirect (np. z Code Snippets)
        // nie będą już miały szansy zareagować.
        exit;
    }
}