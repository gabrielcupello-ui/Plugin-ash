<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Dashboard {

	public static function init() {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_widget' ) );
	}

	public static function add_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'arc_etc_dashboard_widget',
			__( 'Employee Time Clock - Today', 'arc-employee-time-clock' ),
			array( __CLASS__, 'render_widget' )
		);
	}

	public static function render_widget() {
		global $wpdb;

		$today    = current_time( 'Y-m-d' );
		$in_count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT user_id) FROM ' . Arc_ETC_Time_Entries::table() . ' WHERE entry_date = %s AND clock_out IS NULL',
				$today
			)
		);

		$total_minutes = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(total_minutes),0) FROM ' . Arc_ETC_Time_Entries::table() . ' WHERE entry_date = %s AND clock_out IS NOT NULL',
				$today
			)
		);

		$pending_requests = Arc_ETC_Leave::pending_count();

		?>
		<div class="arc-etc-dashboard-widget">
			<ul class="list-disc pl-4 space-y-1">
				<li><strong><?php echo esc_html( $in_count ); ?></strong> <?php esc_html_e( 'employee(s) currently clocked in today', 'arc-employee-time-clock' ); ?></li>
				<li><strong><?php echo esc_html( Arc_ETC_Public::format_minutes( (int) $total_minutes ) ); ?></strong> <?php esc_html_e( 'total hours worked today', 'arc-employee-time-clock' ); ?></li>
				<?php if ( $pending_requests > 0 ) : ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=arc-employee-time-clock-leave' ) ); ?>"><strong><?php echo esc_html( $pending_requests ); ?></strong> <?php esc_html_e( 'pending leave requests', 'arc-employee-time-clock' ); ?></a></li>
				<?php endif; ?>
			</ul>
			<p class="mt-3">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=arc-employee-time-clock' ) ); ?>"><?php esc_html_e( 'View Reports', 'arc-employee-time-clock' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=arc-employee-time-clock-timesheet' ) ); ?>"><?php esc_html_e( 'Review Timesheets', 'arc-employee-time-clock' ); ?></a>
			</p>
		</div>
		<?php
	}
}
