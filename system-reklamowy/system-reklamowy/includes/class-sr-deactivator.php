<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Deactivator {

    public static function deactivate() {
        // np. wyrejestrowanie cronów, jeśli będziesz używać
        // wp_clear_scheduled_hook( 'sr_some_cron_event' );
        flush_rewrite_rules();
    }
}