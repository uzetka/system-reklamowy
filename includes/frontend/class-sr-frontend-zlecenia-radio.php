<?php
/**
 * Frontend - obsługa zleceń radiowych (stub).
 *
 * Plik tymczasowy, aby zapobiec Fatal error przy aktywacji wtyczki.
 * Jest wymagany przez includes/class-sr-frontend.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'SR_Frontend_Zlecenia_Radio' ) ) {

    class SR_Frontend_Zlecenia_Radio {

        /**
         * Konstruktor.
         * W przyszłości tutaj można dodać obsługę shortcode’ów, hooków itd.
         */
        public function __construct() {
            // Tymczasowo nic tutaj nie robimy.
        }
    }
}