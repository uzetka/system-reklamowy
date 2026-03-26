<?php

if ( ! defined('ABSPATH') ) exit;

class SR_Frontend_Settings {

    public static function render_page() {

        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'cennik-radio';

        ?>
        <h2>Ustawienia systemu</h2>
        <div class="sr-tabs">
            ?view=ustawienia&tab=cennik-radioCennik RADIO</a>
            ?view=ustawienia&tab=cennik-tvCennik TV</a>
            ?view=ustawienia&tab=przelicznik-czasuPrzelicznik czasu</a>
            ?view=ustawienia&tab=przedmiotPrzedmiot działalności</a>
            ?view=ustawienia&tab=rabatyRabaty</a>
        </div>
        <?php

        switch ($tab) {
            case 'cennik-radio':
                include SR_PLUGIN_DIR . 'includes/views/settings/cennik-radio.php';
                break;

            default:
                echo '<p>Ta zakładka nie jest jeszcze przeniesiona.</p>';
        }
    }
}