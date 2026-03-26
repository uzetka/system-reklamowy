<?php

if ( ! defined('ABSPATH') ) exit;

class SR_Frontend {

    public function __construct() {

        // Router /panel-reklamy
        require_once __DIR__ . '/frontend/class-sr-frontend-router.php';

        // Widoki modów
        require_once __DIR__ . '/frontend/class-sr-frontend-settings.php';
        require_once __DIR__ . '/frontend/class-sr-frontend-kontrahenci.php';
        require_once __DIR__ . '/frontend/class-sr-frontend-zlecenia-radio.php';

        // Wspólne helpery (np. toast, sprawdzanie uprawnień)
        require_once __DIR__ . '/frontend/class-sr-frontend-shared.php';

        new SR_Frontend_Router();
    }
}

new SR_Frontend();