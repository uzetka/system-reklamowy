<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Zwraca tablicę meta dla zlecenia RADIO (CPT sr_zlecenie_radio).
 */
function sr_radio_get_zlecenie_data( int $post_id ): array {
    return [
        'kontrahent_id'   => (int) get_post_meta( $post_id, 'kontrahent_id', true ),
        'typ'             => get_post_meta( $post_id, 'typ', true ) ?: 'radio',
        'nazwa_reklamy'   => get_post_meta( $post_id, 'nazwa_reklamy', true ),
        'data_zlecenia'   => get_post_meta( $post_id, 'data_zlecenia', true ),
        'data_start'      => get_post_meta( $post_id, 'data_start', true ),
        'data_koniec'     => get_post_meta( $post_id, 'data_koniec', true ),
        'wartosc'         => (float) get_post_meta( $post_id, 'wartosc', true ),
        'do_zaplaty'      => (float) get_post_meta( $post_id, 'do_zaplaty', true ),
        'rabat'           => get_post_meta( $post_id, 'rabat', true ),
        'motive'          => get_post_meta( $post_id, 'motive', true ),
        'dlugosc_spotu'   => (int) get_post_meta( $post_id, 'dlugosc_spotu', true ),
        'status'          => get_post_meta( $post_id, 'status', true ) ?: 'draft',
    ];
}