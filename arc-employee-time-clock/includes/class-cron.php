<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Cron {

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_weekly_schedule' ) );
		add_action( 'arc_etc_auto_close', array( __CLASS__, 'auto_close_open_shifts' ) );
		add_action( 'arc_etc_flag_sweep', array( __CLASS__, 'nightly_flag_sweep' ) );
		add_action( 'arc_etc_pending_digest', array( __CLASS__, 'daily_pending_digest' ) );
		add_action( 'arc_etc_lock_reminder', array( __CLASS__, 'weekly_lock_reminder' ) );
		add_action( 'arc_etc_exception_report', array( __CLASS__, 'weekly_exception_report' ) );
	}

	public static function add_weekly_schedule( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 7 * DAY_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'arc-employee-time-clock' ),
			);
		}
		return $schedules;
	}

	public static function auto_close_open_shifts() {
		global $wpdb;
		$settings  = get_option( 'arc_etc_settings', array() );
		$max_hours = floatval( $settings['max_shift_hours'] ?? 14 );
		$max_min   = $max_hours * 60;

		$entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . ARC_ETC_TABLE . " WHERE status = 'open' AND TIMESTAMPDIFF(MINUTE, clock_in, NOW()) > %d",
				$max_min
			)
		);

		foreach ( $entries as $entry ) {
			Arc_ETC_Time_Entries::clock_out( $entry->id, array( 'notes' => __( 'Auto-closed after max shift hours.', 'arc-employee-time-clock' ) ) );
		}
	}

	public static function nightly_flag_sweep() {
		global $wpdb;
		$table   = $wpdb->prefix . ARC_ETC_TABLE;
		$entries = $wpdb->get_results( "SELECT * FROM {$table} WHERE clock_out IS NOT NULL AND status IN ('pending','submitted','approved')" );

		$settings = get_option( 'arc_etc_settings', array() );
		foreach ( $entries as $entry ) {
			$total  = (int) $entry->total_minutes;
			$hours  = $total / 60;
			$flags  = array_filter( array_map( 'trim', explode( ',', $entry->flags ?? '' ) ) );
			$limit  = (float) ( $settings['flag_daily_hours_over'] ?? 10 );

			if ( $hours > $limit && ! self::has_flag( $flags, 'LONG SHIFT' ) ) {
				$flags[] = 'LONG SHIFT ' . round( $hours, 2 ) . 'h';
			}
			if ( $total > 0 && self::has_flag( $flags, 'NO BREAK' ) === false ) {
				// Lunch fields are not stored historically; only re-evaluate current break minutes.
				if ( $hours >= 6 && (int) $entry->break_minutes === 0 ) {
					$flags[] = 'NO BREAK';
				}
			}

			$updated = implode( ', ', $flags );
			if ( $updated !== $entry->flags ) {
				$wpdb->update( $table, array( 'flags' => $updated ), array( 'id' => $entry->id ), array( '%s' ), array( '%d' ) );
			}
		}
	}

	private static function has_flag( $flags, $needle ) {
		foreach ( $flags as $flag ) {
			if ( 0 === strpos( $flag, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	public static function daily_pending_digest() {
		$settings   = get_option( 'arc_etc_settings', array() );
		$recipients = $settings['report_recipients'] ?? '';
		if ( ! $recipients ) {
			$recipients = get_option( 'admin_email' );
		}
		global $wpdb;
		$table = $wpdb->prefix . ARC_ETC_TABLE;
		$open  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'open'" );
		$pend  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status IN ('pending','submitted')" );
		$flag  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE flags <> ''" );

		$subject = __( 'Daily Time Clock Digest', 'arc-employee-time-clock' );
		$body    = sprintf( "Open: %d\nPending/Submitted: %d\nFlagged: %d", $open, $pend, $flag );
		wp_mail( $recipients, $subject, $body );
	}

	public static function weekly_lock_reminder() {
		if ( 'Sun' !== date_i18n( 'D' ) ) {
			return;
		}
		$settings   = get_option( 'arc_etc_settings', array() );
		$recipients = $settings['report_recipients'] ?? '';
		if ( ! $recipients ) {
			$recipients = get_option( 'admin_email' );
		}
		$week = Arc_ETC_Time_Entries::week_key( strtotime( '-7 days' ) );
		wp_mail( $recipients, __( 'Reminder: lock last week', 'arc-employee-time-clock' ), sprintf( __( 'Please review and lock week %s.', 'arc-employee-time-clock' ), $week ) );
	}

	public static function weekly_exception_report() {
		if ( 'Fri' !== date_i18n( 'D' ) ) {
			return;
		}
		$settings   = get_option( 'arc_etc_settings', array() );
		$recipients = $settings['report_recipients'] ?? '';
		if ( ! $recipients ) {
			$recipients = get_option( 'admin_email' );
		}
		global $wpdb;
		$table = $wpdb->prefix . ARC_ETC_TABLE;
		$rows  = $wpdb->get_results( "SELECT user_id, SUM(total_minutes) AS total_min FROM {$table} WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY user_id" );

		$lines = array( __( 'Weekly Exception Report', 'arc-employee-time-clock' ) );
		foreach ( $rows as $r ) {
			if ( $r->total_min < 240 ) {
				$user  = get_userdata( $r->user_id );
				$lines[] = ( $user ? $user->display_name : '#' . $r->user_id ) . ': ' . round( $r->total_min / 60, 2 ) . 'h';
			}
		}
		wp_mail( $recipients, __( 'Weekly Exception Report', 'arc-employee-time-clock' ), implode( "\n", $lines ) );
	}
}
