<?php
/**
 * Module registry for the ARC Native core.
 *
 * Makes the native solution modular: each module (time_clock, eod, hr, tasks)
 * can register itself and provide shortcodes, REST routes, admin menus and
 * Google sync settings.
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module registry.
 */
class Arc_Native_Modules {

	/**
	 * Singleton instance.
	 *
	 * @var Arc_Native_Modules|null
	 */
	private static $instance = null;

	/**
	 * Registered modules.
     *
     * @var array
	 */
	private $modules = array();

	/**
	 * Get the singleton instance.
	 *
	 * @return Arc_Native_Modules
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
		$this->register_default_modules();

		/**
		 * Fires after the native module registry is initialized.
		 *
		 * @param Arc_Native_Modules $registry Registry instance.
		 */
		do_action( 'arc_native_modules_init', $this );
	}

	/**
	 * Register the built-in modules.
	 */
	private function register_default_modules() {
		$this->register(
			'time_clock',
			array(
				'label'         => __( 'Control Horario', 'arc-native' ),
				'description'   => __( 'Fichaje y timesheet nativo.', 'arc-native' ),
				'icon'          => 'clock',
				'order'         => 10,
				'shortcode'     => 'arc_native_time_clock',
				'rest_namespace' => 'arc-native/v1',
				'capability'    => 'read',
				'google_sync'   => true,
			)
		);

		$this->register(
			'eod',
			array(
				'label'         => __( 'EOD Reports', 'arc-native' ),
				'description'   => __( 'Reportes diarios de fin de jornada.', 'arc-native' ),
				'icon'          => 'file-text',
				'order'         => 20,
				'shortcode'     => 'arc_native_eod',
				'rest_namespace' => 'arc-native/v1',
				'capability'    => 'read',
				'google_sync'   => true,
			)
		);

		$this->register(
			'hr',
			array(
				'label'         => __( 'Recursos Humanos', 'arc-native' ),
				'description'   => __( 'Aplicaciones y gestión de talento.', 'arc-native' ),
				'icon'          => 'users',
				'order'         => 30,
				'shortcode'     => 'arc_native_hr',
				'rest_namespace' => 'arc-native/v1',
				'capability'    => 'read',
				'google_sync'   => true,
			)
		);

		$this->register(
			'tasks',
			array(
				'label'         => __( 'Task App', 'arc-native' ),
				'description'   => __( 'Gestión de tareas y proyectos.', 'arc-native' ),
				'icon'          => 'check-square',
				'order'         => 40,
				'shortcode'     => 'arc_native_tasks',
				'rest_namespace' => 'arc-native/v1',
				'capability'    => 'read',
				'google_sync'   => true,
			)
		);
	}

	/**
	 * Register a module.
	 *
	 * @param string $slug Module slug.
	 * @param array  $config Module configuration.
	 * @return bool
	 */
	public function register( $slug, $config ) {
		$slug = sanitize_key( $slug );
		if ( empty( $slug ) ) {
			return false;
		}

		$defaults = array(
			'label'          => $slug,
			'description'    => '',
			'icon'           => 'admin-generic',
			'order'          => 50,
			'shortcode'      => '',
			'rest_namespace' => '',
			'capability'     => 'read',
			'google_sync'    => false,
			'active'         => true,
		);

		$this->modules[ $slug ] = wp_parse_args( $config, $defaults );
		return true;
	}

	/**
	 * Unregister a module.
	 *
	 * @param string $slug Module slug.
	 */
	public function unregister( $slug ) {
		unset( $this->modules[ sanitize_key( $slug ) ] );
	}

	/**
	 * Check whether a module is active.
	 *
	 * @param string $slug Module slug.
	 * @return bool
	 */
	public function is_active( $slug ) {
		$module = $this->get( $slug );
		return ! empty( $module ) && ! empty( $module['active'] );
	}

	/**
	 * Get a single module.
	 *
	 * @param string $slug Module slug.
	 * @return array|null
	 */
	public function get( $slug ) {
		$slug = sanitize_key( $slug );
		return isset( $this->modules[ $slug ] ) ? $this->modules[ $slug ] : null;
	}

	/**
	 * Get stored module overrides from wp_options.
	 *
	 * @return array
	 */
	public function get_stored_settings() {
		$stored = get_option( 'arc_native_module_settings', array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Merge stored settings into a modules array.
	 *
	 * @param array $modules Modules to merge into.
	 * @return array
	 */
	private function apply_stored_settings( $modules ) {
		$stored = $this->get_stored_settings();
		foreach ( $modules as $slug => $config ) {
			if ( ! isset( $stored[ $slug ] ) || ! is_array( $stored[ $slug ] ) ) {
				continue;
			}
			foreach ( array( 'label', 'description', 'icon', 'order', 'active', 'google_sync' ) as $key ) {
				if ( isset( $stored[ $slug ][ $key ] ) ) {
					if ( in_array( $key, array( 'order' ), true ) ) {
						$modules[ $slug ][ $key ] = (int) $stored[ $slug ][ $key ];
					} elseif ( in_array( $key, array( 'active', 'google_sync' ), true ) ) {
						$modules[ $slug ][ $key ] = (bool) $stored[ $slug ][ $key ];
					} else {
						$modules[ $slug ][ $key ] = sanitize_text_field( $stored[ $slug ][ $key ] );
					}
				}
			}
		}
		return $modules;
	}

	/**
	 * Get all modules sorted by order.
	 *
	 * @return array
	 */
	public function get_all() {
		$modules = $this->modules;
		$modules = $this->apply_stored_settings( $modules );

		uasort(
			$modules,
			function ( $a, $b ) {
				$a_order = isset( $a['order'] ) ? (int) $a['order'] : 50;
				$b_order = isset( $b['order'] ) ? (int) $b['order'] : 50;
				return $a_order - $b_order;
			}
		);

		/**
		 * Filter the list of native modules.
		 *
		 * @param array $modules Registered modules.
		 */
		return apply_filters( 'arc_native_modules', $modules );
	}

	/**
	 * Return modules that support Google sync.
	 *
	 * @return array
	 */
	public function get_sync_modules() {
		return array_filter(
			$this->get_all(),
			function ( $module ) {
				return ! empty( $module['google_sync'] );
			}
		);
	}
}
