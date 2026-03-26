<?php
if ( ! defined('ABSPATH') ) exit;

// pobieramy aktualny widok
$view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'dashboard';
?>

<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <?php wp_head(); ?>
</head>

<body class="sr-panel">

<div id="sr-panel-wrapper">

    <?php SR_Frontend_Shared::render_sidebar(); ?>

    <main class="sr-panel-main">
        <?php
        switch ($view) {

            case 'kontrahenci':
                SR_Frontend_Kontrahenci::render_list();
                break;

            case 'zlecenia-radio-add':
                SR_Frontend_Zlecenia_Radio::render_add();
                break;

            case 'ustawienia':
                SR_Frontend_Settings::render_page();
                break;

            default:
                SR_Frontend_Shared::render_dashboard();
        }
        ?>
    </main>

</div>

<?php wp_footer(); ?>

</body>
</html>