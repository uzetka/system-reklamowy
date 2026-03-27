<?php
/**
 * Router frontowego panelu /panel-reklamy.
 *
 * Zadania:
 * - Przechwycenie URL strony "Panel reklamy" (slug: panel-reklamy)
 * - Sprawdzenie logowania użytkownika
 * - Obsługa POST dla ustawień (Cennik RADIO) przez SR_Frontend_Settings
 * - Załadowanie widoku includes/views/frontend-panel.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Router {

    /**
     * SR_Frontend_Router constructor.
     * Rejestruje hook template_redirect z niskim priorytetem (1),
     * żeby przejąć obsługę zanim inne wtyczki/snippety.
     */
    public function __construct() {
        add_action( 'template_redirect', [ $this, 'intercept_panel_url' ], 1 );
    }

    /**
     * Przechwycenie żądania dla /panel-reklamy.
     */
    public function intercept_panel_url() {
        // Nie nachodzimy na REST API ani inne konteksty.
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }

        // Reagujemy tylko na stronę "Panel reklamy".
        if ( ! is_page( 'panel-reklamy' ) ) {
            return;
        }

        // Wymagane logowanie – jak w snippecie.
        if ( ! is_user_logged_in() ) {
            auth_redirect(); // standardowy redirect na wp-login.php + powrót.
            exit;
        }


		// 🔹 1) Obsługa POST dla KONTRAHENTÓW – klasowo.
		if ( class_exists( 'SR_Frontend_Kontrahenci' ) ) {
			$kontrahenci = new SR_Frontend_Kontrahenci();
			$kontrahenci->handle_post();
		}

		// 🔹 2) Obsługa POST dla Ustawień (Cennik RADIO) – klasowo.
		if ( class_exists( 'SR_Frontend_Settings' ) ) {
			$settings = new SR_Frontend_Settings();
			$settings->handle_post();
		}

        // 🔹 3) Widok (router wewnątrz panelu).
        $view = isset( $_GET['view'] )
            ? sanitize_key( wp_unslash( $_GET['view'] ) )
            : 'dashboard';

        // Zmienna $view będzie dostępna w widoku.
        /** @var string $view */
        $view = $view;

        // Główna ramka HTML panelu (layout) – to, co wcześniej było w snippecie #10.
        include SR_PLUGIN_DIR . 'includes/views/frontend-panel.php';

        // Kończymy obsługę – inne hooki template_redirect (np. z Code Snippets) nie będą już miały szansy zareagować.
        exit;
    }
}