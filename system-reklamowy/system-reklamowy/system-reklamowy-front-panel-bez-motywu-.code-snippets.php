<?php

/**
 * System Reklamowy – Front Panel (bez motywu)
 */
/**
 * System Reklamowy – Front Panel (bez motywu)
 *
 * Frontowy panel Systemu Reklamowego (bez motywu)
 * URL: strona "Panel reklamy" o slugu: panel-reklamy
 *
 * Wersja przygotowana pod Secure Custom Fields:
 * - używa wyłącznie standardowego post_meta (get_post_meta / update_post_meta),
 * - SCF może szyfrować/zabezpieczać te pola po swojej stronie.
 */

add_action( 'template_redirect', function () {
    // 1. Reagujemy tylko na stronę /panel-reklamy/
    if ( ! is_page( 'panel-reklamy' ) ) {
        return;
    }

    // 2. Wymagane logowanie
    if ( ! is_user_logged_in() ) {
        auth_redirect(); // standardowy WP redirect na wp-login.php + powrót
        exit;
    }

    // 2a. OBSŁUGA ZAPISU KONTRAHENTA (POST z formularza frontowego)
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset( $_POST['sr_action'] )
        && $_POST['sr_action'] === 'save_kontrahent'
    ) {
        // Bezpieczeństwo – nonce
        if (
            ! isset( $_POST['sr_nonce'] )
            || ! wp_verify_nonce( $_POST['sr_nonce'], 'sr_save_kontrahent' )
        ) {
            wp_die( 'Nieprawidłowy token bezpieczeństwa.' );
        }

        // Uprawnienia – na razie tylko admin (manage_options); możesz zmienić na własną capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Brak uprawnień do zapisu kontrahentów.' );
        }

        $post_id    = isset( $_POST['kontrahent_id'] ) ? absint( $_POST['kontrahent_id'] ) : 0;
        $nazwa      = sanitize_text_field( wp_unslash( $_POST['nazwa'] ?? '' ) );
        $nip        = sanitize_text_field( wp_unslash( $_POST['nip'] ?? '' ) );
        $adres      = sanitize_textarea_field( wp_unslash( $_POST['adres'] ?? '' ) );
        $kod        = sanitize_text_field( wp_unslash( $_POST['kod'] ?? '' ) );
        $miasto     = sanitize_text_field( wp_unslash( $_POST['miasto'] ?? '' ) );
        $przedmiot  = sanitize_text_field( wp_unslash( $_POST['przedmiot_dzialalnosci'] ?? '' ) );

        // WALIDACJA BACKENDOWA
        $errors = [];

        // Nazwa (wymagana)
        if ( $nazwa === '' ) {
            $errors[] = 'Nazwa firmy jest wymagana.';
        }

        // NIP – jeśli podany, musi mieć 10 cyfr
        if ( $nip !== '' && ! preg_match( '/^[0-9]{10}$/', $nip ) ) {
            $errors[] = 'NIP musi składać się z 10 cyfr (bez myślników).';
        }

        // Kod pocztowy – 00-000
        if ( $kod !== '' && ! preg_match( '/^[0-9]{2}-[0-9]{3}$/', $kod ) ) {
            $errors[] = 'Kod pocztowy musi być w formacie 00-000.';
        }

        // Miasto – tylko litery, spacje i myślniki
        if ( $miasto !== '' && ! preg_match( '/^[\p{L}\s\-]+$/u', $miasto ) ) {
            $errors[] = 'Nazwa miasta zawiera niedozwolone znaki.';
        }

        // Jeśli są błędy -> redirect z listą błędów w GET
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

        // Dane OK – zapisujemy lub aktualizujemy post
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

        if ( is_wp_error( $post_id ) ) {
            wp_die( 'Błąd podczas zapisu kontrahenta.' );
        }

        // Zapis meta – pod Secure Custom Fields (SCF)
        // SCF przejmuje standardowe post_meta, nie trzeba używać specjalnych funkcji.
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
	
	// 2b. OBSŁUGA ZAPISU ZLECENIA RADIO (POST z formularza frontowego)
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset( $_POST['sr_action'] )
        && $_POST['sr_action'] === 'save_zlecenie_radio'
    ) {
        // Bezpieczeństwo – nonce
        if (
            ! isset( $_POST['sr_zlecenie_radio_nonce'] )
            || ! wp_verify_nonce( $_POST['sr_zlecenie_radio_nonce'], 'sr_save_zlecenie_radio' )
        ) {
            wp_die( 'Nieprawidłowy token bezpieczeństwa (zlecenie RADIO).' );
        }

        // Uprawnienia – na razie tylko admin; później można zmienić na sr_operator
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Brak uprawnień do zapisu zleceń.' );
        }

        // === Pobranie i sanityzacja danych z formularza ===
        $firma     = sanitize_text_field( wp_unslash( $_POST['firma'] ?? '' ) );
        $nip       = sanitize_text_field( wp_unslash( $_POST['nip'] ?? '' ) );
        $adres     = sanitize_textarea_field( wp_unslash( $_POST['adres'] ?? '' ) );
        $kod       = sanitize_text_field( wp_unslash( $_POST['kod'] ?? '' ) );
        $miasto    = sanitize_text_field( wp_unslash( $_POST['miasto'] ?? '' ) );
        $przedmiot = sanitize_text_field( wp_unslash( $_POST['przedmiot_dzialalnosci'] ?? '' ) );

        $nazwa_rek  = sanitize_text_field( wp_unslash( $_POST['nazwa_reklamy'] ?? '' ) );
        $motive     = sanitize_text_field( wp_unslash( $_POST['motive'] ?? '' ) );
        $dlugosc    = isset( $_POST['dlugosc_spotu'] ) ? (int) $_POST['dlugosc_spotu'] : 0;
        $data_start = sanitize_text_field( wp_unslash( $_POST['data_start'] ?? '' ) );
        $data_kon   = sanitize_text_field( wp_unslash( $_POST['data_koniec'] ?? '' ) );
        $rabat      = sanitize_text_field( wp_unslash( $_POST['rabat'] ?? 'brak' ) );
        $rabat_neg  = isset( $_POST['rabat_negocjowany'] ) ? (float) $_POST['rabat_negocjowany'] : 0;

        // === Walidacja backendowa ===
        $errors = [];

        if ( $firma === '' ) {
            $errors[] = 'Nazwa firmy jest wymagana.';
        }
        if ( $nazwa_rek === '' ) {
            $errors[] = 'Nazwa reklamy jest wymagana.';
        }
        if ( $dlugosc <= 0 ) {
            $errors[] = 'Długość spotu jest wymagana.';
        }
        if ( $data_start === '' || $data_kon === '' ) {
            $errors[] = 'Daty rozpoczęcia i zakończenia są wymagane.';
        }

        if ( ! empty( $errors ) ) {
            $redirect = add_query_arg(
                [
                    'view'  => 'zlecenia-radio-add',
                    'error' => base64_encode( wp_json_encode( $errors ) ),
                ],
                get_permalink()
            );
            wp_safe_redirect( $redirect );
            exit;
        }

        // === 1) Znajdź lub dodaj KONTRAHENTA ===
        $kontrahent_id = 0;

        // Szukamy po NIP
        if ( $nip !== '' ) {
            $kontrahent = get_posts(
                [
                    'post_type'      => 'sr_kontrahent',
                    'posts_per_page' => 1,
                    'meta_key'       => 'nip',
                    'meta_value'     => $nip,
                    'fields'         => 'ids',
                ]
            );
            if ( ! empty( $kontrahent ) ) {
                $kontrahent_id = (int) $kontrahent[0];
            }
        }

        // Jeśli brak po NIP – spróbuj po nazwie
        if ( ! $kontrahent_id && $firma !== '' ) {
            $kontrahent = get_posts(
                [
                    'post_type'      => 'sr_kontrahent',
                    'posts_per_page' => 1,
                    'title'          => $firma,
                    'fields'         => 'ids',
                ]
            );
            if ( ! empty( $kontrahent ) ) {
                $kontrahent_id = (int) $kontrahent[0];
            }
        }

        // Nadal brak → tworzymy nowego kontrahenta
        if ( ! $kontrahent_id ) {
            $k_post_id = wp_insert_post(
                [
                    'post_title'  => $firma,
                    'post_type'   => 'sr_kontrahent',
                    'post_status' => 'publish',
                ]
            );

            if ( is_wp_error( $k_post_id ) || ! $k_post_id ) {
                wp_die( 'Błąd podczas tworzenia kontrahenta.' );
            }

            update_post_meta( $k_post_id, 'nip', $nip );
            update_post_meta( $k_post_id, 'adres', $adres );
            update_post_meta( $k_post_id, 'kod', $kod );
            update_post_meta( $k_post_id, 'miasto', $miasto );
            update_post_meta( $k_post_id, 'przedmiot_dzialalnosci', $przedmiot );

            $kontrahent_id = (int) $k_post_id;
        }

        // === 2) Tworzymy ZLECENIE RADIO (CPT) jako draft ===
        $post_id = wp_insert_post(
            [
                'post_title'  => $nazwa_rek,
                'post_type'   => 'sr_zlecenie_radio',
                'post_status' => 'draft', // zawsze draft na tym etapie
            ]
        );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            wp_die( 'Błąd podczas tworzenia zlecenia RADIO.' );
        }

        // === 3) Zapis meta – ACF / standard meta ===
        update_post_meta( $post_id, 'kontrahent_id',        $kontrahent_id );
        update_post_meta( $post_id, 'nazwa_reklamy',        $nazwa_rek );
        update_post_meta( $post_id, 'motive',               $motive );
        update_post_meta( $post_id, 'dlugosc_spotu',        $dlugosc );
        update_post_meta( $post_id, 'data_zlecenia',        current_time( 'Y-m-d' ) );
        update_post_meta( $post_id, 'data_start',           $data_start );
        update_post_meta( $post_id, 'data_koniec',          $data_kon );
        update_post_meta( $post_id, 'rabat',                $rabat );
        update_post_meta( $post_id, 'rabat_negocjowany',    $rabat_neg );
        update_post_meta( $post_id, 'wartosc',              0 );
        update_post_meta( $post_id, 'do_zaplaty',           0 );
        update_post_meta( $post_id, 'status',               'draft' );
        update_post_meta( $post_id, 'typ',                  'radio' );

        // === 4) WYWOŁUJEMY PONOWNY ZAPIS, ŻEBY URUCHOMIĆ SYNC DO SQL ===
        wp_update_post(
            [
                'ID'          => $post_id,
                'post_status' => 'draft', // zostaje draft
            ]
        );
        // → wywoła save_post_sr_zlecenie_radio, a SR_Zlecenia_Sync
        // utworzy/uzupełni wpis w wp_sr_zlecenia

        // === 5) Redirect do widoku HARMONOGRAMU ===
        $redirect = add_query_arg(
            [
                'view' => 'zlecenia-radio-plan',
                'id'   => $post_id,
            ],
            get_permalink()
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    // 3. Render panelu (bez motywu)
    status_header( 200 );
    nocache_headers();

    $current_user = wp_get_current_user();
    $view         = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'dashboard';
    $base_url     = get_permalink();
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html( get_bloginfo( 'name' ) . ' – Panel reklamy' ); ?></title>
        <?php wp_head(); ?>
        <style>
            :root { color-scheme: light dark; }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background: #f3f4f6;
                color: #111827;
            }
            .sr-app-shell { display: flex; min-height: 100vh; }
            .sr-sidebar {
                width: 260px;
                background: #111827;
                color: #e5e7eb;
                display: flex;
                flex-direction: column;
            }
            .sr-sidebar-header {
                padding: 20px 24px;
                font-weight: 600;
                font-size: 18px;
                border-bottom: 1px solid #1f2937;
            }
            .sr-menu {
                list-style: none;
                padding: 8px 0;
                margin: 0;
                flex: 1;
            }
            .sr-menu a {
                display: block;
                padding: 10px 24px;
                text-decoration: none;
                color: inherit;
                font-size: 14px;
            }
            .sr-menu a:hover { background: #1f2937; }
            .sr-menu a.is-active {
                background: #374151;
                font-weight: 600;
            }
            .sr-sidebar-footer {
                padding: 12px 24px;
                border-top: 1px solid #1f2937;
                font-size: 13px;
            }
            .sr-content {
                flex: 1;
                padding: 24px 28px;
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            .sr-topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                flex-wrap: wrap;
            }
            .sr-topbar h1 {
                margin: 0;
                font-size: 22px;
            }
            .sr-topbar-user {
                font-size: 14px;
                color: #4b5563;
            }
            .sr-card {
                background: #ffffff;
                border-radius: 10px;
                padding: 18px 20px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            }
            .sr-muted { color: #6b7280; font-size: 14px; }
            .sr-badge {
                display: inline-flex;
                align-items: center;
                padding: 2px 8px;
                border-radius: 999px;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: .04em;
                background: #e5e7eb;
                color: #374151;
            }
            .sr-badge--radio { background: #DBEAFE; color: #1D4ED8; }
            .sr-badge--tv { background: #FEF3C7; color: #92400E; }
            .sr-badge--beta { background: #E0E7FF; color: #4338CA; }
            table.sr-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
            }
            table.sr-table thead { background: #f9fafb; }
            table.sr-table th,
            table.sr-table td {
                padding: 8px 10px;
                border-bottom: 1px solid #e5e7eb;
                text-align: left;
                vertical-align: top;
            }
            table.sr-table th {
                font-weight: 600;
                color: #4b5563;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: .03em;
            }
            .sr-input-error {
                border-color: #DC2626 !important;
                box-shadow: 0 0 0 1px rgba(220,38,38,0.15);
            }
            .sr-input-ok {
                border-color: #16A34A !important;
                box-shadow: 0 0 0 1px rgba(22,163,74,0.12);
            }
            .sr-toast {
                position: fixed;
                top: 16px;
                right: 16px;
                padding: 10px 14px;
                border-radius: 999px;
                font-size: 13px;
                z-index: 9999;
                opacity: 0;
                transform: translateY(-10px);
                pointer-events: none;
                transition: all .3s ease;
            }
            .sr-toast--success {
                background: #16A34A;
                color: #ECFDF5;
            }
            .sr-toast--visible {
                opacity: 1;
                transform: translateY(0);
            }
        </style>
    </head>
    <body>
    <div class="sr-app-shell">
        <!-- SIDEBAR -->
        <aside class="sr-sidebar">
            <div class="sr-sidebar-header">
                📻 System Reklamowy
            </div>
            <ul class="sr-menu">
                <?php
                $links = [
                    'dashboard'     => 'Dashboard',
                    'kontrahenci'   => 'Kontrahenci',
                    'zlecenia-radio'=> 'Zlecenia RADIO',
                    'zlecenia-tv'   => 'Zlecenia TV',
                    'grafik-radio'  => 'Grafik RADIO',
                    'ustawienia'    => 'Ustawienia',
                ];
                foreach ( $links as $key => $label ) :
                    $url       = add_query_arg( 'view', $key, $base_url );
                    $is_active = ( $view === $key ) || ( $view === 'dashboard' && $key === 'dashboard' );
                    ?>
                    <li>
    				<a href="<?php echo esc_url( $url ); ?>" class="<?php echo $is_active ? 'is-active' : ''; ?>">
        			<?php echo esc_html( $label ); ?>
    				</a>
					</li>
                <?php endforeach; ?>
            </ul>
            <div class="sr-sidebar-footer">
                <div style="margin-bottom:6px;">Zalogowany:</div>
                <div style="font-weight:600;"><?php echo esc_html( $current_user->display_name ); ?></div>
                <div style="margin-top:8px;">
                    <a href="<?php echo esc_url( wp_logout_url( home_url('/') ) ); ?>" style="color:#9CA3AF;">
    Wyloguj
					</a>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="sr-content">
            <header class="sr-topbar">
                <div>
                    <h1><?php echo esc_html( sr_front_get_view_title( $view ) ); ?></h1>
                    <div class="sr-muted">
                        Panel wewnętrzny emisji reklam – widok: <?php echo esc_html( $view ); ?>
                    </div>
                </div>
                <div class="sr-topbar-user">
                    <?php echo esc_html( $current_user->user_email ); ?>
                </div>
            </header>
            <section class="sr-card">
                <?php sr_front_render_view( $view ); ?>
            </section>
        </main>
    </div><!-- .sr-app-shell -->
    <?php wp_footer(); ?>
    </body>
    </html>
    <?php
    exit;
} );

/**
 * Tytuł widoku w nagłówku.
 */
function sr_front_get_view_title( string $view ): string {
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
 * Helper do pobierania meta kontrahenta – przygotowany pod Secure Custom Fields.
 * SCF operuje na standardowym post_meta, więc wystarczy get_post_meta().
 */
function sr_front_get_kontrahent_meta( int $post_id, string $key ): string {
    return (string) get_post_meta( $post_id, $key, true );
}

/**
 * Lista kontrahentów – z filtrowaniem i sortowaniem.
 */
function sr_front_render_kontrahent_list(): void {
    $base_url = add_query_arg( 'view', 'kontrahenci', get_permalink() );
    $q        = isset( $_GET['kq'] ) ? sanitize_text_field( wp_unslash( $_GET['kq'] ) ) : '';
    $sort     = isset( $_GET['ksort'] ) ? sanitize_key( wp_unslash( $_GET['ksort'] ) ) : 'nazwa';
    $order    = isset( $_GET['korder'] ) && strtolower( wp_unslash( $_GET['korder'] ) ) === 'desc' ? 'DESC' : 'ASC';

    $orderby  = 'title';
    $meta_key = '';

    if ( $sort === 'miasto' ) {
        $orderby  = 'meta_value';
        $meta_key = 'miasto';
    } elseif ( $sort === 'nip' ) {
        $orderby  = 'meta_value';
        $meta_key = 'nip';
    }

    $args = [
        'post_type'      => 'sr_kontrahent',
        'posts_per_page' => -1,
        'orderby'        => $orderby,
        'order'          => $order,
    ];

    if ( $meta_key ) {
        $args['meta_key'] = $meta_key;
    }

    if ( $q !== '' ) {
        $args['s'] = $q; // search po tytule
    }

    $query        = new WP_Query( $args );
    $kontrahenci  = $query->posts;

    echo '<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">';
    echo '<div>';
    echo '<h2 style="margin:0 0 4px;">Kontrahenci</h2>';
    echo '<p class="sr-muted" style="margin:0;">Lista kontrahentów zarejestrowanych w systemie.</p>';
    echo '</div>';

    // Toast po udanym zapisie
    if ( isset( $_GET['updated'] ) && $_GET['updated'] === '1' ) {
        echo '<div id="sr-toast" class="sr-toast sr-toast--success">Kontrahent został zapisany.</div>';
    }

    $new_url = add_query_arg(
        [
            'view'   => 'kontrahenci',
            'action' => 'new',
        ],
        get_permalink()
    );

    echo '<div>';
    echo '<a href="' . esc_url( $new_url ) . '" style="
        display:inline-flex;align-items:center;gap:6px;
        padding:8px 14px;border-radius:999px;
        background:#111827;color:#F9FAFB;
        text-decoration:none;font-size:13px;
    ">
        + Dodaj kontrahenta
    </a>';
    echo '</div>';
    echo '</div>';

    // Formularz filtrów
    echo '<form method="get" style="
        margin-top:16px;margin-bottom:12px;
        display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;
    ">
        <input type="hidden" name="view" value="kontrahenci">
        <div>
            <label style="font-size:12px; text-transform:uppercase; color:#6b7280;">Szukaj</label><br>
            <input type="text" name="kq" value="' . esc_attr( $q ) . '" style="
                padding:6px 10px;border-radius:6px;border:1px solid #d1d5db;min-width:220px;
            ">
        </div>
        <div>
            <label style="font-size:12px; text-transform:uppercase; color:#6b7280;">Sortuj wg</label><br>
            <select name="ksort" style="padding:6px 10px;border-radius:6px;border:1px solid #d1d5db;">
                <option value="nazwa"' . selected( $sort, 'nazwa', false ) . '>Nazwa</option>
                <option value="nip"' . selected( $sort, 'nip', false ) . '>NIP</option>
                <option value="miasto"' . selected( $sort, 'miasto', false ) . '>Miasto</option>
            </select>
        </div>
        <div>
            <label style="font-size:12px; text-transform:uppercase; color:#6b7280;">Kolejność</label><br>
            <select name="korder" style="padding:6px 10px;border-radius:6px;border:1px solid #d1d5db;">
                <option value="asc"' . selected( strtolower( $order ), 'asc', false ) . '>Rosnąco</option>
                <option value="desc"' . selected( strtolower( $order ), 'desc', false ) . '>Malejąco</option>
            </select>
        </div>
        <div>
            <button type="submit" style="
                padding:7px 14px;border-radius:999px;border:none;
                background:#2563EB;color:#F9FAFB;font-size:13px;cursor:pointer;
            ">Filtruj</button>
        </div>
        <div>
            <a href="' . esc_url( $base_url ) . '" style="font-size:13px;color:#6b7280;">Wyczyść</a>
        </div>
    </form>';

    if ( empty( $kontrahenci ) ) {
        echo '<div class="sr-card" style="margin-top:8px;">Brak kontrahentów do wyświetlenia.</div>';
        return;
    }

    echo '<table class="sr-table" style="margin-top:4px;">';
    echo '<thead>
        <tr>
            <th>Nazwa firmy</th>
            <th>NIP</th>
            <th>Adres</th>
            <th>Kod</th>
            <th>Miasto</th>
            <th>Akcje</th>
        </tr>
    </thead>';
    echo '<tbody>';

    foreach ( $kontrahenci as $k ) {
        $nip    = sr_front_get_kontrahent_meta( $k->ID, 'nip' );
        $adres  = sr_front_get_kontrahent_meta( $k->ID, 'adres' );
        $kod    = sr_front_get_kontrahent_meta( $k->ID, 'kod' );
        $miasto = sr_front_get_kontrahent_meta( $k->ID, 'miasto' );

        $edit_url_front = add_query_arg(
            [
                'view'   => 'kontrahenci',
                'action' => 'edit',
                'id'     => $k->ID,
            ],
            get_permalink()
        );

        echo '<tr>
            <td><strong>' . esc_html( get_the_title( $k->ID ) ) . '</strong></td>
            <td>' . esc_html( $nip ) . '</td>
            <td>' . esc_html( $adres ) . '</td>
            <td>' . esc_html( $kod ) . '</td>
            <td>' . esc_html( $miasto ) . '</td>
            <td><a href="' . esc_url( $edit_url_front ) . '">Edytuj</a></td>
        </tr>';
    }

    echo '</tbody></table>';
}

/**
 * Formularz dodawania/edycji kontrahenta.
 */
function sr_front_render_kontrahent_form( int $post_id = 0 ): void {
    $is_edit   = $post_id > 0;
    $nazwa     = $is_edit ? get_the_title( $post_id ) : '';
    $nip       = $is_edit ? sr_front_get_kontrahent_meta( $post_id, 'nip' ) : '';
    $adres     = $is_edit ? sr_front_get_kontrahent_meta( $post_id, 'adres' ) : '';
    $kod       = $is_edit ? sr_front_get_kontrahent_meta( $post_id, 'kod' ) : '';
    $miasto    = $is_edit ? sr_front_get_kontrahent_meta( $post_id, 'miasto' ) : '';
    $przedmiot = $is_edit ? sr_front_get_kontrahent_meta( $post_id, 'przedmiot_dzialalnosci' ) : '';

    $back_url = add_query_arg( 'view', 'kontrahenci', get_permalink() );

    echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">';
    echo '<div>';
    echo '<h2 style="margin:0 0 4px;">' . ( $is_edit ? 'Edycja kontrahenta' : 'Nowy kontrahent' ) . '</h2>';
    echo '<p class="sr-muted" style="margin:0;">Uzupełnij dane kontrahenta i zapisz.</p>';
    echo '</div>';
    echo '<div><a href="' . esc_url( $back_url ) . '" style="font-size:13px;color:#6b7280;">← Powrót do listy</a></div>';
    echo '</div>';

    // Błędy z walidacji backendowej
    if ( isset( $_GET['error'] ) ) {
        $errors = json_decode( base64_decode( wp_unslash( $_GET['error'] ) ), true );
        if ( is_array( $errors ) && ! empty( $errors ) ) {
            echo '<div id="sr-errors-box" style="margin-top:12px;padding:10px 12px;border-radius:8px;background:#FEF2F2;color:#B91C1C;font-size:13px;">';
            foreach ( $errors as $err ) {
                echo '<div>• ' . esc_html( $err ) . '</div>';
            }
            echo '</div>';
        }
    }

    echo '<form method="post" style="margin-top:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">';
    wp_nonce_field( 'sr_save_kontrahent', 'sr_nonce' );
    echo '<input type="hidden" name="sr_action" value="save_kontrahent">';
    if ( $is_edit ) {
        echo '<input type="hidden" name="kontrahent_id" value="' . esc_attr( $post_id ) . '">';
    }

    // Nazwa
    echo '<div>
        <label style="display:block;font-size:13px;margin-bottom:4px;">Nazwa firmy *</label>
        <input type="text" name="nazwa" value="' . esc_attr( $nazwa ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">
    </div>';

    // NIP
    echo '<div>
        <label style="display:block;font-size:13px;margin-bottom:4px;">NIP</label>
        <input type="text" name="nip" value="' . esc_attr( $nip ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">
    </div>';

    // Adres
    echo '<div style="grid-column:1/-1;">
        <label style="display:block;font-size:13px;margin-bottom:4px;">Adres</label>
        <textarea name="adres" rows="2" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">' . esc_textarea( $adres ) . '</textarea>
    </div>';

    // Kod
    echo '<div>
        <label style="display:block;font-size:13px;margin-bottom:4px;">Kod pocztowy</label>
        <input type="text" name="kod" value="' . esc_attr( $kod ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">
    </div>';

    // Miasto
    echo '<div>
        <label style="display:block;font-size:13px;margin-bottom:4px;">Miasto</label>
        <input type="text" name="miasto" value="' . esc_attr( $miasto ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">
    </div>';

    // Przedmiot działalności
    echo '<div style="grid-column:1/-1;">
        <label style="display:block;font-size:13px;margin-bottom:4px;">Przedmiot działalności</label>
        <input type="text" name="przedmiot_dzialalnosci" value="' . esc_attr( $przedmiot ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">
    </div>';

    echo '<div style="grid-column:1/-1;margin-top:8px;">
        <button type="submit" style="
            padding:9px 18px;border-radius:999px;border:none;
            background:#16A34A;color:#F9FAFB;font-size:14px;cursor:pointer;
        ">' . ( $is_edit ? 'Zapisz zmiany' : 'Dodaj kontrahenta' ) . '</button>
    </div>';

    echo '</form>';
}

/**
 * Router widoków panelu frontendowego.
 */
function sr_front_render_view( string $view ): void {
    switch ( $view ) {
        case 'kontrahenci':
            $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
            $id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

            if ( $action === 'edit' && $id > 0 ) {
                sr_front_render_kontrahent_form( $id );
            } elseif ( $action === 'new' ) {
                sr_front_render_kontrahent_form( 0 );
            } else {
                sr_front_render_kontrahent_list();
            }
            break;

        case 'zlecenia-radio':
        	sr_front_render_zlecenia_radio_list();
        	break;

    	case 'zlecenia-radio-add':
        	sr_front_render_zlecenia_radio_add();
        	break;
			
		case 'zlecenia-radio-edit':
        	sr_front_render_zlecenia_radio_edit();
        	break;
			
		case 'zlecenia-radio-plan':
        	sr_front_render_zlecenia_radio_plan();
        	break;

        case 'zlecenia-tv':
            echo '<h2>Zlecenia TV</h2>';
            echo '<p class="sr-muted">Tu później podłączymy moduł zleceń telewizyjnych.</p>';
            break;

        case 'grafik-radio':
            echo '<h2>Grafik emisji – RADIO</h2>';
            echo '<p class="sr-muted">W tym widoku pojawi się grafik bloków reklam i eksporty (PDF / TXT).</p>';
            break;

        case 'ustawienia':
            echo '<h2>Ustawienia systemu</h2>';
            echo '<p class="sr-muted">Konfiguracja cenników, rabatów i przeliczników czasu spotów.</p>';
            break;

        case 'dashboard':
        default:
            echo '<h2>Dashboard</h2>';
            echo '<p class="sr-muted">Podsumowanie systemu reklamowego (zlecenia, dzisiejsze emisje, itp.).</p>';
            break;
    }
}

/**
 * JS – walidacja live, maski, toast, lookup NIP.
 */
add_action( 'wp_footer', function () {
    if ( ! is_page( 'panel-reklamy' ) ) {
        return;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Lokujemy formularz kontrahenta
            const actionInput = document.querySelector('form[method="post"] input[name="sr_action"][value="save_kontrahent"]');
            const form = actionInput ? actionInput.form : null;

            // Toast "zapisano"
            const toast = document.getElementById('sr-toast');
            if (toast) {
                setTimeout(() => toast.classList.add('sr-toast--visible'), 100);
                setTimeout(() => {
                    toast.classList.remove('sr-toast--visible');
                    setTimeout(() => toast.remove(), 400);
                }, 4100);
            }

            if (!form) {
                return; // nie jesteśmy na widoku formularza
            }

            const nazwaInput = form.querySelector('input[name="nazwa"]');
            const nipInput = form.querySelector('input[name="nip"]');
            const kodInput = form.querySelector('input[name="kod"]');
            const miastoInput = form.querySelector('input[name="miasto"]');
            const przedmiotInput = form.querySelector('input[name="przedmiot_dzialalnosci"]');
            const adresTextarea = form.querySelector('textarea[name="adres"]');

            // Reguły walidacji (JS)
            function validateField(fieldName, value) {
                value = value.trim();
                switch (fieldName) {
                    case 'nazwa':
                        if (value === '') return 'Nazwa firmy jest wymagana.';
                        return null;
                    case 'nip':
                        if (value === '') return null;
                        const digits = value.replace(/\D/g, '');
                        if (!/^[0-9]{10}$/.test(digits)) return 'NIP musi mieć 10 cyfr.';
                        return null;
                    case 'kod':
                        if (value === '') return null;
                        if (!/^[0-9]{2}-[0-9]{3}$/.test(value)) return 'Kod pocztowy musi być w formacie 00-000.';
                        return null;
                    case 'miasto':
                        if (value === '') return null;
                        if (!/^[\p{L}\s\-]+$/u.test(value)) return 'Miasto zawiera niedozwolone znaki.';
                        return null;
                    default:
                        return null;
                }
            }

            function markField(input, errorText) {
                if (!input) return;
                input.classList.remove('sr-input-error', 'sr-input-ok');
                if (errorText) {
                    input.classList.add('sr-input-error');
                } else if (input.value.trim() !== '') {
                    input.classList.add('sr-input-ok');
                }
            }

            // Maski: NIP (cyfry, max 10)
            if (nipInput) {
                nipInput.addEventListener('input', function (e) {
                    let v = e.target.value.replace(/\D/g, '');
                    if (v.length > 10) v = v.slice(0, 10);
                    e.target.value = v;
                });
                nipInput.addEventListener('blur', function (e) {
                    const v = e.target.value;
                    const err = validateField('nip', v);
                    markField(e.target, err);
                    // Jeśli poprawny NIP -> lookup NIP (REST)
                    const digits = v.replace(/\D/g, '');
                    if (!err && digits.length === 10) {
                        srLookupNip(digits, form);
                    }
                });
            }

            // Maski: kod pocztowy 00-000
            if (kodInput) {
                kodInput.addEventListener('input', function (e) {
                    let v = e.target.value.replace(/\D/g, '');
                    if (v.length > 5) v = v.slice(0, 5);
                    if (v.length >= 3) {
                        v = v.slice(0, 2) + '-' + v.slice(2);
                    }
                    e.target.value = v;
                });
                kodInput.addEventListener('blur', function (e) {
                    const err = validateField('kod', e.target.value);
                    markField(e.target, err);
                });
            }

            if (nazwaInput) {
                nazwaInput.addEventListener('blur', function (e) {
                    const err = validateField('nazwa', e.target.value);
                    markField(e.target, err);
                });
            }

            if (miastoInput) {
                miastoInput.addEventListener('blur', function (e) {
                    const err = validateField('miasto', e.target.value);
                    markField(e.target, err);
                });
            }

            // Walidacja całości przy submit
            form.addEventListener('submit', function (e) {
                let errors = [];
                const nazwa = nazwaInput ? nazwaInput.value : '';
                const nip = nipInput ? nipInput.value : '';
                const kod = kodInput ? kodInput.value : '';
                const miasto = miastoInput ? miastoInput.value : '';

                const nazwaErr = validateField('nazwa', nazwa);
                const nipErr = validateField('nip', nip);
                const kodErr = validateField('kod', kod);
                const miastoErr = validateField('miasto', miasto);

                markField(nazwaInput, nazwaErr);
                markField(nipInput, nipErr);
                markField(kodInput, kodErr);
                markField(miastoInput, miastoErr);

                [nazwaErr, nipErr, kodErr, miastoErr].forEach(function (err) {
                    if (err) errors.push(err);
                });

                if (errors.length > 0) {
                    e.preventDefault();
                    let box = document.getElementById('sr-errors-box');
                    if (!box) {
                        box = document.createElement('div');
                        box.id = 'sr-errors-box';
                        box.style.cssText = "margin-top:12px;padding:10px 12px;border-radius:8px;background:#FEF2F2;color:#B91C1C;font-size:13px;";
                        form.parentNode.insertBefore(box, form);
                    }
                    box.innerHTML = errors.map(function (msg) {
                        return '• ' + msg;
                    }).join('<br>');
                    window.scrollTo({ top: box.offsetTop - 80, behavior: 'smooth' });
                }
            });

            // NIP lookup przez REST (szkielet – teraz zwraca dane przykładowe)
            function srLookupNip(nipDigits, form) {
                const url = window.location.origin + '/wp-json/sr/v1/nip-lookup?nip=' + encodeURIComponent(nipDigits);
                fetch(url, {
                    credentials: 'same-origin'
                })
                .then(function (resp) {
                    if (!resp.ok) return null;
                    return resp.json();
                })
                .then(function (data) {
                    if (!data || !data.success) return;
                    // Uzupełniamy pola JEŚLI są puste
                    if (data.nazwa && nazwaInput && nazwaInput.value.trim() === '') {
                        nazwaInput.value = data.nazwa;
                    }
                    if (data.adres && adresTextarea && adresTextarea.value.trim() === '') {
                        adresTextarea.value = data.adres;
                    }
                    if (data.kod && kodInput && kodInput.value.trim() === '') {
                        kodInput.value = data.kod;
                    }
                    if (data.miasto && miastoInput && miastoInput.value.trim() === '') {
                        miastoInput.value = data.miasto;
                    }
                    if (data.przedmiot_dzialalnosci && przedmiotInput && przedmiotInput.value.trim() === '') {
                        przedmiotInput.value = data.przedmiot_dzialalnosci;
                    }

                    const t = document.createElement('div');
                    t.className = 'sr-toast sr-toast--success sr-toast--visible';
                    t.textContent = 'Dane kontrahenta zostały pobrane na podstawie NIP.';
                    document.body.appendChild(t);
                    setTimeout(() => {
                        t.classList.remove('sr-toast--visible');
                        setTimeout(() => t.remove(), 400);
                    }, 3500);
                })
                .catch(function () {
                    // przy błędzie sieci użytkownik po prostu wypełni dane ręcznie
                });
            }
        });
    </script>
    <?php
} );

/**
 * REST API – lookup NIP (stub pod GUS/VIES).
 */
add_action( 'rest_api_init', function () {
    register_rest_route(
        'sr/v1',
        '/nip-lookup',
        [
            'methods'             => 'GET',
            'permission_callback' => function () {
                // tylko zalogowani z odpowiednim uprawnieniem
                return current_user_can( 'manage_options' );
            },
            'callback'            => 'sr_rest_nip_lookup',
        ]
    );
} );

function sr_rest_nip_lookup( $request ) {
    $nip = preg_replace( '/\D/', '', (string) $request->get_param( 'nip' ) );
    if ( strlen( $nip ) !== 10 ) {
        return new WP_REST_Response(
            [ 'success' => false, 'message' => 'Nieprawidłowy NIP.' ],
            400
        );
    }

    // TODO: tutaj wpinamy PRAWDZIWĄ integrację z GUS / VIES:
    // np. wp_remote_post() / SoapClient itp.
    // Na razie stub z przykładowymi danymi:
    $data = [
        'success'                => true,
        'nazwa'                  => 'Przykładowa firma sp. z o.o.',
        'adres'                  => 'ul. Testowa 1',
        'kod'                    => '00-001',
        'miasto'                 => 'Warszawa',
        'przedmiot_dzialalnosci' => 'Działalność przykładowa',
    ];

    return rest_ensure_response( $data );
}
