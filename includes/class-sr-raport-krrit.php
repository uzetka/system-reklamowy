<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Raport_KRRIT {

    public function handle_generate_pdf() {
        // TODO: generowanie PDF
        // Tymczasowe przekierowanie:
        wp_safe_redirect( admin_url( 'admin.php?page=sr-grafik-radio&raport=todo' ) );
        exit;
    }
}