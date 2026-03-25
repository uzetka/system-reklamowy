<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once SR_PLUGIN_DIR . 'admin/class-sr-admin-list-table-kontrahenci.php';

$table = new SR_Admin_List_Table_Kontrahenci();
$table->prepare_items();

$add_new_url = admin_url( 'post-new.php?post_type=sr_kontrahent' );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Kontrahenci</h1>

    <a href="<?php echo esc_url( $add_new_url ); ?>" class="page-title-action">
        Dodaj nowego
    </a>

    <hr class="wp-header-end">

    <form method="get">
        <input type="hidden" name="page" value="sr-kontrahenci" />
        <?php $table->search_box( 'Szukaj kontrahenta', 'sr-kontrahenci' ); ?>
    </form>

    <form method="post">
        <?php
        $table->display();
        ?>
    </form>
</div>