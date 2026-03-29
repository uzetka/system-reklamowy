<?php
/**
 * Frontend – Kontrahenci (lista + formularz + obsługa POST).
 *
 * Zgodnie z layoutem systemu (sr-panel.css)
 * i z JS: assets/js/sr-kontrahenci.js
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Kontrahenci {

    /**
     * Obsługa POST (sr_action = save_kontrahent).
     */
    public function handle_post(): void {

        // Działa tylko na /panel-reklamy.
        if ( ! is_page( 'panel-reklamy' ) ) {
            return;
        }

        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }

        if ( empty( $_POST['sr_action'] ) || 'save_kontrahent' !== $_POST['sr_action'] ) {
            return;
        }

        if (
            ! isset( $_POST['sr_nonce'] )
            || ! wp_verify_nonce( $_POST['sr_nonce'], 'sr_save_kontrahent' )
        ) {
            wp_die( 'Nieprawidłowy token bezpieczeństwa (kontrahent).' );
        }

        // Uprawnienia – na razie admin (docelowo można zmienić na sr_operator).
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Brak uprawnień do zapisu kontrahentów.' );
        }

        // Dane z formularza.
        $post_id   = isset( $_POST['kontrahent_id'] ) ? absint( $_POST['kontrahent_id'] ) : 0;
        $nazwa     = sanitize_text_field( wp_unslash( $_POST['nazwa'] ?? '' ) );
        $nip       = sanitize_text_field( wp_unslash( $_POST['nip'] ?? '' ) );
        $adres     = sanitize_text_field( wp_unslash( $_POST['adres'] ?? '' ) );
        $kod       = sanitize_text_field( wp_unslash( $_POST['kod'] ?? '' ) );
        $miasto    = sanitize_text_field( wp_unslash( $_POST['miasto'] ?? '' ) );
        $przedmiot = sanitize_text_field( wp_unslash( $_POST['przedmiot_dzialalnosci'] ?? '' ) );

        // Walidacja backendowa.
        $errors = array();

        if ( '' === $nazwa ) {
            $errors[] = 'Nazwa firmy jest wymagana.';
        }

        if ( '' !== $nip && ! preg_match( '/^[0-9]{10}$/', $nip ) ) {
            $errors[] = 'NIP musi mieć dokładnie 10 cyfr.';
        }

        if ( '' !== $kod && ! preg_match( '/^[0-9]{2}-[0-9]{3}$/', $kod ) ) {
            $errors[] = 'Kod pocztowy musi być w formacie 00-000.';
        }

        if ( '' !== $miasto && ! preg_match( '/^[\p{L}\s\-]+$/u', $miasto ) ) {
            $errors[] = 'Nazwa miasta zawiera niedozwolone znaki.';
        }

        if ( ! empty( $errors ) ) {
            $redirect = add_query_arg(
                array(
                    'view'   => 'kontrahenci',
                    'action' => $post_id ? 'edit' : 'new',
                    'error'  => base64_encode( wp_json_encode( $errors ) ),
                ),
                get_permalink()
            );
            wp_safe_redirect( $redirect );
            exit;
        }

        // Zapis / update posta.
        $post_data = array(
            'post_title'  => $nazwa,
            'post_type'   => 'sr_kontrahent',
            'post_status' => 'publish',
        );

        if ( $post_id ) {
            $post_data['ID'] = $post_id;
            $post_id         = wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            wp_die( 'Błąd podczas zapisu kontrahenta.' );
        }

        // Meta.
        update_post_meta( $post_id, 'nip', $nip );
        update_post_meta( $post_id, 'adres', $adres );
        update_post_meta( $post_id, 'kod', $kod );
        update_post_meta( $post_id, 'miasto', $miasto );
        update_post_meta( $post_id, 'przedmiot_dzialalnosci', $przedmiot );

        // Powrót na listę z toastem „zapisano”.
        $redirect = add_query_arg(
            array(
                'view'    => 'kontrahenci',
                'updated' => '1',
            ),
            get_permalink()
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Lista kontrahentów (frontend /panel-reklamy?view=kontrahenci).
     */
    public function render_list(): void {

    if ( ! current_user_can( 'manage_options' ) ) {
        echo '<p class="sr-muted">Brak uprawnień do przeglądania kontrahentów.</p>';
        return;
    }

    // Bardzo ważne: zapamiętujemy URL panelu zanim odpalimy WP_Query.
    $panel_url = get_permalink();

    $paged  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit  = 20;
    $offset = ($paged - 1) * $limit;

    $q     = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    $sort  = isset($_GET['sort']) ? sanitize_key($_GET['sort']) : 'title';
    $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

    $allowed_sort = ['title', 'nip', 'miasto'];
    if (!in_array($sort, $allowed_sort, true)) {
        $sort = 'title';
    }

    $query = new WP_Query([
        'post_type'      => 'sr_kontrahent',
        'posts_per_page' => $limit,
        'offset'         => $offset,
        'orderby'        => $sort,
        'order'          => $order,
        's'              => $q,
    ]);

    $total = $query->found_posts;

    $base_url = add_query_arg(['view' => 'kontrahenci'], $panel_url);

    /* -----------------------------------------
       NAGŁÓWEK
       ----------------------------------------- */
    echo '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;">';
    echo '  <div>';
    echo '      <h2 style="margin:0 0 4px;">Kontrahenci</h2>';
    echo '      <p class="sr-muted" style="margin:0;">Lista kontrahentów zarejestrowanych w systemie.</p>';
    echo '  </div>';

    $new_url = add_query_arg(['view' => 'kontrahenci', 'action' => 'new'], $panel_url);
    echo '  <div><a href="' . esc_url($new_url) . '" class="sr-btn sr-btn--primary">+ Dodaj kontrahenta</a></div>';
    echo '</div>';


    /* -----------------------------------------
       FILTR
       ----------------------------------------- */
    echo '<form method="get" style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">';
    echo '<input type="hidden" name="view" value="kontrahenci">';

    echo '<div>
            <label class="sr-label">Szukaj</label>
            <input type="text" name="q" value="'.esc_attr($q).'" class="sr-input" style="min-width:220px;">
          </div>';

    echo '<div>
            <label class="sr-label">Sortuj według</label>
            <select name="sort" class="sr-select">
                <option value="title" '.selected($sort,'title',false).'>Nazwa firmy</option>
                <option value="nip" '.selected($sort,'nip',false).'>NIP</option>
                <option value="miasto" '.selected($sort,'miasto',false).'>Miasto</option>
            </select>
          </div>';

    echo '<div>
            <label class="sr-label">Kolejność</label>
            <select name="order" class="sr-select">
                <option value="asc" '.selected(strtolower($order),'asc',false).'>Rosnąco</option>
                <option value="desc" '.selected(strtolower($order),'desc',false).'>Malejąco</option>
            </select>
          </div>';

    echo '<button type="submit" class="sr-btn sr-btn--primary">Filtruj</button>';

    echo '<a href="'.esc_url($base_url).'" class="sr-btn sr-btn--secondary">Wyczyść</a>';

    echo '</form>';


    /* -----------------------------------------
       TABELA
       ----------------------------------------- */
    if (!$query->have_posts()) {
        echo '<div class="sr-card" style="margin-top:16px;">Brak kontrahentów do wyświetlenia.</div>';
        return;
    }

    echo '<div class="sr-card" style="margin-top:12px;padding:0;">';
    echo '<table class="sr-table">';
    echo '<thead><tr>
            <th>Nazwa firmy</th>
            <th>NIP</th>
            <th>Adres</th>
            <th>Miasto</th>
            <th>Akcje</th>
          </tr></thead>';
    echo '<tbody>';

    while ($query->have_posts()) {
        $query->the_post();

        $id     = get_the_ID();
        $nip    = get_post_meta($id,'nip',true);
        $adres  = get_post_meta($id,'adres',true);
        $kod    = get_post_meta($id,'kod',true);
        $miasto = get_post_meta($id,'miasto',true);

        $edit_url = add_query_arg([
            'view'   => 'kontrahenci',
            'action' => 'edit',
            'id'     => $id,
        ], $panel_url);

        echo '<tr>';
        echo '  <td><strong>'.esc_html(get_the_title()).'</strong></td>';
        echo '  <td>'.esc_html($nip).'</td>';
        echo '<td>' . esc_html(trim($kod . ', ' . $adres, ', ')) . '</td>';
        echo '  <td>'.esc_html($miasto).'</td>';
        echo '  <td><a href="'.esc_url($edit_url).'" class="sr-btn sr-btn--secondary sr-btn--small">Edytuj</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';


    /* -----------------------------------------
       PAGINACJA
       ----------------------------------------- */
    $pages = ceil($total / $limit);

    if ($pages > 1) {
        echo '<div class="sr-pagination" style="margin-top:16px;display:flex;gap:6px;">';

        for ($i = 1; $i <= $pages; $i++) {

            $url = add_query_arg([
                'view'  => 'kontrahenci',
                'page'  => $i,
                'sort'  => $sort,
                'order' => strtolower($order),
                'q'     => $q,
            ], $panel_url);

            $active = $i === $paged
                ? 'background:#111827;color:#fff;'
                : 'background:#E5E7EB;color:#111827;';

            echo '<a href="'.esc_url($url).'"
                     style="padding:6px 10px;border-radius:6px;font-size:13px;text-decoration:none;'.$active.'">
                     '.$i.'
                  </a>';
        }

        echo '</div>';
    }

    wp_reset_postdata();
}

    /**
     * Formularz dodawania/edycji kontrahenta.
     */
    public function render_form( int $post_id = 0 ): void {

    $is_edit = $post_id > 0;

    if ( $is_edit ) {
        $nazwa     = get_the_title( $post_id );
        $nip       = (string) get_post_meta( $post_id, 'nip', true );
        $adres     = (string) get_post_meta( $post_id, 'adres', true );
        $kod       = (string) get_post_meta( $post_id, 'kod', true );
        $miasto    = (string) get_post_meta( $post_id, 'miasto', true );
        $przedmiot = (string) get_post_meta( $post_id, 'przedmiot_dzialalnosci', true );
    } else {
        $nazwa = $nip = $adres = $kod = $miasto = $przedmiot = '';
    }

    $panel_url = get_permalink();
    $back_url  = add_query_arg( 'view', 'kontrahenci', $panel_url );

    /* ----------------------------------------------
       NAGŁÓWEK
       ---------------------------------------------- */
    echo '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;">';

    echo '<div>';
    echo '<h2 style="margin:0 0 4px;">' . ( $is_edit ? 'Edycja kontrahenta' : 'Nowy kontrahent' ) . '</h2>';
    echo '<p class="sr-muted" style="margin:0;">Uzupełnij dane kontrahenta i zapisz.</p>';
    echo '</div>';

    echo '<div><a href="' . esc_url( $back_url ) . '" class="sr-btn sr-btn--secondary sr-btn--small">← Powrót do listy</a></div>';

    echo '</div>';

    /* ----------------------------------------------
       BŁĘDY BACKENDOWE
       ---------------------------------------------- */
    if ( isset( $_GET['error'] ) ) {
        $errors = json_decode( base64_decode( wp_unslash( $_GET['error'] ) ), true );
        if ( is_array( $errors ) && ! empty( $errors ) ) {
            echo '<div id="sr-errors-box" style="
                margin-top:12px;padding:10px 12px;border-radius:8px;
                background:#FEF2F2;color:#B91C1C;font-size:13px;
            ">';
            foreach ( $errors as $e ) {
                echo '<div>• ' . esc_html( $e ) . '</div>';
            }
            echo '</div>';
        }
    }

    /* ----------------------------------------------
       FORMULARZ
       ---------------------------------------------- */
    echo '<form method="post" style="
        margin-top:18px;
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
        gap:16px;
    ">';

    wp_nonce_field( 'sr_save_kontrahent', 'sr_nonce' );
    echo '<input type="hidden" name="sr_action" value="save_kontrahent">';

    if ( $is_edit ) {
        echo '<input type="hidden" name="kontrahent_id" value="' . esc_attr( $post_id ) . '">';
    }

    /* ----------------------------------------------
       Nazwa firmy
       ---------------------------------------------- */
    echo '<div>
            <label class="sr-label">Nazwa firmy *</label>
            <input type="text" name="nazwa" class="sr-input" value="' . esc_attr( $nazwa ) . '">
          </div>';

    /* ----------------------------------------------
       NIP
       ---------------------------------------------- */
    echo '<div>
            <label class="sr-label">NIP</label>
            <input type="text" name="nip" class="sr-input" value="' . esc_attr( $nip ) . '">
          </div>';

    /* ----------------------------------------------
       Adres
       ---------------------------------------------- */
    echo '<div class="sr-grid-full">
            <label class="sr-label">Adres</label>
            <input type="text" name="adres" class="sr-input" value="' . esc_attr( $adres ) .'">
          </div>';

    /* ----------------------------------------------
       Kod pocztowy
       ---------------------------------------------- */
    echo '<div>
            <label class="sr-label">Kod pocztowy</label>
            <input type="text" name="kod" class="sr-input" value="' . esc_attr( $kod ) . '">
          </div>';

    /* ----------------------------------------------
       Miasto
       ---------------------------------------------- */
    echo '<div>
            <label class="sr-label">Miasto</label>
            <input type="text" name="miasto" class="sr-input" value="' . esc_attr( $miasto ) . '">
          </div>';

    /* ----------------------------------------------
       Przedmiot działalności
       ---------------------------------------------- */
    echo '<div class="sr-grid-full">
            <label class="sr-label">Przedmiot działalności</label>';

    $choices = function_exists( 'sr_get_przedmiot_dzialalnosci_choices' )
        ? sr_get_przedmiot_dzialalnosci_choices()
        : [];

    echo '<select name="przedmiot_dzialalnosci" class="sr-select">';
    echo '<option value="">– Wybierz –</option>';

    foreach ( $choices as $val => $label ) {
        echo '<option value="' . esc_attr( $val ) . '" ' . selected( $przedmiot, (string) $val, false ) . '>'
            . esc_html( $label ) .
        '</option>';
    }

    echo '</select></div>';

    /* ----------------------------------------------
       ZAPIS — przycisk frameworkowy
       ---------------------------------------------- */
    echo '<div class="sr-grid-full" style="margin-top:8px;">
            <button type="submit" class="sr-btn sr-btn--success">
                ' . ( $is_edit ? 'Zapisz zmiany' : 'Dodaj kontrahenta' ) . '
            </button>
          </div>';

    echo '</form>';
}
}