<?php
/**
 * Plugin Name: Arc Employee Time Clock
 * Plugin URI:  https://ashrivercollective.com
 * Description: Cronómetro unificado para empleados estilo Clockify e IPC Time Clock. Incluye fichaje en vivo, cliente/actividad/proyecto/tarea/etiquetas, pausa/reanudación, timesheet semanal, reportes, PTO, festivos, ausencias, bloqueo de semanas y aprobaciones.
 * Version:     1.0.0
 * Author:      ARC Automation Team
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: arc-employee-time-clock
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARC_ETC_VERSION', '1.0.0' );
define( 'ARC_ETC_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARC_ETC_URL', plugin_dir_url( __FILE__ ) );
define( 'ARC_ETC_TABLE', 'arc_employee_timeclock_entries' );

require_once ARC_ETC_DIR . 'includes/class-activator.php';
require_once ARC_ETC_DIR . 'includes/class-deactivator.php';
require_once ARC_ETC_DIR . 'includes/class-time-entries.php';
require_once ARC_ETC_DIR . 'includes/class-clients.php';
require_once ARC_ETC_DIR . 'includes/class-activities.php';
require_once ARC_ETC_DIR . 'includes/class-locked-weeks.php';
require_once ARC_ETC_DIR . 'includes/class-holidays.php';
require_once ARC_ETC_DIR . 'includes/class-leave.php';
require_once ARC_ETC_DIR . 'includes/class-dashboard.php';
require_once ARC_ETC_DIR . 'includes/class-admin.php';
require_once ARC_ETC_DIR . 'includes/class-admin-ipc.php';
require_once ARC_ETC_DIR . 'includes/class-public.php';
require_once ARC_ETC_DIR . 'includes/class-cron.php';

register_activation_hook( __FILE__, array( 'Arc_ETC_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Arc_ETC_Deactivator', 'deactivate' ) );

/**
 * Boot the plugin.
 */
function arc_employee_time_clock_init() {
	load_plugin_textdomain( 'arc-employee-time-clock', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	Arc_ETC_Time_Entries::init();
	Arc_ETC_Admin::init();
	Arc_ETC_Admin_IPC::init();
	Arc_ETC_Public::init();
	Arc_ETC_Dashboard::init();
	Arc_ETC_Cron::init();
}
add_action( 'plugins_loaded', 'arc_employee_time_clock_init' );
