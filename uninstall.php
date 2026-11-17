<?php
/**
 * Runs when the plugin is deleted from the Plugins screen (not on deactivation).
 * Removes everything Privento stored. The Cookie Policy page is the site's own
 * content and is left in place.
 *
 * @package Privento
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

foreach ( [
    'privento_settings',
    'privento_db_version',
    'privento_scanned_cookies',
    'privento_last_scan',
    'privento_revoke_link_seen',
] as $privento_option ) {
    delete_option( $privento_option );
}

delete_transient( 'privento_revoke_link_found' );
delete_site_transient( 'privento_update' );

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}privento_consent_log" );
