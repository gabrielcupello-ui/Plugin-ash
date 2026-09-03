<?php
/**
 * Google Apps Script bridge for the ARC Native core.
 *
 * Queues local changes and pushes them to a shared GAS endpoint.
 * Allows a gradual migration: data lives in MySQL, Google gets a copy.
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sync bridge with Google Apps Script / Sheets.
 */
class Arc_Native_Google_Bridge {

	/**
	 * Singleton instance.
	 *
	 * @var Arc_Native_Google_Bridge|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Arc_Native_Google_Bridge
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'arc_native_record_changed', array( $this, 'queue_change' ), 10, 4 );
		add_action( 'init', array( $this, 'schedule_sync' ) );
		add_action( 'arc_native_sync_cron', array( $this, 'process_queue' ) );
	}

	/**
	 * Schedule the sync cron.
	 */
	public function schedule_sync() {
		if ( ! wp_next_scheduled( 'arc_native_sync_cron' ) ) {
			wp_schedule_event( time(), 'five_minutes', 'arc_native_sync_cron' );
		}
	}

	/**
	 * Get the configured sync URL.
	 *
	 * @return string
	 */
	public function get_sync_url() {
		return get_option( 'arc_native_google_sync_url', '' );
	}

	/**
	 * Get the configured API secret.
	 *
	 * @return string
	 */
	public function get_secret() {
		$secret = get_option( 'arc_native_google_api_secret', '' );
		return $secret ? $secret : wp_salt( 'auth' );
	}

	/**
	 * Check if sync is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) get_option( 'arc_native_sync_enabled', false ) && ! empty( $this->get_sync_url() );
	}

	/**
	 * Queue a record change.
	 *
	 * @param string $module  Module slug.
	 * @param int    $record_id Local record ID.
	 * @param string $action  Action: create, update, delete.
	 * @param array  $data    Payload.
	 * @return bool|int
	 */
	public function queue_change( $module, $record_id, $action, $data = array() ) {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		if ( ! Arc_Native_Modules::instance()->is_active( $module ) ) {
			return false;
		}

		global $wpdb;
		$prefix = $wpdb->prefix . ARC_NATIVE_TABLE_PREFIX;

		return $wpdb->insert(
			"{$prefix}sync_queue",
			array(
				'module'    => sanitize_key( $module ),
				'record_id' => (int) $record_id,
				'action'    => sanitize_key( $action ),
				'payload'   => wp_json_encode( $data ),
				'status'    => 'pending',
			),
			array( '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Process the pending sync queue.
	 */
	public function process_queue() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		global $wpdb;
		$prefix = $wpdb->prefix . ARC_NATIVE_TABLE_PREFIX;

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$prefix}sync_queue WHERE status = %s AND attempts < %d ORDER BY id ASC LIMIT %d",
				'pending',
				5,
				50
			),
			ARRAY_A
		);

		if ( empty( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			$this->send_item( $item );
		}
	}

	/**
	 * Send a single sync item to Google Apps Script.
	 *
	 * @param array $item Queue item.
	 */
	private function send_item( $item ) {
		$sync_url = $this->get_sync_url();
		if ( empty( $sync_url ) ) {
			return;
		}

		$body = array(
			'timestamp' => time(),
			'module'    => $item['module'],
			'record_id' => (int) $item['record_id'],
			'action'    => $item['action'],
			'payload'   => json_decode( $item['payload'], true ),
			'signature' => $this->sign_payload( $item ),
		);

		$response = wp_remote_post(
			$sync_url,
			array(
				'body'    => wp_json_encode( $body ),
				'headers' => array( 'Content-Type' => 'application/json' ),
				'timeout' => 30,
			)
		);

		global $wpdb;
		$prefix = $wpdb->prefix . ARC_NATIVE_TABLE_PREFIX;

		if ( is_wp_error( $response ) ) {
			$this->mark_failed( $item['id'], $response->get_error_message(), $prefix );
			return;
		}

		$code    = wp_remote_retrieve_response_code( $response );
		$resp_body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			$this->mark_failed( $item['id'], 'HTTP ' . $code . ': ' . $resp_body, $prefix );
			return;
		}

		$wpdb->update(
			"{$prefix}sync_queue",
			array(
				'status'       => 'completed',
				'processed_at' => current_time( 'mysql' ),
			),
			array( 'id' => $item['id'] ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Mark an item as failed.
	 *
	 * @param int    $id    Item ID.
	 * @param string $error Error message.
	 * @param string $prefix Table prefix.
	 */
	private function mark_failed( $id, $error, $prefix ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$prefix}sync_queue SET attempts = attempts + 1, last_error = %s WHERE id = %d",
				substr( sanitize_text_field( $error ), 0, 500 ),
				$id
			)
		);
	}

	/**
	 * Sign a queue item with HMAC.
	 *
	 * @param array $item Queue item.
	 * @return string
	 */
	private function sign_payload( $item ) {
		$payload = $item['module'] . '|' . $item['record_id'] . '|' . $item['action'] . '|' . $item['payload'];
		return hash_hmac( 'sha256', $payload, $this->get_secret() );
	}

	/**
	 * Verify a signature from a Google callback.
	 *
	 * @param string $module    Module slug.
	 * @param int    $record_id Record ID.
	 * @param string $action    Action.
	 * @param string $payload   JSON payload.
	 * @param string $signature Signature to verify.
	 * @return bool
	 */
	public function verify_signature( $module, $record_id, $action, $payload, $signature ) {
		$expected = hash_hmac( 'sha256', $module . '|' . $record_id . '|' . $action . '|' . $payload, $this->get_secret() );
		return hash_equals( $expected, $signature );
	}
}

/**
 * Add a custom cron interval.
 *
 * @param array $schedules Cron schedules.
 * @return array
 */
function arc_native_cron_intervals( $schedules ) {
	$schedules['five_minutes'] = array(
		'interval' => 300,
		'display'  => __( 'Cada 5 minutos', 'arc-native' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'arc_native_cron_intervals' );
