<?php
/**
 * Widok frontowego panelu /panel-reklamy
 *
 * Ten plik jest ładowany przez SR_Frontend_Router (template_redirect)
 * i odpowiada za pełny HTML:
 * - <html>, <head>, wp_head()
 * - sidebar
 * - topbar
 * - main
 * - wywołanie: sr_front_render_view( $view )
 *
 * Kluczowy jest parametr $_GET['view'], który decyduje o treści panelu.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$base_url     = get_permalink();

$view = isset( $_GET['view'] )
    ? sanitize_key( wp_unslash( $_GET['view'] ) )
    : 'dashboard';

$title = function_exists( 'sr_front_get_view_title' )
    ? sr_front_get_view_title( $view )
    : 'Panel Reklamy';
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
        .sr-app-shell {
            display: flex;
            min-height: 100vh;
        }
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
        .sr-menu a:hover {
            background: #1f2937;
        }
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
        .sr-muted {
            color: #6b7280;
            font-size: 14px;
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
            $links = array(
                'dashboard'      => 'Dashboard',
                'kontrahenci'    => 'Kontrahenci',
                'zlecenia-radio' => 'Zlecenia RADIO',
                'zlecenia-tv'    => 'Zlecenia TV',
                'grafik-radio'   => 'Grafik RADIO',
                'ustawienia'     => 'Ustawienia',
            );

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
                <h1><?php echo esc_html( $title ); ?></h1>
                <div class="sr-muted">
                    Panel wewnętrzny emisji reklam – widok: <?php echo esc_html( $view ); ?>
                </div>
            </div>

            <div class="sr-topbar-user">
                <?php echo esc_html( $current_user->user_email ); ?>
            </div>
        </header>

        <section class="sr-card">
            <?php
            if ( function_exists( 'sr_front_render_view' ) ) {
                sr_front_render_view( $view );
            } else {
                echo '<p class="sr-muted">Brak routera widoków (sr_front_render_view).</p>';
            }
            ?>
        </section>

    </main>

</div><!-- .sr-app-shell -->

<?php wp_footer(); ?>
</body>
</html>