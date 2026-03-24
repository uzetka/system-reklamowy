<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ================================================
 *  PODSTAWOWE GET/SET META
 * ================================================
 */

if ( ! function_exists( 'sr_get_meta' ) ) {
    function sr_get_meta( int $post_id, string $key, $default = '' ) {
        $v = get_post_meta( $post_id, $key, true );
        return $v === '' ? $default : $v;
    }
}

if ( ! function_exists( 'sr_update_meta' ) ) {
    function sr_update_meta( int $post_id, string $key, $value ) {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === '' || $value === null) {
            delete_post_meta($post_id, $key);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    }
}


/**
 * =====================================================
 *  PRZEDMIOT DZIAŁALNOŚCI — SQL → ACF
 * =====================================================
 */

function sr_get_przedmiot_dzialalnosci_choices() {
    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT id, nazwa
        FROM {$wpdb->prefix}sr_przedmiot_dzialalnosci
        WHERE aktywna = 1
        ORDER BY nazwa ASC
    ");

    $choices = [];
    foreach ($rows as $r) {
        $choices[(string)$r->id] = $r->nazwa;
    }
    return $choices;
}

function sr_get_przedmiot_dzialalnosci_label($v) {
    global $wpdb;

    // Fallback dla starych wpisów SCF
    if (!ctype_digit((string)$v)) return (string)$v;

    return $wpdb->get_var($wpdb->prepare(
        "SELECT nazwa FROM {$wpdb->prefix}sr_przedmiot_dzialalnosci WHERE id=%d",
        (int)$v
    ));
}

add_filter('acf/load_field/name=przedmiot_dzialalnosci', function($field){
    $field['choices'] = sr_get_przedmiot_dzialalnosci_choices();
    return $field;
});


/**
 * =====================================================
 *  DŁUGOŚĆ SPOTU — SQL → ACF
 * =====================================================
 */

function sr_get_dlugosci_spotow_choices() {
    global $wpdb;

    $rows = $wpdb->get_results("
        SELECT dlugosc_sec, mnoznik
        FROM {$wpdb->prefix}sr_przelicznik_czasu
        ORDER BY dlugosc_sec ASC
    ");

    $choices = [];
    foreach ($rows as $r) {
        $choices[(int)$r->dlugosc_sec] =
            sprintf('%d sek (%.2fx)', (int)$r->dlugosc_sec, (float)$r->mnoznik);
    }

    return $choices;
}

add_filter('acf/load_field', function($field){

    $dlugosc_keys = [
        'field_69c266c8686c5', // radio
        'field_69c27b60e59c9', // tv
    ];

    if (in_array($field['key'], $dlugosc_keys, true)) {
        $field['choices'] = sr_get_dlugosci_spotow_choices();
    }

    return $field;
});


/**
 * =====================================================
 *  RABAT — DYNAMICZNE CHOICES (RADIO + TV)
 * =====================================================
 */

add_filter('acf/load_field', function($field){

    $rabat_keys = [
        'field_69c2664d686c3', // RADIO
        'field_69c27b60e5951', // TV
    ];

    if (!in_array($field['key'], $rabat_keys, true)) {
        return $field;
    }

    $agency = (int)get_option('sr_rabat_agencyjny_procent', 0);

    $field['choices'] = [
        'brak'        => 'Brak',
        'agencyjny'   => "Agencyjny ({$agency}%)",
        '100'         => '100%',
        'negocjowany' => 'Negocjowany',
    ];

    return $field;
});