<?php
/**
 * Frontend Panel – router widoków (dashboard, kontrahenci, zlecenia, ustawienia).
 *
 * Ten plik przejmuje rolę funkcji sr_front_render_view() oraz
 * sr_front_get_view_title() z Code Snippets (#10 – Front Panel).
 *
 * Na tym etapie:
 * - widok "Kontrahenci" obsługuje klasa SR_Frontend_Kontrahenci,
 * - widok "Zlecenia RADIO":
 *     - lista → SR_Frontend_Zlecenia_Radio::render_list(),
 *     - dodawanie → SR_Frontend_Zlecenia_Radio::render_add(),
 *   z fallbackiem na funkcje snippetowe:
 *     - sr_front_render_zlecenia_radio_list()
 *     - sr_front_render_zlecenia_radio_add()
 * - pozostałe widoki (Zlecenia TV, Grafik RADIO, Ustawienia, Dashboard)
 *   działają jak dotychczas (częściowo w pluginie, częściowo w snippetach).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Panel {

    /**
     * Zwraca tytuł widoku do nagłówka.
     *
     * @param string $view Klucz widoku.
     *
     * @return string
     */
    public function get_view_title( string $view ): string {
        switch ( $view ) {
            case 'kontrahenci':
                return 'Kontrahenci';

            case 'zlecenia-radio':
            case 'zlecenia-radio-add':
            case 'zlecenia-radio-edit':
            case 'zlecenia-radio-plan':
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
     * Router widoków panelu.
     *
     * @param string $view Klucz widoku (dashboard, kontrahenci, zlecenia-radio, itp.).
     */
    public function render_view( string $view ): void {

        switch ( $view ) {

            /**
             * KONTRAHENCI – lista / formularz (NEW / EDIT)
             * Obsługiwane przez SR_Frontend_Kontrahenci.
             */
            case 'kontrahenci':
                $action = isset( $_GET['action'] )
                    ? sanitize_key( wp_unslash( $_GET['action'] ) )
                    : 'list';

                $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

                if ( class_exists( 'SR_Frontend_Kontrahenci' ) ) {
                    $kontrahenci = new SR_Frontend_Kontrahenci();

                    if ( 'edit' === $action && $id > 0 ) {
                        $kontrahenci->render_form( $id );
                    } elseif ( 'new' === $action ) {
                        $kontrahenci->render_form( 0 );
                    } else {
                        $kontrahenci->render_list();
                    }

                // Fallback – tylko na czas migracji, jeśli klasa by nie istniała.
                } elseif ( function_exists( 'sr_front_render_kontrahent_form' )
                    || function_exists( 'sr_front_render_kontrahent_list' )
                ) {

                    if ( 'edit' === $action && $id > 0 && function_exists( 'sr_front_render_kontrahent_form' ) ) {
                        sr_front_render_kontrahent_form( $id );
                    } elseif ( 'new' === $action && function_exists( 'sr_front_render_kontrahent_form' ) ) {
                        sr_front_render_kontrahent_form( 0 );
                    } elseif ( function_exists( 'sr_front_render_kontrahent_list' ) ) {
                        sr_front_render_kontrahent_list();
                    } else {
                        echo '<p class="sr-muted">Widok kontrahentów nie jest jeszcze podłączony.</p>';
                    }

                } else {
                    echo '<p class="sr-muted">Widok kontrahentów nie jest jeszcze podłączony.</p>';
                }
                break;

            /**
             * ZLECENIA RADIO – lista.
             * SR_Frontend_Zlecenia_Radio::render_list() z fallbackiem.
             */
            case 'zlecenia-radio':
                if ( class_exists( 'SR_Frontend_Zlecenia_Radio' ) ) {
                    $radio = new SR_Frontend_Zlecenia_Radio();
                    $radio->render_list();

                } elseif ( function_exists( 'sr_front_render_zlecenia_radio_list' ) ) {
                    sr_front_render_zlecenia_radio_list();

                } else {
                    echo '<h2>Zlecenia RADIO</h2>';
                    echo '<p class="sr-muted">Widok listy zleceń RADIO nie jest jeszcze podłączony.</p>';
                }
                break;

            /**
             * ZLECENIA RADIO – dodawanie (KROK 1).
             * SR_Frontend_Zlecenia_Radio::render_add() z fallbackiem.
             */
            case 'zlecenia-radio-add':
                if ( class_exists( 'SR_Frontend_Zlecenia_Radio' ) ) {
                    $radio = new SR_Frontend_Zlecenia_Radio();
                    $radio->render_add();

                } elseif ( function_exists( 'sr_front_render_zlecenia_radio_add' ) ) {
                    sr_front_render_zlecenia_radio_add();

                } else {
                    echo '<p class="sr-muted">Widok dodawania zlecenia RADIO nie jest jeszcze podłączony.</p>';
                }
                break;

            /**
             * ZLECENIA RADIO – edycja.
             * Na razie nadal z Code Snippets (fallback).
             */
            case 'zlecenia-radio-edit':
                if ( function_exists( 'sr_front_render_zlecenia_radio_edit' ) ) {
                    sr_front_render_zlecenia_radio_edit();
                } else {
                    echo '<p class="sr-muted">Widok edycji zlecenia RADIO nie jest jeszcze podłączony.</p>';
                }
                break;

            /**
             * ZLECENIA RADIO – plan emisji (kroki 2/3).
             * Na razie nadal z Code Snippets (fallback).
             */
            case 'zlecenia-radio-plan':
                if ( function_exists( 'sr_front_render_zlecenia_radio_plan' ) ) {
                    sr_front_render_zlecenia_radio_plan();
                } else {
                    echo '<p class="sr-muted">Widok planu emisji RADIO nie jest jeszcze podłączony.</p>';
                }
                break;

            /**
             * ZLECENIA TV – placeholder.
             */
            case 'zlecenia-tv':
                echo '<h2>Zlecenia TV</h2>';
                echo '<p class="sr-muted">Tu później podłączymy moduł zleceń telewizyjnych.</p>';
                break;

            /**
             * GRAFIK RADIO – placeholder.
             */
            case 'grafik-radio':
                echo '<h2>Grafik emisji – RADIO</h2>';
                echo '<p class="sr-muted">W tym widoku pojawi się grafik bloków reklam i eksporty (PDF / TXT).</p>';
                break;

            /**
             * USTAWIENIA – preferujemy klasę SR_Frontend_Settings.
             */
            case 'ustawienia':
                if ( class_exists( 'SR_Frontend_Settings' ) ) {
                    $settings = new SR_Frontend_Settings();
                    $settings->render_settings_page();
                } elseif ( function_exists( 'sr_front_render_settings_page' ) ) {
                    sr_front_render_settings_page();
                } else {
                    echo '<p class="sr-muted">Widok ustawień nie jest jeszcze podłączony.</p>';
                }
                break;

            /**
             * DASHBOARD – prosty placeholder.
             */
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
 * Definiujemy TYLKO, jeśli funkcja NIE istnieje (na czas migracji,
 * gdy Code Snippets może jeszcze dostarczać swoją wersję).
 */
if ( ! function_exists( 'sr_front_render_view' ) ) {
    function sr_front_render_view( string $view ): void {
        static $panel = null;

        if ( null === $panel ) {
            $panel = new SR_Frontend_Panel();
        }

        $panel->render_view( $view );
    }
}

/**
 * Globalny helper na tytuł widoku (opcjonalny).
 */
if ( ! function_exists( 'sr_front_get_view_title' ) ) {
    function sr_front_get_view_title( string $view ): string {
        static $panel = null;

        if ( null === $panel ) {
            $panel = new SR_Frontend_Panel();
        }

        return $panel->get_view_title( $view );
    }
}