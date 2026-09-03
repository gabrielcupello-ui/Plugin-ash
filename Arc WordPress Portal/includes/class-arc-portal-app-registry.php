<?php
/**
 * App Registry for the ARC Portal.
 *
 * Makes the portal modular and scalable by allowing apps to be registered
 * dynamically via code, configuration files, or third-party plugins.
 *
 * @package Arc_Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central registry for portal apps.
 *
 * Supports:
 * - Default apps (time_clock, eod_report, hr, task_app).
 * - Runtime registration via register_app().
 * - Bulk registration from JSON/PHP arrays.
 * - Filtering through `arc_portal_registered_apps`.
 * - Capability-based visibility per app.
 */
class Arc_Portal_App_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var Arc_Portal_App_Registry|null
	 */
	private static $instance = null;

	/**
	 * Runtime registered apps.
     *
     * @var array
	 */
	private $registered = array();

	/**
	 * Default app definitions shipped with the plugin.
     *
     * @var array
	 */
	private $defaults = array();

	/**
	 * Get the singleton instance.
	 *
	 * @return Arc_Portal_App_Registry
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
				'label'       => __( 'Time Clock', 'arc-portal' ),
				'url'         => '',
				'icon'        => 'clock',
				'enabled'     => true,
				'description' => __( 'Clock in, out, and breaks.', 'arc-portal' ),
				'target'      => 'iframe',
				'capability'  => 'read',
				'order'       => 10,
			),
			'eod_report' => array(
				'label'       => __( 'EOD Report', 'arc-portal' ),
				'url'         => '',
				'icon'        => 'file-text',
				'enabled'     => true,
				'description' => __( 'Submit your daily work summary.', 'arc-portal' ),
				'target'      => 'iframe',
				'capability'  => 'read',
				'order'       => 20,
			),
			'hr'         => array(
				'label'       => __( 'Human Resources', 'arc-portal' ),
				'url'         => '',
				'icon'        => 'users',
				'enabled'     => true,
				'description' => __( 'Requests, interviews and talent management.', 'arc-portal' ),
				'target'      => 'iframe',
				'capability'  => 'read',
				'order'       => 30,
			),
			'task_app'   => array(
				'label'       => __( 'Task App', 'arc-portal' ),
				'url'         => '',
				'icon'        => 'check-square',
				'enabled'     => true,
				'description' => __( 'Projects, tasks and Kanban board (opens in a new tab because it requires a Google session).', 'arc-portal' ),
				'target'      => 'new_tab',
				'capability'  => 'read',
				'order'       => 40,
			),
		);

		// Allow third-party code to pre-register apps very early.
		do_action( 'arc_portal_registry_init', $this );
	}

	/**
	 * Register a new app at runtime.
	 *
	 * @param string $slug App key/slug.
	 * @param array  $config App configuration.
	 * @return bool
	 */
	public function register_app( $slug, $config ) {
		$slug = sanitize_key( $slug );
		if ( empty( $slug ) ) {
			return false;
		}

		$defaults = array(
			'label'       => $slug,
			'url'         => '',
			'icon'        => 'grid',
			'enabled'     => true,
			'description' => '',
			'target'      => 'iframe',
			'capability'  => 'read',
			'order'       => 50,
		);

		$this->registered[ $slug ] = wp_parse_args( $config, $defaults );
		return true;
	}

	/**
	 * Register multiple apps at once.
	 *
	 * @param array $apps Array of slug => config.
	 */
	public function register_apps( $apps ) {
		if ( ! is_array( $apps ) ) {
			return;
		}
		foreach ( $apps as $slug => $config ) {
			$this->register_app( $slug, $config );
		}
	}

	/**
	 * Unregister an app.
	 *
	 * @param string $slug App slug.
	 */
	public function unregister_app( $slug ) {
		unset( $this->registered[ sanitize_key( $slug ) ] );
	}

	/**
	 * Check if an app is registered.
	 *
	 * @param string $slug App slug.
	 * @return bool
	 */
	public function is_registered( $slug ) {
		return isset( $this->registered[ sanitize_key( $slug ) ] ) || isset( $this->defaults[ sanitize_key( $slug ) ] );
	}

	/**
	 * Get a single app definition merged with stored settings.
	 *
	 * @param string $slug App slug.
	 * @param array  $stored_settings Stored settings for apps.
	 * @return array|null
	 */
	public function get_app( $slug, $stored_settings = array() ) {
		$apps = $this->get_apps( $stored_settings );
		return isset( $apps[ sanitize_key( $slug ) ] ) ? $apps[ sanitize_key( $slug ) ] : null;
	}

	/**
	 * Get all merged app definitions.
	 *
	 * Merge order: defaults < runtime registered < stored settings < filters.
	 *
	 * @param array $stored_settings Stored settings for apps.
	 * @return array
	 */
	public function get_apps( $stored_settings = array() ) {
		$apps = array_merge( $this->defaults, $this->registered );

		foreach ( $stored_settings as $slug => $stored ) {
			$slug = sanitize_key( $slug );
			if ( isset( $apps[ $slug ] ) ) {
				$apps[ $slug ] = wp_parse_args( $stored, $apps[ $slug ] );
			} else {
				$apps[ $slug ] = $this->sanitize_app_config( $stored, $slug );
			}
		}

		// Remove disabled apps and apps the user cannot access.
		$apps = array_filter(
			$apps,
			function ( $app ) {
				if ( empty( $app['enabled'] ) ) {
					return false;
				}
				if ( ! empty( $app['capability'] ) && ! current_user_can( $app['capability'] ) ) {
					return false;
				}
				return true;
			}
		);

		// Sort by order.
		uasort(
			$apps,
			function ( $a, $b ) {
				$a_order = isset( $a['order'] ) ? (int) $a['order'] : 50;
				$b_order = isset( $b['order'] ) ? (int) $b['order'] : 50;
				return $a_order - $b_order;
			}
		);

		/**
		 * Filter the final merged app list.
		 *
		 * @param array $apps Portal apps.
		 */
		return apply_filters( 'arc_portal_registered_apps', $apps );
	}

	/**
	 * Sanitize a runtime/stored app config.
	 *
	 * @param array  $config Raw app config.
	 * @param string $slug App slug.
	 * @return array
	 */
	private function sanitize_app_config( $config, $slug ) {
		$allowed_targets = array( 'iframe', 'new_tab', 'modal', 'ajax' );
		$target          = isset( $config['target'] ) ? sanitize_key( $config['target'] ) : 'iframe';

		return array(
			'label'       => ! empty( $config['label'] ) ? sanitize_text_field( $config['label'] ) : $slug,
			'url'         => ! empty( $config['url'] ) ? esc_url_raw( $config['url'] ) : '',
			'icon'        => ! empty( $config['icon'] ) ? sanitize_key( $config['icon'] ) : 'grid',
			'enabled'     => ! empty( $config['enabled'] ),
			'description' => ! empty( $config['description'] ) ? sanitize_text_field( $config['description'] ) : '',
			'target'      => in_array( $target, $allowed_targets, true ) ? $target : 'iframe',
			'capability'  => ! empty( $config['capability'] ) ? sanitize_key( $config['capability'] ) : 'read',
			'order'       => isset( $config['order'] ) ? (int) $config['order'] : 50,
		);
	}
}
