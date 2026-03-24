<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Export_Radio {

    public function handle_export_txt() {
        // TODO: generowanie pliku TXT wg specyfikacji.
        // Tymczasowo po prostu przekierujemy z komunikatem.
        wp_safe_redirect( admin_url( 'admin.php?page=sr-grafik-radio&export=todo' ) );
        exit;
    }
}