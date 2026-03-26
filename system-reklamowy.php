<?php
/**
 * Plugin Name: System Reklamowy
 * Description: System do obsługi zleceń reklamowych (Radio/TV), grafików, raportów i cenników.
 * Version: 0.1.0
 * Author: Marcin
 * Text Domain: system-reklamowy
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // No direct access.
}

define( 'SR_VERSION', '0.1.0' );
define( 'SR_PLUGIN_FILE', __FILE__ );
define( 'SR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload prosty (bez composera) dla klas SR_*
 */
spl_autoload_register( function( $class ) {
    if ( strpos( $class, 'SR_' ) !== 0 ) {
        return;
    }

    $class_slug = strtolower( str_replace( '_', '-', $class ) );

    $paths = [
        SR_PLUGIN_DIR . 'includes/class-' . $class_slug . '.php',
        SR_PLUGIN_DIR . 'admin/class-' . $class_slug . '.php',
    ];

    foreach ( $paths as $path ) {
        if ( file_exists( $path ) ) {
            require_once $path;
            return;
        }
    }
} );

/**
 * Helpery meta (pod Secure Custom Fields)
 */
require_once SR_PLUGIN_DIR . 'includes/helpers-meta.php';
require_once SR_PLUGIN_DIR . 'includes/helpers-zlecenia-radio.php';
// Frontend Panel – CRM
require_once __DIR__ . '/includes/class-sr-frontend.php';

/**
 * Aktywacja wtyczki
 */
function sr_activate() {
    require_once SR_PLUGIN_DIR . 'includes/class-sr-activator.php';
    SR_Activator::activate();
}
register_activation_hook( __FILE__, 'sr_activate' );

/**
 * Deaktywacja wtyczki
 */
function sr_deactivate() {
    require_once SR_PLUGIN_DIR . 'includes/class-sr-deactivator.php';
    SR_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'sr_deactivate' );

/**
 * Start pluginu
 */
function sr_run_plugin() {
    // Loader hooków – centralne miejsce.
    require_once SR_PLUGIN_DIR . 'includes/class-sr-loader.php';
    $loader = new SR_Loader();

    // Rejestracja CPT.
    $cpt = new SR_CPT();
    $loader->add_action( 'init', $cpt, 'register_post_types' );

    // Menu admina.
    $admin_menu = new SR_Admin_Menu();
    $loader->add_action( 'admin_menu', $admin_menu, 'register_menus' );

    // Ustawienia / cenniki.
    if ( is_admin() ) {
        new SR_Admin_Settings();
    }

    // Harmonogramy.
    $harmonogram = new SR_Harmonogram();
    $loader->add_action( 'admin_post_sr_generate_schedule', $harmonogram, 'handle_generate_schedule' );

    // Exporty / raporty – przykładowe endpointy.
    $export_radio = new SR_Export_Radio();
    $loader->add_action( 'admin_post_sr_export_radio_txt', $export_radio, 'handle_export_txt' );

    $raport_krrit = new SR_Raport_KRRIT();
    $loader->add_action( 'admin_post_sr_raport_krrit_pdf', $raport_krrit, 'handle_generate_pdf' );
	
	
    // 🔹 SYNC ZLECEŃ RADIO -> SQL
    $zlecenia_sync = new SR_Zlecenia_Sync();
    $loader->add_action(
        'save_post_sr_zlecenie_radio',
        $zlecenia_sync,
        'sync_zlecenie_radio',
        10,
        3
    );
	
	// 🔹 Synchronizacja zleceń TV (CPT -> SQL wp_sr_zlecenia).
    $zlecenia_tv_sync = new SR_Zlecenia_TV_Sync();
    $loader->add_action(
        'save_post_sr_zlecenie_tv',
        $zlecenia_tv_sync,
        'sync_zlecenie_tv',
        10,
        3
    );


    // Start loadera.
    $loader->run();

    // Assety (CSS/JS w panelu) – funkcja globalna, podpinamy klasycznie.
    add_action( 'admin_enqueue_scripts', 'sr_enqueue_admin_assets' );
}
add_action( 'plugins_loaded', 'sr_run_plugin' );

/**
 * Enqueue CSS/JS
 */
function sr_enqueue_admin_assets( $hook ) {
    // Opcjonalnie: ograniczaj do swoich ekranów (sprawdzaj $hook).
    wp_enqueue_style(
        'system-reklamowy-admin',
        SR_PLUGIN_URL . 'public/css/system-reklamowy.css',
        [],
        SR_VERSION
    );

    wp_enqueue_script(
        'system-reklamowy-admin',
        SR_PLUGIN_URL . 'public/js/system-reklamowy.js',
        [ 'jquery' ],
        SR_VERSION,
        true
    );
}
