<?php
/**
 * Frontend – Kontrahenci (obsługa POST / save_kontrahent).
 *
 * Ta klasa przenosi backendową logikę dodawania/edycji kontrahenta
 * ze snippetu #10 do wtyczki.
 *
 * Na tym etapie:
 * - widoki (lista + formularz) nadal renderuje kod ze snippetu (#10),
 * - router SR_Frontend_Router wywoła handle_post() PRZED renderem HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Kontrahenci {

    /**
     * Obsługa POST (sr_action = save_kontrahent).
     *
     * Odpowiednik bloku 2a. z Code Snippetu #10.
     */
    public function handle_post(): void {
        // Reagujemy tylko na stronę /panel-reklamy
        if ( ! is_page( 'panel-reklamy' ) ) {
            return;
        }

        // Interesuje nas tylko POST
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }

        // Musi być sr_action=save_kontrahent
        if (
            ! isset( $_POST['sr_action'] )
            || 'save_kontrahent' !== $_POST['sr_action']
        ) {
            return;
        }

        // Bezpieczeństwo – nonce
        if (
            ! isset( $_POST['sr_nonce'] )
            || ! wp_verify_nonce( $_POST['sr_nonce'], 'sr_save_kontrahent' )
        ) {
            wp_die( 'Nieprawidłowy token bezpieczeństwa (kontrahent).' );
        }

        // Uprawnienia – tymczasowo tylko admin (jak w snippecie)
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Brak uprawnień do zapisu kontrahentów.' );
        }

        // Dane z formularza
        $post_id  = isset( $_POST['kontrahent_id'] ) ? absint( $_POST['kontrahent_id'] ) : 0;
        $nazwa    = sanitize_text_field( wp_unslash( $_POST['nazwa'] ?? '' ) );
        $nip      = sanitize_text_field( wp_unslash( $_POST['nip'] ?? '' ) );
        $adres    = sanitize_textarea_field( wp_unslash( $_POST['adres'] ?? '' ) );
        $kod      = sanitize_text_field( wp_unslash( $_POST['kod'] ?? '' ) );
        $miasto   = sanitize_text_field( wp_unslash( $_POST['miasto'] ?? '' ) );
        $przedmiot = sanitize_text_field( wp_unslash( $_POST['przedmiot_dzialalnosci'] ?? '' ) );

        // WALIDACJA BACKENDOWA
        $errors = [];

        // Nazwa (wymagana)
        if ( '' === $nazwa ) {
            $errors[] = 'Nazwa firmy jest wymagana.';
        }

        // NIP – jeżeli podany, musi mieć 10 cyfr
        if ( '' !== $nip && ! preg_match( '/^[0-9]{10}$/', $nip ) ) {
            $errors[] = 'NIP musi składać się z 10 cyfr (bez myślników).';
        }

        // Kod pocztowy – 00-000
        if ( '' !== $kod && ! preg_match( '/^[0-9]{2}-[0-9]{3}$/', $kod ) ) {
            $errors[] = 'Kod pocztowy musi być w formacie 00-000.';
        }

        // Miasto – tylko litery, spacje i myślniki
        if ( '' !== $miasto && ! preg_match( '/^[\p{L}\s\-]+$/u', $miasto ) ) {
            $errors[] = 'Nazwa miasta zawiera niedozwolone znaki.';
        }

        // Jeżeli są błędy → redirect z błędami w GET
        if ( ! empty( $errors ) ) {
            $redirect = add_query_arg(
                [
                    'view'   => 'kontrahenci',
                    'action' => $post_id ? 'edit' : 'new',
                    'error'  => base64_encode( wp_json_encode( $errors ) ),
                ],
                get_permalink()
            );

            wp_safe_redirect( $redirect );
            exit;
        }

        // Dane OK – zapisujemy / aktualizujemy post
        $post_data = [
            'post_title'  => $nazwa,
            'post_type'   => 'sr_kontrahent',
            'post_status' => 'publish',
        ];

        if ( $post_id ) {
            $post_data['ID'] = $post_id;
            $post_id         = wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            wp_die( 'Błąd podczas zapisu kontrahenta.' );
        }

        // Zapis meta
        update_post_meta( $post_id, 'nip', $nip );
        update_post_meta( $post_id, 'adres', $adres );
        update_post_meta( $post_id, 'kod', $kod );
        update_post_meta( $post_id, 'miasto', $miasto );
        update_post_meta( $post_id, 'przedmiot_dzialalnosci', $przedmiot );

        // Powrót na listę z toastem „zapisano”
        $redirect = add_query_arg(
            [
                'view'    => 'kontrahenci',
                'updated' => '1',
            ],
            get_permalink()
        );

        wp_safe_redirect( $redirect );
        exit;
    }
}