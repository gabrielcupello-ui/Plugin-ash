<?php
/**
 * REST proxy between WordPress frontend and Google Apps Script endpoints.
 *
 * Todos los shortcodes del frontend llaman a /wp-json/arc-api-frontend/v1/{app}/{action}
 * y este proxy reenvía la petición a la URL de Apps Script configurada,
 * añadiendo la API Key y el email del usuario actual.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_API_Frontend_Proxy {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		$settings  = Arc_API_Frontend_App::instance()->get_settings();
		$endpoints = Arc_API_Frontend_Endpoint_Registry::instance()->get_endpoints( $settings['apps'] );

		foreach ( $endpoints as $app => $config ) {
			if ( empty( $config['enabled'] ) ) {
				continue;
			}

			register_rest_route(
				'arc-api-frontend/v1',
				'/' . $app . '/(?P<action>[a-z0-9_-]+)',
				array(
					'methods'             => array( 'GET', 'POST' ),
					'callback'            => array( $this, 'proxy_request' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'action' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
					),
				)
			);
		}
	}

	public function permission_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'not_logged_in', __( 'Debes iniciar sesión.', 'arc-api-frontend' ), array( 'status' => 401 ) );
		}

		$settings = Arc_API_Frontend_App::instance()->get_settings();
		$user     = wp_get_current_user();

		if ( user_can( $user, 'manage_options' ) ) {
			return true;
		}

		$allowed = array_map( 'sanitize_key', (array) $settings['allowed_roles'] );
		if ( ! (bool) array_intersect( (array) $user->roles, $allowed ) ) {
			return new WP_Error( 'forbidden', __( 'No tienes permisos.', 'arc-api-frontend' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public function proxy_request( $request ) {
		$route  = $request->get_route();
		$parts  = explode( '/', trim( $route, '/' ) );
		$app    = sanitize_key( $parts[2] ?? '' );
		$action = sanitize_key( $request['action'] );
		$method = $request->get_method();

		if ( empty( $app ) || empty( $action ) ) {
			return new WP_Error( 'invalid_request', __( 'Petición inválida.', 'arc-api-frontend' ), array( 'status' => 400 ) );
		}

		$settings  = Arc_API_Frontend_App::instance()->get_settings();
		$registry  = Arc_API_Frontend_Endpoint_Registry::instance();
		$app_config = $registry->get_endpoint( $app, $settings['apps'] );

		if ( ! $app_config ) {
			return new WP_Error( 'not_registered', __( 'App no registrada.', 'arc-api-frontend' ), array( 'status' => 404 ) );
		}

		if ( empty( $app_config['endpoint'] ) ) {
			return new WP_Error( 'not_configured', __( 'Endpoint no configurado.', 'arc-api-frontend' ), array( 'status' => 500 ) );
		}

		if ( ! $registry->is_action_allowed( $app, $action, $settings['apps'] ) ) {
			return new WP_Error( 'action_not_allowed', __( 'Acción no permitida para esta app.', 'arc-api-frontend' ), array( 'status' => 403 ) );
		}

		$endpoint = $app_config['endpoint'];
		$api_key  = $app_config['api_key'];

		$user = wp_get_current_user();
		$body = $request->get_json_params() ?: $request->get_body_params();
		$body = is_array( $body ) ? $body : array();
		$body = array_merge(
			$body,
			array(
				'action'     => $action,
				'wp_email'   => $user->user_email,
				'wp_name'    => $user->display_name,
				'wp_user_id' => $user->ID,
				'api_key'    => $api_key,
			)
		);

		/**
		 * Cache GET responses to reduce Apps Script load.
		 *
		 * Default TTL: 60 seconds. Filterable via `arc_api_frontend_cache_ttl`.
		 */
		$cache_key = 'arc_api_frontend_app_' . $app . '_' . md5( $action . serialize( $body ) );
		$cache_ttl = apply_filters( 'arc_api_frontend_cache_ttl', 60, $app, $action, $body );

		if ( 'GET' === $method && $cache_ttl > 0 ) {
			$cached = wp_cache_get( $cache_key, 'arc-api-frontend' );
			if ( false !== $cached ) {
				return rest_ensure_response( $cached );
			}

			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				wp_cache_set( $cache_key, $cached, 'arc-api-frontend', (int) $cache_ttl );
				return rest_ensure_response( $cached );
			}
		}

		// Apps Script Web Apps handle everything via doPost, so always POST upstream.
		$args = array(
			'body'    => wp_json_encode( $body ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 30,
			'method'  => 'POST',
		);

		$max_retries = max( 0, (int) apply_filters( 'arc_api_frontend_max_retries', 2, $app, $action, $body ) );
		$attempt     = 0;
		$response    = null;

		while ( $attempt <= $max_retries ) {
			$response = wp_remote_request( $endpoint, $args );

			if ( ! is_wp_error( $response ) ) {
				break;
			}

			$attempt++;
			if ( $attempt <= $max_retries ) {
				$this->log_error( $app, $action, 'Retry ' . $attempt . ': ' . $response->get_error_message() );
				usleep( 500000 ); // 500ms backoff.
			}
		}

		if ( is_wp_error( $response ) ) {
			$this->log_error( $app, $action, $response->get_error_message() );
			return new WP_Error( 'proxy_error', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$code    = wp_remote_retrieve_response_code( $response );
		$body    = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$this->log_error( $app, $action, 'HTTP ' . $code . ': ' . $body );
			return new WP_Error( 'upstream_error', __( 'La app de Apps Script respondió con error.', 'arc-api-frontend' ), array( 'status' => $code, 'body' => $body ) );
		}

		$result = is_array( $decoded ) ? $decoded : array( 'raw' => $body );

		if ( 'GET' === $method && $cache_ttl > 0 ) {
			set_transient( $cache_key, $result, (int) $cache_ttl );
			wp_cache_set( $cache_key, $result, 'arc-api-frontend', (int) $cache_ttl );
		}

		// Invalidate cache for this app on successful writes.
		if ( 'POST' === $method && ! empty( $result['success'] ) ) {
			$this->invalidate_cache( $app );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Invalidate all cached keys for an app.
	 *
	 * @param string $app App slug.
	 */
	private function invalidate_cache( $app ) {
		global $wpdb;
		$prefix = '_transient_arc_api_frontend_app_' . sanitize_key( $app ) . '_';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);
		$timeout_prefix = '_transient_timeout_arc_api_frontend_app_' . sanitize_key( $app ) . '_';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $timeout_prefix ) . '%'
			)
		);
	}

	/**
	 * Log proxy errors when WP_DEBUG is enabled.
	 *
	 * @param string $app App slug.
	 * @param string $action Action name.
	 * @param string $message Error message.
	 */
	private function log_error( $app, $action, $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ARC API Frontend [' . $app . '/' . $action . ']: ' . $message );
		}
	}
}
