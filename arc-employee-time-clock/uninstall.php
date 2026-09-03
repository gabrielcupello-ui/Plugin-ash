<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'arc_etc_settings', array() );
if ( ! empty( $settings['delete_data_on_uninstall'] ) ) {
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}arc_employee_timeclock_entries" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}arc_etc_holidays" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}arc_etc_requests" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}arc_etc_clients" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}arc_etc_activities" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}arc_etc_locked_weeks" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}arc_etc_audit_log" );
	delete_option( 'arc_etc_settings' );
	delete_option( 'arc_etc_db_version' );
}
