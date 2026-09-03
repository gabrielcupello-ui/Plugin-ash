<?php
/**
 * Endpoint Registry for the ARC API Frontend.
 *
 * Makes the API proxy modular and scalable by allowing apps/handlers to be
 * registered dynamically via code or configuration instead of hard-coding
 * slugs in the proxy class.
 *
 * @package Arc_API_Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central registry for API apps/endpoints.
 */
class Arc_API_Frontend_Endpoint_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var Arc_API_Frontend_Endpoint_Registry|null
	 */
	private static $instance = null;

	/**
	 * Runtime registered endpoints.
     *
     * @var array
	 */
	private $endpoints = array();

	/**
	 * Default endpoint definitions.
     *
     * @var array
	 */
	private $defaults = array();

	/**
	 * Get the singleton instance.
	 *
	 * @return Arc_API_Frontend_Endpoint_Registry
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
		$this->defaults = array(
			'time_clock' => array(
				'label'    => __( 'Control Horario', 'arc-api-frontend' ),
				'endpoint' => '',
				'api_key'  => '',
				'enabled'  => true,
				'actions'  => array( 'clock_in', 'clock_out', 'get_stats' ),
				'handler'  => '', // FQCN or callable.
				'order'    => 10,
			),
			'eod_report' => array(
				'label'    => __( 'EOD Report', 'arc-api-frontend' ),
				'endpoint' => '',
				'api_key'  => '',
				'enabled'  => true,
				'actions'  => array( 'submit', 'get_stats' ),
				'handler'  => '',
				'order'    => 20,
			),
			'hr'         => array(
				'label'    => __( 'Recursos Humanos', 'arc-api-frontend' ),
				'endpoint' => '',
				'api_key'  => '',
				'enabled'  => true,
				'actions'  => array( 'submit_application', 'get_stats' ),
				'handler'  => '',
				'order'    => 30,
			),
			'task_app'   => array(
				'label'    => __( 'Task App', 'arc-api-frontend' ),
				'endpoint' => '',
				'api_key'  => '',
				'enabled'  => true,
				'actions'  => array( 'get_tasks', 'update_task', 'get_stats' ),
				'handler'  => '',
				'order'    => 40,
			),
		);

		/**
		 * Fires after the endpoint registry is initialized.
		 *
		 * @param Arc_API_Frontend_Endpoint_Registry $registry Registry instance.
		 */
		do_action( 'arc_api_frontend_registry_init', $this );
	}

	/**
	 * Register a new app endpoint at runtime.
	 *
	 * @param string $slug App slug.
	 * @param array  $config Endpoint configuration.
	 * @return bool
	 */
	public function register_endpoint( $slug, $config ) {
		$slug = sanitize_key( $slug );
		if ( empty( $slug ) ) {
			return false;
		}

		$defaults = array(
			'label'    => $slug,
			'endpoint' => '',
			'api_key'  => '',
			'enabled'  => true,
			'actions'  => array(),
			'handler'  => '',
			'order'    => 50,
		);

		$validated = wp_parse_args( $config, $defaults );

		if ( ! is_array( $validated['actions'] ) ) {
			$validated['actions'] = array();
		}

		$this->endpoints[ $slug ] = $validated;
		return true;
	}

	/**
	 * Register multiple endpoints.
	 *
	 * @param array $endpoints Array of slug => config.
	 */
	public function register_endpoints( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			return;
		}
		foreach ( $endpoints as $slug => $config ) {
			$this->register_endpoint( $slug, $config );
		}
	}

	/**
	 * Unregister an endpoint.
	 *
	 * @param string $slug App slug.
	 */
	public function unregister_endpoint( $slug ) {
		unset( $this->endpoints[ sanitize_key( $slug ) ] );
	}

	/**
	 * Check if an endpoint is registered.
	 *
	 * @param string $slug App slug.
	 * @return bool
	 */
	public function is_registered( $slug ) {
		$slug = sanitize_key( $slug );
		return isset( $this->endpoints[ $slug ] ) || isset( $this->defaults[ $slug ] );
	}

	/**
	 * Get all merged endpoint definitions.
	 *
	 * Merge order: defaults < runtime registered < stored settings < filters.
	 *
	 * @param array $stored_settings Stored settings for endpoints.
	 * @return array
	 */
	public function get_endpoints( $stored_settings = array() ) {
		$endpoints = array_merge( $this->defaults, $this->endpoints );

		foreach ( $stored_settings as $slug => $stored ) {
			$slug = sanitize_key( $slug );
			if ( isset( $endpoints[ $slug ] ) ) {
				$endpoints[ $slug ] = $this->merge_config( $endpoints[ $slug ], $stored );
			} else {
				$endpoints[ $slug ] = $this->sanitize_config( $stored, $slug );
			}
		}

		$endpoints = array_filter(
			$endpoints,
			function ( $ep ) {
				return ! empty( $ep['enabled'] );
			}
		);

		uasort(
			$endpoints,
			function ( $a, $b ) {
				$a_order = isset( $a['order'] ) ? (int) $a['order'] : 50;
				$b_order = isset( $b['order'] ) ? (int) $b['order'] : 50;
				return $a_order - $b_order;
			}
		);

		/**
		 * Filter the merged endpoint list.
		 *
		 * @param array $endpoints Endpoint definitions.
		 */
		return apply_filters( 'arc_api_frontend_endpoints', $endpoints );
	}

	/**
	 * Get a single endpoint config.
	 *
	 * @param string $slug Endpoint slug.
	 * @param array  $stored_settings Stored settings.
	 * @return array|null
	 */
	public function get_endpoint( $slug, $stored_settings = array() ) {
		$endpoints = $this->get_endpoints( $stored_settings );
		return isset( $endpoints[ sanitize_key( $slug ) ] ) ? $endpoints[ sanitize_key( $slug ) ] : null;
	}

	/**
	 * Return the list of slugs that have a configured endpoint URL.
	 *
	 * @param array $stored_settings Stored settings.
	 * @return array
	 */
	public function get_active_slugs( $stored_settings = array() ) {
		$slugs = array();
		foreach ( $this->get_endpoints( $stored_settings ) as $slug => $ep ) {
			if ( ! empty( $ep['endpoint'] ) ) {
				$slugs[] = $slug;
			}
		}
		return $slugs;
	}

	/**
	 * Check whether an action is allowed for an endpoint.
	 *
	 * @param string $slug Endpoint slug.
	 * @param string $action Action name.
	 * @param array  $stored_settings Stored settings.
	 * @return bool
	 */
	public function is_action_allowed( $slug, $action, $stored_settings = array() ) {
		$ep = $this->get_endpoint( $slug, $stored_settings );
		if ( ! $ep || empty( $ep['actions'] ) ) {
			return false;
		}
		return in_array( sanitize_key( $action ), $ep['actions'], true );
	}

	/**
	 * Merge stored values into a default config.
	 *
	 * @param array $default Default config.
	 * @param array $stored  Stored config.
	 * @return array
	 */
	private function merge_config( $default, $stored ) {
		$merged = $default;

		if ( isset( $stored['label'] ) ) {
			$merged['label'] = sanitize_text_field( $stored['label'] );
		}
		if ( isset( $stored['endpoint'] ) ) {
			$merged['endpoint'] = esc_url_raw( $stored['endpoint'] );
		}
		if ( isset( $stored['api_key'] ) ) {
			$merged['api_key'] = sanitize_text_field( $stored['api_key'] );
		}
		if ( isset( $stored['enabled'] ) ) {
			$merged['enabled'] = ! empty( $stored['enabled'] );
		}
		if ( ! empty( $stored['actions'] ) && is_array( $stored['actions'] ) ) {
			$merged['actions'] = array_map( 'sanitize_key', $stored['actions'] );
		}
		if ( ! empty( $stored['handler'] ) ) {
			$merged['handler'] = sanitize_text_field( $stored['handler'] );
		}
		if ( isset( $stored['order'] ) ) {
			$merged['order'] = (int) $stored['order'];
		}

		return $merged;
	}

	/**
	 * Sanitize a fully dynamic config.
	 *
	 * @param array  $config Raw config.
	 * @param string $slug   Endpoint slug.
	 * @return array
	 */
	private function sanitize_config( $config, $slug ) {
		$defaults = array(
			'label'    => $slug,
			'endpoint' => '',
			'api_key'  => '',
			'enabled'  => true,
			'actions'  => array(),
			'handler'  => '',
			'order'    => 50,
		);

		$config = wp_parse_args( $config, $defaults );

		if ( ! is_array( $config['actions'] ) ) {
			$config['actions'] = array();
		}

		return array(
			'label'    => sanitize_text_field( $config['label'] ),
			'endpoint' => esc_url_raw( $config['endpoint'] ),
			'api_key'  => sanitize_text_field( $config['api_key'] ),
			'enabled'  => ! empty( $config['enabled'] ),
			'actions'  => array_map( 'sanitize_key', $config['actions'] ),
			'handler'  => sanitize_text_field( $config['handler'] ),
			'order'    => (int) $config['order'],
		);
	}
}
