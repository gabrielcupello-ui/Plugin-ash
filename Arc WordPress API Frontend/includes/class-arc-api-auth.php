<?php
/**
 * Authentication helpers for the ARC API Frontend.
 *
 * Issues short-lived signed tokens that Apps Script can verify
 * to trust requests coming from WordPress.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_API_Frontend_Auth {

	private static $instance = null;
	private $secret_option = 'arc_api_frontend_app_shared_secret';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		if ( false === get_option( $this->secret_option ) ) {
			update_option( $this->secret_option, wp_generate_password( 64, false ) );
		}
	}

	public function register_routes() {
		register_rest_route(
			'arc-api-frontend/v1',
			'/auth/token',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'issue_token' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'arc-api-frontend/v1',
			'/auth/verify',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'verify_token' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function issue_token() {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return new WP_Error( 'not_logged_in', __( 'No autenticado', 'arc-api-frontend' ), array( 'status' => 401 ) );
		}

		$expires = time() + 300;
		$payload = $user->user_email . '|' . $expires . '|' . $user->ID;
		$hash    = hash_hmac( 'sha256', $payload, $this->get_secret() );

		return rest_ensure_response( array(
			'token'   => $payload . '|' . $hash,
			'email'   => $user->user_email,
			'expires' => $expires,
		) );
	}

	public function verify_token( $request ) {
		$params = $request->get_json_params();
		$token  = sanitize_text_field( $params['token'] ?? '' );
		$parts  = explode( '|', $token );

		if ( count( $parts ) !== 4 ) {
			return new WP_Error( 'invalid_token', __( 'Token inválido', 'arc-api-frontend' ), array( 'status' => 400 ) );
		}

		list( $email, $expires, $user_id, $hash ) = $parts;

		if ( ! is_email( $email ) || ! is_numeric( $user_id ) || (int) $expires < time() ) {
			return new WP_Error( 'invalid_token', __( 'Token expirado o inválido', 'arc-api-frontend' ), array( 'status' => 400 ) );
		}

		$payload = $email . '|' . $expires . '|' . $user_id;
		$expected = hash_hmac( 'sha256', $payload, $this->get_secret() );

		if ( ! hash_equals( $expected, $hash ) ) {
			return new WP_Error( 'invalid_signature', __( 'Firma inválida', 'arc-api-frontend' ), array( 'status' => 403 ) );
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user || $user->ID !== (int) $user_id ) {
			return new WP_Error( 'user_mismatch', __( 'Usuario no coincide', 'arc-api-frontend' ), array( 'status' => 403 ) );
		}

		return rest_ensure_response( array(
			'success' => true,
			'email'   => $email,
			'user_id' => (int) $user_id,
		) );
	}

	private function get_secret() {
		return get_option( $this->secret_option, wp_salt( 'auth' ) );
	}
}
