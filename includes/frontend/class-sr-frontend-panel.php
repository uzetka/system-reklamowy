<?php
/**
 * Frontend Panel – router widoków (dashboard, kontrahenci, zlecenia, ustawienia).
 *
 * Ten plik przejmuje rolę funkcji sr_front_render_view() oraz
 * sr_front_get_view_title() z Code Snippets (#10 – Front Panel).
 *
 * Na tym etapie:
 * - konkretne widoki (Kontrahenci, Zlecenia RADIO itp.) nadal dostarcza kod z Code Snippets,
 *   np.:
 *   - sr_front_render_kontrahent_list()
 *   - sr_front_render_kontrahent_form()
 *   - sr_front_render_zlecenia_radio_list()
 *   - sr_front_render_zlecenia_radio_add()
 *   - sr_front_render_zlecenia_radio_edit()
 *   - sr_front_render_zlecenia_radio_plan()
 *
 * - widok Ustawień (Cennik RADIO) jest już dostępny w klasie SR_Frontend_Settings,
 *   ale nadal zostawiamy fallback na funkcję ze snippetu (#13).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Panel {

    /**
     * Zwraca tytuł widoku do nagłówka.
     *
     * Odpowiednik sr_front_get_view_title() ze snippetu.
     */
    public function get_view_title( string $view ): string {
        switch ( $view ) {
            case 'kontrahenci':
                return 'Kontrahenci';

            case 'zlecenia-radio':
                return 'Zlecenia – RADIO';

            case 'zlecenia-tv':
                return 'Zlecenia – TV';

            case 'grafik-radio':
                return 'Grafik emisji – RADIO';

            case 'ustawienia':
                return 'Ustawienia systemu';

            case 'dashboard':
            default:
                return 'Dashboard systemu reklamowego';
        }
    }

    /**
     * Router widoków – odpowiednik sr_front_render_view() ze snippetu.
     *
     * Na razie deleguje do istniejących funkcji globalnych
     * (z Code Snippets) lub do klas frontowych we wtyczce.
     */
    public function render_view( string $view ): void {

        switch ( $view ) {

            case 'kontrahenci':
                $action = isset( $_GET['action'] )
                    ? sanitize_key( wp_unslash( $_GET['action'] ) )
                    : 'list';
                $id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

                if ( 'edit' === $action && $id > 0 && function_exists( 'sr_front_render_kontrahent_form' ) ) {
                    sr_front_render_kontrahent_form( $id );
                } elseif ( 'new' === $action && function_exists( 'sr_front_render_kontrahent_form' ) ) {
                    sr_front_render_kontrahent_form( 0 );
                } elseif ( function_exists( 'sr_front_render_kontrahent_list' ) ) {
                    sr_front_render_kontrahent_list();
                } else {
                    echo '<p class="sr-muted">Widok kontrahentów nie jest jeszcze podłączony.</p>';
                }
                break;

            case 'zlecenia-radio':
                if ( function_exists( 'sr_front_render_zlecenia_radio_list' ) ) {
                    sr_front_render_zlecenia_radio_list();
                } else {
                    echo '<h2>Zlecenia RADIO</h2>';
                    echo '<p class="sr-muted">Widok listy zleceń RADIO nie jest jeszcze podłączony.</p>';
                }
                break;

            case 'zlecenia-radio-add':
                if ( function_exists( 'sr_front_render_zlecenia_radio_add' ) ) {
                    sr_front_render_zlecenia_radio_add();
                } else {
                    echo '<p class="sr-muted">Widok dodawania zlecenia RADIO nie jest jeszcze podłączony.</p>';
                }
                break;

            case 'zlecenia-radio-edit':
                if ( function_exists( 'sr_front_render_zlecenia_radio_edit' ) ) {
                    sr_front_render_zlecenia_radio_edit();
                } else {
                    echo '<p class="sr-muted">Widok edycji zlecenia RADIO nie jest jeszcze podłączony.</p>';
                }
                break;

            case 'zlecenia-radio-plan':
                if ( function_exists( 'sr_front_render_zlecenia_radio_plan' ) ) {
                    sr_front_render_zlecenia_radio_plan();
                } else {
                    echo '<p class="sr-muted">Widok planu emisji RADIO nie jest jeszcze podłączony.</p>';
                }
                break;

            case 'zlecenia-tv':
                // Na razie placeholder jak w snippecie.
                echo '<h2>Zlecenia TV</h2>';
                echo '<p class="sr-muted">Tu później podłączymy moduł zleceń telewizyjnych.</p>';
                break;

            case 'grafik-radio':
                // Placeholder jak w snippecie.
                echo '<h2>Grafik emisji – RADIO</h2>';
                echo '<p class="sr-muted">W tym widoku pojawi się grafik bloków reklam i eksporty (PDF / TXT).</p>';
                break;

            case 'ustawienia':
                // 1) Preferujemy klasę we wtyczce – SR_Frontend_Settings.
                if ( class_exists( 'SR_Frontend_Settings' ) ) {
                    $settings = new SR_Frontend_Settings();
                    $settings->render_settings_page();
                }
                // 2) Fallback na funkcję ze snippetu (#13), jeśli z jakiegoś powodu klasa nie istnieje.
                elseif ( function_exists( 'sr_front_render_settings_page' ) ) {
                    sr_front_render_settings_page();
                } else {
                    echo '<p class="sr-muted">Widok ustawień nie jest jeszcze podłączony.</p>';
                }
                break;

            case 'dashboard':
            default:
                echo '<h2>Dashboard</h2>';
                echo '<p class="sr-muted">Podsumowanie systemu reklamowego (zlecenia, dzisiejsze emisje, itp.).</p>';
                break;
        }
    }
}

/**
 * Globalny helper – kompatybilny z tym, co używa frontend-panel.php.
 *
 * Dzięki temu w widoku możemy wywołać sr_front_render_view( $view ),
 * a faktyczna logika działa w klasie SR_Frontend_Panel.
 */
function sr_front_render_view( string $view ): void {
    static $panel = null;

    if ( null === $panel ) {
        $panel = new SR_Frontend_Panel();
    }

    $panel->render_view( $view );
}

/**
 * Globalny helper na tytuł widoku (opcjonalny).
 */
function sr_front_get_view_title( string $view ): string {
    static $panel = null;

    if ( null === $panel ) {
        $panel = new SR_Frontend_Panel();
    }

    return $panel->get_view_title( $view );
}