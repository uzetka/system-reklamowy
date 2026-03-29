<?php
/**
 * Frontend – Zlecenia RADIO (lista + formularz dodawania).
 *
 * Ta klasa przenosi:
 * - widok listy zleceń radiowych (sr_front_render_zlecenia_radio_list),
 * - widok dodawania zlecenia (sr_front_render_zlecenia_radio_add),
 * z Code Snippets do wtyczki "System Reklamowy".
 *
 * Back-end (zapis zlecenia) nadal obsługuje istniejąca logika POST
 * (sr_action = save_zlecenie_radio) z frontowego panelu,
 * więc tutaj nie dotykamy zapisu, tylko HTML + JS.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Frontend_Zlecenia_Radio {

    /**
     * Konstruktor – w przyszłości można tu podpiąć hooki JS/REST itp.
     */
    public function __construct() {}

	/**
	 * Widok listy zleceń RADIO w panelu /panel-reklamy.
	 *
	 * Odpowiednik sr_front_render_zlecenia_radio_list() ze snippetu.
	 */
	public function render_list(): void {
		global $wpdb;
		
		$deleted = false;

		$base_url = add_query_arg( 'view', 'zlecenia-radio', get_permalink() );

		// --- USUWANIE (SQL + emisje + CPT) ---------------------------------
		if ( isset( $_GET['delete'] ) ) {
			$row_id = (int) $_GET['delete'];

			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'sr_delete_zlecenie_radio' ) ) {

				// Usuń emisje powiązane z row_id.
				$wpdb->delete(
					$wpdb->prefix . 'sr_emisje',
					array( 'zlecenie_id' => $row_id ),
					array( '%d' )
				);

				// Usuń rekord z sr_zlecenia.
				$wpdb->delete(
					$wpdb->prefix . 'sr_zlecenia',
					array( 'id' => $row_id ),
					array( '%d' )
				);

				// Znajdź CPT powiązany meta_key sr_zlecenia_row_id.
				$post_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"
						SELECT post_id
						FROM {$wpdb->postmeta}
						WHERE meta_key = 'sr_zlecenia_row_id'
						  AND meta_value = %d
						LIMIT 1
						",
						$row_id
					)
				);

				if ( $post_id > 0 ) {
					wp_delete_post( $post_id, true );
				}

				// Nie robimy redirectu – ustawiamy flagę, żeby pokazać komunikat
				$deleted = true;
			}
		}

		// --- PARAMETRY (search, sort, paging) -------------------------------
		$q     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$sort  = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'data_zlecenia';
		$order = ( isset( $_GET['order'] ) && 'asc' === strtolower( wp_unslash( $_GET['order'] ) ) ) ? 'asc' : 'desc';
		$page  = isset( $_GET['page'] ) ? max( 1, (int) $_GET['page'] ) : 1;
		$limit = 20;
		$offset = ( $page - 1 ) * $limit;

		$allowed_sort = array(
			'nazwa_reklamy',
			'data_zlecenia',
			'data_start',
			'data_koniec',
			'wartosc',
			'do_zaplaty',
		);

		if ( ! in_array( $sort, $allowed_sort, true ) ) {
			$sort = 'data_zlecenia';
		}

		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		// --- SEARCH ---------------------------------------------------------
		$search_sql = '';
		if ( '' !== $q ) {
			$like       = '%' . $wpdb->esc_like( $q ) . '%';
			$search_sql = $wpdb->prepare(
				' AND (z.nazwa_reklamy LIKE %s OR z.motive LIKE %s)',
				$like,
				$like
			);
		}

		$table = $wpdb->prefix . 'sr_zlecenia';

		$total = (int) $wpdb->get_var(
			"
			SELECT COUNT(*)
			FROM {$table} z
			WHERE z.typ = 'radio' {$search_sql}
			"
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT *
				FROM {$table} z
				WHERE z.typ = 'radio' {$search_sql}
				ORDER BY {$sort} {$order}
				LIMIT %d OFFSET %d
				",
				$limit,
				$offset
			)
		);

		// --- Nagłówek -------------------------------------------------------
		echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">';
		echo '<div><h2 style="margin:0 0 4px;">Zlecenia RADIO</h2><p class="sr-muted" style="margin:0;">Lista zleceń radiowych.</p></div>';

		$add_url = add_query_arg(
			array( 'view' => 'zlecenia-radio-add' ),
			get_permalink()
		);

		echo '<div><a href="' . esc_url( $add_url ) . '" class="sr-btn sr-btn--primary">+ Dodaj zlecenie</a></div>';
		echo '</div>';

		// Komunikat po usunięciu
		if ( $deleted ) {
			echo '<div class="sr-toast sr-toast--success sr-toast--visible"'
			   . ' style="margin-top:12px;position:static;transform:none;">'
			   . 'Zlecenie zostało usunięte.'
			   . '</div>';
		}
		
		// --- Search form ----------------------------------------------------
		echo '<form method="get" style="margin-top:16px;margin-bottom:12px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">';
		echo '<input type="hidden" name="view" value="zlecenia-radio">';
		echo '<div><label style="font-size:12px;text-transform:uppercase;color:#6b7280;">Szukaj</label><br>
			<input type="text" name="q" value="' . esc_attr( $q ) . '" style="
				padding:6px 10px;border-radius:6px;border:1px solid #d1d5db;min-width:220px;
			"></div>';
		echo '<div><button type="submit" class="sr-btn sr-btn--primary">Filtruj</button></div>';
		echo '<div><a href="' . esc_url( $base_url ) . '" class="sr-btn sr-btn--secondary">Wyczyść</a></div>';
		echo '</form>';

		if ( empty( $rows ) ) {
			echo '<div class="sr-card" style="margin-top:8px;">Brak zleceń do wyświetlenia.</div>';
			return;
		}

		// Helper sortujący linki w nagłówkach tabeli.
		$sort_url = function( $col ) use ( $sort, $order, $q, $page ) {
			$new_order = ( $sort === $col && 'asc' === strtolower( $order ) ) ? 'desc' : 'asc';
			return add_query_arg(
				array(
					'view'  => 'zlecenia-radio',
					'sort'  => $col,
					'order' => $new_order,
					'q'     => $q,
					'page'  => $page,
				),
				get_permalink()
			);
		};

		echo '<table class="sr-table" style="margin-top:4px;">';
		echo '<thead><tr>';
		echo '<th><a href="' . esc_url( $sort_url( 'nazwa_reklamy' ) ) . '">Nazwa reklamy</a></th>';
		echo '<th><a href="' . esc_url( $sort_url( 'data_zlecenia' ) ) . '">Data zlecenia</a></th>';
		echo '<th><a href="' . esc_url( $sort_url( 'data_start' ) ) . '">Start</a></th>';
		echo '<th><a href="' . esc_url( $sort_url( 'data_koniec' ) ) . '">Koniec</a></th>';
		echo '<th><a href="' . esc_url( $sort_url( 'wartosc' ) ) . '">Wartość</a></th>';
		echo '<th><a href="' . esc_url( $sort_url( 'do_zaplaty' ) ) . '">Do zapłaty</a></th>';
		echo '<th>Akcje</th>';
		echo '</tr></thead><tbody>';

		foreach ( $rows as $z ) {
			$row_id = (int) $z->id;

			// Znajdź CPT powiązany z row_id.
			$post_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"
					SELECT post_id
					FROM {$wpdb->postmeta}
					WHERE meta_key = 'sr_zlecenia_row_id'
					  AND meta_value = %d
					LIMIT 1
					",
					$row_id
				)
			);

			// Link PDF (MediaPlan).
			$pdf_url = add_query_arg(
				array(
					'action'      => 'sr_print_order_pdf',
					'zlecenie_id' => $row_id,
				),
				admin_url( 'admin-post.php' )
			);

			// Link edycji (frontend).
			$edit_url = '';
			if ( $post_id > 0 ) {
				$edit_url = add_query_arg(
					array(
						'view' => 'zlecenia-radio-edit',
						'id'   => $post_id,
					),
					get_permalink()
				);
			}

			// Link usuń.
			$delete_url = wp_nonce_url(
				add_query_arg(
					array(
						'view'   => 'zlecenia-radio',
						'delete' => $row_id,
					),
					get_permalink()
				),
				'sr_delete_zlecenie_radio'
			);

			echo '<tr>';

			// NAZWA – nie klik PDF, tylko tekst
			echo '<td><strong>' . esc_html( $z->nazwa_reklamy ) . '</strong></td>';

			// DATY I KWOTY – bez zmian
			echo '<td>' . esc_html( $z->data_zlecenia ) . '</td>';
			echo '<td>' . esc_html( $z->data_start ) . '</td>';
			echo '<td>' . esc_html( $z->data_koniec ) . '</td>';
			echo '<td>' . number_format( (float) $z->wartosc, 2, ',', ' ' ) . ' zł</td>';
			echo '<td>' . number_format( (float) $z->do_zaplaty, 2, ',', ' ' ) . ' zł</td>';

			// --- AKCJE (LAYOUT JAK W KONTRAHENTACH) ---
			echo '<td style="white-space:nowrap; display:flex; gap:6px;">';

			// MediaPlan
			echo '<a class="sr-btn sr-btn--secondary sr-btn--small" href="' . esc_url( $pdf_url ) . '" target="_blank">MediaPlan</a>';

			// Edytuj
			if ( $post_id > 0 && $edit_url ) {
				echo '<a class="sr-btn sr-btn--secondary sr-btn--small" href="' . esc_url( $edit_url ) . '">Edytuj</a>';
			} else {
				echo '<span class="sr-muted" style="color:#9CA3AF;font-size:12px;">(brak CPT)</span>';
			}

			// Usuń
			echo '<a class="sr-btn sr-btn--danger sr-btn--small" href="' . esc_url( $delete_url )
				 . '" onclick="return confirm(\'Usunąć zlecenie i wszystkie emisje?\');">Usuń</a>';

			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		// Paging.
		$pages = (int) ceil( $total / $limit );
		if ( $pages > 1 ) {
			echo '<div style="margin-top:12px;display:flex;gap:6px;">';
			for ( $i = 1; $i <= $pages; $i++ ) {
				$url    = add_query_arg(
					array(
						'view'  => 'zlecenia-radio',
						'page'  => $i,
						'sort'  => $sort,
						'order' => strtolower( $order ),
						'q'     => $q,
					),
					get_permalink()
				);
				$active = ( $i === $page );
				echo '<a href="' . esc_url( $url ) . '" style="
					padding:6px 10px;border-radius:6px;font-size:13px;text-decoration:none;
					background:' . ( $active ? '#111827' : '#E5E7EB' ) . ';
					color:' . ( $active ? '#F9FAFB' : '#374151' ) . ';
				">' . $i . '</a>';
			}
			echo '</div>';
		}
	}

    /**
     * Formularz dodawania zlecenia RADIO (krok 1).
     *
     * Odpowiednik sr_front_render_zlecenia_radio_add() ze snippetu.
     */
    public function render_add(): void {

        $back_url = add_query_arg( 'view', 'zlecenia-radio', get_permalink() );

        // Dane z poprzedniego submitu (np. po błędach).
        $post_data = array(
            'firma'      => isset( $_POST['firma'] ) ? wp_unslash( $_POST['firma'] ) : '',
            'nip'        => isset( $_POST['nip'] ) ? wp_unslash( $_POST['nip'] ) : '',
            'adres'      => isset( $_POST['adres'] ) ? wp_unslash( $_POST['adres'] ) : '',
            'kod'        => isset( $_POST['kod'] ) ? wp_unslash( $_POST['kod'] ) : '',
            'miasto'     => isset( $_POST['miasto'] ) ? wp_unslash( $_POST['miasto'] ) : '',
            'przedmiot'  => isset( $_POST['przedmiot_dzialalnosci'] ) ? wp_unslash( $_POST['przedmiot_dzialalnosci'] ) : '',
            'nazwa_rek'  => isset( $_POST['nazwa_reklamy'] ) ? wp_unslash( $_POST['nazwa_reklamy'] ) : '',
            'motive'     => isset( $_POST['motive'] ) ? wp_unslash( $_POST['motive'] ) : '',
            'dlugosc'    => isset( $_POST['dlugosc_spotu'] ) ? wp_unslash( $_POST['dlugosc_spotu'] ) : '',
            'data_start' => isset( $_POST['data_start'] ) ? wp_unslash( $_POST['data_start'] ) : '',
            'data_koniec'=> isset( $_POST['data_koniec'] ) ? wp_unslash( $_POST['data_koniec'] ) : '',
            'rabat'      => isset( $_POST['rabat'] ) ? wp_unslash( $_POST['rabat'] ) : 'brak',
            'rabat_neg'  => isset( $_POST['rabat_negocjowany'] ) ? wp_unslash( $_POST['rabat_negocjowany'] ) : '',
        );

        // Nagłówek.
        echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">';
        echo '<div>';
        echo '<h2 style="margin:0 0 4px;">Nowe zlecenie RADIO</h2>';
        echo '<p class="sr-muted" style="margin:0;">Uzupełnij dane kontrahenta i zlecenia. Harmonogram emisji wygenerujesz w kolejnym kroku.</p>';
        echo '</div>';
        echo '<div><a href="' . esc_url( $back_url ) . '" style="font-size:13px;color:#6b7280;">← Powrót do listy</a></div>';
        echo '</div>';

        // Błędy walidacji (z backendu).
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

        // Formularz.
        echo '<form method="post" style="margin-top:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">';

        wp_nonce_field( 'sr_save_zlecenie_radio', 'sr_zlecenie_radio_nonce' );
        echo '<input type="hidden" name="sr_action" value="save_zlecenie_radio">';

        // ---- DANE KONTRAHENTA ----

        // Wiersz 1: Nazwa firmy / NIP.
        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Nazwa firmy *</label>';
        echo '<input type="text" name="firma" value="' . esc_attr( $post_data['firma'] ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">NIP</label>';
        echo '<input type="text" name="nip" value="' . esc_attr( $post_data['nip'] ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // Wiersz 2: Adres / Kod / Miasto.
        echo '<div style="grid-column:1/-1;">';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Adres</label>';
        echo '<textarea name="adres" rows="2" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">' . esc_textarea( $post_data['adres'] ) . '</textarea>';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Kod pocztowy</label>';
        echo '<input type="text" name="kod" value="' . esc_attr( $post_data['kod'] ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Miasto</label>';
        echo '<input type="text" name="miasto" value="' . esc_attr( $post_data['miasto'] ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // Przedmiot działalności – select.
        echo '<div style="grid-column:1/-1;">';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Przedmiot działalności</label>';

        $choices = function_exists( 'sr_get_przedmiot_dzialalnosci_choices' )
            ? sr_get_przedmiot_dzialalnosci_choices()
            : array();

        echo '<select name="przedmiot_dzialalnosci" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '<option value="">– Wybierz –</option>';

        foreach ( $choices as $val => $label ) {
            echo '<option value="' . esc_attr( $val ) . '" ' . selected( $post_data['przedmiot'], $val, false ) . '>' . esc_html( $label ) . '</option>';
        }

        echo '</select>';
        echo '</div>';

        // ---- DANE ZLECENIA ----

        // Wiersz 4: Nazwa reklamy / Motive.
        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Nazwa reklamy *</label>';
        echo '<input type="text" name="nazwa_reklamy" value="' . esc_attr( $post_data['nazwa_rek'] ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Motive</label>';
        echo '<input type="text" name="motive" value="' . esc_attr( $post_data['motive'] ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // Wiersz 5: Długość / Data start / Data koniec / Rabat.
        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Długość spotu *</label>';
        echo '<select name="dlugosc_spotu" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '<option value="">– Wybierz –</option>';

        if ( function_exists( 'sr_get_dlugosci_spotow_choices' ) ) {
            $len_choices = sr_get_dlugosci_spotow_choices();
            foreach ( $len_choices as $val => $label ) {
                echo '<option value="' . esc_attr( $val ) . '" ' . selected( $post_data['dlugosc'], $val, false ) . '>' . esc_html( $label ) . '</option>';
            }
        }

        echo '</select>';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Data rozpoczęcia *</label>';
        echo '<input type="date" name="data_start" value="' . esc_attr( $post_data['data_start'] ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Data zakończenia *</label>';
        echo '<input type="date" name="data_koniec" value="' . esc_attr( $post_data['data_koniec'] ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // Rabat.
        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Rabat</label>';
        echo '<select name="rabat" id="sr-rabat-radio" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '<option value="brak"' . selected( $post_data['rabat'], 'brak', false ) . '>Brak</option>';
        echo '<option value="agencyjny"' . selected( $post_data['rabat'], 'agencyjny', false ) . '>Agencyjny</option>';
        echo '<option value="100"' . selected( $post_data['rabat'], '100', false ) . '>100%</option>';
        echo '<option value="negocjowany"' . selected( $post_data['rabat'], 'negocjowany', false ) . '>Negocjowany</option>';
        echo '</select>';
        echo '</div>';

        // Rabat negocjowany.
        echo '<div id="sr-rabat-negocjowany-wrap" style="display:' . ( 'negocjowany' === $post_data['rabat'] ? 'block' : 'none' ) . ';">';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Rabat negocjowany (%)</label>';
        echo '<input type="number" name="rabat_negocjowany" min="0" max="100" value="' . esc_attr( $post_data['rabat_neg'] ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // PRZYCISK.
        echo '<div style="grid-column:1/-1;margin-top:8px;">';
        echo '<button type="submit" style="
            padding:9px 18px;border-radius:999px;border:none;
            background:#16A34A;color:#F9FAFB;font-size:14px;cursor:pointer;
        ">
            Zapisz i przejdź do harmonogramu
        </button>';
        echo '</div>';

        echo '</form>';

        // ---- JS – Rabat negocjowany + autocomplete/NIP (GUS) ---------------
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Rabat negocjowany – pokaz/ukryj pole
            var selectRabat = document.getElementById('sr-rabat-radio');
            var wrapNeg = document.getElementById('sr-rabat-negocjowany-wrap');
            if (selectRabat && wrapNeg) {
                selectRabat.addEventListener('change', function () {
                    if (this.value === 'negocjowany') {
                        wrapNeg.style.display = 'block';
                    } else {
                        wrapNeg.style.display = 'none';
                    }
                });
            }

            // AUTOCOMPLETE + NIP LOOKUP
            var firmaInput = document.querySelector('input[name="firma"]');
            if (!firmaInput) {
                return;
            }

            var nipInput = document.querySelector('input[name="nip"]');
            var adresField = document.querySelector('textarea[name="adres"]');
            var kodField = document.querySelector('input[name="kod"]');
            var miastoField = document.querySelector('input[name="miasto"]');
            var przedmiotSelect = document.querySelector('select[name="przedmiot_dzialalnosci"]');

            // 1) AUTOCOMPLETE PO NAZWIE KONTRAHENTA
            var suggestBox = document.createElement('div');
            suggestBox.style.position = 'absolute';
            suggestBox.style.background = '#ffffff';
            suggestBox.style.border = '1px solid #d1d5db';
            suggestBox.style.width = firmaInput.offsetWidth + 'px';
            suggestBox.style.maxHeight = '160px';
            suggestBox.style.overflowY = 'auto';
            suggestBox.style.zIndex = '9999';
            suggestBox.style.display = 'none';
            suggestBox.style.borderRadius = '6px';
            suggestBox.style.boxShadow = '0 2px 6px rgba(0,0,0,0.15)';
            suggestBox.style.fontSize = '13px';
            suggestBox.style.lineHeight = '1.35';
            suggestBox.style.padding = '0';
            suggestBox.style.marginTop = '2px';
            firmaInput.parentNode.appendChild(suggestBox);

            var timer = null;

            firmaInput.addEventListener('input', function () {
                var q = this.value.trim();
                if (q.length < 2) {
                    suggestBox.style.display = 'none';
                    return;
                }

                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetch('/wp-json/sr/v1/kontrahent-find?q=' + encodeURIComponent(q), {
                        credentials: 'same-origin'
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        suggestBox.innerHTML = '';
                        if (!Array.isArray(data) || data.length === 0) {
                            suggestBox.style.display = 'none';
                            return;
                        }

                        data.forEach(function (k) {
                            var div = document.createElement('div');
                            div.textContent = k.nazwa + (k.nip ? ' (NIP: ' + k.nip + ')' : '');
                            div.style.padding = '6px 10px';
                            div.style.cursor = 'pointer';
                            div.style.fontSize = '13px';
                            div.style.borderBottom = '1px solid #f3f4f6';

                            div.addEventListener('click', function () {
                                firmaInput.value = k.nazwa || '';
                                if (nipInput) nipInput.value = k.nip || '';
                                if (adresField) adresField.value = k.adres || '';
                                if (kodField) kodField.value = k.kod || '';
                                if (miastoField) miastoField.value = k.miasto || '';
                                if (przedmiotSelect) przedmiotSelect.value = k.przedmiot || '';

                                suggestBox.style.display = 'none';
                            });

                            div.addEventListener('mouseover', function () {
                                div.style.background = '#f9fafb';
                            });
                            div.addEventListener('mouseout', function () {
                                div.style.background = '#ffffff';
                            });

                            suggestBox.appendChild(div);
                        });

                        suggestBox.style.display = 'block';
                    });
                }, 250);
            });

            document.addEventListener('click', function (e) {
                if (e.target !== firmaInput && !suggestBox.contains(e.target)) {
                    suggestBox.style.display = 'none';
                }
            });

            // 2) LOOKUP NIP -> GUS
            function srRadioLookupNip(nipDigits) {
                var url = '/wp-json/sr/v1/nip-lookup?nip=' + encodeURIComponent(nipDigits);

                fetch(url, { credentials: 'same-origin' })
                    .then(function (resp) {
                        return resp.json().catch(function () {
                            return {
                                success: false,
                                message: 'Błąd połączenia z GUS (HTTP ' + resp.status + ').'
                            };
                        });
                    })
                    .then(function (data) {
                        if (!data) return;

                        // Błąd – czerwony toast
                        if (!data.success) {
                            var t = document.createElement('div');
                            t.className = 'sr-toast sr-toast--success sr-toast--visible';
                            t.style.background = '#DC2626';
                            t.style.color = '#F9FAFB';
                            t.textContent = data.message || 'Nie udało się pobrać danych z GUS.';
                            document.body.appendChild(t);
                            setTimeout(function () {
                                t.classList.remove('sr-toast--visible');
                                setTimeout(function () { t.remove(); }, 400);
                            }, 4000);
                            return;
                        }

                        // Sukces – uzupełniamy TYLKO puste pola
                        if (data.nazwa && firmaInput && firmaInput.value.trim() === '') {
                            firmaInput.value = data.nazwa;
                        }
                        if (data.adres && adresField && adresField.value.trim() === '') {
                            adresField.value = data.adres;
                        }
                        if (data.kod && kodField && kodField.value.trim() === '') {
                            kodField.value = data.kod;
                        }
                        if (data.miasto && miastoField && miastoField.value.trim() === '') {
                            miastoField.value = data.miasto;
                        }
                        if (data.przedmiot_dzialalnosci && przedmiotSelect && przedmiotSelect.value === '') {
                            przedmiotSelect.value = data.przedmiot_dzialalnosci;
                        }

                        var t2 = document.createElement('div');
                        t2.className = 'sr-toast sr-toast--success sr-toast--visible';
                        t2.textContent = 'Dane kontrahenta zostały pobrane z GUS.';
                        document.body.appendChild(t2);
                        setTimeout(function () {
                            t2.classList.remove('sr-toast--visible');
                            setTimeout(function () { t2.remove(); }, 400);
                        }, 4000);
                    })
                    .catch(function () {
                        // przy błędzie sieciowym – użytkownik wypełni ręcznie
                    });
            }

            if (nipInput) {
                var lastLookupNip = '';

                // input – wklejenie / wpisanie
                nipInput.addEventListener('input', function (e) {
                    var v = e.target.value.replace(/\D/g, '');
                    if (v.length > 10) v = v.slice(0, 10);
                    e.target.value = v;

                    if (v.length === 10 && v !== lastLookupNip) {
                        lastLookupNip = v;
                        srRadioLookupNip(v);
                    }
                });

                // blur – awaryjny
                nipInput.addEventListener('blur', function (e) {
                    var digits = e.target.value.replace(/\D/g, '');
                    if (digits.length === 10 && digits !== lastLookupNip) {
                        lastLookupNip = digits;
                        srRadioLookupNip(digits);
                    }
                });
            }
        });
        </script>
        <?php
    }
	
	/**
     * Formularz edycji zlecenia RADIO.
     *
     * Odpowiednik sr_front_render_zlecenia_radio_edit() ze snippetu.
     *
     * @param int $post_id ID wpisu typu sr_zlecenie_radio.
     */
    public function render_edit( int $post_id ): void {

        // Jeżeli metoda wywołana bez ID, spróbuj odczytać z GET (dla zgodności).
        if ( $post_id <= 0 && isset( $_GET['id'] ) ) {
            $post_id = (int) $_GET['id'];
        }

        // 1) Podstawowa walidacja ID.
        if ( $post_id <= 0 ) {
            echo '<p class="sr-muted">Brak ID zlecenia do edycji.</p>';
            return;
        }

        $post = get_post( $post_id );
        if ( ! $post || 'sr_zlecenie_radio' !== $post->post_type ) {
            echo '<p class="sr-muted">Nie znaleziono zlecenia RADIO o podanym ID.</p>';
            return;
        }

        // 2) Uprawnienia – na razie tak jak w snippecie (tylko admin).
        if ( ! current_user_can( 'manage_options' ) ) {
            echo '<p class="sr-muted">Brak uprawnień do edycji zleceń.</p>';
            return;
        }

        // 3) Dane zlecenia (meta).
        $kontrahent_id   = (int) get_post_meta( $post_id, 'kontrahent_id', true );
        $nazwa_reklamy   = (string) get_post_meta( $post_id, 'nazwa_reklamy', true );
        $motive          = (string) get_post_meta( $post_id, 'motive', true );
        $dlugosc_spotu   = (int)    get_post_meta( $post_id, 'dlugosc_spotu', true );

        // Surowe daty – mogą być w formacie dd.mm.rrrr lub yyyy-mm-dd.
        $data_start_raw  = (string) get_post_meta( $post_id, 'data_start', true );
        $data_koniec_raw = (string) get_post_meta( $post_id, 'data_koniec', true );

        // Konwersja dd.mm.rrrr -> yyyy-mm-dd (żeby pasowało do input[type=date]).
        $normalize_date = function( string $d ): string {
            if ( preg_match( '/^\d{2}\.\d{2}\.\d{4}$/', $d ) ) {
                list( $day, $month, $year ) = explode( '.', $d );
                return sprintf( '%04d-%02d-%02d', (int) $year, (int) $month, (int) $day );
            }
            return $d;
        };

        $data_start  = $normalize_date( $data_start_raw );
        $data_koniec = $normalize_date( $data_koniec_raw );

        $rabat       = (string) get_post_meta( $post_id, 'rabat', true );
        $rabat_neg   = (float)  get_post_meta( $post_id, 'rabat_negocjowany', true );

        // 4) Dane kontrahenta powiązanego ze zleceniem.
        $firma     = '';
        $nip       = '';
        $adres     = '';
        $kod       = '';
        $miasto    = '';
        $przedmiot = '';

        if ( $kontrahent_id > 0 ) {
            $firma     = get_the_title( $kontrahent_id );
            $nip       = (string) get_post_meta( $kontrahent_id, 'nip', true );
            $adres     = (string) get_post_meta( $kontrahent_id, 'adres', true );
            $kod       = (string) get_post_meta( $kontrahent_id, 'kod', true );
            $miasto    = (string) get_post_meta( $kontrahent_id, 'miasto', true );
            $przedmiot = (string) get_post_meta( $kontrahent_id, 'przedmiot_dzialalnosci', true );
        }

        $back_url = add_query_arg( 'view', 'zlecenia-radio', get_permalink() );

        // 5) Nagłówek widoku.
        echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">';
        echo '<div>';
        echo '<h2 style="margin:0 0 4px;">Edycja zlecenia RADIO</h2>';
        echo '<p class="sr-muted" style="margin:0;">Zmień dane kontrahenta lub zlecenia. Harmonogram emisji modyfikuje się w osobnym kroku.</p>';
        echo '</div>';
        echo '<div><a href="' . esc_url( $back_url ) . '" style="font-size:13px;color:#6b7280;">← Powrót do listy</a></div>';
        echo '</div>';

        // 6) Błędy walidacji (z backendu).
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

        // 7) Formularz edycji.
        echo '<form method="post" style="margin-top:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">';

        // Nonce + hidden action + hidden zlecenie_id.
        wp_nonce_field( 'sr_save_zlecenie_radio', 'sr_zlecenie_radio_nonce' );
        echo '<input type="hidden" name="sr_action" value="save_zlecenie_radio">';
        echo '<input type="hidden" name="zlecenie_id" value="' . esc_attr( $post_id ) . '">';

        // ---- DANE KONTRAHENTA ----

        // Nazwa firmy / NIP.
        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Nazwa firmy *</label>';
        echo '<input type="text" name="firma" value="' . esc_attr( $firma ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">NIP</label>';
        echo '<input type="text" name="nip" value="' . esc_attr( $nip ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // Adres / Kod / Miasto.
        echo '<div style="grid-column:1/-1;">';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Adres</label>';
        echo '<textarea name="adres" rows="2" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">' . esc_textarea( $adres ) . '</textarea>';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Kod pocztowy</label>';
        echo '<input type="text" name="kod" value="' . esc_attr( $kod ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Miasto</label>';
        echo '<input type="text" name="miasto" value="' . esc_attr( $miasto ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // Przedmiot działalności – select.
        echo '<div style="grid-column:1/-1;">';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Przedmiot działalności</label>';

        $choices = function_exists( 'sr_get_przedmiot_dzialalnosci_choices' )
            ? sr_get_przedmiot_dzialalnosci_choices()
            : array();

        echo '<select name="przedmiot_dzialalnosci" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '<option value="">– Wybierz –</option>';
        foreach ( $choices as $val => $label ) {
            echo '<option value="' . esc_attr( $val ) . '" ' . selected( $przedmiot, $val, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        // ---- DANE ZLECENIA ----

        // Nazwa reklamy / Motive.
        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Nazwa reklamy *</label>';
        echo '<input type="text" name="nazwa_reklamy" value="' . esc_attr( $nazwa_reklamy ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Motive</label>';
        echo '<input type="text" name="motive" value="' . esc_attr( $motive ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // Długość / Daty / Rabat.
        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Długość spotu *</label>';
        echo '<select name="dlugosc_spotu" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '<option value="">– Wybierz –</option>';

        if ( function_exists( 'sr_get_dlugosci_spotow_choices' ) ) {
            $len_choices = sr_get_dlugosci_spotow_choices();
            foreach ( $len_choices as $val => $label ) {
                echo '<option value="' . esc_attr( $val ) . '" ' . selected( $dlugosc_spotu, $val, false ) . '>' . esc_html( $label ) . '</option>';
            }
        }

        echo '</select>';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Data rozpoczęcia *</label>';
        echo '<input type="date" name="data_start" value="' . esc_attr( $data_start ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Data zakończenia *</label>';
        echo '<input type="date" name="data_koniec" value="' . esc_attr( $data_koniec ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // Rabat.
        echo '<div>';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Rabat</label>';
        echo '<select name="rabat" id="sr-rabat-radio" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '<option value="brak"' . selected( $rabat, 'brak', false ) . '>Brak</option>';
        echo '<option value="agencyjny"' . selected( $rabat, 'agencyjny', false ) . '>Agencyjny</option>';
        echo '<option value="100"' . selected( $rabat, '100', false ) . '>100%</option>';
        echo '<option value="negocjowany"' . selected( $rabat, 'negocjowany', false ) . '>Negocjowany</option>';
        echo '</select>';
        echo '</div>';

        // Rabat negocjowany.
        echo '<div id="sr-rabat-negocjowany-wrap" style="display:' . ( 'negocjowany' === $rabat ? 'block' : 'none' ) . ';">';
        echo '<label style="display:block;font-size:13px;margin-bottom:4px;">Rabat negocjowany (%)</label>';
        echo '<input type="number" name="rabat_negocjowany" min="0" max="100" value="' . esc_attr( $rabat_neg ) . '" style="
            width:100%;padding:8px 10px;border-radius:6px;border:1px solid #d1d5db;
        ">';
        echo '</div>';

        // Przyciski.
        echo '<div style="grid-column:1/-1;margin-top:8px;">';
        echo '<button type="submit" style="
            padding:9px 18px;border-radius:999px;border:none;
            background:#16A34A;color:#F9FAFB;font-size:14px;cursor:pointer;
        ">Zapisz zmiany zlecenia</button>';

        $plan_url = add_query_arg(
            array(
                'view' => 'zlecenia-radio-plan',
                'id'   => $post_id,
            ),
            get_permalink()
        );

        echo ' <a href="' . esc_url( $plan_url ) . ' style="
            padding:9px 18px;border-radius:999px;
            background:#2563EB;color:#F9FAFB;font-size:14px;
            text-decoration:none; margin-left:10px;
        ">Edytuj harmonogram</a>';
        echo '</div>';

        echo '</form>';

        // JS obsługujący pokaz/ukryj rabatu negocjowanego.
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
	
	/**
     * Plan emisji zlecenia RADIO (kroki 2/3).
     *
     * Obsługuje:
     * - podział zakresu dat na tygodnie,
     * - pobranie aktywnych godzin z cennika (wp_sr_cennik),
     * - wczytanie istniejących emisji z wp_sr_emisje do mapy $plan_db,
     * - POST:
     *      - plan_radio_generate – pierwsze wygenerowanie planu na podstawie wybranych dni/godzin,
     *      - plan_radio_save     – zapis harmonogramu do bazy (kasowanie starych emisji + insert nowych),
     * - formularz wyboru dni tygodnia i godzin emisji (KROK 1),
     * - tabelę tygodni/dni/godzin z checkboxami + licznik spotów (KROK 2/3).
     *
     * @param int $post_id ID wpisu typu sr_zlecenie_radio.
     */
    public function render_plan( int $post_id ): void {
        global $wpdb;

        // 1) Odczyt ID (dla zgodności – jeśli wywołane bez parametru).
        if ( $post_id <= 0 && isset( $_GET['id'] ) ) {
            $post_id = (int) $_GET['id'];
        }

        // 2) Walidacja ID i typu postu.
        if ( $post_id <= 0 ) {
            echo '<p class="sr-muted">Brak identyfikatora zlecenia.</p>';
            return;
        }

        $post = get_post( $post_id );
        if ( ! $post || 'sr_zlecenie_radio' !== $post->post_type ) {
            echo '<p class="sr-muted">Nie znaleziono zlecenia RADIO o podanym ID.</p>';
            return;
        }

        // 3) Uprawnienia – na razie: tylko admin.
        if ( ! current_user_can( 'manage_options' ) ) {
            echo '<p class="sr-muted">Brak uprawnień do edycji planu emisji.</p>';
            return;
        }

        // 4) Wczytanie meta zlecenia.
        $kontrahent_id = (int) get_post_meta( $post_id, 'kontrahent_id', true );
        $nazwa_reklamy = (string) get_post_meta( $post_id, 'nazwa_reklamy', true );
        $dlugosc_spotu = (int)    get_post_meta( $post_id, 'dlugosc_spotu', true );
        $data_start    = (string) get_post_meta( $post_id, 'data_start', true );
        $data_koniec   = (string) get_post_meta( $post_id, 'data_koniec', true );

        // 5) Dane kontrahenta (opcjonalne).
        $firma  = '';
        $nip    = '';
        $adres  = '';
        $kod    = '';
        $miasto = '';

        if ( $kontrahent_id > 0 ) {
            $firma  = get_the_title( $kontrahent_id );
            $nip    = (string) get_post_meta( $kontrahent_id, 'nip', true );
            $adres  = (string) get_post_meta( $kontrahent_id, 'adres', true );
            $kod    = (string) get_post_meta( $kontrahent_id, 'kod', true );
            $miasto = (string) get_post_meta( $kontrahent_id, 'miasto', true );
        }

        $back_url = add_query_arg( 'view', 'zlecenia-radio', get_permalink() );

        // 6) Nagłówek widoku – kontekst zlecenia.
        echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">';
        echo '<div>';
        echo '<h2 style="margin:0 0 4px;">Plan emisji – krok 1 / 2</h2>';
        echo '<p class="sr-muted" style="margin:0;">Najpierw wybierz dni i godziny emisji lub edytuj istniejący harmonogram.</p>';
        echo '</div>';
        echo '<div><a href="' . esc_url( $back_url ) . '" style="font-size:13px;color:#6b7280;">← Powrót do listy</a></div>';
        echo '</div>';

        // 7) Karta z informacjami o zleceniu.
        echo '<div class="sr-card" style="margin-top:16px;">';
        echo '<strong>Zlecenie:</strong> ' . esc_html( $nazwa_reklamy ) . '<br>';
        echo '<strong>Kontrahent:</strong> ' . esc_html( $firma ) . '<br>';
        echo '<strong>Okres emisji:</strong> ' . esc_html( $data_start ) . ' → ' . esc_html( $data_koniec ) . '<br>';
        echo '<strong>Długość spotu:</strong> ' . esc_html( $dlugosc_spotu ) . ' sek<br>';
        echo '</div>';

        // 8) Mapa dni tygodnia i offsety (Pn=0, Wt=1, ...).
        $dni_map = array(
            'pn' => 'Pn',
            'wt' => 'Wt',
            'sr' => 'Śr',
            'cz' => 'Cz',
            'pt' => 'Pt',
            'so' => 'So',
            'nd' => 'Nd',
        );

        $dni_offset = array(
            'pn' => 0,
            'wt' => 1,
            'sr' => 2,
            'cz' => 3,
            'pt' => 4,
            'so' => 5,
            'nd' => 6,
        );

        $offset_to_dkey = array_flip( $dni_offset );

        // 9) Wyliczanie tygodni w zakresie dat zlecenia.
        $weeks = array();

        try {
            $start = new DateTime( $data_start );
            $end   = new DateTime( $data_koniec );
            $end->setTime( 23, 59, 59 ); // żeby złapać cały ostatni dzień
        } catch ( Exception $e ) {
            echo '<p class="sr-muted">Błąd formatu daty zlecenia.</p>';
            return;
        }

        $week_start = clone $start;

        while ( $week_start <= $end ) {
            $week_end = (clone $week_start)->modify( '+6 days' );

            if ( $week_end > $end ) {
                $week_end = clone $end;
            }

            $weeks[] = array(
                'start' => $week_start->format( 'Y-m-d' ),
                'end'   => $week_end->format( 'Y-m-d' ),
            );

            $week_start->modify( '+7 days' );
        }

        if ( empty( $weeks ) ) {
            echo '<p class="sr-muted">Zakres dat nie pozwala wygenerować tygodni.</p>';
            return;
        }

        // 10) Pobranie aktywnych godzin z cennika RADIO (wp_sr_cennik).
        $table_cennik = $wpdb->prefix . 'sr_cennik';

        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $table_cennik
            )
        );

        if ( $table_exists !== $table_cennik ) {
            echo '<p class="sr-muted" style="margin-top:12px;">Brak tabeli cennika (sr_cennik) – nie można wczytać godzin emisji.</p>';
            return;
        }

        $godziny = $wpdb->get_col(
            "
            SELECT godzina
            FROM {$table_cennik}
            WHERE kanal = 'radio' AND aktywna = 1
            ORDER BY godzina ASC
            "
        );

        if ( empty( $godziny ) ) {
            echo '<p class="sr-muted" style="margin-top:12px;">Brak aktywnych godzin w cenniku RADIO. Dodaj je w Ustawieniach → Cennik RADIO.</p>';
            return;
        }

        // 11) Wczytanie istniejących emisji z wp_sr_emisje i zbudowanie mapy $plan_db.
        $plan_db  = array();
        $has_plan = false;

        $row_id = (int) get_post_meta( $post_id, 'sr_zlecenia_row_id', true );
        if ( $row_id <= 0 ) {
            $row_id = $post_id;
        }

        $table_emisje = $wpdb->prefix . 'sr_emisje';

        $emisje = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT data_emisji, godzina
                FROM {$table_emisje}
                WHERE zlecenie_id = %d
                ORDER BY data_emisji, godzina
                ",
                $row_id
            )
        );

        if ( $emisje ) {
            foreach ( $emisje as $e ) {
                try {
                    $d = new DateTime( $e->data_emisji );
                } catch ( Exception $ex ) {
                    continue;
                }

                if ( $d < $start || $d > $end ) {
                    continue;
                }

                $diff_days  = (int) $start->diff( $d )->days;
                $week_index = (int) floor( $diff_days / 7 );
                $offset     = $diff_days % 7;

                $dkey = isset( $offset_to_dkey[ $offset ] ) ? $offset_to_dkey[ $offset ] : null;
                if ( ! $dkey ) {
                    continue;
                }

                if ( ! isset( $plan_db[ $week_index ][ $dkey ] ) ) {
                    $plan_db[ $week_index ][ $dkey ] = array();
                }

                $plan_db[ $week_index ][ $dkey ][] = (string) $e->godzina;
                $has_plan = true;
            }
        }

        // 12) Obsługa POST: GENERATE (pierwsze generowanie) i SAVE (zapis planu).

        $is_post = ( 'POST' === $_SERVER['REQUEST_METHOD'] );

        $sr_action = $is_post && isset( $_POST['sr_action'] )
            ? sanitize_key( wp_unslash( $_POST['sr_action'] ) )
            : '';

        $nonce_ok = $is_post
            && isset( $_POST['sr_plan_radio_nonce'] )
            && wp_verify_nonce( wp_unslash( $_POST['sr_plan_radio_nonce'] ), 'sr_plan_radio' );

        $is_generate = $is_post && $nonce_ok && ( 'plan_radio_generate' === $sr_action );
        $is_save     = $is_post && $nonce_ok && ( 'plan_radio_save' === $sr_action );

        // 12a) Zapis planu – plan_radio_save.
        if ( $is_save ) {
            $zlecenie_id = isset( $_POST['zlecenie_id'] ) ? (int) $_POST['zlecenie_id'] : $post_id;
            if ( $zlecenie_id <= 0 ) {
                $zlecenie_id = $post_id;
            }

            $row_id = (int) get_post_meta( $zlecenie_id, 'sr_zlecenia_row_id', true );
            if ( $row_id <= 0 ) {
                $row_id = $zlecenie_id;
            }

            $plan = isset( $_POST['plan'] ) && is_array( $_POST['plan'] )
                ? $_POST['plan']
                : array();

            // Czyścimy stare emisje.
            $wpdb->delete(
                $table_emisje,
                array( 'zlecenie_id' => $row_id ),
                array( '%d' )
            );

            // Insert nowych emisji na podstawie POST i tygodni.
            foreach ( $plan as $week_index => $dni ) {
                $week_index = (int) $week_index;
                if ( ! isset( $weeks[ $week_index ] ) ) {
                    continue;
                }

                $week_start_date = new DateTime( $weeks[ $week_index ]['start'] );

                if ( ! is_array( $dni ) ) {
                    continue;
                }

                foreach ( $dni as $dkey => $godziny_dnia ) {
                    if ( ! isset( $dni_offset[ $dkey ] ) ) {
                        continue;
                    }

                    $offset = $dni_offset[ $dkey ];
                    $date   = (clone $week_start_date)->modify( '+' . $offset . ' days' );

                    // Upewniamy się, że data mieści się w zakresie zlecenia.
                    if ( $date < $start || $date > $end ) {
                        continue;
                    }

                    if ( ! is_array( $godziny_dnia ) ) {
                        continue;
                    }

                    $data_emisji = $date->format( 'Y-m-d' );

                    foreach ( $godziny_dnia as $godzina ) {
                        $godzina = sanitize_text_field( wp_unslash( $godzina ) );
                        if ( '' === $godzina ) {
                            continue;
                        }

                        $wpdb->insert(
                            $table_emisje,
                            array(
                                'zlecenie_id' => $row_id,
                                'data_emisji' => $data_emisji,
                                'godzina'     => $godzina,
                                'kanal'       => 'radio',
                            ),
                            array( '%d', '%s', '%s', '%s' )
                        );
                    }
                }
            }

            // Redirect po zapisie – zostajemy na widoku planu z parametrem "saved".
            $redirect = add_query_arg(
                array(
                    'view'  => 'zlecenia-radio-plan',
                    'id'    => $post_id,
                    'saved' => 1,
                ),
                get_permalink()
            );
            wp_safe_redirect( $redirect );
            exit;
        }

        // 12b) Generowanie planu – plan_radio_generate (na podstawie checkboxów dni/godzin).
        if ( $is_generate ) {
            $selected_dni = isset( $_POST['dni'] ) && is_array( $_POST['dni'] )
                ? array_map( 'sanitize_key', $_POST['dni'] )
                : array();

            $selected_godziny = isset( $_POST['godziny'] ) && is_array( $_POST['godziny'] )
                ? array_map( 'sanitize_text_field', $_POST['godziny'] )
                : array();

            $selected_dni      = array_values( array_unique( $selected_dni ) );
            $selected_godziny  = array_values( array_unique( $selected_godziny ) );

            if ( empty( $selected_dni ) || empty( $selected_godziny ) ) {
                echo '<p class="sr-muted" style="margin-top:12px;">Nie wybrano żadnych dni lub godzin emisji. Wróć i wybierz co najmniej jedną kombinację.</p>';
                echo '<p><a href="' . esc_url(
                    add_query_arg(
                        array(
                            'view' => 'zlecenia-radio-plan',
                            'id'   => $post_id,
                        ),
                        get_permalink()
                    )
                ) . '" style="font-size:13px;color:#2563EB;">← Wróć do wyboru dni i godzin</a></p>';
                return;
            }

            // Budujemy $plan_db na podstawie wybranego zestawu.
            $plan_db  = array();
            $has_plan = false;

            foreach ( $weeks as $week_index => $w ) {
                foreach ( $selected_dni as $dkey ) {
                    if ( ! isset( $dni_offset[ $dkey ] ) ) {
                        continue;
                    }
                    foreach ( $selected_godziny as $godzina ) {
                        if ( ! isset( $plan_db[ $week_index ][ $dkey ] ) ) {
                            $plan_db[ $week_index ][ $dkey ] = array();
                        }
                        $plan_db[ $week_index ][ $dkey ][] = $godzina;
                        $has_plan = true;
                    }
                }
            }
        }

        // 13) Toaster po zapisie (plan_radio_save).
        if ( isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) {
            echo '<div class="sr-toast sr-toast--success sr-toast--visible" style="position:static;margin-top:12px;">Harmonogram emisji został zapisany.</div>';
        }

        // 14) Widok – jeżeli NIE ma planu (ani w bazie, ani z generate) → FORMULARZ WYBORU DNI I GODZIN (KROK 1).
        if ( ! $has_plan ) {
            echo '<form method="post" style="margin-top:20px;">';

            wp_nonce_field( 'sr_plan_radio', 'sr_plan_radio_nonce' );
            echo '<input type="hidden" name="sr_action" value="plan_radio_generate">';
            echo '<input type="hidden" name="zlecenie_id" value="' . esc_attr( $post_id ) . '">';

            // Dni emisji.
            echo '<h3>Dni emisji</h3>';
            echo '<div style="display:flex;gap:20px;flex-wrap:wrap;">';
            foreach ( $dni_map as $key => $label ) {
                $highlight = in_array( $key, array( 'so', 'nd' ), true )
                    ? 'font-weight:600;color:#b45309;'
                    : '';
                echo '<label style="display:flex;align-items:center;gap:6px;' . $highlight . '">';
                echo '<input type="checkbox" name="dni[]" value="' . esc_attr( $key ) . '"> ';
                echo esc_html( $label );
                echo '</label>';
            }
            echo '</div>';

            // Godziny emisji (z cennika).
            echo '<h3 style="margin-top:24px;">Godziny emisji (aktywny cennik)</h3>';

            echo '<div style="display:flex;flex-wrap:wrap;gap:14px;">';
            foreach ( $godziny as $g ) {
                echo '<label style="display:flex;align-items:center;gap:6px;">';
                echo '<input type="checkbox" name="godziny[]" value="' . esc_attr( $g ) . '"> ';
                echo esc_html( substr( (string) $g, 0, 5 ) );
                echo '</label>';
            }
            echo '</div>';

            echo '<div style="margin-top:24px;">';
            echo '<button type="submit" style="
                padding:10px 20px;border-radius:999px;border:none;
                background:#2563EB;color:white;font-size:15px;cursor:pointer;
            ">Generuj harmonogram</button>';
            echo '</div>';

            echo '</form>';

            echo '<div style="margin-top:16px;">';
            echo '<a href="' . esc_url( $back_url ) . '" style="font-size:13px;color:#6b7280;">Anuluj</a>';
            echo '</div>';

            return;
        }

        // 15) Gdy plan istnieje (z bazy lub z generate) – TABELA TYGODNI/DNI/GODZIN + JS.

        // CSS tabeli i zakładek tygodni.
        echo '<style>
        .sr-week-tabs { display:flex; flex-direction:column; gap:8px; margin-right:16px; }
        .sr-week-tab-btn {
            display:block; padding:6px 10px; border-radius:6px;
            border:1px solid #d1d5db; background:#f9fafb; font-size:13px;
            cursor:pointer; text-align:left;
        }
        .sr-week-tab-btn.is-active {
            background:#111827; color:#f9fafb; border-color:#111827;
        }
        .sr-week-panel { display:none; }
        .sr-week-panel.is-active { display:block; }

        table.sr-plan-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:8px; }
        table.sr-plan-table th,
        table.sr-plan-table td {
            border:1px solid #e5e7eb;
            padding:6px 8px;
            text-align:center;
            vertical-align:middle;
        }
        table.sr-plan-table thead th {
            background:#f9fafb;
            font-weight:600;
            text-transform:uppercase;
            font-size:11px;
            letter-spacing:0.03em;
        }
        .sr-col-so,
        .sr-col-nd {
            background:#FFF7ED;
        }
        </style>';

        // Formularz zapisu (plan_radio_save).
        echo '<form method="post" style="margin-top:20px;">';
        wp_nonce_field( 'sr_plan_radio', 'sr_plan_radio_nonce' );
        echo '<input type="hidden" name="sr_action" value="plan_radio_save">';
        echo '<input type="hidden" name="zlecenie_id" value="' . esc_attr( $post_id ) . '">';

        echo '<div style="margin-bottom:8px;font-size:13px;">';
        echo '<strong>Ilość spotów:</strong> <span id="sr-ilosc-spotow">0</span>';
        echo '</div>';

        echo '<div style="margin-top:0; display:flex; gap:16px;">';

        // Zakładki tygodni.
        echo '<div class="sr-week-tabs">';
        foreach ( $weeks as $index => $w ) {
            $label = 'Tydzień ' . ( $index + 1 ) . '<br>' .
                esc_html( $w['start'] ) . ' – ' . esc_html( $w['end'] );
            echo '<button type="button" class="sr-week-tab-btn' . ( 0 === $index ? ' is-active' : '' ) . '" data-week="' . $index . '">';
            echo $label;
            echo '</button>';
        }
        echo '</div>';

        // Panele tygodni z tabelą dni/godzin.
        echo '<div style="flex:1;">';

        echo '<div id="sr-week-panels">';

        foreach ( $weeks as $index => $w ) {
            $panel_class = 'sr-week-panel' . ( 0 === $index ? ' is-active' : '' );

            echo '<div class="' . esc_attr( $panel_class ) . '" data-week-panel="' . esc_attr( $index ) . '">';
            echo '<div style="font-size:13px;margin-bottom:4px;"><strong>Tydzień ' . ( $index + 1 ) . ':</strong> ' .
                esc_html( $w['start'] ) . ' – ' . esc_html( $w['end'] ) . '</div>';

            echo '<table class="sr-plan-table">';
            echo '<thead><tr>';
            echo '<th>Godzina</th>';

            foreach ( $dni_map as $dkey => $dlabel ) {
                $th_class = in_array( $dkey, array( 'so', 'nd' ), true ) ? ' class="sr-col-' . $dkey . '"' : '';
                echo '<th' . $th_class . '>' . esc_html( $dlabel ) . '</th>';
            }

            echo '</tr></thead><tbody>';

            foreach ( $godziny as $godzina ) {
                $godzina_raw = (string) $godzina;
                $godzina_hhmm = substr( $godzina_raw, 0, 5 );

                echo '<tr>';
                echo '<td>' . esc_html( $godzina_hhmm ) . '</td>';

                foreach ( $dni_map as $dkey => $dlabel ) {
                    $td_class = in_array( $dkey, array( 'so', 'nd' ), true ) ? ' class="sr-col-' . $dkey . '"' : '';
                    $name     = 'plan[' . $index . '][' . $dkey . '][]';

                    $is_checked = (
                        isset( $plan_db[ $index ][ $dkey ] ) &&
                        in_array( $godzina_raw, (array) $plan_db[ $index ][ $dkey ], true )
                    );

                    echo '<td' . $td_class . '>';
                    echo '<input type="checkbox" class="sr-spot-checkbox" name="' . esc_attr( $name ) . '" value="' . esc_attr( $godzina_raw ) . '"' . checked( $is_checked, true, false ) . '>';
                    echo '</td>';
                }

                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</div>'; // .sr-week-panel
        }

        echo '</div>'; // #sr-week-panels
        echo '</div>'; // prawa kolumna (tabela)
        echo '</div>'; // wrapper flex

        // Przyciski zapisu/anulowania.
        echo '<div style="margin-top:20px;display:flex;gap:10px;">';
        echo '<a href="' . esc_url( $back_url ) . '" style="
            padding:8px 16px;border-radius:999px;border:1px solid #d1d5db;
            background:#fff;color:#374151;font-size:13px;text-decoration:none;
        ">Anuluj</a>';

        echo '<button type="submit" style="
            padding:8px 16px;border-radius:999px;border:none;
            background:#16A34A;color:#F9FAFB;font-size:13px;cursor:pointer;
        ">Zapisz harmonogram</button>';

        echo '</div>';

        echo '</form>';

        // JS – zakładki tygodni + licznik ilości spotów.
        ?>
       	<script>
		document.addEventListener('DOMContentLoaded', function () {

			/* -------------------------------------------------------
			 *  Zakładki tygodni – BEZ ZMIAN
			 * ------------------------------------------------------- */
			var tabButtons = document.querySelectorAll('.sr-week-tab-btn');
			var panels = document.querySelectorAll('.sr-week-panel');

			tabButtons.forEach(function (btn) {
				btn.addEventListener('click', function () {
					var week = this.getAttribute('data-week');

					tabButtons.forEach(function (b) { b.classList.remove('is-active'); });
					this.classList.add('is-active');

					panels.forEach(function (p) {
						p.classList.toggle('is-active', p.getAttribute('data-week-panel') === week);
					});
				});
			});

			/* -------------------------------------------------------
			 * Licznik spotów – BEZ ZMIAN
			 * ------------------------------------------------------- */
			var checkboxes = document.querySelectorAll('.sr-spot-checkbox');
			var counterEl = document.getElementById('sr-ilosc-spotow');

			function recalcSpots() {
				var count = 0;
				checkboxes.forEach(function (cb) { if (cb.checked) count++; });
				if (counterEl) counterEl.textContent = String(count);
			}

			checkboxes.forEach(function (cb) { cb.addEventListener('change', recalcSpots); });
			recalcSpots();


			/* -------------------------------------------------------
			 *  WALIDACJA FORMULARZA — NOWOŚĆ
			 *
			 *  Obejmuje:
			 *   - GENERATE (krok 1)
			 *   - SAVE (krok 2)
			 *
			 *  Każda akcja ma własną walidację.
			 * ------------------------------------------------------- */

			// Funkcja: wyświetl toast błędu.
			function srToastError(msg) {
				var t = document.createElement('div');
				t.className = 'sr-toast sr-toast--success sr-toast--visible';
				t.style.background = '#DC2626';   // czerwony
				t.style.color = '#F9FAFB';
				t.style.position = 'fixed';
				t.style.top = '20px';
				t.style.right = '20px';
				t.style.zIndex = 999999;
				t.textContent = msg;
				document.body.appendChild(t);

				setTimeout(function () {
					t.classList.remove('sr-toast--visible');
					setTimeout(function () { t.remove(); }, 350);
				}, 3000);
			}

			/* -------------------------------
			 *   Obsługa GENERATE (krok 1)
			 * ------------------------------- */
			var genForm = document.querySelector('form [name="sr_action"][value="plan_radio_generate"]');
			if (genForm) {

				// Znajdziemy cały <form> (rodzica inputa)
				var formGenerate = genForm.closest('form');

				formGenerate.addEventListener('submit', function (e) {

					var dni = formGenerate.querySelectorAll('input[name="dni[]"]:checked');
					var godziny = formGenerate.querySelectorAll('input[name="godziny[]"]:checked');

					if (dni.length === 0) {
						e.preventDefault();
						srToastError('Wybierz przynajmniej jeden dzień emisji.');
						return false;
					}

					if (godziny.length === 0) {
						e.preventDefault();
						srToastError('Wybierz przynajmniej jedną godzinę emisji.');
						return false;
					}

					return true;
				});
			}

			/* -------------------------------
			 *   Obsługa SAVE (krok 2)
			 * ------------------------------- */
			var saveForm = document.querySelector('form [name="sr_action"][value="plan_radio_save"]');
			if (saveForm) {

				var formSave = saveForm.closest('form');

				formSave.addEventListener('submit', function (e) {

					var spoty = formSave.querySelectorAll('.sr-spot-checkbox:checked');

					if (spoty.length === 0) {
						e.preventDefault();
						srToastError('Zaznacz przynajmniej jedną emisję, aby zapisać harmonogram.');
						return false;
					}

					return true;
				});
			}

		});
		</script>
        <?php
    }
	
}