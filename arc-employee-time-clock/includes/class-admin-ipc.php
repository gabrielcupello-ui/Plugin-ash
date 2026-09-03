<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Admin_IPC {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_arc_etc_save_client', array( __CLASS__, 'save_client' ) );
		add_action( 'admin_post_arc_etc_save_activity', array( __CLASS__, 'save_activity' ) );
		add_action( 'admin_post_arc_etc_lock_week', array( __CLASS__, 'lock_week' ) );
		add_action( 'admin_post_arc_etc_unlock_week', array( __CLASS__, 'unlock_week' ) );
		add_action( 'admin_post_arc_etc_save_rules', array( __CLASS__, 'save_rules' ) );
		add_action( 'admin_post_arc_etc_import_csv', array( __CLASS__, 'import_csv' ) );
	}

	public static function add_menu() {
		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Clients', 'arc-employee-time-clock' ),
			__( 'Clients', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-clients',
			array( __CLASS__, 'render_clients_page' )
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Activities', 'arc-employee-time-clock' ),
			__( 'Activities', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-activities',
			array( __CLASS__, 'render_activities_page' )
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Locked Weeks', 'arc-employee-time-clock' ),
			__( 'Locked Weeks', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-locked',
			array( __CLASS__, 'render_locked_weeks_page' )
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Payroll', 'arc-employee-time-clock' ),
			__( 'Payroll', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-payroll',
			array( __CLASS__, 'render_payroll_page' )
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'IPC Rules', 'arc-employee-time-clock' ),
			__( 'Rules', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-rules',
			array( __CLASS__, 'render_rules_page' )
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Import CSV', 'arc-employee-time-clock' ),
			__( 'Import CSV', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-import',
			array( __CLASS__, 'render_import_page' )
		);
	}

	public static function save_client() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'arc_etc_client_nonce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'arc-employee-time-clock' ) );
		}

		$data = array(
			'name'             => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'entity'           => sanitize_text_field( wp_unslash( $_POST['entity'] ?? '' ) ),
			'billable_default' => isset( $_POST['billable_default'] ) ? 1 : 0,
			'active'           => isset( $_POST['active'] ) ? 1 : 0,
		);
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		Arc_ETC_Clients::save( $data, $id );
		wp_safe_redirect( admin_url( 'admin.php?page=arc-employee-time-clock-clients' ) );
		exit;
	}

	public static function save_activity() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'arc_etc_activity_nonce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'arc-employee-time-clock' ) );
		}

		$data = array(
			'name'             => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'billable_default' => isset( $_POST['billable_default'] ) ? 1 : 0,
			'paid'             => isset( $_POST['paid'] ) ? 1 : 0,
			'active'           => isset( $_POST['active'] ) ? 1 : 0,
		);
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		Arc_ETC_Activities::save( $data, $id );
		wp_safe_redirect( admin_url( 'admin.php?page=arc-employee-time-clock-activities' ) );
		exit;
	}

	public static function lock_week() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'arc_etc_lock_nonce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'arc-employee-time-clock' ) );
		}
		$week = sanitize_text_field( wp_unslash( $_POST['week_key'] ?? '' ) );
		if ( $week ) {
			Arc_ETC_Locked_Weeks::lock( $week );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=arc-employee-time-clock-locked' ) );
		exit;
	}

	public static function unlock_week() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'arc_etc_unlock_nonce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'arc-employee-time-clock' ) );
		}
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( $id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'arc_etc_locked_weeks', array( 'id' => $id ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=arc-employee-time-clock-locked' ) );
		exit;
	}

	public static function render_clients_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
		$edit    = $edit_id ? Arc_ETC_Clients::get( $edit_id ) : null;
		$clients = Arc_ETC_Clients::get_all();
		?>
		<div class="wrap max-w-4xl mx-auto p-4">
			<h1 class="text-2xl font-bold text-slate-900 mb-6"><?php esc_html_e( 'Clients / Companies', 'arc-employee-time-clock' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=arc_etc_save_client' ) ); ?>" class="bg-white rounded-xl border border-slate-200 p-5 mb-8 space-y-4">
				<?php wp_nonce_field( 'arc_etc_client_nonce' ); ?>
				<?php if ( $edit_id ) : ?>
					<input type="hidden" name="id" value="<?php echo esc_attr( $edit_id ); ?>">
				<?php endif; ?>
				<div class="grid md:grid-cols-2 gap-4">
					<input type="text" name="name" value="<?php echo esc_attr( $edit ? $edit->name : '' ); ?>" placeholder="<?php esc_attr_e( 'Client name', 'arc-employee-time-clock' ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full" required>
					<input type="text" name="entity" value="<?php echo esc_attr( $edit ? $edit->entity : '' ); ?>" placeholder="<?php esc_attr_e( 'Entity (optional)', 'arc-employee-time-clock' ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full">
				</div>
				<div class="flex gap-6 text-sm">
					<label class="flex items-center gap-2"><input type="checkbox" name="billable_default" <?php checked( ! $edit || $edit->billable_default ); ?>> <?php esc_html_e( 'Billable by default', 'arc-employee-time-clock' ); ?></label>
					<label class="flex items-center gap-2"><input type="checkbox" name="active" <?php checked( ! $edit || $edit->active ); ?>> <?php esc_html_e( 'Active', 'arc-employee-time-clock' ); ?></label>
				</div>
				<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'Save Client', 'arc-employee-time-clock' ); ?></button>
			</form>

			<table class="min-w-full divide-y divide-slate-200 bg-white rounded-xl border border-slate-200">
				<thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Name', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Entity', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Billable', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Active', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Actions', 'arc-employee-time-clock' ); ?></th></tr></thead>
				<tbody class="divide-y divide-slate-200">
				<?php foreach ( $clients as $c ) : ?>
					<tr>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $c->name ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $c->entity ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo $c->billable_default ? esc_html__( 'Yes', 'arc-employee-time-clock' ) : esc_html__( 'No', 'arc-employee-time-clock' ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo $c->active ? esc_html__( 'Yes', 'arc-employee-time-clock' ) : esc_html__( 'No', 'arc-employee-time-clock' ); ?></td>
						<td class="px-4 py-3 text-sm"><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'arc-employee-time-clock-clients', 'edit' => $c->id ), admin_url( 'admin.php' ) ) ); ?>" class="text-blue-600 hover:underline"><?php esc_html_e( 'Edit', 'arc-employee-time-clock' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function render_activities_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$edit_id  = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
		$edit     = $edit_id ? Arc_ETC_Activities::get( $edit_id ) : null;
		$activities = Arc_ETC_Activities::get_all();
		?>
		<div class="wrap max-w-4xl mx-auto p-4">
			<h1 class="text-2xl font-bold text-slate-900 mb-6"><?php esc_html_e( 'Activities', 'arc-employee-time-clock' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=arc_etc_save_activity' ) ); ?>" class="bg-white rounded-xl border border-slate-200 p-5 mb-8 space-y-4">
				<?php wp_nonce_field( 'arc_etc_activity_nonce' ); ?>
				<?php if ( $edit_id ) : ?>
					<input type="hidden" name="id" value="<?php echo esc_attr( $edit_id ); ?>">
				<?php endif; ?>
				<input type="text" name="name" value="<?php echo esc_attr( $edit ? $edit->name : '' ); ?>" placeholder="<?php esc_attr_e( 'Activity name', 'arc-employee-time-clock' ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full" required>
				<div class="flex gap-6 text-sm">
					<label class="flex items-center gap-2"><input type="checkbox" name="billable_default" <?php checked( ! $edit || $edit->billable_default ); ?>> <?php esc_html_e( 'Billable by default', 'arc-employee-time-clock' ); ?></label>
					<label class="flex items-center gap-2"><input type="checkbox" name="paid" <?php checked( ! $edit || $edit->paid ); ?>> <?php esc_html_e( 'Counts toward paid hours', 'arc-employee-time-clock' ); ?></label>
					<label class="flex items-center gap-2"><input type="checkbox" name="active" <?php checked( ! $edit || $edit->active ); ?>> <?php esc_html_e( 'Active', 'arc-employee-time-clock' ); ?></label>
				</div>
				<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'Save Activity', 'arc-employee-time-clock' ); ?></button>
			</form>

			<table class="min-w-full divide-y divide-slate-200 bg-white rounded-xl border border-slate-200">
				<thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Name', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Billable', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Paid', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Active', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Actions', 'arc-employee-time-clock' ); ?></th></tr></thead>
				<tbody class="divide-y divide-slate-200">
				<?php foreach ( $activities as $a ) : ?>
					<tr>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $a->name ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo $a->billable_default ? esc_html__( 'Yes', 'arc-employee-time-clock' ) : esc_html__( 'No', 'arc-employee-time-clock' ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo $a->paid ? esc_html__( 'Yes', 'arc-employee-time-clock' ) : esc_html__( 'No', 'arc-employee-time-clock' ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo $a->active ? esc_html__( 'Yes', 'arc-employee-time-clock' ) : esc_html__( 'No', 'arc-employee-time-clock' ); ?></td>
						<td class="px-4 py-3 text-sm"><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'arc-employee-time-clock-activities', 'edit' => $a->id ), admin_url( 'admin.php' ) ) ); ?>" class="text-blue-600 hover:underline"><?php esc_html_e( 'Edit', 'arc-employee-time-clock' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function render_locked_weeks_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$locked = Arc_ETC_Locked_Weeks::get_all();
		?>
		<div class="wrap max-w-4xl mx-auto p-4">
			<h1 class="text-2xl font-bold text-slate-900 mb-6"><?php esc_html_e( 'Locked Weeks', 'arc-employee-time-clock' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=arc_etc_lock_week' ) ); ?>" class="bg-white rounded-xl border border-slate-200 p-5 mb-8 flex gap-4 items-end">
				<?php wp_nonce_field( 'arc_etc_lock_nonce' ); ?>
				<div class="flex-1">
					<label class="block text-xs font-semibold uppercase text-slate-500 mb-1"><?php esc_html_e( 'Week (Monday date YYYY-MM-DD)', 'arc-employee-time-clock' ); ?></label>
					<input type="text" name="week_key" value="<?php echo esc_attr( Arc_ETC_Locked_Weeks::week_key() ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full" required pattern="\d{4}-\d{2}-\d{2}">
				</div>
				<button type="submit" class="inline-flex items-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 transition"><?php esc_html_e( 'Lock Week', 'arc-employee-time-clock' ); ?></button>
			</form>

			<table class="min-w-full divide-y divide-slate-200 bg-white rounded-xl border border-slate-200">
				<thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Week Key', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Locked At', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Actions', 'arc-employee-time-clock' ); ?></th></tr></thead>
				<tbody class="divide-y divide-slate-200">
				<?php foreach ( $locked as $l ) : ?>
					<tr>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $l->week_key ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $l->locked_at ); ?></td>
						<td class="px-4 py-3 text-sm"><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'arc-employee-time-clock-locked', 'action' => 'arc_etc_unlock_week', 'id' => $l->id ), admin_url( 'admin.php' ) ), 'arc_etc_unlock_nonce' ) ); ?>" class="text-rose-600 hover:underline"><?php esc_html_e( 'Unlock', 'arc-employee-time-clock' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function render_payroll_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$month = isset( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : current_time( 'Y-m' );
		global $wpdb;
		$table = $wpdb->prefix . ARC_ETC_TABLE;
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, status, SUM(total_minutes) AS total_min, SUM(overtime_minutes) AS ot_min, SUM(CASE WHEN billable = 1 THEN total_minutes END) AS bill_min FROM {$table} WHERE month_key = %s GROUP BY user_id, status", $month ) );

		$users = array();
		foreach ( $rows as $r ) {
			if ( ! isset( $users[ $r->user_id ] ) ) {
				$user = get_userdata( $r->user_id );
				$users[ $r->user_id ] = array(
					'name'           => $user ? $user->display_name : '#' . $r->user_id,
					'rate'           => (float) get_user_meta( $r->user_id, 'arc_etc_hourly_rate', true ),
					'total'          => 0,
					'approved'       => 0,
					'pending'        => 0,
					'billable'       => 0,
					'overtime'       => 0,
				);
			}
			$hours = ( $r->total_min ?? 0 ) / 60;
			$users[ $r->user_id ]['total']    += $hours;
			$users[ $r->user_id ]['overtime'] += ( $r->ot_min ?? 0 ) / 60;
			$users[ $r->user_id ]['billable'] += ( $r->bill_min ?? 0 ) / 60;
			if ( 'approved' === $r->status ) {
				$users[ $r->user_id ]['approved'] += $hours;
			} else {
				$users[ $r->user_id ]['pending'] += $hours;
			}
		}
		?>
		<div class="wrap max-w-5xl mx-auto p-4">
			<h1 class="text-2xl font-bold text-slate-900 mb-6"><?php esc_html_e( 'Payroll', 'arc-employee-time-clock' ); ?></h1>
			<form method="get" class="mb-6 flex gap-3">
				<input type="hidden" name="page" value="arc-employee-time-clock-payroll">
				<input type="month" name="month" value="<?php echo esc_attr( $month ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
				<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'View', 'arc-employee-time-clock' ); ?></button>
			</form>

			<table class="min-w-full divide-y divide-slate-200 bg-white rounded-xl border border-slate-200">
				<thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Employee', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Rate', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Approved Hrs', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Pending Hrs', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Billable Hrs', 'arc-employee-time-clock' ); ?></th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500"><?php esc_html_e( 'Amount', 'arc-employee-time-clock' ); ?></th></tr></thead>
				<tbody class="divide-y divide-slate-200">
				<?php foreach ( $users as $u ) : ?>
					<tr>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $u['name'] ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( number_format( $u['rate'], 2 ) ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( number_format( $u['approved'], 2 ) ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( number_format( $u['pending'], 2 ) ); ?></td>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( number_format( $u['billable'], 2 ) ); ?></td>
						<td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( number_format( $u['approved'] * $u['rate'], 2 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function save_rules() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'arc_etc_rules_nonce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'arc-employee-time-clock' ) );
		}
		$settings = get_option( 'arc_etc_settings', array() );
		$settings['max_shift_hours']         = isset( $_POST['max_shift_hours'] ) ? (float) $_POST['max_shift_hours'] : 14;
		$settings['flag_daily_hours_over']   = isset( $_POST['flag_daily_hours_over'] ) ? (float) $_POST['flag_daily_hours_over'] : 10;
		$settings['weekly_variance_pct']     = isset( $_POST['weekly_variance_pct'] ) ? (float) $_POST['weekly_variance_pct'] : 20;
		$settings['round_minutes']           = isset( $_POST['round_minutes'] ) ? (int) $_POST['round_minutes'] : 15;
		$settings['week_starts_monday']      = isset( $_POST['week_starts_monday'] );
		$settings['required_notes_length']   = isset( $_POST['required_notes_length'] ) ? (int) $_POST['required_notes_length'] : 5;
		$settings['report_recipients']       = isset( $_POST['report_recipients'] ) ? sanitize_email( wp_unslash( $_POST['report_recipients'] ) ) : '';
		update_option( 'arc_etc_settings', $settings );
		wp_safe_redirect( admin_url( 'admin.php?page=arc-employee-time-clock-rules' ) );
		exit;
	}

	public static function render_rules_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = wp_parse_args(
			get_option( 'arc_etc_settings', array() ),
			array(
				'max_shift_hours'       => 14,
				'flag_daily_hours_over' => 10,
				'weekly_variance_pct'   => 20,
				'round_minutes'         => 15,
				'week_starts_monday'    => true,
				'required_notes_length' => 5,
				'report_recipients'     => '',
			)
		);
		?>
		<div class="wrap max-w-3xl mx-auto p-4">
			<h1 class="text-2xl font-bold text-slate-900 mb-6"><?php esc_html_e( 'IPC Time Clock Rules', 'arc-employee-time-clock' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=arc_etc_save_rules' ) ); ?>" class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
				<?php wp_nonce_field( 'arc_etc_rules_nonce' ); ?>
				<div class="grid md:grid-cols-2 gap-4">
					<div class="flex flex-col gap-1"><label class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Max shift hours (auto-close)', 'arc-employee-time-clock' ); ?></label><input type="number" step="0.5" name="max_shift_hours" value="<?php echo esc_attr( $settings['max_shift_hours'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm"></div>
					<div class="flex flex-col gap-1"><label class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Flag daily hours over', 'arc-employee-time-clock' ); ?></label><input type="number" step="0.5" name="flag_daily_hours_over" value="<?php echo esc_attr( $settings['flag_daily_hours_over'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm"></div>
					<div class="flex flex-col gap-1"><label class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Weekly variance %', 'arc-employee-time-clock' ); ?></label><input type="number" step="1" name="weekly_variance_pct" value="<?php echo esc_attr( $settings['weekly_variance_pct'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm"></div>
					<div class="flex flex-col gap-1"><label class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Round minutes', 'arc-employee-time-clock' ); ?></label><input type="number" step="1" name="round_minutes" value="<?php echo esc_attr( $settings['round_minutes'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm"></div>
					<div class="flex flex-col gap-1"><label class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Required notes length', 'arc-employee-time-clock' ); ?></label><input type="number" step="1" name="required_notes_length" value="<?php echo esc_attr( $settings['required_notes_length'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm"></div>
				</div>
				<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="week_starts_monday" <?php checked( $settings['week_starts_monday'] ); ?>> <?php esc_html_e( 'Week starts on Monday', 'arc-employee-time-clock' ); ?></label>
				<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'Save Rules', 'arc-employee-time-clock' ); ?></button>
			</form>
		</div>
		<?php
	}

	public static function import_csv() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'arc_etc_import_nonce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'arc-employee-time-clock' ) );
		}

		if ( ! isset( $_FILES['csv_file'] ) || empty( $_FILES['csv_file']['tmp_name'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=arc-employee-time-clock-import&error=1' ) );
			exit;
		}

		$handle = fopen( sanitize_text_field( wp_unslash( $_FILES['csv_file']['tmp_name'] ) ), 'r' );
		if ( ! $handle ) {
			wp_safe_redirect( admin_url( 'admin.php?page=arc-employee-time-clock-import&error=1' ) );
			exit;
		}

		$headers = fgetcsv( $handle );
		$map     = array(
			'employee'    => array_search( 'employee', array_map( 'strtolower', $headers ), true ),
			'date'        => array_search( 'date', array_map( 'strtolower', $headers ), true ),
			'start'       => array_search( 'start', array_map( 'strtolower', $headers ), true ),
			'end'         => array_search( 'end', array_map( 'strtolower', $headers ), true ),
			'client'      => array_search( 'client', array_map( 'strtolower', $headers ), true ),
			'activity'    => array_search( 'activity', array_map( 'strtolower', $headers ), true ),
			'project'     => array_search( 'project', array_map( 'strtolower', $headers ), true ),
			'task'        => array_search( 'task', array_map( 'strtolower', $headers ), true ),
			'tags'        => array_search( 'tags', array_map( 'strtolower', $headers ), true ),
			'notes'       => array_search( 'notes', array_map( 'strtolower', $headers ), true ),
			'break'       => array_search( 'break', array_map( 'strtolower', $headers ), true ),
		);

		$count = 0;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$user_email = $map['employee'] !== false ? sanitize_text_field( $row[ $map['employee'] ] ) : '';
			$user       = get_user_by( 'email', $user_email );
			if ( ! $user ) {
				$user = get_user_by( 'login', $user_email );
			}
			if ( ! $user ) {
				continue;
			}

			$date  = $map['date'] !== false ? sanitize_text_field( $row[ $map['date'] ] ) : '';
			$start = $map['start'] !== false ? sanitize_text_field( $row[ $map['start'] ] ) : '';
			$end   = $map['end'] !== false ? sanitize_text_field( $row[ $map['end'] ] ) : '';

			if ( ! $date || ! $start || ! $end ) {
				continue;
			}

			$tz       = wp_timezone();
			$start_dt = DateTime::createFromFormat( 'Y-m-d H:i', $date . ' ' . $start, $tz ) ?: DateTime::createFromFormat( 'Y-m-d g:i A', $date . ' ' . $start, $tz );
			$end_dt   = DateTime::createFromFormat( 'Y-m-d H:i', $date . ' ' . $end, $tz ) ?: DateTime::createFromFormat( 'Y-m-d g:i A', $date . ' ' . $end, $tz );
			if ( ! $start_dt || ! $end_dt ) {
				continue;
			}

			$manual = array(
				'entry_date'    => $start_dt->format( 'Y-m-d' ),
				'clock_in'      => $start_dt->format( 'Y-m-d\TH:i' ),
				'clock_out'     => $end_dt->format( 'Y-m-d\TH:i' ),
				'break_minutes' => $map['break'] !== false ? (int) $row[ $map['break'] ] : 0,
				'entry_type'    => 'regular',
				'client'        => $map['client'] !== false ? sanitize_text_field( $row[ $map['client'] ] ) : '',
				'activity'      => $map['activity'] !== false ? sanitize_text_field( $row[ $map['activity'] ] ) : '',
				'project'       => $map['project'] !== false ? sanitize_text_field( $row[ $map['project'] ] ) : '',
				'task'          => $map['task'] !== false ? sanitize_text_field( $row[ $map['task'] ] ) : '',
				'tags'          => $map['tags'] !== false ? sanitize_text_field( $row[ $map['tags'] ] ) : '',
				'billable'      => 1,
				'notes'         => $map['notes'] !== false ? sanitize_textarea_field( $row[ $map['notes'] ] ) : '',
			);
			Arc_ETC_Time_Entries::create_manual( $user->ID, $manual );
			$count++;
		}
		fclose( $handle );

		wp_safe_redirect( admin_url( 'admin.php?page=arc-employee-time-clock-import&imported=' . $count ) );
		exit;
	}

	public static function render_import_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$imported = isset( $_GET['imported'] ) ? (int) $_GET['imported'] : 0;
		?>
		<div class="wrap max-w-3xl mx-auto p-4">
			<h1 class="text-2xl font-bold text-slate-900 mb-6"><?php esc_html_e( 'Import CSV', 'arc-employee-time-clock' ); ?></h1>
			<?php if ( $imported ) : ?>
				<div class="mb-4 rounded-lg bg-emerald-50 text-emerald-700 p-3 text-sm font-medium"><?php printf( esc_html__( 'Imported %d entries.', 'arc-employee-time-clock' ), $imported ); ?></div>
			<?php endif; ?>
			<div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
				<p class="text-sm text-slate-600 mb-2"><?php esc_html_e( 'CSV columns: employee, date, start, end, client, activity, project, task, tags, notes, break', 'arc-employee-time-clock' ); ?></p>
				<p class="text-sm text-slate-600 mb-4"><?php esc_html_e( 'Date format YYYY-MM-DD, time format HH:MM or H:MM AM/PM. Employee can be email or username.', 'arc-employee-time-clock' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=arc_etc_import_csv' ) ); ?>" enctype="multipart/form-data" class="space-y-4">
					<?php wp_nonce_field( 'arc_etc_import_nonce' ); ?>
					<input type="file" name="csv_file" accept=".csv" required class="text-sm">
					<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'Import', 'arc-employee-time-clock' ); ?></button>
				</form>
			</div>
		</div>
		<?php
	}
}
