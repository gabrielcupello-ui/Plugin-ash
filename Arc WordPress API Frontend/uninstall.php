<?php
/**
 * Uninstall handler for ARC API Frontend.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'arc_api_frontend_app_settings' );
delete_option( 'arc_api_frontend_app_auto_import_done' );
delete_option( 'arc_api_frontend_app_shared_secret' );

// Clean transients created by the proxy.
function arc_api_frontend_delete_transients() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_arc_api_frontend_app_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_arc_api_frontend_app_%'" );
}
add_action( 'delete_option', 'arc_api_frontend_delete_transients' );

arc_api_frontend_delete_transients();
