<?php

/**
 * System Reklamowy – Zlecenia RADIO (FRONT)
 */
/**
 * LISTA ZLECEŃ RADIO – front panel
 * Sortowanie, wyszukiwanie, paginacja, akcje (PDF / edytuj / usuń)
 */
function sr_front_render_zlecenia_radio_list(): void {

    global $wpdb;

    $base_url = add_query_arg( 'view', 'zlecenia-radio', get_permalink() );

    // --- Parametry GET ---
    $q      = isset($_GET['q'])      ? sanitize_text_field($_GET['q']) : '';
    $sort   = isset($_GET['sort'])   ? sanitize_key($_GET['sort'])    : 'data_zlecenia';
    $order  = isset($_GET['order'])  ? sanitize_key($_GET['order'])   : 'desc';
    $page   = isset($_GET['page'])   ? max(1, intval($_GET['page']))  : 1;
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    // --- Akceptowalne sorty ---
    $allowed_sort = [
        'nazwa_reklamy',
        'data_zlecenia',
        'data_start',
        'data_koniec',
        'wartosc',
        'do_zaplaty',
    ];

    if ( !in_array($sort, $allowed_sort, true) ) {
        $sort = 'data_zlecenia';
    }

    $order = ($order === 'asc') ? 'ASC' : 'DESC';

    // --- Filtr wyszukiwania ---
    $search_sql = '';
    if ( $q !== '' ) {
        $like = '%' . $wpdb->esc_like($q) . '%';
        $search_sql = $wpdb->prepare(" AND (z.nazwa_reklamy LIKE %s OR z.motive LIKE %s)", $like, $like);
    }

    // --- Pobieramy zlecenia z SQL ---
    $table = $wpdb->prefix . 'sr_zlecenia';

    $sql_total = "
        SELECT COUNT(*) 
        FROM {$table} z
        WHERE z.typ = 'radio'
        {$search_sql}
    ";

    $total = (int) $wpdb->get_var($sql_total);

    $sql = "
        SELECT *
        FROM {$table} z
        WHERE z.typ = 'radio'
        {$search_sql}
        ORDER BY {$sort} {$order}
        LIMIT {$limit} OFFSET {$offset}
    ";

    $rows = $wpdb->get_results($sql);

    // --- Nagłówek listy ---
    echo '<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">';
    echo '<div>';
    echo '<h2 style="margin:0 0 4px;">Zlecenia RADIO</h2>';
    echo '<p class="sr-muted" style="margin:0;">Lista zleceń radiowych – aktywne, zakończone i draft.</p>';
    echo '</div>';

    // --- Przycisk Dodaj zlecenie ---
    $url_new = add_query_arg(['view' => 'zlecenia-radio-add'], get_permalink());
    echo '<div>';
    echo '<a href="' . esc_url($url_new) . '" style="
        display:inline-flex; align-items:center; gap:6px;
        padding:8px 14px; border-radius:999px;
        background:#111827; color:#F9FAFB; text-decoration:none; font-size:13px;">
        + Dodaj zlecenie
    </a>';
    echo '</div>';
    echo '</div>';

    // --- Formularz wyszukiwania ---
    echo '<form method="get" style="margin-top:16px; display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">';
    echo '<input type="hidden" name="view" value="zlecenia-radio">';

    echo '<div>';
    echo '<label style="font-size:12px; text-transform:uppercase; color:#6b7280;">Szukaj</label><br>';
    echo '<input type="text" name="q" value="' . esc_attr($q) . '" style="
        padding:6px 10px; border-radius:6px; border:1px solid #d1d5db; min-width:220px;">';
    echo '</div>';

    echo '<div>';
    echo '<button type="submit" style="
        padding:7px 14px; border-radius:999px; border:none;
        background:#2563EB; color:#F9FAFB; font-size:13px; cursor:pointer;">
        Filtruj
    </button>';
    echo '</div>';

    $url_clear = add_query_arg('view', 'zlecenia-radio', get_permalink());
    echo '<div><a href="' . esc_url($url_clear) . '" style="font-size:13px; color:#6b7280;">Wyczyść</a></div>';

    echo '</form>';

    // --- Tabela rezultatów ---
    if (empty($rows)) {
        echo '<div class="sr-card" style="margin-top:8px;">Brak zleceń do wyświetlenia.</div>';
        return;
    }

    // Helper nagłówków sortowania
    $sort_url = function($col) use ($base_url, $sort, $order, $q, $page) {
        $new_order = ($sort === $col && $order === 'asc') ? 'desc' : 'asc';
        return add_query_arg([
            'view'  => 'zlecenia-radio',
            'sort'  => $col,
            'order' => $new_order,
            'q'     => $q,
            'page'  => $page
        ], get_permalink());
    };

    echo '<table class="sr-table" style="margin-top:4px;">';
    echo '<thead>';
    echo '<tr>';
        echo '<th><a href="'. esc_url($sort_url('nazwa_reklamy')) .'">Nazwa reklamy</a></th>';
        echo '<th><a href="'. esc_url($sort_url('data_zlecenia')) .'">Data zlecenia</a></th>';
        echo '<th><a href="'. esc_url($sort_url('data_start')) .'">Start</a></th>';
        echo '<th><a href="'. esc_url($sort_url('data_koniec')) .'">Koniec</a></th>';
        echo '<th><a href="'. esc_url($sort_url('wartosc')) .'">Wartość</a></th>';
        echo '<th><a href="'. esc_url($sort_url('do_zaplaty')) .'">Do zapłaty</a></th>';
        echo '<th>Akcje</th>';
    echo '</tr>';
    echo '</thead>';

    echo '<tbody>';

    foreach ($rows as $z) {

        // Link do PDF – jeden dokument
        $pdf_url = add_query_arg([
            'action'       => 'sr_print_order_pdf',
            'zlecenie_id'  => $z->id,
        ], admin_url('admin-post.php'));

        // Link edycji
        $edit_url = add_query_arg([
            'view' => 'zlecenia-radio-edit',
            'id'   => $z->id
        ], get_permalink());

        // Link usuwania
        $delete_url = wp_nonce_url(
            add_query_arg([
                'view' => 'zlecenia-radio',
                'delete' => $z->id
            ], get_permalink()),
            'sr_delete_zlecenie_radio'
        );

        echo '<tr>';
            echo '<td><a href="'. esc_url($pdf_url) .'" target="_blank">'. esc_html($z->nazwa_reklamy) .'</a></td>';
            echo '<td>'. esc_html( $z->data_zlecenia ) .'</td>';
            echo '<td>'. esc_html( $z->data_start ) .'</td>';
            echo '<td>'. esc_html( $z->data_koniec ) .'</td>';
            echo '<td>'. number_format($z->wartosc, 2, ',', ' ') .' zł</td>';
            echo '<td>'. number_format($z->do_zaplaty, 2, ',', ' ') .' zł</td>';
            echo '<td>
                <a href="'. esc_url($edit_url) .'">Edytuj</a> |
                <a href="'. esc_url($delete_url) .'" onclick="return confirm(\'Usunąć zlecenie i wszystkie emisje?\');">Usuń</a>
            </td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // --- Paginacja ---
    $total_pages = ceil($total / $limit);
    if ($total_pages > 1) {

        echo '<div style="margin-top:12px; display:flex; gap:6px;">';

        for ($i=1; $i <= $total_pages; $i++) {
            $url = add_query_arg([
                'view' => 'zlecenia-radio',
                'page' => $i,
                'sort' => $sort,
                'order'=> $order,
                'q'    => $q
            ], get_permalink());

            $active = ($page === $i);
            echo '<a href="'. esc_url($url) .'" style="
                padding:6px 10px;
                border-radius:6px;
                font-size:13px;
                text-decoration:none;
                background:' . ($active ? '#111827' : '#E5E7EB') . ';
                color:' . ($active ? '#F9FAFB' : '#374151') . ';">
                '. $i .'
            </a>';
        }

        echo '</div>';
    }


    // 🔥 Obsługa usuwania (radio + emisje)
    if ( isset($_GET['delete']) ) {

        $del_id = intval($_GET['delete']);

        if ( wp_verify_nonce($_GET['_wpnonce'], 'sr_delete_zlecenie_radio') ) {

            // 1) usuń emisje
            $wpdb->delete(
                $wpdb->prefix.'sr_emisje',
                [ 'zlecenie_id' => $del_id ],
                [ '%d' ]
            );

            // 2) usuń wpis z wp_sr_zlecenia
            $wpdb->delete(
                $wpdb->prefix.'sr_zlecenia',
                [ 'id' => $del_id ],
                [ '%d' ]
            );

            // 3) usuń CPT (jeśli istnieje jako post)
            wp_delete_post( $del_id, true );

            // redirect
            wp_safe_redirect( remove_query_arg(['delete','_wpnonce']) );
            exit;
        }
    }
}
