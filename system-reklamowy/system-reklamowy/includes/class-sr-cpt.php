<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_CPT {
    public function register_post_types() {
        // Kontrahenci
        register_post_type( 'sr_kontrahent', [
            'labels' => [
                'name'               => 'Kontrahenci',
                'singular_name'      => 'Kontrahent',
                'add_new'            => 'Dodaj kontrahenta',
                'add_new_item'       => 'Dodaj nowego kontrahenta',
                'edit_item'          => 'Edytuj kontrahenta',
                'new_item'           => 'Nowy kontrahent',
                'view_item'          => 'Zobacz kontrahenta',
                'search_items'       => 'Szukaj kontrahentów',
                'not_found'          => 'Brak kontrahentów',
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false, // własne menu w SR_Admin_Menu
            'supports'      => [ 'title' ],
            'capability_type'=> 'post',
        ] );

        // Zlecenia RADIO
        register_post_type( 'sr_zlecenie_radio', [
            'labels' => [
                'name'          => 'Zlecenia RADIO',
                'singular_name' => 'Zlecenie RADIO',
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => [ 'title' ],
            'capability_type'=> 'post',
        ] );

        // Zlecenia TV
        register_post_type( 'sr_zlecenie_tv', [
            'labels' => [
                'name'          => 'Zlecenia TV',
                'singular_name' => 'Zlecenie TV',
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => false,
            'supports'      => [ 'title' ],
            'capability_type'=> 'post',
        ] );
    }
}