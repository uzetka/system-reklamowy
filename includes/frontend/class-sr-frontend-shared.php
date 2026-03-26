<?php

if ( ! defined('ABSPATH') ) exit;

class SR_Frontend_Shared {

    public static function render_sidebar() {
        ?>
        <aside class="sr-sidebar">
            ?view=dashboardDashboard</a>
            ?view=kontrahenciKontrahenci</a>
            ?view=zlecenia-radio-addZlecenia RADIO</a>
            ?view=ustawieniaUstawienia</a>
        </aside>
        <?php
    }

    public static function render_dashboard() {
        echo '<h2>Panel Reklamowy</h2><p>Wybierz moduł z menu po lewej.</p>';
    }
}