<?php
/**
 * Core class for the ARC Native solution.
 *
 * Renders the dashboard, enqueues assets, registers shortcodes and
 * exposes REST endpoints used by the native modules.
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Native core.
 */
class Arc_Native_Core {

	/**
	 * Singleton instance.
	 *
	 * @var Arc_Native_Core|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Arc_Native_Core
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
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register shortcodes for each module and the dashboard.
	 */
	public function register_shortcodes() {
		add_shortcode( 'arc_native_dashboard', array( $this, 'render_dashboard' ) );

		foreach ( Arc_Native_Modules::instance()->get_all() as $slug => $module ) {
			if ( ! empty( $module['shortcode'] ) ) {
				add_shortcode( $module['shortcode'], array( $this, 'render_module_shortcode' ) );
			}
		}
	}

	/**
	 * Enqueue assets only when native shortcodes are present.
	 */
	public function enqueue_assets() {
		global $post;

		$tags = array( 'arc_native_dashboard' );
		foreach ( Arc_Native_Modules::instance()->get_all() as $slug => $module ) {
			if ( ! empty( $module['shortcode'] ) ) {
				$tags[] = $module['shortcode'];
			}
		}

		$load = false;
		if ( is_singular() && isset( $post->post_content ) ) {
			foreach ( $tags as $tag ) {
				if ( has_shortcode( $post->post_content, $tag ) ) {
					$load = true;
					break;
				}
			}
		}

		if ( apply_filters( 'arc_native_force_assets', false ) ) {
			$load = true;
		}

		if ( ! $load ) {
			return;
		}

		// Tailwind CSS v4 browser CDN.
		wp_enqueue_script(
			'tailwind-cdn',
			'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4',
			array(),
			null,
			false
		);

		wp_enqueue_style(
			'arc-native-css',
			ARC_NATIVE_URL . 'assets/css/native.css',
			array(),
			ARC_NATIVE_VERSION
		);

		wp_enqueue_script(
			'arc-native-js',
			ARC_NATIVE_URL . 'assets/js/native.js',
			array(),
			ARC_NATIVE_VERSION,
			true
		);

		$user = wp_get_current_user();
		wp_localize_script(
			'arc-native-js',
			'arcNativeData',
			array(
				'restUrl'   => rest_url( 'arc-native/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'userId'    => $user->exists() ? $user->ID : 0,
				'userEmail' => $user->exists() ? $user->user_email : '',
				'modules'   => Arc_Native_Modules::instance()->get_all(),
			)
		);
	}

	/**
	 * Enqueue admin assets on ARC Native admin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'arc-native' ) === false ) {
			return;
		}

		wp_enqueue_script(
			'tailwind-cdn',
			'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4',
			array(),
			null,
			false
		);
	}

	/**
	 * Render the [arc_native_dashboard] shortcode.
	 *
	 * @return string
	 */
	public function render_dashboard() {
		if ( ! is_user_logged_in() ) {
			return $this->access_denied();
		}

		$modules = Arc_Native_Modules::instance()->get_all();

		ob_start();
		include ARC_NATIVE_DIR . 'templates/dashboard.php';
		return ob_get_clean();
	}

	/**
	 * Generic module shortcode renderer.
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag Shortcode tag.
	 * @return string
	 */
	public function render_module_shortcode( $atts, $content = '', $tag = '' ) {
		if ( ! is_user_logged_in() ) {
			return $this->access_denied();
		}

		$modules = Arc_Native_Modules::instance()->get_all();
		$module  = null;
		foreach ( $modules as $slug => $m ) {
			if ( ! empty( $m['shortcode'] ) && $m['shortcode'] === $tag ) {
				$module         = $m;
				$module['slug'] = $slug;
				break;
			}
		}

		if ( ! $module ) {
			return '<div class="arc-native-notice error">' . esc_html__( 'Módulo no encontrado.', 'arc-native' ) . '</div>';
		}

		if ( ! empty( $module['capability'] ) && ! current_user_can( $module['capability'] ) ) {
			return $this->access_denied();
		}

		ob_start();

		/**
		 * Fires to render a native module template.
		 *
		 * @param array  $module Module configuration.
		 * @param array  $atts   Shortcode attributes.
		 */
		do_action( 'arc_native_render_module_' . $module['slug'], $module, $atts );

		// Default fallback: look for templates/{module}.php.
		$template = ARC_NATIVE_DIR . 'templates/' . sanitize_file_name( $module['slug'] ) . '.php';
		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<div class="arc-native-card">';
			echo '<h2>' . esc_html( $module['label'] ) . '</h2>';
			echo '<p>' . esc_html( $module['description'] ) . '</p>';
			echo '<p class="arc-native-hint">' . esc_html__( 'Instala o activa el módulo correspondiente para ver su contenido.', 'arc-native' ) . '</p>';
			echo '</div>';
		}

		return ob_get_clean();
	}

