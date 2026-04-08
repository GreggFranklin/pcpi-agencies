<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers the pcpi_agency custom post type and customises the admin list table.
 */
class PCPI_Agencies_CPT {

    public function init(): void {
        add_action( 'init', [ $this, 'register_cpt' ] );

        // Admin list table
        add_filter( 'manage_pcpi_agency_posts_columns',         [ $this, 'set_columns'       ] );
        add_action( 'manage_pcpi_agency_posts_custom_column',   [ $this, 'render_column'     ], 10, 2 );
        add_filter( 'manage_edit-pcpi_agency_sortable_columns', [ $this, 'sortable_columns'  ] );
        add_action( 'admin_enqueue_scripts',                    [ $this, 'enqueue_list_styles' ] );
    }

    // -------------------------------------------------------------------------
    // CPT registration
    // -------------------------------------------------------------------------

    public function register_cpt(): void {
        $labels = [
            'name'               => 'Agencies',
            'singular_name'      => 'Agency',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add Agency',
            'edit_item'          => 'Edit Agency',
            'new_item'           => 'New Agency',
            'view_item'          => 'View Agency',
            'search_items'       => 'Search Agencies',
            'not_found'          => 'No agencies found',
            'not_found_in_trash' => 'No agencies found in trash',
            'all_items'          => 'All Agencies',
            'menu_name'          => 'Agencies',
        ];

        register_post_type( 'pcpi_agency', [
            'labels'       => $labels,
            'public'       => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-building',
            'supports'     => [ 'title', 'thumbnail' ],
        ] );
    }

    // -------------------------------------------------------------------------
    // List table columns
    // -------------------------------------------------------------------------

    /**
     * Define column order and headings.
     * Drops the default thumbnail column (we handle logo ourselves).
     */
    public function set_columns( array $columns ): array {
    return [
        'cb'             => $columns['cb'],
        'pcpi_logo'      => 'Logo',
        'title'          => 'Agency Name',
        'pcpi_contact'   => 'Agency Contact',   // NEW
        'pcpi_email'     => 'Email Address',    // NEW
        'pcpi_phone'     => 'Phone',
        'pcpi_address'   => 'Address',
        'date'           => 'Date',
    ];
}

    /** Output cell content for each custom column. */
    public function render_column( string $column, int $post_id ): void {
        switch ( $column ) {

            case 'pcpi_logo':
                $logo_id = (int) get_post_meta( $post_id, '_pcpi_logo_id', true );
                if ( $logo_id ) {
                    $url = wp_get_attachment_image_url( $logo_id, 'thumbnail' );
                    if ( $url ) {
                        printf(
                            '<img src="%s" alt="" class="pcpi-col-logo">',
                            esc_url( $url )
                        );
                    }
                }
                break;

            case 'pcpi_contact':
                echo esc_html( get_post_meta( $post_id, '_pcpi_contact', true ) );
                break;

            case 'pcpi_email':
                $email = get_post_meta( $post_id, '_pcpi_email', true );
                 if ( $email ) {
                    echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
                }
                 break;

            case 'pcpi_phone':
                echo esc_html( get_post_meta( $post_id, '_pcpi_phone', true ) );
                break;

            case 'pcpi_address':
                $lines = pcpi_parse_address_lines(
                    (string) get_post_meta( $post_id, '_pcpi_address', true )
                );
                // Collapse to a single line for the table cell
                echo esc_html( implode( ' ', $lines ) );
                break;
        }
    }

    /** Make Agency Name, Phone, and Address column headers clickable for sorting. */
    public function sortable_columns( array $columns ): array {
        $columns['title']         = 'title';
        $columns['pcpi_contact']  = 'pcpi_contact'; // NEW
        $columns['pcpi_email']    = 'pcpi_email';   // NEW
        $columns['pcpi_phone']    = 'pcpi_phone';
        $columns['pcpi_address']  = 'pcpi_address';
        return $columns;
    }

    /**
     * Enqueue a tiny stylesheet for list-table column widths.
     * Scoped to the agencies list screen only — no leakage into other admin pages.
     */
    public function enqueue_list_styles( string $hook ): void {
        if ( $hook !== 'edit.php' ) return;
        if ( get_current_screen()->post_type !== 'pcpi_agency' ) return;

        wp_add_inline_style( 'list-tables', '
             .column-pcpi_logo     { width: 70px; }
            .column-pcpi_contact  { width: 180px; }  /* NEW */
            .column-pcpi_email    { width: 220px; }  /* NEW */
            .column-pcpi_phone    { width: 160px; }
            .column-pcpi_address  { width: 340px; }
            .pcpi-col-logo        { width: 44px; height: 44px; object-fit: contain; display: block; }
        ' );
    }
}
