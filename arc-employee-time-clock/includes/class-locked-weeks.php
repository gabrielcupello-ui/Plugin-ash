<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Locked_Weeks {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'arc_etc_locked_weeks';
	}

	public static function is_locked( $week_key, $user_id = null ) {
		global $wpdb;
		if ( $user_id ) {
			$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE week_key = %s AND (user_id = %d OR user_id IS NULL)', $week_key, $user_id ) );
		} else {
			$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE week_key = %s AND user_id IS NULL', $week_key ) );
		}
		return ! empty( $locked );
	}

	public static function lock( $week_key, $user_id = null, $locked_by = 0 ) {
		global $wpdb;
		if ( self::is_locked( $week_key, $user_id ) ) {
			return true;
		}
		$wpdb->insert(
			self::table(),
			array(
				'week_key'  => $week_key,
				'user_id'   => $user_id,
				'locked_by' => $locked_by,
			),
			array( '%s', '%d', '%d' )
		);
		return $wpdb->insert_id ? true : false;
	}

	public static function unlock( $week_key, $user_id = null ) {
		global $wpdb;
		$where = array( 'week_key' => $week_key );
		if ( $user_id ) {
			$where['user_id'] = $user_id;
		}
		return $wpdb->delete( self::table(), $where );
	}

	public static function get_all() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY week_key DESC' );
	}

	public static function week_key( $date = null ) {
		$ts = $date ? strtotime( $date ) : current_time( 'timestamp' );
		$start = date_i18n( 'Y-m-d', strtotime( 'monday this week', $ts ) );
		return $start;
	}
}
