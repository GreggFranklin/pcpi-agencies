<?php
/**
 * Plugin Name:       _PCPI Agencies
 * Description:       Registers the Agency custom post type, meta fields, REST fields, Gutenberg block, and shortcode.
 * Version:           2.2.1
 * Author:            Gregg Franklin, Marc Benzakein
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pcpi
 * Domain Path:       /languages
 * Requires at least: 6.1
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PCPI_AGENCIES_VERSION', '2.2.0' );
define( 'PCPI_AGENCIES_PATH',    plugin_dir_path( __FILE__ ) );
define( 'PCPI_AGENCIES_URL',     plugin_dir_url( __FILE__ ) );

require_once PCPI_AGENCIES_PATH . 'includes/class-cpt.php';
require_once PCPI_AGENCIES_PATH . 'includes/class-meta.php';
require_once PCPI_AGENCIES_PATH . 'includes/class-block.php';
require_once PCPI_AGENCIES_PATH . 'includes/helpers.php';

( new PCPI_Agencies_CPT() )->init();
( new PCPI_Agencies_Meta() )->init();
( new PCPI_Agencies_Block() )->init();

// "Settings" link on the Plugins list screen
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( array $links ): array {
    $url = admin_url( 'edit.php?post_type=pcpi_agency' );
    array_unshift( $links, '<a href="' . esc_url( $url ) . '">Manage Agencies</a>' );
    return $links;
} );

// Remove unnecessary meta boxes
add_action( 'do_meta_boxes', function() {

    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'pcpi_agency' ) {
        return;
    }

    // Core removals
    remove_meta_box( 'postcustom', 'pcpi_agency', 'normal' );
    remove_meta_box( 'postimagediv', 'pcpi_agency', 'side' );
    remove_meta_box( 'slugdiv', 'pcpi_agency', 'normal' ); // optional but recommended

    // WPCode
    remove_meta_box( 'wpcode-metabox-snippets', 'pcpi_agency', 'normal' );

}, 100 );

// Remove Kadence meta boxes
add_action( 'add_meta_boxes', function() {

    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'pcpi_agency' ) {
        return;
    }

    global $wp_meta_boxes;

    if ( empty( $wp_meta_boxes['pcpi_agency'] ) ) {
        return;
    }

    foreach ( $wp_meta_boxes['pcpi_agency'] as $context => $priorities ) {

        foreach ( $priorities as $priority => $boxes ) {

            if ( ! is_array( $boxes ) ) continue;

            foreach ( $boxes as $id => $box ) {

                if (
                    isset( $box['callback'] )
                    && is_array( $box['callback'] )
                    && is_object( $box['callback'][0] )
                ) {

                    $class = get_class( $box['callback'][0] );

                    // Target Kadence class directly
                    if ( strpos( $class, 'Kadence' ) !== false ) {
                        remove_meta_box( $id, 'pcpi_agency', $context );
                    }
                }
            }
        }
    }

}, 999 );

// Change "Entrer title here" to "Agency Name" on the edit screen
add_filter( 'enter_title_here', function( $title, $post ) {
    if ( $post->post_type === 'pcpi_agency' ) {
        return 'Agency Name';
    }
    return $title;
}, 10, 2 );

add_action( 'init', function() {
    remove_post_type_support( 'pcpi_agency', 'editor' );
});