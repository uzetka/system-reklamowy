<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Harmonogram {

    public function handle_generate_schedule() {
        // TODO: logika generowania harmonogramu emisji.
        // Tymczasowa zwrotka, żeby nie było 500:
        wp_safe_redirect( admin_url( 'admin.php?page=sr-zlecenia-radio&schedule=ok' ) );
        exit;
    }
}