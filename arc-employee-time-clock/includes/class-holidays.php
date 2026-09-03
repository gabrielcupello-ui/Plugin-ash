<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Holidays {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'arc_etc_holidays';
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
	}

	public static function get_all( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'year'   => null,
			'limit'  => 1000,
			'offset' => 0,
		);
		$args     = wp_parse_args( $args, $defaults );

		$sql    = 'SELECT * FROM ' . self::table() . ' WHERE 1=1';
		$params = array();

		if ( $args['year'] ) {
			$sql .= ' AND (YEAR(holiday_date) = %d OR recurring = 1)';
			$params[] = $args['year'];
		}
		$sql .= ' ORDER BY holiday_date DESC LIMIT %d OFFSET %d';
		$params[] = $args['limit'];
		$params[] = $args['offset'];

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public static function is_holiday( $date = null ) {
		global $wpdb;
		$date = $date ?: current_time( 'Y-m-d' );

		$month_day = gmdate( 'm-d', strtotime( $date ) );
		$year      = gmdate( 'Y', strtotime( $date ) );

		$found = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::table() . ' WHERE (holiday_date = %s) OR (recurring = 1 AND DATE_FORMAT(holiday_date, "%m-%d") = %s)',
				$date,
				$month_day
			)
		);

		return (int) $found > 0;
	}

	public static function add( $name, $date, $recurring = 0 ) {
		global $wpdb;
		return $wpdb->insert(
			self::table(),
			array(
				'name'         => sanitize_text_field( $name ),
				'holiday_date' => sanitize_text_field( $date ),
				'recurring'    => $recurring ? 1 : 0,
			)
		);
	}

	public static function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
	}
}
