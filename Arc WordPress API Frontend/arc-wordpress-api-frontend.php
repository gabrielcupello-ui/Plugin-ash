<?php
/**
 * Plugin Name: Ash River Collective — API Frontend
 * Plugin URI:  https://ashrivercollective.com
 * Description: WordPress como frontend nativo que consume y envía datos a las apps de Google Apps Script vía APIs REST.
 * Version:     1.1.0
 * Author:      ARC Automation Team
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: arc-api-frontend
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      6.6
 *
 * Security: this file should not be accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARC_API_FRONTEND_VERSION', '1.0.0' );
define( 'ARC_API_FRONTEND_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARC_API_FRONTEND_URL', plugin_dir_url( __FILE__ ) );

require_once ARC_API_FRONTEND_DIR . 'includes/class-arc-api-endpoint-registry.php';
require_once ARC_API_FRONTEND_DIR . 'includes/class-arc-api-frontend.php';
require_once ARC_API_FRONTEND_DIR . 'includes/class-arc-api-proxy.php';
require_once ARC_API_FRONTEND_DIR . 'includes/class-arc-api-auth.php';

function arc_api_frontend_app_init() {
	Arc_API_Frontend_App::instance();
	Arc_API_Frontend_Proxy::instance();
	Arc_API_Frontend_Auth::instance();
}
add_action( 'plugins_loaded', 'arc_api_frontend_app_init' );
