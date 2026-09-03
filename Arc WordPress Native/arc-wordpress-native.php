<?php
/**
 * Plugin Name: Ash River Collective — WordPress Native Core
 * Plugin URI:  https://ashrivercollective.com
 * Description: Option 3: native WordPress core for Time Clock, EOD, HR, and Task App,
 *              with connectors to Google Apps Script / Sheets for gradual synchronization.
 * Version:     1.0.0
 * Author:      ARC Automation Team
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: arc-native
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      6.6
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARC_NATIVE_VERSION', '1.0.0' );
define( 'ARC_NATIVE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARC_NATIVE_URL', plugin_dir_url( __FILE__ ) );
define( 'ARC_NATIVE_TABLE_PREFIX', 'arc_native_' );

require_once ARC_NATIVE_DIR . 'includes/class-arc-native-activator.php';
require_once ARC_NATIVE_DIR . 'includes/class-arc-native-modules.php';
require_once ARC_NATIVE_DIR . 'includes/class-arc-native-core.php';
require_once ARC_NATIVE_DIR . 'includes/class-arc-native-google-bridge.php';

register_activation_hook( __FILE__, array( 'Arc_Native_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Arc_Native_Activator', 'deactivate' ) );

/**
 * Boot the native core.
 */
function arc_native_init() {
	load_plugin_textdomain( 'arc-native', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	Arc_Native_Core::instance();
	Arc_Native_Google_Bridge::instance();
}
add_action( 'plugins_loaded', 'arc_native_init' );

/**
 * Helper to check if the native time clock module is active.
 *
 * @return bool
 */
function arc_native_time_clock_active() {
	return class_exists( 'Arc_ETC_Time_Entries' ) || Arc_Native_Modules::instance()->is_active( 'time_clock' );
}
