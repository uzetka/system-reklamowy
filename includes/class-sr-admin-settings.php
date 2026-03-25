<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Obsługa zakładki "Ustawienia" w module Reklama.
 *
 * - CRUD:
 *   - wp_sr_cennik              (RADIO / TV)
 *   - wp_sr_przelicznik_czasu
 *   - wp_sr_przedmiot_dzialalnosci
 *   - opcja rabatu agencyjnego (wp_options)
 */
class SR_Admin_Settings {

    public function __construct() {
        add_action( 'admin_init', [ $this, 'handle_post' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Ładowanie CSS/JS tylko na stronie page=sr-settings.
     * Plik klasy jest w /includes, a assety w /admin/css|js – stąd "../admin/...".
     */
    public function enqueue_assets( $hook_suffix ) {
        if ( empty( $_GET['page'] ) || $_GET['page'] !== 'sr-settings' ) {
            return;
        }

        // CSS modali / layoutu ustawień
        wp_enqueue_style(
            'sr-admin-settings',
            plugins_url( '../admin/css/sr-admin-settings.css', __FILE__ ),
            [],
            '1.0.0'
        );

        // JS obsługujący modale i edycję
        wp_enqueue_script(
            'sr-admin-settings',
            plugins_url( '../admin/js/sr-admin-settings.js', __FILE__ ),
            [ 'jquery' ],
            '1.0.0',
            true
        );
    }

    /**
     * Router obsługujący POSTy z formularzy ustawień.
     */
    public function handle_post() {
        if ( empty( $_POST['sr_settings_action'] ) || empty( $_POST['sr_settings_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['sr_settings_nonce'], 'sr_settings_nonce' ) ) {
            return;
        }

        // Dopasowane do add_submenu_page(..., 'manage_options', 'sr-settings', ...)
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $action = sanitize_text_field( wp_unslash( $_POST['sr_settings_action'] ) );

        switch ( $action ) {
            case 'save_cennik':
                $this->save_cennik();
                break;

            case 'delete_cennik':
                $this->delete_cennik();
                break;

            case 'save_przelicznik':
                $this->save_przelicznik();
                break;

            case 'delete_przelicznik':
                $this->delete_przelicznik();
                break;

            case 'save_przedmiot':
                $this->save_przedmiot();
                break;

            case 'delete_przedmiot':
                $this->delete_przedmiot();
                break;

            case 'save_rabaty':
                $this->save_rabaty();
                break;
        }
    }

    /* ======================================================================
     *                               C E N N I K
     * ==================================================================== */

    /**
     * Insert/Update w wp_sr_cennik (RADIO / TV).
     */
    protected function save_cennik() {
        global $wpdb;

        $table = $wpdb->prefix . 'sr_cennik';

        $id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $kanal = ( isset( $_POST['kanal'] ) && $_POST['kanal'] === 'tv' ) ? 'tv' : 'radio';

        $godzina       = isset( $_POST['godzina'] ) ? sanitize_text_field( wp_unslash( $_POST['godzina'] ) ) : '';
        $cena          = isset( $_POST['cena'] ) ? str_replace( ',', '.', wp_unslash( $_POST['cena'] ) ) : '0';
        $cena_weekend  = isset( $_POST['cena_weekend'] ) ? str_replace( ',', '.', wp_unslash( $_POST['cena_weekend'] ) ) : '0';

        // start_reklamy tylko dla RADIO; dla TV trzymamy jakiś sensowny default
        $start_reklamy = 'BackwardFloating';
        if ( $kanal === 'radio' && isset( $_POST['start_reklamy'] ) ) {
            $start_reklamy = ( $_POST['start_reklamy'] === 'Floating' ) ? 'Floating' : 'BackwardFloating';
        }

        $aktywna = isset( $_POST['aktywna'] ) ? 1 : 0;

        if ( empty( $godzina ) ) {
            return;
        }

        // Normalizacja "HH:MM" -> "HH:MM:SS"
        if ( preg_match( '/^\d{1,2}:\d{2}$/', $godzina ) ) {
            $godzina .= ':00';
        }

        $data = [
            'kanal'         => $kanal,
            'godzina'       => $godzina,
            'cena'          => (float) $cena,
            'cena_weekend'  => (float) $cena_weekend,
            'start_reklamy' => $start_reklamy,
            'aktywna'       => $aktywna,
        ];

        $formats = [ '%s', '%s', '%f', '%f', '%s', '%d' ];

        if ( $id > 0 ) {
            $wpdb->update(
                $table,
                $data,
                [ 'id' => $id ],
                $formats,
                [ '%d' ]
            );
        } else {
            $wpdb->insert(
                $table,
                $data,
                $formats
            );
        }

        $tab = ( $kanal === 'tv' ) ? 'tv_cennik' : 'radio_cennik';

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => 'sr-settings',
                    'tab'     => $tab,
                    'updated' => 1,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Delete z wp_sr_cennik.
     */
    protected function delete_cennik() {
        global $wpdb;

        $table = $wpdb->prefix . 'sr_cennik';

        $id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $kanal = ( isset( $_POST['kanal'] ) && $_POST['kanal'] === 'tv' ) ? 'tv' : 'radio';

        if ( $id > 0 ) {
            $wpdb->delete(
                $table,
                [ 'id' => $id ],
                [ '%d' ]
            );
        }

        $tab = ( $kanal === 'tv' ) ? 'tv_cennik' : 'radio_cennik';

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => 'sr-settings',
                    'tab'     => $tab,
                    'deleted' => 1,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /* ======================================================================
     *                      P R Z E L I C Z N I K   C Z A S U
     * ==================================================================== */

    /**
     * Insert/Update w wp_sr_przelicznik_czasu (UNIQUE po dlugosc_sec).
     */
    protected function save_przelicznik() {
        global $wpdb;

        $table = $wpdb->prefix . 'sr_przelicznik_czasu';

        $id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $dlugosc = isset( $_POST['dlugosc'] ) ? absint( $_POST['dlugosc'] ) : 0;
        $mnoznik = isset( $_POST['mnoznik'] ) ? str_replace( ',', '.', wp_unslash( $_POST['mnoznik'] ) ) : '1';

        if ( $dlugosc <= 0 ) {
            return;
        }

        $data = [
            'dlugosc_sec' => $dlugosc,
            'mnoznik'     => (float) $mnoznik,
        ];

        if ( $id > 0 ) {
            // Update konkretnego rekordu
            $wpdb->update(
                $table,
                $data,
                [ 'id' => $id ],
                [ '%d', '%f' ],
                [ '%d' ]
            );
        } else {
            // Unikalność po dlugosc_sec – jeśli istnieje, robimy update
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE dlugosc_sec = %d",
                    $dlugosc
                )
            );

            if ( $existing_id ) {
                $wpdb->update(
                    $table,
                    $data,
                    [ 'id' => $existing_id ],
                    [ '%d', '%f' ],
                    [ '%d' ]
                );
            } else {
                $wpdb->insert(
                    $table,
                    $data,
                    [ '%d', '%f' ]
                );
            }
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => 'sr-settings',
                    'tab'     => 'przelicznik',
                    'updated' => 1,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Delete z wp_sr_przelicznik_czasu.
     */
    protected function delete_przelicznik() {
        global $wpdb;

        $table = $wpdb->prefix . 'sr_przelicznik_czasu';

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( $id > 0 ) {
            $wpdb->delete(
                $table,
                [ 'id' => $id ],
                [ '%d' ]
            );
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => 'sr-settings',
                    'tab'     => 'przelicznik',
                    'deleted' => 1,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /* ======================================================================
     *                  P R Z E D M I O T   D Z I A Ł A L N O Ś C I
     * ==================================================================== */

    /**
     * Insert/Update w wp_sr_przedmiot_dzialalnosci.
     */
    protected function save_przedmiot() {
        global $wpdb;

        $table = $wpdb->prefix . 'sr_przedmiot_dzialalnosci';

        $id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $nazwa   = isset( $_POST['nazwa'] ) ? sanitize_text_field( wp_unslash( $_POST['nazwa'] ) ) : '';
        $aktywna = isset( $_POST['aktywna'] ) ? 1 : 0;

        if ( $nazwa === '' ) {
            return;
        }

        $data = [
            'nazwa'   => $nazwa,
            'aktywna' => $aktywna,
        ];

        if ( $id > 0 ) {
            $wpdb->update(
                $table,
                $data,
                [ 'id' => $id ],
                [ '%s', '%d' ],
                [ '%d' ]
            );
        } else {
            $wpdb->insert(
                $table,
                $data,
                [ '%s', '%d' ]
            );
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => 'sr-settings',
                    'tab'     => 'przedmiot',
                    'updated' => 1,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Delete z wp_sr_przedmiot_dzialalnosci.
     */
    protected function delete_przedmiot() {
        global $wpdb;

        $table = $wpdb->prefix . 'sr_przedmiot_dzialalnosci';

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( $id > 0 ) {
            $wpdb->delete(
                $table,
                [ 'id' => $id ],
                [ '%d' ]
            );
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => 'sr-settings',
                    'tab'     => 'przedmiot',
                    'deleted' => 1,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /* ======================================================================
     *                               R A B A T Y
     * ==================================================================== */

    /**
     * Zapisz rabat agencyjny (procent) w wp_options.
     */
    protected function save_rabaty() {
        if ( isset( $_POST['rabat_agencyjny'] ) ) {
            $val = (int) $_POST['rabat_agencyjny'];

            if ( $val < 0 ) {
                $val = 0;
            } elseif ( $val > 100 ) {
                $val = 100;
            }

            update_option( 'sr_rabat_agencyjny_procent', $val );
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'    => 'sr-settings',
                    'tab'     => 'rabaty',
                    'updated' => 1,
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }
}