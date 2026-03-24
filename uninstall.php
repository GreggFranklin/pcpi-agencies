<?php
/**
 * Fired when the plugin is deleted via wp-admin → Plugins → Delete.
 *
 * Removes all pcpi_agency posts (and their meta) from the database.
 * Only runs when a user explicitly deletes the plugin — not on deactivation.
 *
 * To preserve data on uninstall (e.g. if you plan to reinstall), simply
 * delete or rename this file before deleting the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Delete all agency posts and their associated meta / taxonomy terms
$posts = get_posts( [
    'post_type'      => 'pcpi_agency',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
] );

foreach ( $posts as $post_id ) {
    wp_delete_post( $post_id, true ); // true = force delete, bypass trash
}
