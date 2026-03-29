<?php
/**
 * REST API – endpointy systemu reklamowego.
 *
 * - /wp-json/sr/v1/nip-lookup?nip=XXXXXXXXXX
 * - /wp-json/sr/v1/kontrahent-find?q=fraza
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_REST {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes(): void {

        // NIP -> GUS
        register_rest_route(
            'sr/v1',
            '/nip-lookup',
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'handle_nip_lookup' ),

                // UWAGA: to MUSI być globalna funkcja, nie metoda klasy:
                'permission_callback' => '__return_true',
                'args'                => array(
                    'nip' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Autocomplete kontrahenta
        register_rest_route(
            'sr/v1',
            '/kontrahent-find',
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'handle_kontrahent_find' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'q' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );
    }

    /**
     * /sr/v1/nip-lookup – lookup w GUS po NIP.
     */
    public function handle_nip_lookup( WP_REST_Request $request ): WP_REST_Response {

        $nip = preg_replace( '/\D/', '', (string) $request->get_param( 'nip' ) );

        if ( 10 !== strlen( $nip ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'NIP musi mieć 10 cyfr.',
                ),
                200
            );
        }

        if ( ! function_exists( 'sr_gus_lookup_nip' ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Integracja z GUS (sr_gus_lookup_nip) nie jest dostępna.',
                ),
                200
            );
        }

        $result = sr_gus_lookup_nip( $nip ); // z mu-plugins/sr-gusapi.php [1](https://wzielonejpl-my.sharepoint.com/personal/marcin_wzielonejpl_onmicrosoft_com/Documents/Pliki%20czatu%20funkcji%20Microsoft%20Copilot/sr-gusapi.php)

        if ( empty( $result['success'] ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => $result['message'] ?? 'Nie udało się pobrać danych z GUS.',
                ),
                200
            );
        }

        return new WP_REST_Response(
            array(
                'success'                 => true,
                'message'                 => 'OK',
                'nazwa'                   => $result['nazwa'] ?? '',
                'adres'                   => $result['adres'] ?? '',
                'kod'                     => $result['kod'] ?? '',
                'miasto'                  => $result['miasto'] ?? '',
                'przedmiot_dzialalnosci'  => $result['przedmiot_dzialalnosci'] ?? '',
            ),
            200
        );
    }

    /**
     * /sr/v1/kontrahent-find – autocomplete kontrahenta po nazwie.
     */
    public function handle_kontrahent_find( WP_REST_Request $request ): WP_REST_Response {

        $q = (string) $request->get_param( 'q' );

        $args = array(
            'post_type'      => 'sr_kontrahent',
            'posts_per_page' => 10,
            's'              => $q,
        );

        $query   = new WP_Query( $args );
        $results = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();

                $results[] = array(
                    'id'        => $id,
                    'nazwa'     => get_the_title(),
                    'nip'       => get_post_meta( $id, 'nip', true ),
                    'adres'     => get_post_meta( $id, 'adres', true ),
                    'kod'       => get_post_meta( $id, 'kod', true ),
                    'miasto'    => get_post_meta( $id, 'miasto', true ),
                    'przedmiot' => get_post_meta( $id, 'przedmiot_dzialalnosci', true ),
                );
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response( $results, 200 );
    }
}