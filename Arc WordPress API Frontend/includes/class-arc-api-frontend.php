<?php
/**
 * Main class for the ARC API Frontend plugin.
 *
 * WordPress muestra los datos y envía datos a Google Apps Script / Sheets
 * a través de un proxy REST propio.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_API_Frontend_App {

	private static $instance = null;
	private $option_key = 'arc_api_frontend_app_settings';

	private $defaults = array(
		'logo_url'          => '',
		'allowed_roles'     => array( 'administrator', 'editor', 'subscriber' ),
		'apps'              => array(
			'time_clock' => array(
				'label'     => 'Control Horario',
				'endpoint'  => '',
				'api_key'   => '',
				'enabled'   => true,
			),
			'eod_report' => array(
				'label'     => 'EOD Report',
				'endpoint'  => '',
				'api_key'   => '',
				'enabled'   => true,
			),
			'hr'         => array(
				'label'     => 'Recursos Humanos',
				'endpoint'  => '',
				'api_key'   => '',
				'enabled'   => true,
			),
			'task_app'   => array(
				'label'     => 'Task App',
				'endpoint'  => '',
				'api_key'   => '',
				'enabled'   => true,
			),
		),
		'google_api_key'    => '',
		'google_client_id'    => '',
		'google_client_secret' => '',
	);

	private $settings = array();

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->settings = get_option( $this->option_key, $this->defaults );
		$this->settings = wp_parse_args( $this->settings, $this->defaults );

		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_import_apps_config' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Return possible paths to the shared apps config file.
	 *
	 * @return array
	 */
	private function get_config_paths() {
		return array(
			ARC_API_FRONTEND_DIR . 'arc-apps-config.json',
			WP_CONTENT_DIR . '/uploads/arc-apps-config.json',
			dirname( ARC_API_FRONTEND_DIR ) . '/plantillas/arc-apps-config.json',
		);
	}

	/**
	 * Import URLs from arc-apps-config.json if found and newer than stored settings.
	 */
	public function maybe_import_apps_config() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Manual trigger from settings page.
		if ( isset( $_POST['arc_api_frontend_import_config'] ) && check_admin_referer( 'arc_api_frontend_import_config' ) ) {
			$path = $this->find_config_file();
			if ( $path ) {
				$result = $this->import_apps_config( $path );
				add_settings_error(
					'arc_api_frontend_messages',
					'arc_api_frontend_import',
					$result ? __( 'Configuración importada correctamente.', 'arc-api-frontend' ) : __( 'No se pudo importar la configuración.', 'arc-api-frontend' ),
					$result ? 'success' : 'error'
				);
			} else {
				add_settings_error(
					'arc_api_frontend_messages',
					'arc_api_frontend_import',
					__( 'No se encontró arc-apps-config.json.', 'arc-api-frontend' ),
					'error'
				);
			}
		}

		// Auto-import on activation if config exists.
		$auto = get_option( 'arc_api_frontend_app_auto_import_done', false );
		if ( $auto ) {
			return;
		}
		$path = $this->find_config_file();
		if ( $path && $this->import_apps_config( $path ) ) {
			update_option( 'arc_api_frontend_app_auto_import_done', true );
		}
	}

	/**
	 * Find the first existing config file.
	 *
	 * @return string|false
	 */
	private function find_config_file() {
		foreach ( $this->get_config_paths() as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}
		return false;
	}

	/**
	 * Import apps config from a JSON file.
	 *
	 * @param string $path Absolute path to JSON.
	 * @return bool
	 */
	public function import_apps_config( $path ) {
		if ( ! file_exists( $path ) ) {
			return false;
		}

		$raw  = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $raw, true );

		if ( empty( $data['apps'] ) || ! is_array( $data['apps'] ) ) {
			return false;
		}

		$settings = $this->settings;
		$defaults = Arc_API_Frontend_Endpoint_Registry::instance()->get_endpoints();

		foreach ( $data['apps'] as $key => $app ) {
			$key = sanitize_key( $key );
			if ( empty( $key ) ) {
				continue;
			}

			if ( ! isset( $settings['apps'][ $key ] ) ) {
				$settings['apps'][ $key ] = isset( $defaults[ $key ] ) ? $defaults[ $key ] : array(
					'label'    => $key,
					'endpoint' => '',
					'api_key'  => '',
					'enabled'  => true,
				);
			}

			if ( ! empty( $app['endpoint'] ) ) {
				$settings['apps'][ $key ]['endpoint'] = esc_url_raw( $app['endpoint'] );
			}
			if ( ! empty( $app['name'] ) ) {
				$settings['apps'][ $key ]['label'] = sanitize_text_field( $app['name'] );
			}
		}

		update_option( $this->option_key, $settings );
		$this->settings = wp_parse_args( $settings, $this->defaults );
		return true;
	}

	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get merged endpoint definitions.
	 *
	 * Combines registry defaults + runtime registered endpoints + stored settings.
	 *
	 * @return array
	 */
	public function get_endpoints() {
		$stored = isset( $this->settings['apps'] ) ? $this->settings['apps'] : array();
		return Arc_API_Frontend_Endpoint_Registry::instance()->get_endpoints( $stored );
	}

	/**
	 * Get all shortcode tags supported by the plugin.
	 *
	 * @return array
	 */
	public function get_shortcode_tags() {
		$tags = array( 'arc_api_dashboard' );
		foreach ( array_keys( $this->get_endpoints() ) as $slug ) {
			$tags[] = 'arc_api_' . $slug;
		}
		return array_unique( $tags );
	}

	/**
	 * Show friendly admin notices when setup is incomplete.
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && $screen->id === 'toplevel_page_arc-api-frontend-setup' ) {
			return;
		}

		$configured = 0;
		$total      = 0;
		foreach ( $this->get_endpoints() as $app ) {
			if ( empty( $app['enabled'] ) ) {
				continue;
			}
			$total++;
			if ( ! empty( $app['endpoint'] ) ) {
				$configured++;
			}
		}

		if ( $total === 0 ) {
			return;
		}

		if ( $configured === 0 ) {
			echo '<div class="notice notice-warning is-dismissible"><p>';
			echo '<strong>' . esc_html__( 'ARC API Frontend', 'arc-api-frontend' ) . ':</strong> ';
			esc_html_e( 'Ningún endpoint está configurado. Ve a ', 'arc-api-frontend' );
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=arc-api-frontend-setup' ) ) . '">' . esc_html__( 'Configuración rápida', 'arc-api-frontend' ) . '</a>';
			echo '</p></div>';
		} elseif ( $configured < $total ) {
			echo '<div class="notice notice-info is-dismissible"><p>';
			echo '<strong>' . esc_html__( 'ARC API Frontend', 'arc-api-frontend' ) . ':</strong> ';
			printf( esc_html__( 'Tienes %1$d de %2$d endpoints configurados. ', 'arc-api-frontend' ), absint( $configured ), absint( $total ) );
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=arc-api-frontend-setup' ) ) . '">' . esc_html__( 'Revisar configuración', 'arc-api-frontend' ) . '</a>';
			echo '</p></div>';
		}
	}

	/**
	 * Return status text and color for an endpoint URL.
	 *
	 * @param string $url Endpoint URL.
	 * @return array
	 */
	public function get_app_status( $url ) {
		if ( empty( $url ) ) {
			return array( 'text' => __( 'Sin configurar', 'arc-api-frontend' ), 'color' => 'red' );
		}
		if ( false === wp_http_validate_url( $url ) ) {
			return array( 'text' => __( 'URL inválida', 'arc-api-frontend' ), 'color' => 'orange' );
		}
		return array( 'text' => __( 'OK', 'arc-api-frontend' ), 'color' => 'green' );
	}

	public function register_shortcodes() {
		add_shortcode( 'arc_api_dashboard', array( $this, 'render_dashboard' ) );

		$map = array(
			'time_clock' => 'render_time_clock',
			'eod_report' => 'render_eod_form',
			'task_app'   => 'render_tasks',
			'hr'         => 'render_hr',
		);

		foreach ( $this->get_endpoints() as $slug => $config ) {
			$tag     = 'arc_api_' . $slug;
			$method  = isset( $map[ $slug ] ) ? $map[ $slug ] : 'render_module';
			add_shortcode( $tag, array( $this, $method ) );
		}
	}

	/**
	 * Generic render method for dynamic endpoints.
	 *
	 * Falls back when no specific render method exists.
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag Shortcode tag.
	 * @return string
	 */
	public function render_module( $atts, $content = '', $tag = '' ) {
		if ( ! $this->can_access() ) {
			return $this->render_access_denied();
		}

		$endpoints = $this->get_endpoints();
		$slug      = str_replace( 'arc_api_', '', sanitize_key( $tag ) );
		$app       = isset( $endpoints[ $slug ] ) ? $endpoints[ $slug ] : array();

		ob_start();
		echo '<div class="arc-api-wrap">';
		echo '<div class="arc-api-card">';
		echo '<h2 class="arc-api-title">' . esc_html( $app['label'] ?? $slug ) . '</h2>';

		/**
		 * Fires when a dynamic module shortcode is rendered.
		 *
		 * @param array  $app  Endpoint config.
		 * @param array  $atts Shortcode attributes.
		 */
		do_action( 'arc_api_frontend_render_' . $slug, $app, $atts );

		if ( empty( $app['endpoint'] ) ) {
			echo '<div class="arc-api-message show error">' . esc_html__( 'Endpoint no configurado.', 'arc-api-frontend' ) . '</div>';
		} else {
			echo '<p>' . esc_html__( 'Módulo cargado dinámicamente.', 'arc-api-frontend' ) . '</p>';
		}

		echo '</div>';
		echo '</div>';
		return ob_get_clean();
	}

	public function enqueue_assets() {
		global $post;
		$shortcodes = $this->get_shortcode_tags();
		$load = false;
		if ( is_singular() && isset( $post->post_content ) ) {
			foreach ( $shortcodes as $sc ) {
				if ( has_shortcode( $post->post_content, $sc ) ) {
					$load = true;
					break;
				}
			}
		}
		if ( apply_filters( 'arc_api_frontend_force_assets', false ) ) {
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

		wp_enqueue_style( 'arc-api-frontend-css', ARC_API_FRONTEND_URL . 'assets/css/api-frontend.css', array(), ARC_API_FRONTEND_VERSION );
		wp_enqueue_script( 'arc-api-frontend-js', ARC_API_FRONTEND_URL . 'assets/js/api-frontend.js', array(), ARC_API_FRONTEND_VERSION, true );

		$user = wp_get_current_user();
		wp_localize_script(
			'arc-api-frontend-js',
			'arcApiFrontend',
			array(
				'restUrl'   => rest_url( 'arc-api-frontend/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'userEmail' => $user->exists() ? $user->user_email : '',
				'userName'  => $user->exists() ? $user->display_name : '',
			)
		);
	}

	/**
	 * Enqueue admin assets on ARC API Frontend admin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'arc-api-frontend' ) === false ) {
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

	public function can_access() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user = wp_get_current_user();
		if ( user_can( $user, 'manage_options' ) ) {
			return true;
		}
		$allowed = array_map( 'sanitize_key', (array) $this->settings['allowed_roles'] );
		return (bool) array_intersect( (array) $user->roles, $allowed );
	}

	public function render_access_denied() {
		return '<div class="max-w-md mx-auto my-10 p-8 bg-gray-100 rounded-2xl text-center"><p class="text-gray-700 mb-4">' . esc_html__( 'Debes iniciar sesión para acceder.', 'arc-api-frontend' ) . '</p><a class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition no-underline" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Iniciar sesión', 'arc-api-frontend' ) . '</a></div>';
	}

	public function render_time_clock( $atts ) {
		if ( ! $this->can_access() ) {
			return $this->render_access_denied();
		}

		$endpoints = $this->get_endpoints();
		$app       = isset( $endpoints['time_clock'] ) ? $endpoints['time_clock'] : array();
		$atts      = shortcode_atts( array( 'mode' => 'clock' ), $atts, 'arc_api_time_clock' );

		ob_start();
		include ARC_API_FRONTEND_DIR . 'templates/time-clock.php';
		return ob_get_clean();
	}

	public function render_eod_form( $atts ) {
		if ( ! $this->can_access() ) {
			return $this->render_access_denied();
		}

		$endpoints = $this->get_endpoints();
		$app       = isset( $endpoints['eod_report'] ) ? $endpoints['eod_report'] : array();

		ob_start();
		include ARC_API_FRONTEND_DIR . 'templates/eod-form.php';
		return ob_get_clean();
	}

	public function render_dashboard( $atts ) {
		if ( ! $this->can_access() ) {
			return $this->render_access_denied();
		}

		$endpoints = $this->get_endpoints();

		ob_start();
		include ARC_API_FRONTEND_DIR . 'templates/dashboard.php';
		return ob_get_clean();
	}

	public function render_tasks( $atts ) {
		if ( ! $this->can_access() ) {
			return $this->render_access_denied();
		}

		$endpoints = $this->get_endpoints();
		$app       = isset( $endpoints['task_app'] ) ? $endpoints['task_app'] : array();

		ob_start();
		include ARC_API_FRONTEND_DIR . 'templates/tasks.php';
		return ob_get_clean();
	}

	public function render_hr( $atts ) {
		if ( ! $this->can_access() ) {
			return $this->render_access_denied();
		}

		$endpoints = $this->get_endpoints();
		$app       = isset( $endpoints['hr'] ) ? $endpoints['hr'] : array();

		ob_start();
		include ARC_API_FRONTEND_DIR . 'templates/hr.php';
		return ob_get_clean();
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'ARC API Frontend', 'arc-api-frontend' ),
			__( 'ARC API Frontend', 'arc-api-frontend' ),
			'manage_options',
			'arc-api-frontend-setup',
			array( $this, 'render_setup_page' ),
			'dashicons-admin-generic',
			30
		);

		add_submenu_page(
			'arc-api-frontend-setup',
			__( 'Configuración rápida', 'arc-api-frontend' ),
			__( 'Configuración rápida', 'arc-api-frontend' ),
			'manage_options',
			'arc-api-frontend-setup',
			array( $this, 'render_setup_page' )
		);

		add_submenu_page(
			'arc-api-frontend-setup',
			__( 'Ajustes avanzados', 'arc-api-frontend' ),
			__( 'Ajustes avanzados', 'arc-api-frontend' ),
			'manage_options',
			'arc-api-frontend-advanced',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'arc_api_frontend_settings_group', $this->option_key, array( $this, 'sanitize_settings' ) );
	}

	public function sanitize_settings( $input ) {
		$output = $this->defaults;

		// Merge app defaults from the registry so dynamic apps survive sanitization.
		$output['apps'] = Arc_API_Frontend_Endpoint_Registry::instance()->get_endpoints();

		if ( isset( $input['logo_url'] ) ) {
			$output['logo_url'] = esc_url_raw( $input['logo_url'] );
		}
		if ( isset( $input['allowed_roles'] ) && is_array( $input['allowed_roles'] ) ) {
			$output['allowed_roles'] = array_map( 'sanitize_key', $input['allowed_roles'] );
		}
		if ( isset( $input['apps'] ) && is_array( $input['apps'] ) ) {
			foreach ( $input['apps'] as $key => $app ) {
				$key = sanitize_key( $key );
				if ( empty( $key ) ) {
					continue;
				}

				$base = isset( $output['apps'][ $key ] ) ? $output['apps'][ $key ] : array(
					'label'    => $key,
					'endpoint' => '',
					'api_key'  => '',
					'enabled'  => true,
				);

				$output['apps'][ $key ]['label']    = sanitize_text_field( $app['label'] ?? $base['label'] );
				$output['apps'][ $key ]['endpoint'] = esc_url_raw( $app['endpoint'] ?? $base['endpoint'] );
				$output['apps'][ $key ]['api_key']  = sanitize_text_field( $app['api_key'] ?? $base['api_key'] );
				$output['apps'][ $key ]['enabled']  = ! empty( $app['enabled'] );
				if ( isset( $app['order'] ) ) {
					$output['apps'][ $key ]['order'] = (int) $app['order'];
				}
			}
		}
		$output['google_api_key']         = ! empty( $input['google_api_key'] ) ? sanitize_text_field( $input['google_api_key'] ) : '';
		$output['google_client_id']       = ! empty( $input['google_client_id'] ) ? sanitize_text_field( $input['google_client_id'] ) : '';
		$output['google_client_secret']   = ! empty( $input['google_client_secret'] ) ? sanitize_text_field( $input['google_client_secret'] ) : '';
		return $output;
	}

	public function render_settings_page() {
		$settings = $this->settings;
		$roles    = wp_roles()->get_names();
		?>
		<div class="wrap bg-white p-6 font-sans">
			<h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors( 'arc_api_frontend_messages' ); ?>
			<form method="post" action="options.php" class="max-w-6xl space-y-6">
				<?php
				settings_fields( 'arc_api_frontend_settings_group' );
				do_settings_sections( 'arc-api-frontend' );
				?>
				<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm space-y-5">
					<div>
						<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Logo URL', 'arc-api-frontend' ); ?></label>
						<input type="url" name="<?php echo esc_attr( $this->option_key ); ?>[logo_url]" value="<?php echo esc_url( $settings['logo_url'] ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
					</div>
					<div>
						<label class="block text-sm font-semibold text-gray-700 mb-2"><?php esc_html_e( 'Roles permitidos', 'arc-api-frontend' ); ?></label>
						<div class="space-y-2">
							<?php foreach ( $roles as $role_key => $role_label ) : ?>
								<label class="flex items-center gap-2 text-sm text-gray-700">
									<input type="checkbox" name="<?php echo esc_attr( $this->option_key ); ?>[allowed_roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, (array) $settings['allowed_roles'], true ) ); ?> class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
									<?php echo esc_html( translate_user_role( $role_label ) ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div>
						<h3 class="text-lg font-semibold text-gray-900 mb-3"><?php esc_html_e( 'Endpoints de Apps Script', 'arc-api-frontend' ); ?></h3>
						<div class="overflow-x-auto border border-gray-200 rounded-2xl">
							<table class="w-full text-left border-collapse">
								<thead class="bg-gray-50 border-b border-gray-200">
									<tr>
										<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Hab.', 'arc-api-frontend' ); ?></th>
										<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'App', 'arc-api-frontend' ); ?></th>
										<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Endpoint URL (doPost/doGet)', 'arc-api-frontend' ); ?></th>
										<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'API Key', 'arc-api-frontend' ); ?></th>
										<th class="px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Estado', 'arc-api-frontend' ); ?></th>
									</tr>
								</thead>
								<tbody class="text-sm text-gray-700 divide-y divide-gray-100">
									<?php foreach ( $this->get_endpoints() as $key => $app ) : ?>
									<tr>
										<td class="px-4 py-3"><input type="checkbox" name="<?php echo esc_attr( $this->option_key ); ?>[apps][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $app['enabled'] ) ); ?>></td>
										<td class="px-4 py-3"><?php echo esc_html( $app['label'] ); ?></td>
										<td class="px-4 py-3"><input type="url" name="<?php echo esc_attr( $this->option_key ); ?>[apps][<?php echo esc_attr( $key ); ?>][endpoint]" value="<?php echo esc_url( $app['endpoint'] ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm" style="width:100%;"></td>
										<td class="px-4 py-3"><input type="text" name="<?php echo esc_attr( $this->option_key ); ?>[apps][<?php echo esc_attr( $key ); ?>][api_key]" value="<?php echo esc_attr( $app['api_key'] ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"></td>
										<td class="px-4 py-3">
											<?php
											$status     = $this->get_app_status( $app['endpoint'] );
											$status_dot = 'green' === $status['color'] ? '🟢' : ( 'orange' === $status['color'] ? '🟠' : '🔴' );
											?>
											<?php echo esc_html( $status_dot . ' ' . $status['text'] ); ?>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<p class="text-sm text-gray-400 mt-2"><?php esc_html_e( 'Cada endpoint debe exponer un doPost en Apps Script que valide la API Key. Ver docs/apps-script-api.md.', 'arc-api-frontend' ); ?></p>
					</div>
					<div>
						<h3 class="text-lg font-semibold text-gray-900 mb-3"><?php esc_html_e( 'Google API (opcional)', 'arc-api-frontend' ); ?></h3>
						<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'API Key', 'arc-api-frontend' ); ?></label>
						<input type="text" name="<?php echo esc_attr( $this->option_key ); ?>[google_api_key]" value="<?php echo esc_attr( $settings['google_api_key'] ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
						<p class="text-sm text-gray-400 mt-1"><?php esc_html_e( 'Usar Google Sheets API directamente requiere Service Account. Recomendado proxy via Apps Script.', 'arc-api-frontend' ); ?></p>
					</div>
				</div>
				<?php submit_button( __( 'Guardar cambios', 'arc-api-frontend' ), 'primary', 'submit', false, array( 'class' => 'px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition cursor-pointer' ) ); ?>
			</form>

			<form method="post" action="" class="max-w-6xl mt-10">
				<?php wp_nonce_field( 'arc_api_frontend_import_config' ); ?>
				<h2 class="text-xl font-bold text-gray-900 mb-2"><?php esc_html_e( 'Auto-configuración desde arc-apps-config.json', 'arc-api-frontend' ); ?></h2>
				<p class="text-sm text-gray-500 mb-4">
					<?php esc_html_e( 'Importa URLs de despliegue desde el archivo generado por update-arc-urls.js. El plugin busca arc-apps-config.json en su carpeta, en wp-content/uploads/ o junto a plantillas/.', 'arc-api-frontend' ); ?>
				</p>
				<?php
				$config_path = $this->find_config_file();
				if ( $config_path ) {
					echo '<p class="text-sm text-emerald-600 mb-4">' . esc_html__( 'Archivo encontrado:', 'arc-api-frontend' ) . ' <code class="bg-gray-100 px-2 py-1 rounded text-xs">' . esc_html( $config_path ) . '</code></p>';
				}
				?>
				<input type="hidden" name="arc_api_frontend_import_config" value="1">
				<?php submit_button( __( 'Importar endpoints ahora', 'arc-api-frontend' ), 'secondary', 'arc_api_frontend_import_config_submit', false, array( 'class' => 'px-6 py-2.5 bg-slate-500 hover:bg-slate-600 text-white font-medium rounded-lg transition cursor-pointer' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the quick setup wizard page.
	 */
	public function render_setup_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos.', 'arc-api-frontend' ) );
		}

		$settings = $this->settings;

		if ( isset( $_POST['arc_api_frontend_import_config'] ) && check_admin_referer( 'arc_api_frontend_import_config' ) ) {
			$path = $this->find_config_file();
			if ( $path && $this->import_apps_config( $path ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Endpoints importados correctamente.', 'arc-api-frontend' ) . '</p></div>';
				$settings = $this->get_settings();
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No se pudo importar. Asegúrate de haber ejecutado update-arc-urls.js.', 'arc-api-frontend' ) . '</p></div>';
			}
		}

		?>
		<div class="wrap bg-white p-6 font-sans">
			<h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="text-gray-500 mb-6"><?php esc_html_e( 'Configura el frontend API en 3 pasos:', 'arc-api-frontend' ); ?></p>

			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
				<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
					<h2 class="text-xl font-bold mb-2">1. <?php esc_html_e( 'Importar endpoints', 'arc-api-frontend' ); ?></h2>
					<p class="text-sm text-gray-500 mb-4"><?php esc_html_e( 'Ejecuta update-arc-urls.js y luego importa el JSON.', 'arc-api-frontend' ); ?></p>
					<?php
					$config_path = $this->find_config_file();
					if ( $config_path ) {
						echo '<p class="text-sm text-emerald-600 mb-4"><span class="font-semibold">✓</span> ' . esc_html__( 'Config listo', 'arc-api-frontend' ) . '</p>';
					} else {
						echo '<p class="text-sm text-amber-600 mb-4"><span class="font-semibold">⚠</span> ' . esc_html__( 'No se encontró arc-apps-config.json', 'arc-api-frontend' ) . '</p>';
					}
					?>
					<form method="post" action="">
						<?php wp_nonce_field( 'arc_api_frontend_import_config' ); ?>
						<input type="hidden" name="arc_api_frontend_import_config" value="1">
						<?php submit_button( __( 'Importar endpoints', 'arc-api-frontend' ), 'primary', 'arc_api_frontend_import_config_submit', false, array( 'class' => 'px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition cursor-pointer' ) ); ?>
					</form>
				</div>

				<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
					<h2 class="text-xl font-bold mb-2">2. <?php esc_html_e( 'Verificar endpoints', 'arc-api-frontend' ); ?></h2>
					<p class="text-sm text-gray-500 mb-4"><?php esc_html_e( 'Revisa que cada app tenga URL y API Key.', 'arc-api-frontend' ); ?></p>
					<ul class="space-y-2 text-sm text-gray-700 mb-4">
					<?php foreach ( $this->get_endpoints() as $key => $app ) : ?>
						<?php
						$status = $this->get_app_status( $app['endpoint'] );
						$dot    = 'green' === $status['color'] ? '🟢' : ( 'orange' === $status['color'] ? '🟠' : '🔴' );
						?>
						<li>
							<strong class="text-gray-900"><?php echo esc_html( $app['label'] ); ?>:</strong>
							<?php echo esc_html( $dot . ' ' . $status['text'] ); ?>
						</li>
					<?php endforeach; ?>
					</ul>
					<a class="inline-flex items-center px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition no-underline" href="<?php echo esc_url( admin_url( 'admin.php?page=arc-api-frontend-advanced' ) ); ?>"><?php esc_html_e( 'Editar ajustes avanzados', 'arc-api-frontend' ); ?></a>
				</div>

				<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
					<h2 class="text-xl font-bold mb-2">3. <?php esc_html_e( 'Publicar páginas', 'arc-api-frontend' ); ?></h2>
					<p class="text-sm text-gray-500 mb-4"><?php esc_html_e( 'Copia los shortcodes en páginas de WordPress.', 'arc-api-frontend' ); ?></p>
					<ul class="space-y-2 text-sm text-gray-700">
						<li><code class="bg-gray-100 px-2 py-1 rounded text-xs">[arc_api_dashboard]</code></li>
						<li><code class="bg-gray-100 px-2 py-1 rounded text-xs">[arc_api_time_clock]</code></li>
						<li><code class="bg-gray-100 px-2 py-1 rounded text-xs">[arc_api_eod_form]</code></li>
						<li><code class="bg-gray-100 px-2 py-1 rounded text-xs">[arc_api_tasks]</code></li>
						<li><code class="bg-gray-100 px-2 py-1 rounded text-xs">[arc_api_hr]</code></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
}