	/**
	 * Access denied message.
	 *
	 * @return string
	 */
	private function access_denied() {
		return '<div class="max-w-md mx-auto my-10 p-8 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-center"><p class="mb-4">' . esc_html__( 'Debes iniciar sesión para acceder.', 'arc-native' ) . '</p><a class="inline-flex items-center px-5 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition no-underline" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Iniciar sesión', 'arc-native' ) . '</a></div>';
	}

	/**
	 * Register REST routes used by the dashboard and modules.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'arc-native/v1',
			'/modules',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_modules' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'arc-native/v1',
			'/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_stats' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}

	/**
	 * REST: list modules.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_get_modules() {
		return rest_ensure_response( Arc_Native_Modules::instance()->get_all() );
	}

	/**
	 * REST: basic stats for the dashboard.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_get_stats() {
		$user_id = get_current_user_id();
		$stats   = array(
			'week_hours'   => 0,
			'eod_count'    => 0,
			'active_tasks' => 0,
			'candidates'   => 0,
		);

		// Time clock stats: try native plugin first, then generic filter.
		if ( function_exists( 'arc_etc_time_entries' ) || class_exists( 'Arc_ETC_Time_Entries' ) ) {
			$start = gmdate( 'Y-m-d', strtotime( 'monday this week' ) );
			$end   = gmdate( 'Y-m-d', strtotime( 'sunday this week' ) );
			if ( method_exists( 'Arc_ETC_Time_Entries', 'get_total_minutes' ) ) {
				$stats['week_hours'] = round( Arc_ETC_Time_Entries::get_total_minutes( $user_id, $start, $end ) / 60, 2 );
			}
		}

		global $wpdb;
		$prefix = $wpdb->prefix . ARC_NATIVE_TABLE_PREFIX;

		if ( Arc_Native_Modules::instance()->is_active( 'eod' ) ) {
			$stats['eod_count'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}eod_reports WHERE user_id = %d", $user_id ) );
		}

		if ( Arc_Native_Modules::instance()->is_active( 'tasks' ) ) {
			$stats['active_tasks'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}tasks WHERE assignee_id = %d AND status != 'Done'", $user_id ) );
		}

		if ( Arc_Native_Modules::instance()->is_active( 'hr' ) ) {
			$stats['candidates'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}hr_applications WHERE status = 'new'" );
		}

		/**
		 * Filter native dashboard stats.
		 *
		 * @param array $stats   Stats array.
		 * @param int   $user_id Current user ID.
		 */
		return rest_ensure_response( apply_filters( 'arc_native_dashboard_stats', $stats, $user_id ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'ARC Native', 'arc-native' ),
			__( 'ARC Native', 'arc-native' ),
			'manage_options',
			'arc-native',
			array( $this, 'render_admin_page' ),
			'dashicons-admin-generic',
			30
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 'arc_native_settings_group', 'arc_native_google_sync_url', 'esc_url_raw' );
		register_setting( 'arc_native_settings_group', 'arc_native_google_api_secret', 'sanitize_text_field' );
		register_setting( 'arc_native_settings_group', 'arc_native_sync_enabled', 'rest_sanitize_boolean' );
	}

	/**
	 * Render the admin settings page.
	 */
	public function render_admin_page() {
		if ( isset( $_POST['arc_native_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arc_native_settings_nonce'] ) ), 'arc_native_save_settings' ) && current_user_can( 'manage_options' ) ) {
			update_option( 'arc_native_google_sync_url', isset( $_POST['arc_native_google_sync_url'] ) ? esc_url_raw( wp_unslash( $_POST['arc_native_google_sync_url'] ) ) : '' );
			update_option( 'arc_native_google_api_secret', isset( $_POST['arc_native_google_api_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['arc_native_google_api_secret'] ) ) : '' );
			update_option( 'arc_native_sync_enabled', ! empty( $_POST['arc_native_sync_enabled'] ) );

			$stored = array();
			if ( ! empty( $_POST['arc_native_modules'] ) && is_array( $_POST['arc_native_modules'] ) ) {
				foreach ( $_POST['arc_native_modules'] as $slug => $data ) {
					$slug = sanitize_key( $slug );
					if ( empty( $slug ) ) {
						continue;
					}
					$stored[ $slug ] = array(
						'label'       => isset( $data['label'] ) ? sanitize_text_field( wp_unslash( $data['label'] ) ) : '',
						'description' => isset( $data['description'] ) ? sanitize_textarea_field( wp_unslash( $data['description'] ) ) : '',
						'icon'        => isset( $data['icon'] ) ? sanitize_text_field( wp_unslash( $data['icon'] ) ) : '',
						'order'       => isset( $data['order'] ) ? (int) $data['order'] : 50,
						'active'      => ! empty( $data['active'] ),
						'google_sync' => ! empty( $data['google_sync'] ),
					);
				}
			}
			update_option( 'arc_native_module_settings', $stored );

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Configuración guardada.', 'arc-native' ) . '</p></div>';
		}

		$modules = Arc_Native_Modules::instance()->get_all();
		?>
		<div class="wrap bg-white p-6 font-sans">
			<h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="text-gray-500 mb-8"><?php esc_html_e( 'Núcleo nativo de Ash River Collective. Configura la sincronización con Google y los módulos activos.', 'arc-native' ); ?></p>

			<form method="post" class="max-w-5xl space-y-8">
				<?php wp_nonce_field( 'arc_native_save_settings', 'arc_native_settings_nonce' ); ?>
				<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm space-y-5">
					<div>
						<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'URL de Google Sync Bridge', 'arc-native' ); ?></label>
						<input type="url" name="arc_native_google_sync_url" value="<?php echo esc_url( get_option( 'arc_native_google_sync_url', '' ) ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
						<p class="text-sm text-gray-400 mt-1"><?php esc_html_e( 'Endpoint de Apps Script que recibe cambios de la cola de sincronización.', 'arc-native' ); ?></p>
					</div>
					<div>
						<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'API Secret', 'arc-native' ); ?></label>
						<input type="text" name="arc_native_google_api_secret" value="<?php echo esc_attr( get_option( 'arc_native_google_api_secret', '' ) ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
						<p class="text-sm text-gray-400 mt-1"><?php esc_html_e( 'Secreto compartido con Google Apps Script. Dejar en blanco para usar wp_salt().', 'arc-native' ); ?></p>
					</div>
					<div class="flex items-center gap-2">
						<input type="checkbox" name="arc_native_sync_enabled" value="1" <?php checked( get_option( 'arc_native_sync_enabled', false ) ); ?> class="w-5 h-5 text-sky-600 rounded focus:ring-sky-500 border-gray-300">
						<label class="text-sm font-medium text-gray-700"><?php esc_html_e( 'Encolar cambios para sincronizar con Google.', 'arc-native' ); ?></label>
					</div>
				</div>

				<h2 class="text-xl font-bold text-gray-900 mt-10 mb-4"><?php esc_html_e( 'Módulos registrados', 'arc-native' ); ?></h2>
				<div class="overflow-x-auto border border-gray-200 rounded-2xl shadow-sm">
					<table class="w-full text-left border-collapse">
						<thead class="bg-gray-50 border-b border-gray-200">
							<tr>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Módulo', 'arc-native' ); ?></th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Shortcode', 'arc-native' ); ?></th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Icono', 'arc-native' ); ?></th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Orden', 'arc-native' ); ?></th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Google Sync', 'arc-native' ); ?></th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Activo', 'arc-native' ); ?></th>
							</tr>
						</thead>
						<tbody class="text-sm text-gray-700 divide-y divide-gray-100">
							<?php foreach ( $modules as $slug => $module ) : ?>
							<tr>
								<td class="px-4 py-3 align-top">
									<input type="text" name="arc_native_modules[<?php echo esc_attr( $slug ); ?>][label]" value="<?php echo esc_attr( $module['label'] ); ?>" class="w-full mb-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none font-medium text-gray-900">
									<textarea name="arc_native_modules[<?php echo esc_attr( $slug ); ?>][description]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none text-gray-500 text-xs"><?php echo esc_textarea( $module['description'] ); ?></textarea>
								</td>
								<td class="px-4 py-3 align-top"><code class="bg-gray-100 px-2 py-1 rounded text-xs">[<?php echo esc_html( $module['shortcode'] ); ?>]</code></td>
								<td class="px-4 py-3 align-top"><input type="text" name="arc_native_modules[<?php echo esc_attr( $slug ); ?>][icon]" value="<?php echo esc_attr( $module['icon'] ); ?>" class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none text-center"></td>
								<td class="px-4 py-3 align-top"><input type="number" name="arc_native_modules[<?php echo esc_attr( $slug ); ?>][order]" value="<?php echo esc_attr( $module['order'] ); ?>" class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none"></td>
								<td class="px-4 py-3 align-top"><input type="checkbox" name="arc_native_modules[<?php echo esc_attr( $slug ); ?>][google_sync]" value="1" <?php checked( ! empty( $module['google_sync'] ) ); ?> class="w-5 h-5 text-sky-600 rounded focus:ring-sky-500 border-gray-300"></td>
								<td class="px-4 py-3 align-top"><input type="checkbox" name="arc_native_modules[<?php echo esc_attr( $slug ); ?>][active]" value="1" <?php checked( ! empty( $module['active'] ) ); ?> class="w-5 h-5 text-sky-600 rounded focus:ring-sky-500 border-gray-300"></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<?php submit_button( __( 'Guardar cambios', 'arc-native' ), 'primary', 'submit', false, array( 'class' => 'px-6 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition cursor-pointer' ) ); ?>
			</form>
		</div>
		<?php
	}
}
