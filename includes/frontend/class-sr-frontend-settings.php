<?php
/**
 * Frontend – Ustawienia systemu reklamowego.
 *
 * Logika widoku ustawień na stronie /panel-reklamy:
 * - Cennik RADIO
 * - Przelicznik czasu
 * - Przedmiot działalności
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Settings {

    /**
     * Obsługa POST (CRUD Cennik RADIO, Przelicznik czasu, Przedmiot działalności).
     *
     * Wywoływana przez router tylko na /panel-reklamy,
     * przed renderem HTML.
     */
    public function handle_post(): void {
        // Tylko na właściwej stronie.
        if ( ! is_page( 'panel-reklamy' ) ) {
            return;
        }

        // Tylko zalogowani z odpowiednimi uprawnieniami.
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Tylko żądania POST.
        if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
            return;
        }

        if ( empty( $_POST['sr_settings_action'] ) ) {
            return;
        }

        $action = sanitize_key( wp_unslash( $_POST['sr_settings_action'] ) );
        $tab    = isset( $_POST['sr_settings_tab'] )
            ? sanitize_key( wp_unslash( $_POST['sr_settings_tab'] ) )
            : 'cennik-radio';

        // Nonce – ochrona przed CSRF.
        check_admin_referer( 'sr_settings_front_action', 'sr_settings_front_nonce' );

        // URL powrotu do aktualnej zakładki.
        $redirect = add_query_arg(
            [
                'view' => 'ustawienia',
                'tab'  => $tab,
            ],
            get_permalink()
        );

        global $wpdb;

        switch ( $action ) {

            /*
             * CENNIK RADIO – ZAPIS (Dodaj/Edycja)
             */
            case 'save_cennik_radio':
                $table = $wpdb->prefix . 'sr_cennik';

                $id      = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
                $godzina = sanitize_text_field( wp_unslash( $_POST['godzina'] ?? '' ) );

                $cena_raw      = (string) ( $_POST['cena'] ?? '0' );
                $cena_week_raw = (string) ( $_POST['cena_weekend'] ?? '0' );

                $cena         = (float) str_replace( ',', '.', $cena_raw );
                $cena_weekend = (float) str_replace( ',', '.', $cena_week_raw );

                // ENUM w bazie: 'BackwardFloating' | 'Floating'
                $start_raw = isset( $_POST['start_reklamy'] )
                    ? sanitize_text_field( wp_unslash( $_POST['start_reklamy'] ) )
                    : 'BackwardFloating';

                $allowed = [ 'BackwardFloating', 'Floating' ];
                $start   = in_array( $start_raw, $allowed, true )
                    ? $start_raw
                    : 'BackwardFloating';

                $aktywna = isset( $_POST['aktywna'] ) ? 1 : 0;

                $errors = [];

                if ( ! preg_match( '/^\d{2}:\d{2}$/', $godzina ) ) {
                    $errors[] = 'Godzina musi być w formacie HH:MM.';
                }
                if ( $cena <= 0 ) {
                    $errors[] = 'Cena musi być większa od zera.';
                }
                if ( $cena_weekend <= 0 ) {
                    $errors[] = 'Cena weekend musi być większa od zera.';
                }

                if ( ! empty( $errors ) ) {
                    $redirect = add_query_arg(
                        [ 'error' => rawurlencode( implode( ' ', $errors ) ) ],
                        $redirect
                    );
                    wp_safe_redirect( $redirect );
                    exit;
                }

                $data = [
                    'godzina'       => $godzina,
                    'cena'          => $cena,
                    'cena_weekend'  => $cena_weekend,
                    'start_reklamy' => $start,
                    'aktywna'       => $aktywna,
                    'kanal'         => 'radio',
                ];

                $format = [ '%s', '%f', '%f', '%s', '%d', '%s' ];

                if ( $id > 0 ) {
                    $wpdb->update(
                        $table,
                        $data,
                        [ 'id' => $id ],
                        $format,
                        [ '%d' ]
                    );
                } else {
                    $wpdb->insert(
                        $table,
                        $data,
                        $format
                    );
                }

                $redirect = add_query_arg( 'saved', '1', $redirect );
                wp_safe_redirect( $redirect );
                exit;

            /*
             * CENNIK RADIO – USUNIĘCIE
             */
            case 'delete_cennik_radio':
                $table = $wpdb->prefix . 'sr_cennik';
                $id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

                if ( $id > 0 ) {
                    $wpdb->delete(
                        $table,
                        [ 'id' => $id ],
                        [ '%d' ]
                    );
                }

                $redirect = add_query_arg( 'deleted', '1', $redirect );
                wp_safe_redirect( $redirect );
                exit;

            /*
             * PRZELICZNIK CZASU – ZAPIS (Dodaj/Edycja)
             */
            case 'save_przelicznik_czasu':
                $table = $wpdb->prefix . 'sr_przelicznik_czasu';

                $id          = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
                $dlugosc     = isset( $_POST['dlugosc'] ) ? absint( $_POST['dlugosc'] ) : 0;
                $mnoznik_raw = (string) ( $_POST['mnoznik'] ?? '0' );
                $mnoznik     = (float) str_replace( ',', '.', $mnoznik_raw );

                $errors = [];

                if ( $dlugosc <= 0 ) {
                    $errors[] = 'Długość musi być większa od zera.';
                }
                if ( $mnoznik <= 0 ) {
                    $errors[] = 'Mnożnik musi być większy od zera.';
                }

                if ( ! empty( $errors ) ) {
                    $redirect = add_query_arg(
                        [ 'error' => rawurlencode( implode( ' ', $errors ) ) ],
                        $redirect
                    );
                    wp_safe_redirect( $redirect );
                    exit;
                }

                $data   = [
                    'dlugosc_sec' => $dlugosc,
                    'mnoznik'     => $mnoznik,
                ];
                $format = [ '%d', '%f' ];

                if ( $id > 0 ) {
                    $wpdb->update(
                        $table,
                        $data,
                        [ 'id' => $id ],
                        $format,
                        [ '%d' ]
                    );
                } else {
                    $wpdb->insert(
                        $table,
                        $data,
                        $format
                    );
                }

                $redirect = add_query_arg( 'saved', '1', $redirect );
                wp_safe_redirect( $redirect );
                exit;

            /*
             * PRZELICZNIK CZASU – USUNIĘCIE
             */
            case 'delete_przelicznik_czasu':
                $table = $wpdb->prefix . 'sr_przelicznik_czasu';
                $id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

                if ( $id > 0 ) {
                    $wpdb->delete(
                        $table,
                        [ 'id' => $id ],
                        [ '%d' ]
                    );
                }

                $redirect = add_query_arg( 'deleted', '1', $redirect );
                wp_safe_redirect( $redirect );
                exit;

            /*
             * PRZEDMIOT DZIAŁALNOŚCI – ZAPIS (Dodaj/Edycja)
             */
            case 'save_przedmiot_dzialalnosci':
                $table = $wpdb->prefix . 'sr_przedmiot_dzialalnosci';

                $id      = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
                $nazwa   = sanitize_text_field( wp_unslash( $_POST['nazwa'] ?? '' ) );
                $aktywna = isset( $_POST['aktywna'] ) ? 1 : 0;

                $errors = [];

                if ( '' === $nazwa ) {
                    $errors[] = 'Nazwa przedmiotu działalności jest wymagana.';
                }

                if ( ! empty( $errors ) ) {
                    $redirect = add_query_arg(
                        [ 'error' => rawurlencode( implode( ' ', $errors ) ) ],
                        $redirect
                    );
                    wp_safe_redirect( $redirect );
                    exit;
                }

                $data   = [
                    'nazwa'   => $nazwa,
                    'aktywna' => $aktywna,
                ];
                $format = [ '%s', '%d' ];

                if ( $id > 0 ) {
                    $wpdb->update(
                        $table,
                        $data,
                        [ 'id' => $id ],
                        $format,
                        [ '%d' ]
                    );
                } else {
                    $wpdb->insert(
                        $table,
                        $data,
                        $format
                    );
                }

                $redirect = add_query_arg( 'saved', '1', $redirect );
                wp_safe_redirect( $redirect );
                exit;

            /*
             * PRZEDMIOT DZIAŁALNOŚCI – USUNIĘCIE
             */
            case 'delete_przedmiot_dzialalnosci':
                $table = $wpdb->prefix . 'sr_przedmiot_dzialalnosci';
                $id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

                if ( $id > 0 ) {
                    $wpdb->delete(
                        $table,
                        [ 'id' => $id ],
                        [ '%d' ]
                    );
                }

                $redirect = add_query_arg( 'deleted', '1', $redirect );
                wp_safe_redirect( $redirect );
                exit;
        }
    }

    /**
     * Główny widok ustawień – pasek zakładek + kontent.
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            echo '<p class="sr-muted">Brak uprawnień do ustawień systemu.</p>';
            return;
        }

        $tab = isset( $_GET['tab'] )
            ? sanitize_key( wp_unslash( $_GET['tab'] ) )
            : 'cennik-radio';

        $tabs = [
            'cennik-radio'      => 'Cennik RADIO',
            'cennik-tv'         => 'Cennik TV',
            'przelicznik-czasu' => 'Przelicznik czasu',
            'przedmiot'         => 'Przedmiot działalności',
            'rabaty'            => 'Rabaty',
        ];

        $base_url = add_query_arg( 'view', 'ustawienia', get_permalink() );

        echo '<div style="display:flex;flex-direction:column;gap:16px;">';

        // Nagłówek.
        echo '<div>';
        echo '<h2 style="margin:0 0 4px;">Ustawienia systemu</h2>';
        echo '<p class="sr-muted" style="margin:0;">Konfiguracja cenników, rabatów i przeliczników czasu spotów.</p>';
        echo '</div>';

        // Pasek zakładek (pills).
        echo '<div style="border-bottom:1px solid #e5e7eb;display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">';
        foreach ( $tabs as $key => $label ) {
            $url    = add_query_arg( 'tab', $key, $base_url );
            $active = ( $tab === $key );

            echo '<a href="' . esc_url( $url ) . '" style="'
                . 'padding:6px 12px;border-radius:999px;font-size:13px;text-decoration:none;'
                . ( $active
                    ? 'background:#111827;color:#F9FAFB;font-weight:600;'
                    : 'background:#f3f4f6;color:#374151;'
                )
                . '">'
                . esc_html( $label )
                . '</a>';
        }
        echo '</div>';

        // Toasty (saved / deleted / error).
        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="sr-toast sr-toast--success sr-toast--visible"'
                . ' style="margin-top:12px;position:static;transform:none;">'
                . 'Ustawienia zostały zapisane.'
                . '</div>';
        }

        if ( isset( $_GET['deleted'] ) ) {
            echo '<div class="sr-toast sr-toast--success sr-toast--visible"'
                . ' style="margin-top:12px;position:static;transform:none;background:#DC2626;">'
                . 'Pozycja została usunięta.'
                . '</div>';
        }

        if ( isset( $_GET['error'] ) ) {
            $msg = sanitize_text_field( wp_unslash( $_GET['error'] ) );
            echo '<div class="sr-toast sr-toast--success sr-toast--visible"'
                . ' style="margin-top:12px;position:static;transform:none;background:#DC2626;">'
                . esc_html( $msg )
                . '</div>';
        }

        echo '<div style="margin-top:12px;">';

        switch ( $tab ) {
            case 'cennik-radio':
                $this->render_cennik_radio();
                break;

            case 'cennik-tv':
                echo '<p class="sr-muted">Cennik TV – (TODO) przeniesiemy CRUD z kokpitu. Na razie konfiguracja tylko w adminie.</p>';
                break;

            case 'przelicznik-czasu':
                $this->render_przelicznik_czasu();
                break;

            case 'przedmiot':
                $this->render_przedmiot_dzialalnosci();
                break;

            case 'rabaty':
                echo '<p class="sr-muted">Rabaty – (TODO) edycja rabatu agencyjnego. Obecnie ustawiany w kokpicie.</p>';
                break;

            default:
                echo '<p class="sr-muted">Wybierz zakładkę, aby zobaczyć ustawienia.</p>';
                break;
        }

        echo '</div>'; // content
        echo '</div>'; // wrapper
    }

    /**
     * Cennik RADIO – lista + modal.
     */
    private function render_cennik_radio(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'sr_cennik';

        $rows = $wpdb->get_results(
            "SELECT id, godzina, cena, cena_weekend, start_reklamy, aktywna
             FROM {$table}
             WHERE kanal = 'radio'
             ORDER BY godzina ASC"
        );

        echo '<div class="sr-card" style="padding:16px;">';

        echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">';
        echo '<div>';
        echo '<h3 style="margin:0 0 4px;">Cennik emisji reklam RADIO</h3>';
        echo '<p class="sr-muted" style="margin:0;">Stawki bazowe wykorzystywane przy wyliczaniu wartości zleceń radiowych.</p>';
        echo '</div>';
        echo '<div>';
        echo '<button type="button" class="sr-btn-add-cennik-radio" style="'
            . 'padding:8px 14px;border-radius:999px;border:none;'
            . 'background:#111827;color:#F9FAFB;font-size:13px;cursor:pointer;'
            . '">+ Dodaj</button>';
        echo '</div>';
        echo '</div>';

        if ( empty( $rows ) ) {
            echo '<p class="sr-muted">Brak zdefiniowanych stawek w cenniku RADIO.</p>';
            $this->render_modal_cennik_radio();
            echo '</div>';
            return;
        }

        echo '<table class="sr-table" style="width:100%;border-collapse:collapse;font-size:14px;">';
        echo '<thead><tr>';
        echo '<th>Godzina (HH:MM)</th>';
        echo '<th>Cena</th>';
        echo '<th>Cena weekend</th>';
        echo '<th>Start reklamy</th>';
        echo '<th>Aktywna</th>';
        echo '<th style="width:140px;">Akcje</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $r ) {
            $id           = (int) $r->id;
            $godzina_raw  = (string) $r->godzina;
            $godzina      = esc_html( substr( $godzina_raw, 0, 5 ) );
            $cena         = number_format( (float) $r->cena, 2, ',', ' ' );
            $cena_weekend = number_format( (float) $r->cena_weekend, 2, ',', ' ' );
            $start_raw    = (string) $r->start_reklamy;
            $start_label  = $start_raw !== '' ? $start_raw : '(brak)';
            $aktywny      = (string) $r->aktywna === '1' ? 'ON' : 'OFF';

            echo '<tr'
                . ' data-id="' . esc_attr( $id ) . '"'
                . ' data-godzina="' . esc_attr( substr( $godzina_raw, 0, 5 ) ) . '"'
                . ' data-cena="' . esc_attr( (string) $r->cena ) . '"'
                . ' data-cena-weekend="' . esc_attr( (string) $r->cena_weekend ) . '"'
                . ' data-start="' . esc_attr( $start_raw ) . '"'
                . ' data-aktywna="' . esc_attr( (string) $r->aktywna ) . '"'
                . '>';

            echo '<td>' . $godzina . '</td>';
            echo '<td>' . $cena . ' zł</td>';
            echo '<td>' . $cena_weekend . ' zł</td>';
            echo '<td>' . esc_html( $start_label ) . '</td>';
            echo '<td>' . esc_html( $aktywny ) . '</td>';

            echo '<td>';
            echo '<button type="button" class="sr-btn-edit-cennik-radio" style="'
                . 'padding:4px 10px;border-radius:999px;border:1px solid #d1d5db;'
                . 'background:#fff;color:#111827;font-size:12px;cursor:pointer;margin-right:6px;'
                . '">Edytuj</button>';
            echo '<button type="button" class="sr-btn-del-cennik-radio" style="'
                . 'padding:4px 10px;border-radius:999px;border:1px solid #dc2626;'
                . 'background:#fff;color:#dc2626;font-size:12px;cursor:pointer;'
                . '">Usuń</button>';
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table>';

        $this->render_modal_cennik_radio();

        echo '</div>';
    }

    /**
     * Przelicznik czasu – lista + modal.
     */
    private function render_przelicznik_czasu(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'sr_przelicznik_czasu';

        $rows = $wpdb->get_results(
            "SELECT id, dlugosc_sec, mnoznik
             FROM {$table}
             ORDER BY dlugosc_sec ASC"
        );

        echo '<div class="sr-card" style="padding:16px;">';

        echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">';
        echo '<div>';
        echo '<h3 style="margin:0 0 4px;">Przelicznik czasu spotu</h3>';
        echo '<p class="sr-muted" style="margin:0;">Mnożniki ceny zależne od długości spotu w sekundach.</p>';
        echo '</div>';
        echo '<div>';
        echo '<button type="button" class="sr-btn-add-przelicznik" style="'
            . 'padding:8px 14px;border-radius:999px;border:none;'
            . 'background:#111827;color:#F9FAFB;font-size:13px;cursor:pointer;'
            . '">+ Dodaj</button>';
        echo '</div>';
        echo '</div>';

        if ( empty( $rows ) ) {
            echo '<p class="sr-muted">Brak zdefiniowanych przeliczników czasu.</p>';
            $this->render_modal_przelicznik_czasu();
            echo '</div>';
            return;
        }

        echo '<table class="sr-table" style="width:100%;border-collapse:collapse;font-size:14px;">';
        echo '<thead><tr>';
        echo '<th>Długość (sekundy)</th>';
        echo '<th>Mnożnik ceny</th>';
        echo '<th style="width:140px;">Akcje</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $r ) {
            $id        = (int) $r->id;
            $dlugosc   = (int) $r->dlugosc_sec;
            $mnoznik   = (float) $r->mnoznik;
            $mnoznik_f = number_format( $mnoznik, 2, ',', ' ' );

            echo '<tr'
                . ' data-id="' . esc_attr( $id ) . '"'
                . ' data-dlugosc="' . esc_attr( $dlugosc ) . '"'
                . ' data-mnoznik="' . esc_attr( (string) $mnoznik ) . '"'
                . '>';

            echo '<td>' . esc_html( $dlugosc ) . '</td>';
            echo '<td>' . esc_html( $mnoznik_f ) . '</td>';

            echo '<td>';
            echo '<button type="button" class="sr-btn-edit-przelicznik" style="'
                . 'padding:4px 10px;border-radius:999px;border:1px solid #d1d5db;'
                . 'background:#fff;color:#111827;font-size:12px;cursor:pointer;margin-right:6px;'
                . '">Edytuj</button>';
            echo '<button type="button" class="sr-btn-del-przelicznik" style="'
                . 'padding:4px 10px;border-radius:999px;border:1px solid #dc2626;'
                . 'background:#fff;color:#dc2626;font-size:12px;cursor:pointer;'
                . '">Usuń</button>';
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table>';

        $this->render_modal_przelicznik_czasu();

        echo '</div>';
    }

    /**
     * Przedmiot działalności – lista + modal.
     */
    private function render_przedmiot_dzialalnosci(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'sr_przedmiot_dzialalnosci';

        $rows = $wpdb->get_results(
            "SELECT id, nazwa, aktywna
             FROM {$table}
             ORDER BY nazwa ASC"
        );

        echo '<div class="sr-card" style="padding:16px;">';

        echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">';
        echo '<div>';
        echo '<h3 style="margin:0 0 4px;">Przedmiot działalności</h3>';
        echo '<p class="sr-muted" style="margin:0;">Słownik przedmiotów działalności używany w kontrahentach.</p>';
        echo '</div>';
        echo '<div>';
        echo '<button type="button" class="sr-btn-add-przedmiot" style="'
            . 'padding:8px 14px;border-radius:999px;border:none;'
            . 'background:#111827;color:#F9FAFB;font-size:13px;cursor:pointer;'
            . '">+ Dodaj</button>';
        echo '</div>';
        echo '</div>';

        if ( empty( $rows ) ) {
            echo '<p class="sr-muted">Brak zdefiniowanych przedmiotów działalności.</p>';
            $this->render_modal_przedmiot_dzialalnosci();
            echo '</div>';
            return;
        }

        echo '<table class="sr-table" style="width:100%;border-collapse:collapse;font-size:14px;">';
        echo '<thead><tr>';
        echo '<th>Nazwa</th>';
        echo '<th>Aktywna</th>';
        echo '<th style="width:140px;">Akcje</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $r ) {
            $id      = (int) $r->id;
            $nazwa   = (string) $r->nazwa;
            $aktywny = (string) $r->aktywna === '1' ? 'ON' : 'OFF';

            echo '<tr'
                . ' data-id="' . esc_attr( $id ) . '"'
                . ' data-nazwa="' . esc_attr( $nazwa ) . '"'
                . ' data-aktywna="' . esc_attr( (string) $r->aktywna ) . '"'
                . '>';

            echo '<td>' . esc_html( $nazwa ) . '</td>';
            echo '<td>' . esc_html( $aktywny ) . '</td>';

            echo '<td>';
            echo '<button type="button" class="sr-btn-edit-przedmiot" style="'
                . 'padding:4px 10px;border-radius:999px;border:1px solid #d1d5db;'
                . 'background:#fff;color:#111827;font-size:12px;cursor:pointer;margin-right:6px;'
                . '">Edytuj</button>';
            echo '<button type="button" class="sr-btn-del-przedmiot" style="'
                . 'padding:4px 10px;border-radius:999px;border:1px solid #dc2626;'
                . 'background:#fff;color:#dc2626;font-size:12px;cursor:pointer;'
                . '">Usuń</button>';
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table>';

        $this->render_modal_przedmiot_dzialalnosci();

        echo '</div>';
    }

    /**
     * Modal HTML – Cennik RADIO.
     */
    private function render_modal_cennik_radio(): void {
        ?>
        <div id="sr-modal-overlay" style="
            display:none;position:fixed;inset:0;background:rgba(15,23,42,0.45);
            z-index:9998;
        "></div>

        <div id="sr-modal-cennik-radio" style="
            display:none;position:fixed;z-index:9999;
            top:50%;left:50%;transform:translate(-50%,-50%);
            background:#ffffff;border-radius:10px;
            padding:20px 22px;min-width:320px;max-width:480px;
            box-shadow:0 10px 25px rgba(15,23,42,0.25);
        ">
            <h3 id="sr-modal-cr-title" style="margin:0 0 14px;font-size:18px;">Dodaj godzinę RADIO</h3>

            <form method="post" id="sr-modal-cr-form">
                <?php wp_nonce_field( 'sr_settings_front_action', 'sr_settings_front_nonce' ); ?>
                <input type="hidden" name="sr_settings_action" value="save_cennik_radio">
                <input type="hidden" name="sr_settings_tab" value="cennik-radio">
                <input type="hidden" name="id" value="0" id="sr-modal-cr-id">

                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:13px;margin-bottom:4px;">Godzina (HH:MM)</label>
                    <input type="time" name="godzina" id="sr-modal-cr-godzina" style="
                        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
                    ">
                </div>

                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:13px;margin-bottom:4px;">Cena</label>
                    <input type="text" name="cena" id="sr-modal-cr-cena" style="
                        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
                    ">
                </div>

                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:13px;margin-bottom:4px;">Cena weekend</label>
                    <input type="text" name="cena_weekend" id="sr-modal-cr-cena-weekend" style="
                        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
                    ">
                </div>

                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:13px;margin-bottom:4px;">Start reklamy</label>
                    <label style="font-size:13px;display:block;margin-bottom:2px;">
                        <input type="radio" name="start_reklamy" value="BackwardFloating" checked>
                        >> (BackwardFloating)
                    </label>
                    <label style="font-size:13px;display:block;">
                        <input type="radio" name="start_reklamy" value="Floating">
                        << (Floating)
                    </label>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                        <input type="checkbox" name="aktywna" id="sr-modal-cr-aktywna" checked> Aktywna (ON)
                    </label>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
                    <button type="button" id="sr-modal-cr-cancel" style="
                        padding:7px 14px;border-radius:999px;border:1px solid #d1d5db;
                        background:#fff;color:#374151;font-size:13px;cursor:pointer;
                    ">Anuluj</button>
                    <button type="submit" style="
                        padding:7px 14px;border-radius:999px;border:none;
                        background:#2563EB;color:#F9FAFB;font-size:13px;cursor:pointer;
                    ">Zapisz</button>
                </div>
            </form>

            <form method="post" id="sr-modal-cr-del-form" style="margin-top:16px;display:none;">
                <?php wp_nonce_field( 'sr_settings_front_action', 'sr_settings_front_nonce' ); ?>
                <input type="hidden" name="sr_settings_action" value="delete_cennik_radio">
                <input type="hidden" name="sr_settings_tab" value="cennik-radio">
                <input type="hidden" name="id" value="0" id="sr-modal-cr-del-id">

                <p style="font-size:14px;margin-bottom:12px;">Czy na pewno usunąć tę pozycję cennika?</p>

                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" id="sr-modal-cr-del-cancel" style="
                        padding:7px 14px;border-radius:999px;border:1px solid #d1d5db;
                        background:#fff;color:#374151;font-size:13px;cursor:pointer;
                    ">Anuluj</button>
                    <button type="submit" style="
                        padding:7px 14px;border-radius:999px;border:none;
                        background:#DC2626;color:#F9FAFB;font-size:13px;cursor:pointer;
                    ">Usuń</button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Modal HTML – Przelicznik czasu.
     */
    private function render_modal_przelicznik_czasu(): void {
        ?>
        <div id="sr-modal-overlay-przelicznik" style="
            display:none;position:fixed;inset:0;background:rgba(15,23,42,0.45);
            z-index:9998;
        "></div>

        <div id="sr-modal-przelicznik" style="
            display:none;position:fixed;z-index:9999;
            top:50%;left:50%;transform:translate(-50%,-50%);
            background:#ffffff;border-radius:10px;
            padding:20px 22px;min-width:320px;max-width:480px;
            box-shadow:0 10px 25px rgba(15,23,42,0.25);
        ">
            <h3 id="sr-modal-przel-title" style="margin:0 0 14px;font-size:18px;">Dodaj przelicznik</h3>

            <form method="post" id="sr-modal-przel-form">
                <?php wp_nonce_field( 'sr_settings_front_action', 'sr_settings_front_nonce' ); ?>
                <input type="hidden" name="sr_settings_action" value="save_przelicznik_czasu">
                <input type="hidden" name="sr_settings_tab" value="przelicznik-czasu">
                <input type="hidden" name="id" value="0" id="sr-modal-przel-id">

                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:13px;margin-bottom:4px;">Długość (sekundy)</label>
                    <input type="number" name="dlugosc" id="sr-modal-przel-dlugosc" style="
                        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
                    " min="1" step="1">
                </div>

                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:13px;margin-bottom:4px;">Mnożnik ceny</label>
                    <input type="number" name="mnoznik" id="sr-modal-przel-mnoznik" style="
                        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
                    " min="0" step="0.01">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
                    <button type="button" id="sr-modal-przel-cancel" style="
                        padding:7px 14px;border-radius:999px;border:1px solid #d1d5db;
                        background:#fff;color:#374151;font-size:13px;cursor:pointer;
                    ">Anuluj</button>
                    <button type="submit" style="
                        padding:7px 14px;border-radius:999px;border:none;
                        background:#2563EB;color:#F9FAFB;font-size:13px;cursor:pointer;
                    ">Zapisz</button>
                </div>
            </form>

            <form method="post" id="sr-modal-przel-del-form" style="margin-top:16px;display:none;">
                <?php wp_nonce_field( 'sr_settings_front_action', 'sr_settings_front_nonce' ); ?>
                <input type="hidden" name="sr_settings_action" value="delete_przelicznik_czasu">
                <input type="hidden" name="sr_settings_tab" value="przelicznik-czasu">
                <input type="hidden" name="id" value="0" id="sr-modal-przel-del-id">

                <p style="font-size:14px;margin-bottom:12px;">Czy na pewno usunąć ten przelicznik?</p>

                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" id="sr-modal-przel-del-cancel" style="
                        padding:7px 14px;border-radius:999px;border:1px solid #d1d5db;
                        background:#fff;color:#374151;font-size:13px;cursor:pointer;
                    ">Anuluj</button>
                    <button type="submit" style="
                        padding:7px 14px;border-radius:999px;border:none;
                        background:#DC2626;color:#F9FAFB;font-size:13px;cursor:pointer;
                    ">Usuń</button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Modal HTML – Przedmiot działalności.
     */
    private function render_modal_przedmiot_dzialalnosci(): void {
        ?>
        <div id="sr-modal-overlay-przedmiot" style="
            display:none;position:fixed;inset:0;background:rgba(15,23,42,0.45);
            z-index:9998;
        "></div>

        <div id="sr-modal-przedmiot" style="
            display:none;position:fixed;z-index:9999;
            top:50%;left:50%;transform:translate(-50%,-50%);
            background:#ffffff;border-radius:10px;
            padding:20px 22px;min-width:320px;max-width:480px;
            box-shadow:0 10px 25px rgba(15,23,42,0.25);
        ">
            <h3 id="sr-modal-przedmiot-title" style="margin:0 0 14px;font-size:18px;">Dodaj przedmiot działalności</h3>

            <form method="post" id="sr-modal-przedmiot-form">
                <?php wp_nonce_field( 'sr_settings_front_action', 'sr_settings_front_nonce' ); ?>
                <input type="hidden" name="sr_settings_action" value="save_przedmiot_dzialalnosci">
                <input type="hidden" name="sr_settings_tab" value="przedmiot">
                <input type="hidden" name="id" value="0" id="sr-modal-przedmiot-id">

                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:13px;margin-bottom:4px;">Nazwa</label>
                    <input type="text" name="nazwa" id="sr-modal-przedmiot-nazwa" style="
                        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
                    ">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                        <input type="checkbox" name="aktywna" id="sr-modal-przedmiot-aktywna" checked> Aktywna (ON)
                    </label>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
                    <button type="button" id="sr-modal-przedmiot-cancel" style="
                        padding:7px 14px;border-radius:999px;border:1px solid #d1d5db;
                        background:#fff;color:#374151;font-size:13px;cursor:pointer;
                    ">Anuluj</button>
                    <button type="submit" style="
                        padding:7px 14px;border-radius:999px;border:none;
                        background:#2563EB;color:#F9FAFB;font-size:13px;cursor:pointer;
                    ">Zapisz</button>
                </div>
            </form>

            <form method="post" id="sr-modal-przedmiot-del-form" style="margin-top:16px;display:none;">
                <?php wp_nonce_field( 'sr_settings_front_action', 'sr_settings_front_nonce' ); ?>
                <input type="hidden" name="sr_settings_action" value="delete_przedmiot_dzialalnosci">
                <input type="hidden" name="sr_settings_tab" value="przedmiot">
                <input type="hidden" name="id" value="0" id="sr-modal-przedmiot-del-id">

                <p style="font-size:14px;margin-bottom:12px;">Czy na pewno usunąć ten przedmiot działalności?</p>

                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" id="sr-modal-przedmiot-del-cancel" style="
                        padding:7px 14px;border-radius:999px;border:1px solid #d1d5db;
                        background:#fff;color:#374151;font-size:13px;cursor:pointer;
                    ">Anuluj</button>
                    <button type="submit" style="
                        padding:7px 14px;border-radius:999px;border:none;
                        background:#DC2626;color:#F9FAFB;font-size:13px;cursor:pointer;
                    ">Usuń</button>
                </div>
            </form>
        </div>
        <?php
    }
}