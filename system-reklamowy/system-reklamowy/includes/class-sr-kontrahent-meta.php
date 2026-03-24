<?php
/**
 * Metabox dla CPT sr_kontrahent – dane kontrahenta (pod Secure Custom Fields).
 *
 * SCF operuje na standardowym post_meta, więc używamy get_post_meta()/update_post_meta().
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'SR_Kontrahent_Meta' ) ) {

    class SR_Kontrahent_Meta {

        public function __construct() {
            add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
            add_action( 'save_post_sr_kontrahent', [ $this, 'save_meta_boxes' ], 10, 2 );
        }

        /**
         * Rejestracja metaboxa dla sr_kontrahent.
         */
        public function register_meta_boxes() {
            add_meta_box(
                'sr_kontrahent_meta_box',
                __( 'Dane kontrahenta', 'system-reklamowy' ),
                [ $this, 'render_meta_box' ],
                'sr_kontrahent',
                'normal',
                'default'
            );
        }

        /**
         * Render pól metaboxa.
         *
         * @param WP_Post $post
         */
        public function render_meta_box( WP_Post $post ) {
            wp_nonce_field( 'sr_kontrahent_save_meta', 'sr_kontrahent_nonce' );

            // Możemy użyć helperów, jeśli chcesz sr_get_meta()
            $nip    = get_post_meta( $post->ID, 'nip', true );
            $adres  = get_post_meta( $post->ID, 'adres', true );
            $kod    = get_post_meta( $post->ID, 'kod', true );
            $miasto = get_post_meta( $post->ID, 'miasto', true );
            $pd     = get_post_meta( $post->ID, 'przedmiot_dzialalnosci', true );
            ?>
            <style>
                .sr-kontrahent-meta-table th {
                    width: 160px;
                }
                .sr-kontrahent-meta-table input[type="text"],
                .sr-kontrahent-meta-table textarea {
                    max-width: 480px;
                }
            </style>
            <table class="form-table sr-kontrahent-meta-table">
                <tr>
                    <th><label for="sr_nip"><?php esc_html_e( 'NIP', 'system-reklamowy' ); ?></label></th>
                    <td>
                        <input type="text" id="sr_nip" name="sr_nip" class="regular-text"
                               value="<?php echo esc_attr( $nip ); ?>" maxlength="10"
                               placeholder="np. 1234567890" />
                        <p class="description">10 cyfr, bez myślników.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="sr_adres"><?php esc_html_e( 'Adres', 'system-reklamowy' ); ?></label></th>
                    <td>
                        <textarea id="sr_adres" name="sr_adres" class="large-text" rows="3"
                                  placeholder="ulica, nr domu/lokalu"><?php
                            echo esc_textarea( $adres );
                        ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="sr_kod"><?php esc_html_e( 'Kod pocztowy', 'system-reklamowy' ); ?></label></th>
                    <td>
                        <input type="text" id="sr_kod" name="sr_kod" class="regular-text"
                               value="<?php echo esc_attr( $kod ); ?>" maxlength="6"
                               placeholder="00-000" />
                    </td>
                </tr>
                <tr>
                    <th><label for="sr_miasto"><?php esc_html_e( 'Miasto', 'system-reklamowy' ); ?></label></th>
                    <td>
                        <input type="text" id="sr_miasto" name="sr_miasto" class="regular-text"
                               value="<?php echo esc_attr( $miasto ); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="sr_przedmiot_dzialalnosci"><?php esc_html_e( 'Przedmiot działalności', 'system-reklamowy' ); ?></label></th>
                    <td>
                        <textarea id="sr_przedmiot_dzialalnosci" name="sr_przedmiot_dzialalnosci"
                                  class="large-text" rows="3"
                                  placeholder="np. agencja reklamowa, produkcja spotów"><?php
                            echo esc_textarea( $pd );
                        ?></textarea>
                    </td>
                </tr>
            </table>
            <?php
        }

        /**
         * Zapis meta z metaboxa – z prostą walidacją (spójną z frontem).
         *
         * @param int     $post_id
         * @param WP_Post $post
         */
        public function save_meta_boxes( int $post_id, WP_Post $post ) {

            // Nonce
            if (
                ! isset( $_POST['sr_kontrahent_nonce'] )
                || ! wp_verify_nonce( wp_unslash( $_POST['sr_kontrahent_nonce'] ), 'sr_kontrahent_save_meta' )
            ) {
                return;
            }

            // Autosave / revision
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            if ( wp_is_post_revision( $post_id ) ) {
                return;
            }

            // Uprawnienia
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            // Pobranie wartości z POST
            $nip    = isset( $_POST['sr_nip'] ) ? sanitize_text_field( wp_unslash( $_POST['sr_nip'] ) ) : '';
            $adres  = isset( $_POST['sr_adres'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sr_adres'] ) ) : '';
            $kod    = isset( $_POST['sr_kod'] ) ? sanitize_text_field( wp_unslash( $_POST['sr_kod'] ) ) : '';
            $miasto = isset( $_POST['sr_miasto'] ) ? sanitize_text_field( wp_unslash( $_POST['sr_miasto'] ) ) : '';
            $pd     = isset( $_POST['sr_przedmiot_dzialalnosci'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sr_przedmiot_dzialalnosci'] ) ) : '';

            // Walidacja – 1:1 jak front (soft: nie blokujemy zapisu)
            // NIP – jeśli podany, 10 cyfr
            if ( $nip !== '' && ! preg_match( '/^[0-9]{10}$/', $nip ) ) {
                // Można tu np. dodać admin_notices w przyszłości
            }

            // Kod pocztowy – 00-000
            if ( $kod !== '' && ! preg_match( '/^[0-9]{2}-[0-9]{3}$/', $kod ) ) {
                // jw.
            }

            // Miasto – tylko litery, spacje i myślniki
            if ( $miasto !== '' && ! preg_match( '/^[\p{L}\s\-]+$/u', $miasto ) ) {
                // jw.
            }

            // Zapis meta – standardowe post_meta (SCF je przejmie)
            update_post_meta( $post_id, 'nip', $nip );
            update_post_meta( $post_id, 'adres', $adres );
            update_post_meta( $post_id, 'kod', $kod );
            update_post_meta( $post_id, 'miasto', $miasto );
            update_post_meta( $post_id, 'przedmiot_dzialalnosci', $pd );
        }
    }
}