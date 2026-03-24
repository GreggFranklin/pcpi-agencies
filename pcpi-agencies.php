<?php
/**
 * Plugin Name:       _PCPI Agencies
 * Plugin URI:        https://pcpolygraph.com
 * Description:       Registers the Agency custom post type, meta fields, REST fields, Gutenberg block, and shortcode.
 * Version:           2.2.0
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
