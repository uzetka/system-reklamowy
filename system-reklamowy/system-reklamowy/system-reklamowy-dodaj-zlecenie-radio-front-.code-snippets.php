<?php

/**
 * System Reklamowy – Dodaj Zlecenie RADIO (FRONT)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Formularz "Dodaj zlecenie RADIO" – FRONT
 *
 * Założenia:
 * - jeśli podano kontrahent_id → wiążemy z istniejącym;
 * - jeśli nie, spróbujemy znaleźć po NIP, a jak nie istnieje → stworzymy nowego (w POST handlerze).
 * - W tym widoku nie generujemy jeszcze harmonogramu – tylko dane nagłówkowe.
 */
function sr_front_render_zlecenia_radio_add(): void {

    $back_url = add_query_arg( 'view', 'zlecenia-radio', get_permalink() );

    // Ewentualne dane z poprzedniego submitu (np. po błędach)
    $post_data = [
        'firma'      => isset($_POST['firma'])      ? wp_unslash($_POST['firma'])      : '',
        'nip'        => isset($_POST['nip'])        ? wp_unslash($_POST['nip'])        : '',
        'adres'      => isset($_POST['adres'])      ? wp_unslash($_POST['adres'])      : '',
        'kod'        => isset($_POST['kod'])        ? wp_unslash($_POST['kod'])        : '',
        'miasto'     => isset($_POST['miasto'])     ? wp_unslash($_POST['miasto'])     : '',
        'przedmiot'  => isset($_POST['przedmiot_dzialalnosci']) ? wp_unslash($_POST['przedmiot_dzialalnosci']) : '',
        'nazwa_rek'  => isset($_POST['nazwa_reklamy']) ? wp_unslash($_POST['nazwa_reklamy']) : '',
        'motive'     => isset($_POST['motive'])     ? wp_unslash($_POST['motive'])     : '',
        'dlugosc'    => isset($_POST['dlugosc_spotu']) ? wp_unslash($_POST['dlugosc_spotu']) : '',
        'data_start' => isset($_POST['data_start']) ? wp_unslash($_POST['data_start']) : '',
        'data_koniec'=> isset($_POST['data_koniec'])? wp_unslash($_POST['data_koniec']) : '',
        'rabat'      => isset($_POST['rabat'])      ? wp_unslash($_POST['rabat'])      : '',
        'rabat_neg'  => isset($_POST['rabat_negocjowany']) ? wp_unslash($_POST['rabat_negocjowany']) : '',
    ];

    echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">';
    echo '<div>';
    echo '<h2 style="margin:0 0 4px;">Nowe zlecenie RADIO</h2>';
    echo '<p class="sr-muted" style="margin:0;">Uzupełnij dane kontrahenta i zlecenia. Harmonogram emisji wygenerujesz w kolejnym kroku.</p>';
    echo '</div>';
    echo '<div><a href="'. esc_url($back_url) .'" style="font-size:13px;color:#6b7280;">← Powrót do listy</a></div>';
    echo '</div>';

    // Błędy walidacji (z backendu)
    if ( isset($_GET['error']) ) {
        $errors = json_decode( base64_decode( wp_unslash($_GET['error']) ), true );
        if ( is_array($errors) && ! empty($errors) ) {
            echo '<div id="sr-errors-box" style="margin-top:12px;padding:10px 12px;border-radius:8px;background:#FEF2F2;color:#B91C1C;font-size:13px;">';
            foreach ( $errors as $err ) {
                echo '<div>• '. esc_html($err) .'</div>';
            }
            echo '</div>';
        }
    }

    echo '<form method="post" style="margin-top:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">';
    wp_nonce_field( 'sr_save_zlecenie_radio', 'sr_zlecenie_radio_nonce' );
    echo '<input type="hidden" name="sr_action" value="save_zlecenie_radio">';

    // ---- DANE KONTRAHENTA ----

    // Wiersz 1: Nazwa firmy | NIP
    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Nazwa firmy *</label>';
    echo '<input type="text" name="firma" value="'. esc_attr($post_data['firma']) .'" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">NIP</label>';
    echo '<input type="text" name="nip" value="'. esc_attr($post_data['nip']) .'" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '</div>';

    // Wiersz 2: Adres | Kod | Miasto
    echo '<div style="grid-column:1/-1;">';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Adres</label>';
    echo '<textarea name="adres" rows="2" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">'. esc_textarea($post_data['adres']) .'</textarea>';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Kod pocztowy</label>';
    echo '<input type="text" name="kod" value="'. esc_attr($post_data['kod']) .'" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Miasto</label>';
    echo '<input type="text" name="miasto" value="'. esc_attr($post_data['miasto']) .'" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '</div>';

    // Przedmiot działalności – select (choices z helpers-meta / ACF)
    echo '<div style="grid-column:1/-1;">';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Przedmiot działalności</label>';

    // Jeśli korzystasz z ACF dla kontrahenta:
    $choices = function_exists('sr_get_przedmiot_dzialalnosci_choices') ? sr_get_przedmiot_dzialalnosci_choices() : [];
    echo '<select name="przedmiot_dzialalnosci" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '<option value="">– Wybierz –</option>';
    foreach ( $choices as $val => $label ) {
        echo '<option value="'. esc_attr($val) .'" '. selected($post_data['przedmiot'], $val, false) .'>'. esc_html($label) .'</option>';
    }
    echo '</select>';
    echo '</div>';

    // ---- DANE ZLECENIA ----

    // Wiersz 4: Nazwa reklamy | Motive
    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Nazwa reklamy *</label>';
    echo '<input type="text" name="nazwa_reklamy" value="'. esc_attr($post_data['nazwa_rek']) .'" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Motive</label>';
    echo '<input type="text" name="motive" value="'. esc_attr($post_data['motive']) .'" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '</div>';

    // Wiersz 5: Długość spotu | Data start | Data koniec | Rabat
    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Długość spotu *</label>';
    echo '<select name="dlugosc_spotu" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '<option value="">– Wybierz –</option>';

    if ( function_exists('sr_get_dlugosci_spotow_choices') ) {
        $len_choices = sr_get_dlugosci_spotow_choices();
        foreach ( $len_choices as $val => $label ) {
            echo '<option value="'. esc_attr($val) .'" '. selected($post_data['dlugosc'], $val, false) .'>'. esc_html($label) .'</option>';
        }
    }

    echo '</select>';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Data rozpoczęcia *</label>';
    echo '<input type="date" name="data_start" value="'. esc_attr($post_data['data_start']) .'" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Data zakończenia *</label>';
    echo '<input type="date" name="data_koniec" value="'. esc_attr($post_data['data_koniec']) .'" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '</div>';

    // Rabat
    echo '<div>';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Rabat</label>';
    echo '<select name="rabat" id="sr-rabat-radio" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '<option value="brak" '. selected($post_data['rabat'], 'brak', false) .'>Brak</option>';
    echo '<option value="agencyjny" '. selected($post_data['rabat'], 'agencyjny', false) .'>Agencyjny</option>';
    echo '<option value="100" '. selected($post_data['rabat'], '100', false) .'>100%</option>';
    echo '<option value="negocjowany" '. selected($post_data['rabat'], 'negocjowany', false) .'>Negocjowany</option>';
    echo '</select>';
    echo '</div>';

    // Pole dla rabatu negocjowanego
    echo '<div id="sr-rabat-negocjowany-wrap" style="display:'. ( $post_data['rabat']==='negocjowany' ? 'block' : 'none' ) .';">';
    echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Rabat negocjowany (%)</label>';
    echo '<input type="number" name="rabat_negocjowany" min="0" max="100" value="'. esc_attr($post_data['rabat_neg']) .'" style="
        width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;">';
    echo '</div>';

    // PRZYCISKI
    echo '<div style="grid-column:1/-1;margin-top:8px;">';
    echo '<button type="submit" style="
        padding:9px 18px;border-radius:999px;border:none;
        background:#16A34A;color:#F9FAFB;font-size:14px;cursor:pointer;">
        Zapisz i przejdź do harmonogramu
    </button>';
    echo '</div>';

    echo '</form>';

    // Mały JS do pokazywania pola "rabat negocjowany"
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var selectRabat = document.getElementById('sr-rabat-radio');
        var wrapNeg = document.getElementById('sr-rabat-negocjowany-wrap');
        if (!selectRabat || !wrapNeg) return;
        selectRabat.addEventListener('change', function () {
            if (this.value === 'negocjowany') {
                wrapNeg.style.display = 'block';
            } else {
                wrapNeg.style.display = 'none';
            }
        });
    });
    </script>
    <?php
}
