<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SR_Admin_List_Table_Kontrahenci extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'kontrahent',
            'plural'   => 'kontrahenci',
            'ajax'     => false,
        ] );
    }

    public function get_columns() {
        return [
            'cb'      => '<input type="checkbox" />',
            'title'   => 'Nazwa firmy',
            'nip'     => 'NIP',
            'adres'   => 'Adres',
            'kod'     => 'Kod',
            'miasto'  => 'Miasto',
            'actions' => 'Akcje',
        ];
    }

    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="kontrahent[]" value="%d" />',
            $item->ID
        );
    }

    public function column_default( $item, $column_name ) {

        switch ( $column_name ) {

            case 'title':
                $edit_link = get_edit_post_link( $item->ID );
                return sprintf(
                    '<strong><a href="%s">%s</a></strong>',
                    esc_url( $edit_link ),
                    esc_html( get_the_title( $item->ID ) )
                );

            case 'nip':
            case 'adres':
            case 'kod':
            case 'miasto':
                return esc_html( get_post_meta( $item->ID, $column_name, true ) );

            case 'actions':
                $edit   = get_edit_post_link( $item->ID );
                $delete = get_delete_post_link( $item->ID );
                return sprintf(
                    '<a href="%s">Edytuj</a> | <a href="%s" onclick="return confirm(\'Na pewno usunąć?\');">Usuń</a>',
                    esc_url( $edit ),
                    esc_url( $delete )
                );

            default:
                return '';
        }
    }

    public function get_sortable_columns() {
        return [
            'title'  => [ 'title', true ],
            'miasto' => [ 'miasto', false ],
        ];
    }

    public function prepare_items() {
        $per_page = 20;

        $paged   = $this->get_pagenum();
        $orderby = ! empty( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'title';
        $order   = ! empty( $_GET['order'] )   ? sanitize_text_field( $_GET['order'] )   : 'ASC';

        $args = [
            'post_type'      => 'sr_kontrahent',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => $orderby,
            'order'          => $order,
        ];

        if ( ! empty( $_GET['s'] ) ) {
            $args['s'] = sanitize_text_field( $_GET['s'] );
        }

        $query = new WP_Query( $args );

        $this->items = $query->posts;

        $total_items = $query->found_posts;

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );

        $this->_column_headers = [
            $this->get_columns(),
            [], // hidden
            $this->get_sortable_columns(),
        ];
    }
}