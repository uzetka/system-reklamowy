<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Admin_Menu {

    public function register_menus() {

        add_menu_page(
            'System Reklamowy',
            'Reklama',
            'sr_manage_reklamy',
            'sr-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-megaphone',
            25
        );

        add_submenu_page(
            'sr-dashboard',
            'Kontrahenci',
            'Kontrahenci',
            'manage_options',
            'sr-kontrahenci',
            [ $this, 'render_kontrahenci' ]
        );

        add_submenu_page(
            'sr-dashboard',
            'Zlecenia RADIO',
            'Zlecenia RADIO',
            'manage_options',
            'sr-zlecenia-radio',
            [ $this, 'render_zlecenia_radio' ]
        );

        add_submenu_page(
            'sr-dashboard',
            'Zlecenia TV',
            'Zlecenia TV',
            'manage_options',
            'sr-zlecenia-tv',
            [ $this, 'render_zlecenia_tv' ]
        );

        add_submenu_page(
            'sr-dashboard',
            'Grafik RADIO',
            'Grafik RADIO',
            'manage_options',
            'sr-grafik-radio',
            [ $this, 'render_grafik_radio' ]
        );

        add_submenu_page(
            'sr-dashboard',
            'Ustawienia',
            'Ustawienia',
            'manage_options',
            'sr-settings',
            [ $this, 'render_settings' ]
        );
    }

    public function render_dashboard() {
        require SR_PLUGIN_DIR . 'admin/partials/view-dashboard.php';
    }

    public function render_kontrahenci() {
        require SR_PLUGIN_DIR . 'admin/partials/view-kontrahenci-list.php';
    }

    public function render_zlecenia_radio() {
        require SR_PLUGIN_DIR . 'admin/partials/view-zlecenia-radio-edit.php';
    }

    public function render_zlecenia_tv() {
        require SR_PLUGIN_DIR . 'admin/partials/view-zlecenia-tv-edit.php';
    }

    public function render_grafik_radio() {
        require SR_PLUGIN_DIR . 'admin/partials/view-grafik-radio.php';
    }

    public function render_settings() {
        require SR_PLUGIN_DIR . 'admin/partials/view-settings.php';
    }
}