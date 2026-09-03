<?php
/**
 * Plugin Name: Ash River Collective — Portal Integrado
 * Plugin URI:  https://ashrivercollective.com
 * Description: Portal centralizado en WordPress que agrupa las apps de Google Apps Script: IPC Time Clock, EOD Report, Human Resources y Task App.
 * Version:     1.1.0
 * Author:      ARC Automation Team
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: arc-portal
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

define( 'ARC_PORTAL_VERSION', '1.1.0' );
define( 'ARC_PORTAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARC_PORTAL_URL', plugin_dir_url( __FILE__ ) );

require_once ARC_PORTAL_DIR . 'includes/class-arc-portal-app-registry.php';
require_once ARC_PORTAL_DIR . 'includes/class-arc-portal.php';
require_once ARC_PORTAL_DIR . 'includes/class-arc-portal-router.php';
require_once ARC_PORTAL_DIR . 'includes/class-arc-portal-gas-auth-bridge.php';

/**
 * Boot the plugin on plugins_loaded.
 */
function arc_portal_app_init() {
	Arc_Portal_App::instance();
	Arc_Portal_Router::instance();
	Arc_Portal_GAS_Auth_Bridge::instance();
}
add_action( 'plugins_loaded', 'arc_portal_app_init' );

/**
 * Flush rewrite rules on activation and deactivation.
 */
function arc_portal_app_activate() {
	Arc_Portal_Router::add_rewrite_rules();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'arc_portal_app_activate' );

function arc_portal_app_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'arc_portal_app_deactivate' );
