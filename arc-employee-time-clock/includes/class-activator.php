<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Activator {

	public static function activate() {
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();
		$entries_table   = $wpdb->prefix . ARC_ETC_TABLE;
		$holidays_table  = $wpdb->prefix . 'arc_etc_holidays';
		$requests_table  = $wpdb->prefix . 'arc_etc_requests';
		$clients_table   = $wpdb->prefix . 'arc_etc_clients';
		$activities_table= $wpdb->prefix . 'arc_etc_activities';
		$locked_table    = $wpdb->prefix . 'arc_etc_locked_weeks';
		$audit_table     = $wpdb->prefix . 'arc_etc_audit_log';

		$entries_sql = "CREATE TABLE {$entries_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			entry_date DATE NOT NULL,
			clock_in DATETIME NULL,
			clock_out DATETIME NULL,
			break_start DATETIME NULL,
			lunch_start TIME NULL,
			lunch_end TIME NULL,
			break_minutes INT(10) UNSIGNED NOT NULL DEFAULT 0,
			total_minutes INT(10) UNSIGNED NOT NULL DEFAULT 0,
			overtime_minutes INT(10) UNSIGNED NOT NULL DEFAULT 0,
			entry_type VARCHAR(20) NOT NULL DEFAULT 'regular',
			client VARCHAR(120),
			activity VARCHAR(120),
			project VARCHAR(80),
			task VARCHAR(80),
			tags VARCHAR(255),
			billable TINYINT(1) NOT NULL DEFAULT 1,
			notes TEXT,
			ip_address VARCHAR(100),
			latitude VARCHAR(20),
			longitude VARCHAR(20),
			source VARCHAR(20) NOT NULL DEFAULT 'timer',
			flags VARCHAR(255),
			status VARCHAR(20) NOT NULL DEFAULT 'draft',
			week_key VARCHAR(10),
			month_key VARCHAR(7),
			submitted_at DATETIME NULL,
			approved_by BIGINT(20) UNSIGNED NULL,
			approved_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_date (user_id, entry_date),
			KEY idx_status (status),
			KEY idx_week_key (week_key),
			KEY idx_month_key (month_key)
		) {$charset_collate};";

		$clients_sql = "CREATE TABLE {$clients_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(120) NOT NULL,
			entity VARCHAR(120),
			billable_default TINYINT(1) NOT NULL DEFAULT 1,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_name (name)
		) {$charset_collate};";

		$activities_sql = "CREATE TABLE {$activities_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(120) NOT NULL,
			billable_default TINYINT(1) NOT NULL DEFAULT 1,
			paid TINYINT(1) NOT NULL DEFAULT 1,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_name (name)
		) {$charset_collate};";

		$locked_sql = "CREATE TABLE {$locked_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			week_key VARCHAR(10) NOT NULL,
			user_id BIGINT(20) UNSIGNED NULL,
			locked_by BIGINT(20) UNSIGNED NULL,
			locked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_week_user (week_key, user_id)
		) {$charset_collate};";

		$audit_sql = "CREATE TABLE {$audit_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			entry_id BIGINT(20) UNSIGNED NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			field VARCHAR(50) NOT NULL,
			old_value TEXT,
			new_value TEXT,
			changed_by BIGINT(20) UNSIGNED NULL,
			changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY entry_id (entry_id)
		) {$charset_collate};";

		$holidays_sql = "CREATE TABLE {$holidays_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			holiday_date DATE NOT NULL,
			recurring TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_holiday_date (holiday_date)
		) {$charset_collate};";

		$requests_sql = "CREATE TABLE {$requests_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			request_type VARCHAR(20) NOT NULL DEFAULT 'pto',
			start_date DATE NOT NULL,
			end_date DATE NOT NULL,
			hours DECIMAL(6,2) NOT NULL DEFAULT 0,
			notes TEXT,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			approved_by BIGINT(20) UNSIGNED NULL,
			approved_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_status (user_id, status),
			KEY start_date (start_date)
		) {$charset_collate};";

		dbDelta( $entries_sql );
		dbDelta( $clients_sql );
		dbDelta( $activities_sql );
		dbDelta( $locked_sql );
		dbDelta( $audit_sql );
		dbDelta( $holidays_sql );
		dbDelta( $requests_sql );

		self::schedule_crons();

		update_option( 'arc_etc_db_version', ARC_ETC_VERSION );

		if ( false === get_option( 'arc_etc_settings' ) ) {
			update_option(
				'arc_etc_settings',
				array(
					'overtime_daily_threshold'  => 8,
					'overtime_weekly_threshold' => 40,
					'overtime_multiplier'       => 1.5,
					'allowed_roles'             => array( 'administrator', 'editor', 'author', 'subscriber' ),
					'pto_enabled'               => true,
					'pto_default_hours'         => 80,
					'time_format'               => 'g:i A',
					'delete_data_on_uninstall'  => false,
					'max_shift_hours'           => 14,
					'flag_daily_hours_over'     => 10,
					'weekly_variance_pct'       => 20,
					'round_minutes'             => 15,
					'week_starts_monday'        => true,
					'required_notes_length'     => 5,
					'report_recipients'         => '',
				)
			);
		}
	}

	private static function schedule_crons() {
		$events = array(
			'arc_etc_auto_close'     => 'hourly',
			'arc_etc_flag_sweep'     => 'daily',
			'arc_etc_pending_digest' => 'daily',
			'arc_etc_lock_reminder'  => 'daily',
			'arc_etc_exception_report' => 'daily',
		);
		foreach ( $events as $hook => $recurrence ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_event( time(), $recurrence, $hook );
			}
		}
	}
}
