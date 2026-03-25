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

/**
 * Zwraca procent rabatu na podstawie typu:
 *  - 'brak'        → 0%
 *  - 'agencyjny'   → procent z Ustawień
 *  - '100'         → 100%
 *  - 'negocjowany' → wartość z pola (0–100)
 *
 * @param string $typ 'brak' | 'agencyjny' | '100' | 'negocjowany'
 * @param float  $neg wartość negocjowana, jeśli typ = negocjowany
 * @return float 0–100
 */
function sr_get_rabat_procent( string $typ, float $neg = 0.0 ): float {

    switch ( $typ ) {
        case 'brak':
        default:
            return 0.0;

        case 'agencyjny':
            // Klucz opcji możesz sprawdzić w swoim module Ustawień.
            // Przykładowo używamy 'sr_rabat_agencyjny_procent'
            $val = get_option( 'sr_rabat_agencyjny_procent', 0 );
            $val = (float) $val;
            if ( $val < 0 )  $val = 0;
            if ( $val > 100 ) $val = 100;
            return $val;

        case '100':
            return 100.0;

        case 'negocjowany':
            $neg = (float) $neg;
            if ( $neg < 0 )  $neg = 0;
            if ( $neg > 100 ) $neg = 100;
            return $neg;
    }
}
