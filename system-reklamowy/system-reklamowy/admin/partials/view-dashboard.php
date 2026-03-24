<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1>System Reklamowy – Dashboard</h1>

    <p>Witaj w module emisji reklam (Radio/TV).</p>

    <h2>Szybkie skróty</h2>
    <ul>
        <li><a href="<?php echo admin_url( 'admin.php?page=sr-kontrahenci' ); ?>">Kontrahenci</a></li>
        <li><a href="<?php echo admin_url( 'admin.php?page=sr-zlecenia-radio' ); ?>">Zlecenia RADIO</a></li>
        <li><a href="<?php echo admin_url( 'admin.php?page=sr-zlecenia-tv' ); ?>">Zlecenia TV</a></li>
        <li><a href="<?php echo admin_url( 'admin.php?page=sr-grafik-radio' ); ?>">Grafik RADIO</a></li>
        <li><a href="<?php echo admin_url( 'admin.php?page=sr-settings' ); ?>">Ustawienia</a></li>
    </ul>

    <p>Na kolejnych etapach dodamy tu statystyki: liczba bieżących zleceń, dzisiejsze emisje, itp.</p>
</div>