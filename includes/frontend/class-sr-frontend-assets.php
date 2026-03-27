<?php
/**
 * Frontend – rejestracja i enqueue assetów (CSS/JS) panelu /panel-reklamy.
 *
 * Ta klasa dba o to, żeby:
 * - CSS panelu (sr-panel.css) był ładowany tylko tam, gdzie trzeba,
 * - JS ustawień (sr-settings.js) działał na stronie "Panel reklamy".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Assets {

    /**
     * Konstruktor – podpina hook na wp_enqueue_scripts.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
    }

    /**
     * Enqueue CSS i JS dla panelu /panel-reklamy.
     */
    public function enqueue_frontend_assets(): void {
        // Ograniczamy się tylko do strony "Panel reklamy" (slug: panel-reklamy).
        if ( ! is_page( 'panel-reklamy' ) ) {
            return;
        }

        // 🔹 CSS panelu (jeśli plik istnieje)
        $css_path = SR_PLUGIN_DIR . 'assets/css/sr-panel.css';
        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'sr-panel',
                SR_PLUGIN_URL . 'assets/css/sr-panel.css',
                [],
                defined( 'SR_VERSION' ) ? SR_VERSION : null
            );
        }

        // 🔹 JS – ustawienia (Cennik RADIO, toasty, modale)
        $js_path = SR_PLUGIN_DIR . 'assets/js/sr-settings.js';
        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'sr-settings',
                SR_PLUGIN_URL . 'assets/js/sr-settings.js',
                [],
                defined( 'SR_VERSION' ) ? SR_VERSION : null,
                true // w stopce
            );
        }
		
		
		// 🔹 JS – Kontrahenci (walidacja, GUS, toast)
		$js_kontr = SR_PLUGIN_DIR . 'assets/js/sr-kontrahenci.js';
		if ( file_exists( $js_kontr ) ) {
			wp_enqueue_script(
				'sr-kontrahenci',
				SR_PLUGIN_URL . 'assets/js/sr-kontrahenci.js',
				[],
				defined( 'SR_VERSION' ) ? SR_VERSION : null,
				true
			);
		}

        // Jeśli w przyszłości będziesz potrzebował przekazywać dane z PHP do JS,
        // możesz odkomentować wp_localize_script i wstrzyknąć np. URL REST API
        // lub nonce:
        //
        // if ( wp_script_is( 'sr-settings', 'enqueued' ) ) {
        //     wp_localize_script(
        //         'sr-settings',
        //         'SRSettings',
        //         [
        //             'restUrl' => esc_url_raw( rest_url( 'sr/v1/' ) ),
        //             'nonce'   => wp_create_nonce( 'wp_rest' ),
        //         ]
        //     );
        // }
    }
}