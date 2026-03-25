<?php
/**
 * Synchronizacja zleceń RADIO (CPT sr_zlecenie_radio) z tabelą SQL wp_sr_zlecenia.
 *
 * Mapowanie:
 * - post_type: sr_zlecenie_radio
 * - meta:
 *   kontrahent_id       -> wp_sr_zlecenia.kontrahent_id
 *   typ                 -> typ  (radio/tv, tutaj zawsze 'radio')
 *   nazwa_reklamy       -> nazwa_reklamy
 *   data_zlecenia       -> data_zlecenia
 *   data_start          -> data_start
 *   data_koniec         -> data_koniec
 *   wartosc             -> wartosc
 *   do_zaplaty          -> do_zaplaty
 *   rabat               -> rabat
 *   motive              -> motive
 *   dlugosc_spotu       -> dlugosc_spotu
 *   status              -> status
 *
 * Powiązanie w drugą stronę:
 *  - post_meta 'sr_zlecenia_row_id' przechowuje id rekordu w wp_sr_zlecenia.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Zlecenia_Sync {

    /**
     * Hook: save_post_sr_zlecenie_radio
     *
     * @param int     $post_id
     * @param WP_Post $post
     * @param bool    $update
     */
	public function sync_zlecenie_radio( int $post_id, WP_Post $post, bool $update ) {
        global $wpdb;

        // Bezpieczeństwo: tylko właściwy typ, bez autosave/rev.
        if ( $post->post_type !== 'sr_zlecenie_radio' ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
		
		// 🔥 FIX: Nie synchronizujemy, jeśli zlecenie NIE MA jeszcze wymaganych meta.
		// Zapobiega tworzeniu PUSTYCH wierszy w wp_sr_zlecenia.
		// Dotyczy głównie sytuacji, gdy CPT jest tworzone we FRONT-PANELU.
		$required_meta = [
			'kontrahent_id',
			'nazwa_reklamy',
			'dlugosc_spotu',
			'data_start',
			'data_koniec',
		];

		foreach ( $required_meta as $m ) {
			$val = get_post_meta( $post_id, $m, true );
			if ( $v === '' || $v === null ) {
				// Meta nie są jeszcze ustawione → przerwij sync
				return;
			}
		}

        // Jeżeli zlecenie w koszu – można np. oznaczyć status jako "cancelled".
        if ( $post->post_status === 'trash' ) {
            $this->mark_cancelled( $post_id );
            return;
        }

        // Pobranie meta – używamy helpera sr_get_meta jeśli istnieje, inaczej get_post_meta.
        $get_meta = function( $key, $default = '' ) use ( $post_id ) {
            if ( function_exists( 'sr_get_meta' ) ) {
                return sr_get_meta( $post_id, $key, $default );
            }
            $v = get_post_meta( $post_id, $key, true );
            return $v === '' ? $default : $v;
        };

        $kontrahent_id   = (int) $get_meta( 'kontrahent_id', 0 );
        $typ             = (string) $get_meta( 'typ', 'radio' );
        $nazwa_reklamy   = (string) $get_meta( 'nazwa_reklamy', '' );
        $data_zlecenia   = (string) $get_meta( 'data_zlecenia', '' );
        $data_start      = (string) $get_meta( 'data_start', '' );
        $data_koniec     = (string) $get_meta( 'data_koniec', '' );
        $wartosc         = (string) $get_meta( 'wartosc', '0' );
        $do_zaplaty      = (string) $get_meta( 'do_zaplaty', '0' );
        $rabat           = (string) $get_meta( 'rabat', '' );
        $motive          = (string) $get_meta( 'motive', '' );
        $dlugosc_spotu   = (int) $get_meta( 'dlugosc_spotu', 0 );
        $status          = (string) $get_meta( 'status', 'draft' );

        // Wymuszamy typ "radio" dla tego CPT, żeby nie było niespodzianek.
        $typ = 'radio';

        $table_zlecenia = $wpdb->prefix . 'sr_zlecenia';

        // Budujemy payload do SQL – null dla pustych dat i kontrahenta.
        $data = [
            'kontrahent_id' => $kontrahent_id ?: null,
            'typ'           => $typ,
            'nazwa_reklamy' => $nazwa_reklamy,
            'data_zlecenia' => $data_zlecenia !== '' ? $data_zlecenia : null,
            'data_start'    => $data_start !== '' ? $data_start : null,
            'data_koniec'   => $data_koniec !== '' ? $data_koniec : null,
            'wartosc'       => $wartosc !== '' ? $wartosc : '0',
            'do_zaplaty'    => $do_zaplaty !== '' ? $do_zaplaty : '0',
            'rabat'         => $rabat,
            'motive'        => $motive,
            'dlugosc_spotu' => $dlugosc_spotu,
            'status'        => $status,
        ];

        $formats = [
            '%d', // kontrahent_id
            '%s', // typ
            '%s', // nazwa_reklamy
            '%s', // data_zlecenia
            '%s', // data_start
            '%s', // data_koniec
            '%s', // wartosc
            '%s', // do_zaplaty
            '%s', // rabat
            '%s', // motive
            '%d', // dlugosc_spotu
            '%s', // status
        ];

        // Czy mamy już przypisany rekord w tabeli SQL?
        $row_id = (int) get_post_meta( $post_id, 'sr_zlecenia_row_id', true );

        if ( $row_id > 0 ) {
            // UPDATE
            $wpdb->update(
                $table_zlecenia,
                $data,
                [ 'id' => $row_id ],
                $formats,
                [ '%d' ]
            );
        } else {
            // INSERT
            $inserted = $wpdb->insert(
                $table_zlecenia,
                $data,
                $formats
            );

            if ( $inserted !== false ) {
                $row_id = (int) $wpdb->insert_id;
                if ( $row_id > 0 ) {
                    update_post_meta( $post_id, 'sr_zlecenia_row_id', $row_id );
                }
            }
        }
    }

    /**
     * Oznacza zlecenie jako anulowane w SQL, gdy CPT leci do kosza.
     *
     * @param int $post_id
     */
    protected function mark_cancelled( int $post_id ) {
        global $wpdb;

        $row_id = (int) get_post_meta( $post_id, 'sr_zlecenia_row_id', true );
        if ( $row_id <= 0 ) {
            return;
        }

        $table_zlecenia = $wpdb->prefix . 'sr_zlecenia';

        $wpdb->update(
            $table_zlecenia,
            [ 'status' => 'cancelled' ],
            [ 'id' => $row_id ],
            [ '%s' ],
            [ '%d' ]
        );
    }
}
