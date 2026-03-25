<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Activator {

    public static function activate() {
        self::create_tables();
        self::add_roles();
        flush_rewrite_rules();
    }

    protected static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $table_zlecenia     = $wpdb->prefix . 'sr_zlecenia';
        $table_emisje       = $wpdb->prefix . 'sr_emisje';
        $table_cennik       = $wpdb->prefix . 'sr_cennik';
        $table_przelicznik  = $wpdb->prefix . 'sr_przelicznik_czasu';
        $table_przedmiot    = $wpdb->prefix . 'sr_przedmiot_dzialalnosci';

        // --- ZLECENIA (bez zmian względem obecnej wersji) ---
        $sql_zlecenia = "CREATE TABLE {$table_zlecenia} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            kontrahent_id BIGINT(20) UNSIGNED NULL,
            typ ENUM('radio','tv') NOT NULL DEFAULT 'radio',
            nazwa_reklamy VARCHAR(255) NOT NULL,
            data_zlecenia DATE NULL,
            data_start DATE NULL,
            data_koniec DATE NULL,
            wartosc DECIMAL(10,2) DEFAULT 0,
            do_zaplaty DECIMAL(10,2) DEFAULT 0,
            rabat VARCHAR(50) NULL,
            motive VARCHAR(255) NULL,
            dlugosc_spotu INT(11) NULL,
            status VARCHAR(50) DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY kontrahent_id (kontrahent_id),
            KEY typ (typ),
            KEY data_start (data_start),
            KEY data_koniec (data_koniec)
        ) {$charset_collate};";

        // --- EMISJE (bez zmian względem obecnej wersji) ---
        $sql_emisje = "CREATE TABLE {$table_emisje} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            zlecenie_id BIGINT(20) UNSIGNED NOT NULL,
            data_emisji DATE NOT NULL,
            godzina TIME NOT NULL,
            kanal ENUM('radio','tv') NOT NULL DEFAULT 'radio',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY zlecenie_id (zlecenie_id),
            KEY data_emisji (data_emisji),
            KEY godzina (godzina)
        ) {$charset_collate};";

        // --- CENNIK (dopasowany do istniejącej wersji – nazwy i ENUM bez zmian) ---
        $sql_cennik = "CREATE TABLE {$table_cennik} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            kanal ENUM('radio','tv') NOT NULL DEFAULT 'radio',
            godzina TIME NOT NULL,
            cena DECIMAL(10,2) DEFAULT 0,
            cena_weekend DECIMAL(10,2) DEFAULT 0,
            start_reklamy ENUM('BackwardFloating','Floating') DEFAULT 'BackwardFloating',
            aktywna TINYINT(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY kanal (kanal),
            KEY godzina (godzina)
        ) {$charset_collate};";

        // --- PRZELICZNIK CZASU (dopasowany do istniejącej wersji) ---
        $sql_przelicznik = "CREATE TABLE {$table_przelicznik} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            dlugosc_sec INT(11) NOT NULL,
            mnoznik DECIMAL(5,2) NOT NULL DEFAULT 1.00,
            PRIMARY KEY (id),
            UNIQUE KEY dlugosc_sec (dlugosc_sec)
        ) {$charset_collate};";

        // --- NOWA TABELA: PRZEDMIOT DZIAŁALNOŚCI (Etap E) ---
        $sql_przedmiot = "CREATE TABLE {$table_przedmiot} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nazwa VARCHAR(255) NOT NULL,
            aktywna TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nazwa (nazwa)
        ) {$charset_collate};";

        dbDelta( $sql_zlecenia );
        dbDelta( $sql_emisje );
        dbDelta( $sql_cennik );
        dbDelta( $sql_przelicznik );
        dbDelta( $sql_przedmiot );
    }

    protected static function add_roles() {
        // Zostawiamy tak, jak masz – rola operatora reklam
        add_role(
            'sr_operator',
            'Operator Reklam',
            [
                'read'           => true,
                'edit_posts'     => false,
                'manage_options' => false,
                'sr_manage_reklamy' => true,
                'sr_view_reports'   => true,
            ]
        );
    }
}