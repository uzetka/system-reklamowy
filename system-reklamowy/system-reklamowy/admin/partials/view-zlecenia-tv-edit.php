<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$lista_url = admin_url( 'edit.php?post_type=sr_zlecenie_tv' );
$nowe_url  = admin_url( 'post-new.php?post_type=sr_zlecenie_tv' );
?>
<div class="wrap">
    <h1>Zlecenia TV</h1>

    <p>
        Na razie zarządzanie zleceniami odbywa się przez standardowy edytor wpisów typu
        <code>sr_zlecenie_tv</code>.
    </p>

    <p style="margin-top:16px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">

        <!-- LINK: Dodaj nowe zlecenie TV -->
        <a href="<?php echo esc_url( $nowe_url ); ?>"
		   class="page-title-action">
            + Dodaj nowe zlecenie TV
        </a>

        <!-- LINK: Lista zleceń TV -->
        <a href="<?php echo esc_url( $lista_url ); ?>"
		   class="button button-secondary">
            Lista zleceń TV
        </a>

    </p>

    <p style="margin-top:24px; color:#6b7280; max-width:720px;">
        W kolejnych etapach podłączymy tutaj dedykowany ekran listy i edycji zleceń TV
        (<code>WP_List_Table</code> + integracja z tabelą <code>wp_sr_zlecenia</code>).
    </p>
</div>