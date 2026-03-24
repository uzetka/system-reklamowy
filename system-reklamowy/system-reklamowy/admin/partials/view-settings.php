<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$table_cennik    = $wpdb->prefix . 'sr_cennik';
$table_przel     = $wpdb->prefix . 'sr_przelicznik_czasu';
$table_przedmiot = $wpdb->prefix . 'sr_przedmiot_dzialalnosci';

$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'radio_cennik';

// Dane do widoków
$cennik_radio = $wpdb->get_results( "SELECT * FROM {$table_cennik} WHERE kanal = 'radio' ORDER BY godzina ASC" );
$cennik_tv    = $wpdb->get_results( "SELECT * FROM {$table_cennik} WHERE kanal = 'tv' ORDER BY godzina ASC" );
$przelicznik  = $wpdb->get_results( "SELECT * FROM {$table_przel} ORDER BY dlugosc_sec ASC" );

$przedmioty = [];
// Bezpiecznie sprawdzamy, czy tabela istnieje
if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_przedmiot ) ) === $table_przedmiot ) {
    $przedmioty = $wpdb->get_results( "SELECT * FROM {$table_przedmiot} ORDER BY nazwa ASC" );
}

$agencyjny_procent = (int) get_option( 'sr_rabat_agencyjny_procent', 15 );
?>

