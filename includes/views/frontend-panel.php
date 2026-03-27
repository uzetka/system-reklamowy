<?php
/**
 * Frontend panel – pełny layout (bez motywu).
 *
 * Ten plik przejmuje rolę HTML-a z Code Snippets (#10 – Front Panel bez motywu).
 * Wywoływany jest z klasy SR_Frontend_Router:
 *  - sprawdza slug strony: panel-reklamy
 *  - sprawdza logowanie
 *  - ustawia zmienną $view
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Nagłówki jak w snippecie – pełna kontrola nad odpowiedzią.
status_header( 200 );
nocache_headers();

// Użytkownik i podstawowe zmienne panelu.
$current_user = wp_get_current_user();

// Jeśli z jakiegoś powodu $view nie zostało ustawione w routerze, zabezpieczamy się.
if ( ! isset( $view ) || ! is_string( $view ) || $view === '' ) {
    $view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'dashboard';
}

$base_url = get_permalink();
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
                'dashboard'      => 'Dashboard',
                'kontrahenci'    => 'Kontrahenci',
                'zlecenia-radio' => 'Zlecenia RADIO',
                'zlecenia-tv'    => 'Zlecenia TV',
                'grafik-radio'   => 'Grafik RADIO',
                'ustawienia'     => 'Ustawienia',
            ];

            foreach ( $links as $key => $label ) :
                $url       = add_query_arg( 'view', $key, $base_url );
                $is_active = ( $view === $key ) || ( 'dashboard' === $view && 'dashboard' === $key );
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
                <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" style="color:#9CA3AF;">
                    Wyloguj
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="sr-content">
        <header class="sr-topbar">
            <div>
                <h1><?php echo esc_html( function_exists( 'sr_front_get_view_title' ) ? sr_front_get_view_title( $view ) : 'Panel reklamowy' ); ?></h1>
                <div class="sr-muted">
                    Panel wewnętrzny emisji reklam – widok:
                    <?php echo esc_html( $view ); ?>
                </div>
            </div>
            <div class="sr-topbar-user">
                <?php echo esc_html( $current_user->user_email ); ?>
            </div>
        </header>

        <section class="sr-card">
            <?php
            // Router widoków pochodzi na razie ze snippetu (#10):
            // function sr_front_render_view( string $view )
            if ( function_exists( 'sr_front_render_view' ) ) {
                sr_front_render_view( $view );
            } else {
                echo '<p class="sr-muted">Brak zarejestrowanego routera widoków (sr_front_render_view).</p>';
            }
            ?>
        </section>
    </main>
</div><!-- .sr-app-shell -->

<?php wp_footer(); ?>
</body>
</html>