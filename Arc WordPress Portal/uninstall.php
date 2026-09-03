<?php
/**
 * Uninstall handler for ARC Portal.
 *
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'arc_portal_app_settings' );
delete_option( 'arc_portal_app_gas_auth_url' );
delete_option( 'arc_portal_app_gas_api_secret' );
delete_option( 'arc_portal_app_auto_import_done' );
delete_option( 'arc_portal_app_page_created' );