<div class="wrap">
    <?php
      $base_url = admin_url( 'admin.php?page=sr-settings' );
    ?>

    <h2 class="nav-tab-wrapper">
    <a href="<?php echo esc_url( add_query_arg( 'tab', 'radio_cennik', $base_url ) ); ?>"
       class="nav-tab <?php echo ( $tab === 'radio_cennik' ) ? 'nav-tab-active' : ''; ?>">
        Cennik RADIO
    </a>

    <a href="<?php echo esc_url( add_query_arg( 'tab', 'tv_cennik', $base_url ) ); ?>"
       class="nav-tab <?php echo ( $tab === 'tv_cennik' ) ? 'nav-tab-active' : ''; ?>">
        Cennik TV
    </a>

    <a href="<?php echo esc_url( add_query_arg( 'tab', 'przelicznik', $base_url ) ); ?>"
       class="nav-tab <?php echo ( $tab === 'przelicznik' ) ? 'nav-tab-active' : ''; ?>">
        Przelicznik czasu
    </a>

    <a href="<?php echo esc_url( add_query_arg( 'tab', 'przedmiot', $base_url ) ); ?>"
       class="nav-tab <?php echo ( $tab === 'przedmiot' ) ? 'nav-tab-active' : ''; ?>">
        Przedmiot działalności
    </a>

    <a href="<?php echo esc_url( add_query_arg( 'tab', 'rabaty', $base_url ) ); ?>"
       class="nav-tab <?php echo ( $tab === 'rabaty' ) ? 'nav-tab-active' : ''; ?>">
        Rabaty
    </a>
    </h2>

    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Zapisano ustawienia.', 'system-reklamowy' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( isset( $_GET['deleted'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Usunięto rekord.', 'system-reklamowy' ); ?></p>
        </div>
    <?php endif; ?>

    <?php
    /* ==========================================================
                          C E N N I K   R A D I O
       ========================================================== */
    if ( $tab === 'radio_cennik' ) :
        ?>
        <h2>Cennik emisji reklam RADIO</h2>

        <p>
            <button type="button"
                    class="button button-primary sr-open-modal"
                    data-modal="sr-modal-cennik-radio">
                Dodaj
            </button>
        </p>

        <table class="widefat fixed striped">
            <thead>
            <tr>
                <th>Godzina (HH:MM)</th>
                <th>Cena</th>
                <th>Cena weekend</th>
                <th>Start reklamy</th>
                <th>Aktywna</th>
                <th>Edycja</th>
            </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $cennik_radio ) ) : ?>
                <?php foreach ( $cennik_radio as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( substr( $row->godzina, 0, 5 ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $row->cena, 2 ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $row->cena_weekend, 2 ) ); ?></td>
                        <td><?php echo ( $row->start_reklamy === 'BackwardFloating' ) ? '&gt;&gt;' : '&lt;&lt;'; ?></td>
                        <td><?php echo $row->aktywna ? 'ON' : 'OFF'; ?></td>
                        <td>
                            <button type="button"
                                    class="button button-small sr-edit-cennik"
                                    data-modal="sr-modal-cennik-radio"
                                    data-id="<?php echo esc_attr( $row->id ); ?>"
                                    data-kanal="radio"
                                    data-godzina="<?php echo esc_attr( substr( $row->godzina, 0, 5 ) ); ?>"
                                    data-cena="<?php echo esc_attr( number_format( $row->cena, 2, ',', '' ) ); ?>"
                                    data-cena_weekend="<?php echo esc_attr( number_format( $row->cena_weekend, 2, ',', '' ) ); ?>"
                                    data-start="<?php echo esc_attr( $row->start_reklamy ); ?>"
                                    data-aktywna="<?php echo esc_attr( $row->aktywna ); ?>">
                                Edycja
                            </button>

                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Usunąć ten rekord?');">
                                <?php wp_nonce_field( 'sr_settings_nonce', 'sr_settings_nonce' ); ?>
                                <input type="hidden" name="sr_settings_action" value="delete_cennik">
                                <input type="hidden" name="id" value="<?php echo esc_attr( $row->id ); ?>">
                                <input type="hidden" name="kanal" value="radio">
                                <button type="submit" class="button button-small button-link-delete">
                                    Usuń
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="6">Brak zdefiniowanych godzin.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Modal: Cennik RADIO -->
        <div id="sr-modal-cennik-radio" class="sr-modal">
            <div class="sr-modal-content">
                <h2 id="sr-modal-cennik-radio-title">Dodaj godzinę RADIO</h2>

                <form method="post">
                    <?php wp_nonce_field( 'sr_settings_nonce', 'sr_settings_nonce' ); ?>
                    <input type="hidden" name="sr_settings_action" value="save_cennik">
                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="kanal" value="radio">

                    <table class="form-table">
                        <tr>
                            <th><label for="sr-godzina-radio">Godzina (HH:MM)</label></th>
                            <td><input type="time" id="sr-godzina-radio" name="godzina" required></td>
                        </tr>
                        <tr>
                            <th><label for="sr-cena-radio">Cena</label></th>
                            <td><input type="text" id="sr-cena-radio" name="cena" value="0,00"></td>
                        </tr>
                        <tr>
                            <th><label for="sr-cena-weekend-radio">Cena weekend</label></th>
                            <td><input type="text" id="sr-cena-weekend-radio" name="cena_weekend" value="0,00"></td>
                        </tr>
                        <tr>
                            <th>Start reklamy</th>
                            <td>
                                <label>
                                    <input type="radio" name="start_reklamy" value="BackwardFloating" checked>
                                    &gt;&gt; (BackwardFloating)
                                </label><br>
                                <label>
                                    <input type="radio" name="start_reklamy" value="Floating">
                                    &lt;&lt; (Floating)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th>Aktywna</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aktywna" value="1" checked> ON
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="button" class="button sr-close-modal">Anuluj</button>
                        <button type="submit" class="button button-primary">Zapisz</button>
                    </p>
                </form>
            </div>
        </div>

    <?php
    /* ==========================================================
                            C E N N I K   T V
       ========================================================== */
    elseif ( $tab === 'tv_cennik' ) :
        ?>
        <h2>Cennik emisji reklam TV</h2>

        <p>
            <button type="button"
                    class="button button-primary sr-open-modal"
                    data-modal="sr-modal-cennik-tv">
                Dodaj
            </button>
        </p>

        <table class="widefat fixed striped">
            <thead>
            <tr>
                <th>Godzina (HH:MM)</th>
                <th>Cena</th>
                <th>Cena weekend</th>
                <th>Aktywna</th>
                <th>Edycja</th>
            </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $cennik_tv ) ) : ?>
                <?php foreach ( $cennik_tv as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( substr( $row->godzina, 0, 5 ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $row->cena, 2 ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $row->cena_weekend, 2 ) ); ?></td>
                        <td><?php echo $row->aktywna ? 'ON' : 'OFF'; ?></td>
                        <td>
                            <button type="button"
                                    class="button button-small sr-edit-cennik"
                                    data-modal="sr-modal-cennik-tv"
                                    data-id="<?php echo esc_attr( $row->id ); ?>"
                                    data-kanal="tv"
                                    data-godzina="<?php echo esc_attr( substr( $row->godzina, 0, 5 ) ); ?>"
                                    data-cena="<?php echo esc_attr( number_format( $row->cena, 2, ',', '' ) ); ?>"
                                    data-cena_weekend="<?php echo esc_attr( number_format( $row->cena_weekend, 2, ',', '' ) ); ?>"
                                    data-aktywna="<?php echo esc_attr( $row->aktywna ); ?>">
                                Edycja
                            </button>

                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Usunąć ten rekord?');">
                                <?php wp_nonce_field( 'sr_settings_nonce', 'sr_settings_nonce' ); ?>
                                <input type="hidden" name="sr_settings_action" value="delete_cennik">
                                <input type="hidden" name="id" value="<?php echo esc_attr( $row->id ); ?>">
                                <input type="hidden" name="kanal" value="tv">
                                <button type="submit" class="button button-small button-link-delete">
                                    Usuń
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="5">Brak zdefiniowanych godzin.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Modal: Cennik TV -->
        <div id="sr-modal-cennik-tv" class="sr-modal">
            <div class="sr-modal-content">
                <h2>Dodaj godzinę TV</h2>

                <form method="post">
                    <?php wp_nonce_field( 'sr_settings_nonce', 'sr_settings_nonce' ); ?>
                    <input type="hidden" name="sr_settings_action" value="save_cennik">
                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="kanal" value="tv">

                    <table class="form-table">
                        <tr>
                            <th><label for="sr-godzina-tv">Godzina (HH:MM)</label></th>
                            <td><input type="time" id="sr-godzina-tv" name="godzina" required></td>
                        </tr>
                        <tr>
                            <th><label for="sr-cena-tv">Cena</label></th>
                            <td><input type="text" id="sr-cena-tv" name="cena" value="0,00"></td>
                        </tr>
                        <tr>
                            <th><label for="sr-cena-weekend-tv">Cena weekend</label></th>
                            <td><input type="text" id="sr-cena-weekend-tv" name="cena_weekend" value="0,00"></td>
                        </tr>
                        <tr>
                            <th>Aktywna</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aktywna" value="1" checked> ON
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="button" class="button sr-close-modal">Anuluj</button>
                        <button type="submit" class="button button-primary">Zapisz</button>
                    </p>
                </form>
            </div>
        </div>

    <?php
    /* ==========================================================
                       P R Z E L I C Z N I K   C Z A S U
       ========================================================== */
    elseif ( $tab === 'przelicznik' ) :
        ?>
        <h2>Przelicznik czasu spotu</h2>

        <p>
            <button type="button"
                    class="button button-primary sr-open-modal"
                    data-modal="sr-modal-przelicznik">
                Dodaj
            </button>
        </p>

        <table class="widefat fixed striped">
            <thead>
            <tr>
                <th>Długość (sekundy)</th>
                <th>Mnożnik ceny</th>
                <th>Edycja</th>
            </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $przelicznik ) ) : ?>
                <?php foreach ( $przelicznik as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row->dlugosc_sec ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $row->mnoznik, 2 ) ); ?></td>
                        <td>
                            <button type="button"
                                    class="button button-small sr-edit-przelicznik"
                                    data-modal="sr-modal-przelicznik"
                                    data-id="<?php echo esc_attr( $row->id ); ?>"
                                    data-dlugosc="<?php echo esc_attr( $row->dlugosc_sec ); ?>"
                                    data-mnoznik="<?php echo esc_attr( number_format( $row->mnoznik, 2, ',', '' ) ); ?>">
                                Edycja
                            </button>

                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Usunąć ten rekord?');">
                                <?php wp_nonce_field( 'sr_settings_nonce', 'sr_settings_nonce' ); ?>
                                <input type="hidden" name="sr_settings_action" value="delete_przelicznik">
                                <input type="hidden" name="id" value="<?php echo esc_attr( $row->id ); ?>">
                                <button type="submit" class="button button-small button-link-delete">
                                    Usuń
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="3">Brak zdefiniowanych przeliczników.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Modal: Przelicznik -->
        <div id="sr-modal-przelicznik" class="sr-modal">
            <div class="sr-modal-content">
                <h2>Dodaj przelicznik</h2>

                <form method="post">
                    <?php wp_nonce_field( 'sr_settings_nonce', 'sr_settings_nonce' ); ?>
                    <input type="hidden" name="sr_settings_action" value="save_przelicznik">
                    <input type="hidden" name="id" value="">

                    <table class="form-table">
                        <tr>
                            <th><label for="sr-dlugosc">Długość (sekundy)</label></th>
                            <td><input type="number" id="sr-dlugosc" name="dlugosc" min="1" step="1" required></td>
                        </tr>
                        <tr>
                            <th><label for="sr-mnoznik">Mnożnik ceny</label></th>
                            <td><input type="text" id="sr-mnoznik" name="mnoznik" value="1,00"></td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="button" class="button sr-close-modal">Anuluj</button>
                        <button type="submit" class="button button-primary">Zapisz</button>
                    </p>
                </form>
            </div>
        </div>

    <?php
    /* ==========================================================
                   P R Z E D M I O T   D Z I A Ł A L N O Ś C I
       ========================================================== */
    elseif ( $tab === 'przedmiot' ) :
        ?>
        <h2>Przedmiot działalności</h2>

        <p>
            <button type="button"
                    class="button button-primary sr-open-modal"
                    data-modal="sr-modal-przedmiot">
                Dodaj
            </button>
        </p>

        <table class="widefat fixed striped">
            <thead>
            <tr>
                <th>Nazwa</th>
                <th>Aktywna</th>
                <th>Edycja</th>
            </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $przedmioty ) ) : ?>
                <?php foreach ( $przedmioty as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row->nazwa ); ?></td>
                        <td><?php echo $row->aktywna ? 'ON' : 'OFF'; ?></td>
                        <td>
                            <button type="button"
                                    class="button button-small sr-edit-przedmiot"
                                    data-modal="sr-modal-przedmiot"
                                    data-id="<?php echo esc_attr( $row->id ); ?>"
                                    data-nazwa="<?php echo esc_attr( $row->nazwa ); ?>"
                                    data-aktywna="<?php echo esc_attr( $row->aktywna ); ?>">
                                Edycja
                            </button>

                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Usunąć ten wpis?');">
                                <?php wp_nonce_field( 'sr_settings_nonce', 'sr_settings_nonce' ); ?>
                                <input type="hidden" name="sr_settings_action" value="delete_przedmiot">
                                <input type="hidden" name="id" value="<?php echo esc_attr( $row->id ); ?>">
                                <button type="submit" class="button button-small button-link-delete">
                                    Usuń
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="3">Brak zdefiniowanych pozycji.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Modal: Przedmiot działalności -->
        <div id="sr-modal-przedmiot" class="sr-modal">
            <div class="sr-modal-content">
                <h2>Dodaj przedmiot działalności</h2>

                <form method="post">
                    <?php wp_nonce_field( 'sr_settings_nonce', 'sr_settings_nonce' ); ?>
                    <input type="hidden" name="sr_settings_action" value="save_przedmiot">
                    <input type="hidden" name="id" value="">

                    <table class="form-table">
                        <tr>
                            <th><label for="sr-nazwa-przedmiot">Nazwa</label></th>
                            <td><input type="text" id="sr-nazwa-przedmiot" name="nazwa" required></td>
                        </tr>
                        <tr>
                            <th>Aktywna</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="aktywna" value="1" checked> ON
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="button" class="button sr-close-modal">Anuluj</button>
                        <button type="submit" class="button button-primary">Zapisz</button>
                    </p>
                </form>
            </div>
        </div>

    <?php
    /* ==========================================================
                               R A B A T Y
       ========================================================== */
    elseif ( $tab === 'rabaty' ) :
        ?>
        <h2>Rabaty</h2>

        <form method="post">
            <?php wp_nonce_field( 'sr_settings_nonce', 'sr_settings_nonce' ); ?>
            <input type="hidden" name="sr_settings_action" value="save_rabaty">

            <table class="form-table">
                <tr>
                    <th><label for="sr-rabat-agencyjny">Rabat agencyjny (%)</label></th>
                    <td>
                        <input type="number"
                               id="sr-rabat-agencyjny"
                               name="rabat_agencyjny"
                               min="0"
                               max="100"
                               step="1"
                               value="<?php echo esc_attr( $agencyjny_procent ); ?>">
                        <p class="description">
                            Wykorzystywany przy wyborze rabatu „agencyjny” w zleceniach.
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Zapisz</button>
            </p>
        </form>

    <?php endif; ?>

</div>