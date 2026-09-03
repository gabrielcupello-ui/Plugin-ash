<?php
/**
 * Router for the /portal/ virtual page.
 *
 * Allows access via https://example.com/portal/ in addition to the shortcode.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_Portal_Router {

	/**
	 * Singleton instance.
	 *
	 * @var Arc_Portal_Router|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Arc_Portal_Router
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
		add_action( 'init', array( $this, 'add_rewrite_rules' ), 10, 0 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_include', array( $this, 'portal_template' ) );
		add_filter( 'document_title_parts', array( $this, 'portal_title' ) );
	}

	/**
	 * Add rewrite endpoint for /portal/.
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule( '^arc-portal/?$', 'index.php?arc_portal_app_page=1', 'top' );
	}

	/**
	 * Add custom query var.
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'arc_portal_app_page';
		return $vars;
	}

	/**
	 * Render the portal on the virtual page.
	 *
	 * @param string $template Current template.
	 * @return string
	 */
	public function portal_template( $template ) {
		if ( ! get_query_var( 'arc_portal_app_page' ) ) {
			return $template;
		}

		// Minimal page shell.
		get_header();
		echo '<main id="arc-portal-page" class="arc-portal-page">';
		echo do_shortcode( '[arc_portal]' );
		echo '</main>';
		get_footer();

		// Return a blank template file path to stop WordPress from loading another template.
		return ARC_PORTAL_DIR . 'templates/blank.php';
	}

	/**
	 * Set page title for the portal.
	 *
	 * @param array $title Title parts.
	 * @return array
	 */
	public function portal_title( $title ) {
		if ( get_query_var( 'arc_portal_app_page' ) ) {
			$settings = Arc_Portal_App::instance()->get_settings();
			$title['title'] = ! empty( $settings['portal_title'] ) ? $settings['portal_title'] : __( 'Portal ARC', 'arc-portal' );
		}
		return $title;
	}
}
