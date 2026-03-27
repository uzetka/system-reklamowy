<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend {

    public function __construct() {
        // Router /panel-reklamy
        require_once __DIR__ . '/frontend/class-sr-frontend-router.php';

        // Panel – router widoków (dashboard, kontrahenci, zlecenia, ustawienia)
        require_once __DIR__ . '/frontend/class-sr-frontend-panel.php';

        // Ustawienia (logika + widoki)
        if ( file_exists( __DIR__ . '/frontend/class-sr-frontend-settings.php' ) ) {
            require_once __DIR__ . '/frontend/class-sr-frontend-settings.php';
        }

        // Kontrahenci / Zlecenia RADIO – na przyszłość (gdy przeniesiemy ze snippets)
        if ( file_exists( __DIR__ . '/frontend/class-sr-frontend-kontrahenci.php' ) ) {
            require_once __DIR__ . '/frontend/class-sr-frontend-kontrahenci.php';
        }
        if ( file_exists( __DIR__ . '/frontend/class-sr-frontend-zlecenia-radio.php' ) ) {
            require_once __DIR__ . '/frontend/class-sr-frontend-zlecenia-radio.php';
        }

        // Wspólne helpery (jeśli istnieją)
        if ( file_exists( __DIR__ . '/frontend/class-sr-frontend-shared.php' ) ) {
            require_once __DIR__ . '/frontend/class-sr-frontend-shared.php';
        }

        // Assety (CSS/JS dla frontowego panelu)
        if ( file_exists( __DIR__ . '/frontend/class-sr-frontend-assets.php' ) ) {
            require_once __DIR__ . '/frontend/class-sr-frontend-assets.php';
            new SR_Frontend_Assets();
        }

        // Start routera.
        new SR_Frontend_Router();
    }
}

new SR_Frontend();