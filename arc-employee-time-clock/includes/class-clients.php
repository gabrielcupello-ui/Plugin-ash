<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Clients {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'arc_etc_clients';
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
	}

	public static function get_all( $active_only = false ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . self::table();
		if ( $active_only ) {
			$sql .= ' WHERE active = 1';
		}
		$sql .= ' ORDER BY name ASC';
		return $wpdb->get_results( $sql );
	}

	public static function save( $data, $id = 0 ) {
		global $wpdb;
		$insert = array(
			'name'             => sanitize_text_field( $data['name'] ),
			'entity'           => ! empty( $data['entity'] ) ? sanitize_text_field( $data['entity'] ) : '',
			'billable_default' => ! empty( $data['billable_default'] ) ? 1 : 0,
			'active'           => isset( $data['active'] ) ? (int) $data['active'] : 1,
		);
		if ( $id ) {
			$wpdb->update( self::table(), $insert, array( 'id' => $id ), array( '%s', '%s', '%d', '%d' ), array( '%d' ) );
			return $id;
		}
		$wpdb->insert( self::table(), $insert, array( '%s', '%s', '%d', '%d' ) );
		return $wpdb->insert_id;
	}

	public static function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
	}
}
