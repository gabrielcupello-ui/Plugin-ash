<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Leave {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'arc_etc_requests';
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
	}

	public static function get_by_user( $user_id, $status = null ) {
		global $wpdb;
		$sql    = 'SELECT * FROM ' . self::table() . ' WHERE user_id = %d';
		$params = array( $user_id );
		if ( $status ) {
			$sql .= ' AND status = %s';
			$params[] = $status;
		}
		$sql .= ' ORDER BY start_date DESC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public static function get_all( $status = null ) {
		global $wpdb;
		$sql    = 'SELECT * FROM ' . self::table() . ' WHERE 1=1';
		$params = array();
		if ( $status ) {
			$sql .= ' AND status = %s';
			$params[] = $status;
		}
		$sql .= ' ORDER BY created_at DESC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public static function pending_count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE status = 'pending'" );
	}

	public static function create( $user_id, $data ) {
		global $wpdb;
		$defaults = array(
			'user_id'      => $user_id,
			'request_type' => 'pto',
			'start_date'   => current_time( 'Y-m-d' ),
			'end_date'     => current_time( 'Y-m-d' ),
			'hours'        => 0,
			'notes'        => '',
			'status'       => 'pending',
			'created_at'   => current_time( 'mysql' ),
		);
		$data = wp_parse_args( $data, $defaults );

		$insert = array(
			'user_id'      => $user_id,
			'request_type' => sanitize_text_field( $data['request_type'] ),
			'start_date'   => sanitize_text_field( $data['start_date'] ),
			'end_date'     => sanitize_text_field( $data['end_date'] ),
			'hours'        => floatval( $data['hours'] ),
			'notes'        => sanitize_textarea_field( $data['notes'] ),
			'status'       => 'pending',
			'created_at'   => current_time( 'mysql' ),
		);

		$wpdb->insert( self::table(), $insert );
		return $wpdb->insert_id;
	}

	public static function update_status( $id, $status, $approver_id = 0 ) {
		global $wpdb;
		$update = array(
			'status' => sanitize_text_field( $status ),
		);
		if ( in_array( $status, array( 'approved', 'rejected' ), true ) ) {
			$update['approved_by'] = $approver_id;
			$update['approved_at'] = current_time( 'mysql' );
		}
		return $wpdb->update( self::table(), $update, array( 'id' => $id ), array( '%s', '%d', '%s' ), array( '%d' ) );
	}

	public static function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
	}
}
