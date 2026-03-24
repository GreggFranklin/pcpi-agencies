<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers the pcpi/agency-card Gutenberg block.
 *
 * The block is server-side rendered; the JS only handles the editor
 * experience (agency picker + live preview). The save() function
 * returns null, so WP always calls render_callback on the front end.
 */
class PCPI_Agencies_Block {

    public function init(): void {
        add_action( 'init', [ $this, 'register_block' ] );
    }

    public function register_block(): void {
        $js_path  = PCPI_AGENCIES_PATH . 'build/index.js';
        $css_path = PCPI_AGENCIES_PATH . 'build/style.css';
        $ed_path  = PCPI_AGENCIES_PATH . 'build/editor.css';

        // Guard against missing build files (e.g. fresh checkout before npm build)
        if ( ! file_exists( $js_path ) ) return;

        wp_register_script(
            'pcpi-agency-block-editor',
            PCPI_AGENCIES_URL . 'build/index.js',
            // Note: 'wp-editor' is deprecated since WP 5.9 — use 'wp-block-editor' instead
            [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'wp-html-entities' ],
            filemtime( $js_path ),
            true
        );

        if ( file_exists( $ed_path ) ) {
            wp_register_style(
                'pcpi-agency-block-editor-style',
                PCPI_AGENCIES_URL . 'build/editor.css',
                [ 'wp-edit-blocks' ],
                filemtime( $ed_path )
            );
        }

        if ( file_exists( $css_path ) ) {
            wp_register_style(
                'pcpi-agency-block-style',
                PCPI_AGENCIES_URL . 'build/style.css',
                [],
                filemtime( $css_path )
            );
        }

        register_block_type( 'pcpi/agency-card', [
            'editor_script'   => 'pcpi-agency-block-editor',
            'editor_style'    => 'pcpi-agency-block-editor-style',
            'style'           => 'pcpi-agency-block-style',
            'render_callback' => [ $this, 'render_block' ],
            'attributes'      => [
                'agencyId' => [
                    'type'    => 'number',
                    'default' => 0,
                ],
            ],
        ] );
    }

    /**
     * Server-side render callback for the pcpi/agency-card block.
     *
     * @param  array $atts Block attributes.
     * @return string      Rendered HTML.
     */
    public function render_block( array $atts ): string {
        $agency_id = isset( $atts['agencyId'] ) ? (int) $atts['agencyId'] : 0;
        return $agency_id ? pcpi_render_agency_card( $agency_id ) : '';
    }
}
