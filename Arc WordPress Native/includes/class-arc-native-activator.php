<?php
/**
 * Activation, deactivation and schema management for ARC Native.
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin activation and database schema.
 */
class Arc_Native_Activator {

	/**
	 * Current database schema version.
     *
     * @var string
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * Activate the plugin: create tables and rewrite rules.
	 */
	public static function activate() {
		self::create_tables();
		update_option( 'arc_native_schema_version', self::SCHEMA_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin: clean rewrite rules.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Create the custom tables needed by the native core.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix . ARC_NATIVE_TABLE_PREFIX;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// EOD Reports.
		$sql_eod = "CREATE TABLE IF NOT EXISTS {$prefix}eod_reports (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			report_date date NOT NULL,
			hours_worked decimal(4,2) NOT NULL DEFAULT 0.00,
			work_description text,
			shipped_today text,
			in_progress text,
			blockers text,
			top_priorities text,
			status varchar(20) NOT NULL DEFAULT 'submitted',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_date (user_id, report_date),
			KEY status (status)
		) {$charset_collate};";

		// HR Applications.
		$sql_hr = "CREATE TABLE IF NOT EXISTS {$prefix}hr_applications (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			first_name varchar(100) NOT NULL,
			last_name varchar(100) NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(50),
			years_experience int(11),
			english_level varchar(20),
			positions_worked text,
			domain_experience text,
			accounting_software varchar(255),
			excel_level varchar(20),
			summary text,
			status varchar(20) NOT NULL DEFAULT 'new',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY email (email),
			KEY status (status)
		) {$charset_collate};";

		// Tasks.
		$sql_tasks = "CREATE TABLE IF NOT EXISTS {$prefix}tasks (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			description text,
			project_name varchar(255),
			assignee_id bigint(20) unsigned,
			status varchar(20) NOT NULL DEFAULT 'To Do',
			priority varchar(20) NOT NULL DEFAULT 'Medium',
			due_date date,
			created_by bigint(20) unsigned,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY assignee_status (assignee_id, status),
			KEY status (status)
		) {$charset_collate};";

		// Sync queue with Google Apps Script / Sheets.
		$sql_sync = "CREATE TABLE IF NOT EXISTS {$prefix}sync_queue (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			module varchar(50) NOT NULL,
			record_id bigint(20) unsigned NOT NULL,
			action varchar(20) NOT NULL,
			payload longtext,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts tinyint(4) NOT NULL DEFAULT 0,
			last_error text,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			processed_at datetime,
			PRIMARY KEY (id),
			KEY status_module (status, module),
			KEY record (module, record_id)
		) {$charset_collate};";

		dbDelta( $sql_eod );
		dbDelta( $sql_hr );
		dbDelta( $sql_tasks );
		dbDelta( $sql_sync );
	}

	/**
	 * Return the list of managed table names.
	 *
	 * @return array
	 */
	public static function get_table_names() {
		global $wpdb;
		$prefix = $wpdb->prefix . ARC_NATIVE_TABLE_PREFIX;
		return array(
			$prefix . 'eod_reports',
			$prefix . 'hr_applications',
			$prefix . 'tasks',
			$prefix . 'sync_queue',
		);
	}
}
