<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_arc_etc_admin_action', array( __CLASS__, 'ajax_admin_action' ) );
		add_action( 'admin_post_arc_etc_export_csv', array( __CLASS__, 'export_csv' ) );
	}

	public static function add_menu() {
		add_menu_page(
			__( 'Employee Time Clock', 'arc-employee-time-clock' ),
			__( 'Time Clock', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock',
			array( __CLASS__, 'render_reports_page' ),
			'dashicons-clock',
			26
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Reports', 'arc-employee-time-clock' ),
			__( 'Reports', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock',
			array( __CLASS__, 'render_reports_page' )
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Timesheet Review', 'arc-employee-time-clock' ),
			__( 'Timesheet Review', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-timesheet',
			array( __CLASS__, 'render_timesheet_page' )
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Holidays', 'arc-employee-time-clock' ),
			__( 'Holidays', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-holidays',
			array( __CLASS__, 'render_holidays_page' )
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Leave Requests', 'arc-employee-time-clock' ),
			__( 'Leave Requests', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-leave',
			array( __CLASS__, 'render_leave_page' )
		);

		add_submenu_page(
			'arc-employee-time-clock',
			__( 'Settings', 'arc-employee-time-clock' ),
			__( 'Settings', 'arc-employee-time-clock' ),
			'manage_options',
			'arc-employee-time-clock-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'arc-employee-time-clock' ) ) {
			return;
		}

		wp_enqueue_script(
			'arc-etc-tailwind-admin',
			'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4',
			array(),
			null,
			false
		);

		wp_enqueue_style(
			'arc-etc-admin',
			ARC_ETC_URL . 'assets/css/admin.css',
			array(),
			ARC_ETC_VERSION
		);

		wp_enqueue_script(
			'arc-etc-admin',
			ARC_ETC_URL . 'assets/js/admin.js',
			array( 'jquery', 'arc-etc-tailwind-admin' ),
			ARC_ETC_VERSION,
			true
		);

		wp_localize_script(
			'arc-etc-admin',
			'arcEtcAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'arc_etc_admin_action' ),
			)
		);
	}

	public static function render_reports_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : date_i18n( 'Y-m-d', strtotime( '-7 days' ) );
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : date_i18n( 'Y-m-d' );
		$user_id    = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;

		$args = array(
			'start_date' => $start_date,
			'end_date'   => $end_date,
			'user_id'    => $user_id,
		);
		$entries = Arc_ETC_Time_Entries::get_all( $args );

		$users = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );
		?>
		<div class="wrap max-w-7xl mx-auto p-6">
			<h1 class="text-2xl font-bold text-slate-900 mb-6"><?php esc_html_e( 'Time Clock Reports', 'arc-employee-time-clock' ); ?></h1>

			<form method="get" class="bg-white rounded-xl border border-slate-200 p-4 mb-6 flex flex-wrap gap-4 items-end">
				<input type="hidden" name="page" value="arc-employee-time-clock">

				<div class="flex flex-col gap-1">
					<label for="user_id" class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Employee', 'arc-employee-time-clock' ); ?></label>
					<select name="user_id" id="user_id" class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
						<option value="0"><?php esc_html_e( 'All employees', 'arc-employee-time-clock' ); ?></option>
						<?php foreach ( $users as $u ) : ?>
							<option value="<?php echo esc_attr( $u->ID ); ?>" <?php selected( $user_id, $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="flex flex-col gap-1">
					<label for="start_date" class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'From', 'arc-employee-time-clock' ); ?></label>
					<input type="date" name="start_date" id="start_date" value="<?php echo esc_attr( $start_date ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
				</div>

				<div class="flex flex-col gap-1">
					<label for="end_date" class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'To', 'arc-employee-time-clock' ); ?></label>
					<input type="date" name="end_date" id="end_date" value="<?php echo esc_attr( $end_date ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
				</div>

				<div class="flex gap-2">
					<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'Filter', 'arc-employee-time-clock' ); ?></button>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=arc_etc_export_csv&start_date=' . urlencode( $start_date ) . '&end_date=' . urlencode( $end_date ) . '&user_id=' . $user_id ), 'arc_etc_export_csv' ) ); ?>" class="inline-flex items-center rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition"><?php esc_html_e( 'Export CSV', 'arc-employee-time-clock' ); ?></a>
				</div>
			</form>

			<div class="overflow-hidden rounded-xl border border-slate-200">
				<table class="min-w-full divide-y divide-slate-200">
					<thead class="bg-slate-50">
						<tr>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Employee', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Date', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Clock In', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Clock Out', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Break', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Regular', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Overtime', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Total', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Type', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Status', 'arc-employee-time-clock' ); ?></th>
							<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Notes', 'arc-employee-time-clock' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-200 bg-white">
						<?php if ( empty( $entries ) ) : ?>
							<tr><td colspan="11" class="px-4 py-6 text-center text-slate-500"><?php esc_html_e( 'No entries found.', 'arc-employee-time-clock' ); ?></td></tr>
						<?php else : ?>
							<?php
							$grand_total = 0; $grand_regular = 0; $grand_overtime = 0;
							foreach ( $entries as $entry ) :
								$regular = max( 0, $entry->total_minutes - $entry->overtime_minutes ); $grand_total += $entry->total_minutes; $grand_regular += $regular; $grand_overtime += $entry->overtime_minutes;
								$user_info = get_userdata( $entry->user_id );
								?>
								<tr class="hover:bg-slate-50 transition">
									<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $user_info ? $user_info->display_name : '#' . $entry->user_id ); ?></td>
									<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $entry->entry_date ); ?></td>
									<td class="px-4 py-3 text-sm text-slate-700"><?php echo $entry->clock_in ? esc_html( mysql2date( 'g:i A', $entry->clock_in ) ) : '-'; ?></td>
									<td class="px-4 py-3 text-sm text-slate-700"><?php echo $entry->clock_out ? esc_html( mysql2date( 'g:i A', $entry->clock_out ) ) : '-'; ?></td>
									<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( Arc_ETC_Public::format_minutes( $entry->break_minutes ) ); ?></td>
									<td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( Arc_ETC_Public::format_minutes( max( 0, $entry->total_minutes - $entry->overtime_minutes ) ) ); ?></td>
									<td class="px-4 py-3 text-sm text-rose-600"><?php echo $entry->overtime_minutes ? esc_html( Arc_ETC_Public::format_minutes( $entry->overtime_minutes ) ) : '-'; ?></td>
									<td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( Arc_ETC_Public::format_minutes( $entry->total_minutes ) ); ?></td>
									<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( ucfirst( $entry->entry_type ?? '' ) ); ?></td>
									<td class="px-4 py-3 text-sm"><?php self::status_badge( $entry->status ?? '' ); ?></td>
									<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( $entry->notes ? $entry->notes : '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
					<?php if ( ! empty( $entries ) ) : ?>
						<tfoot class="bg-slate-50">
							<tr>
								<td colspan="5" class="px-4 py-3 text-right text-sm font-semibold text-slate-700"><?php esc_html_e( 'Grand total', 'arc-employee-time-clock' ); ?></td>
								<td class="px-4 py-3 text-sm font-bold text-slate-900"><?php echo esc_html( Arc_ETC_Public::format_minutes( $grand_regular ) ); ?></td>
								<td class="px-4 py-3 text-sm font-bold text-rose-600"><?php echo esc_html( Arc_ETC_Public::format_minutes( $grand_overtime ) ); ?></td>
								<td class="px-4 py-3 text-sm font-bold text-slate-900"><?php echo esc_html( Arc_ETC_Public::format_minutes( $grand_total ) ); ?></td>
								<td colspan="3"></td>
							</tr>
						</tfoot>
					<?php endif; ?>
				</table>
			</div>
		</div>
		<?php
	}

	public static function render_timesheet_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$week_start = isset( $_GET['week_start'] ) ? sanitize_text_field( wp_unslash( $_GET['week_start'] ) ) : date_i18n( 'Y-m-d', strtotime( 'monday this week' ) );
		$week_end   = date_i18n( 'Y-m-d', strtotime( $week_start . ' +6 days' ) );
		$user_id    = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;

		$entries = array();
		if ( $user_id ) {
			$entries = Arc_ETC_Time_Entries::get_by_user( $user_id, $week_start, $week_end );
		}

		$users = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );

		$week_days = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$week_days[] = date_i18n( 'Y-m-d', strtotime( $week_start . ' +' . $i . ' days' ) );
		}

		$by_day = array();
		foreach ( $entries as $e ) {
			$by_day[ $e->entry_date ][] = $e;
		}

		?>
		<div class="wrap max-w-7xl mx-auto p-6">
			<h1 class="text-2xl font-bold text-slate-900 mb-6"><?php esc_html_e( 'Timesheet Review', 'arc-employee-time-clock' ); ?></h1>

			<form method="get" class="bg-white rounded-xl border border-slate-200 p-4 mb-6 flex flex-wrap gap-4 items-end">
				<input type="hidden" name="page" value="arc-employee-time-clock-timesheet">

				<div class="flex flex-col gap-1">
					<label for="user_id" class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Employee', 'arc-employee-time-clock' ); ?></label>
					<select name="user_id" id="user_id" class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
						<option value="0"><?php esc_html_e( 'Select employee', 'arc-employee-time-clock' ); ?></option>
						<?php foreach ( $users as $u ) : ?>
							<option value="<?php echo esc_attr( $u->ID ); ?>" <?php selected( $user_id, $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="flex flex-col gap-1">
					<label for="week_start" class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Week start', 'arc-employee-time-clock' ); ?></label>
					<input type="date" name="week_start" id="week_start" value="<?php echo esc_attr( $week_start ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
				</div>

				<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'View', 'arc-employee-time-clock' ); ?></button>
			</form>

			<?php if ( $user_id ) : ?>
				<h2 class="text-lg font-semibold text-slate-700 mb-4"><?php printf( esc_html__( 'Week: %s to %s', 'arc-employee-time-clock' ), esc_html( $week_start ), esc_html( $week_end ) ); ?></h2>
				<div class="overflow-hidden rounded-xl border border-slate-200">
					<table class="min-w-full divide-y divide-slate-200">
						<thead class="bg-slate-50">
							<tr>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Day', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Clock In', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Clock Out', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Break', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Regular', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Overtime', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Total', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Type', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Status', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Actions', 'arc-employee-time-clock' ); ?></th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-200 bg-white">
							<?php foreach ( $week_days as $day ) :
								$day_entries = isset( $by_day[ $day ] ) ? $by_day[ $day ] : array();
								if ( empty( $day_entries ) ) :
									?>
									<tr class="hover:bg-slate-50 transition">
										<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( mysql2date( 'D, M j', $day . ' 12:00:00' ) ); ?></td>
										<td colspan="9" class="px-4 py-3 text-sm text-slate-500"><?php esc_html_e( 'No entries', 'arc-employee-time-clock' ); ?></td>
									</tr>
								<?php else : ?>
									<?php foreach ( $day_entries as $e ) : ?>
										<tr data-entry="<?php echo esc_attr( $e->id ); ?>" class="hover:bg-slate-50 transition">
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( mysql2date( 'D, M j', $day . ' 12:00:00' ) ); ?></td>
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo $e->clock_in ? esc_html( mysql2date( 'g:i A', $e->clock_in ) ) : '-'; ?></td>
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo $e->clock_out ? esc_html( mysql2date( 'g:i A', $e->clock_out ) ) : '-'; ?></td>
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( Arc_ETC_Public::format_minutes( $e->break_minutes ) ); ?></td>
											<td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( Arc_ETC_Public::format_minutes( max( 0, $e->total_minutes - $e->overtime_minutes ) ) ); ?></td>
											<td class="px-4 py-3 text-sm text-rose-600"><?php echo $e->overtime_minutes ? esc_html( Arc_ETC_Public::format_minutes( $e->overtime_minutes ) ) : '-'; ?></td>
											<td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( Arc_ETC_Public::format_minutes( $e->total_minutes ) ); ?></td>
											<td class="px-4 py-3 text-sm text-slate-700">
												<select class="arc-entry-type border border-slate-200 rounded-lg px-2 py-1 text-sm bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" data-field="entry_type">
													<?php foreach ( self::entry_types() as $key => $label ) : ?>
														<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $e->entry_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
													<?php endforeach; ?>
												</select>
											</td>
											<td class="px-4 py-3 text-sm"><?php self::status_badge( $e->status ); ?></td>
											<td class="px-4 py-3 text-sm">
												<?php if ( 'pending' === $e->status || 'submitted' === $e->status ) : ?>
													<button type="button" class="arc-approve-entry inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 transition mr-2" data-id="<?php echo esc_attr( $e->id ); ?>"><?php esc_html_e( 'Approve', 'arc-employee-time-clock' ); ?></button>
												<?php endif; ?>
												<button type="button" class="arc-delete-entry inline-flex items-center rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 transition" data-id="<?php echo esc_attr( $e->id ); ?>"><?php esc_html_e( 'Delete', 'arc-employee-time-clock' ); ?></button>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = get_option( 'arc_etc_settings', array() );
		$defaults = array(
			'overtime_daily_threshold'  => 8,
			'overtime_weekly_threshold' => 40,
			'overtime_multiplier'       => 1.5,
			'allowed_roles'             => array( 'administrator', 'editor', 'author', 'subscriber' ),
			'pto_enabled'               => true,
			'pto_default_hours'         => 80,
			'time_format'               => 'g:i A',
			'delete_data_on_uninstall'  => false,
			'max_shift_hours'           => 14,
			'flag_daily_hours_over'     => 10,
			'weekly_variance_pct'       => 20,
			'round_minutes'             => 15,
			'week_starts_monday'        => true,
			'required_notes_length'     => 5,
		);
		$settings = wp_parse_args( $settings, $defaults );

		if ( isset( $_POST['arc_etc_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arc_etc_settings_nonce'] ) ), 'arc_etc_save_settings' ) ) {
			$allowed_roles = isset( $_POST['allowed_roles'] ) && is_array( $_POST['allowed_roles'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['allowed_roles'] ) ) : array();
			$settings['overtime_daily_threshold']  = isset( $_POST['overtime_daily_threshold'] ) ? (float) $_POST['overtime_daily_threshold'] : 8;
			$settings['overtime_weekly_threshold'] = isset( $_POST['overtime_weekly_threshold'] ) ? (float) $_POST['overtime_weekly_threshold'] : 40;
			$settings['overtime_multiplier']     = isset( $_POST['overtime_multiplier'] ) ? (float) $_POST['overtime_multiplier'] : 1.5;
			$settings['pto_enabled']             = isset( $_POST['pto_enabled'] );
			$settings['pto_default_hours']       = isset( $_POST['pto_default_hours'] ) ? (float) $_POST['pto_default_hours'] : 80;
			$settings['time_format']             = isset( $_POST['time_format'] ) ? sanitize_text_field( wp_unslash( $_POST['time_format'] ) ) : 'g:i A';
			$settings['allowed_roles']           = $allowed_roles;
			$settings['delete_data_on_uninstall'] = isset( $_POST['delete_data_on_uninstall'] );
			$settings['max_shift_hours']           = isset( $_POST['max_shift_hours'] ) ? (float) $_POST['max_shift_hours'] : 14;
			$settings['flag_daily_hours_over']   = isset( $_POST['flag_daily_hours_over'] ) ? (float) $_POST['flag_daily_hours_over'] : 10;
			$settings['weekly_variance_pct']     = isset( $_POST['weekly_variance_pct'] ) ? (float) $_POST['weekly_variance_pct'] : 20;
			$settings['round_minutes']           = isset( $_POST['round_minutes'] ) ? (int) $_POST['round_minutes'] : 15;
			$settings['week_starts_monday']      = isset( $_POST['week_starts_monday'] );
			$settings['required_notes_length']   = isset( $_POST['required_notes_length'] ) ? (int) $_POST['required_notes_length'] : 5;

			update_option( 'arc_etc_settings', $settings );
			echo '<div class="max-w-3xl mx-auto mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-700 font-medium">' . esc_html__( 'Settings saved.', 'arc-employee-time-clock' ) . '</div>';
		}

		$roles = get_editable_roles();
		?>
		<div class="wrap max-w-3xl mx-auto p-6">
			<h1 class="text-2xl font-bold text-slate-900 mb-6"><?php esc_html_e( 'Time Clock Settings', 'arc-employee-time-clock' ); ?></h1>

			<form method="post" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8 space-y-6">
				<?php wp_nonce_field( 'arc_etc_save_settings', 'arc_etc_settings_nonce' ); ?>

				<div class="grid gap-4 md:grid-cols-2">
					<div class="flex flex-col gap-1">
						<label for="overtime_daily_threshold" class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Daily overtime threshold (hours)', 'arc-employee-time-clock' ); ?></label>
						<input type="number" step="0.5" name="overtime_daily_threshold" id="overtime_daily_threshold" value="<?php echo esc_attr( $settings['overtime_daily_threshold'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
					</div>

					<div class="flex flex-col gap-1">
						<label for="overtime_weekly_threshold" class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Weekly overtime threshold (hours)', 'arc-employee-time-clock' ); ?></label>
						<input type="number" step="0.5" name="overtime_weekly_threshold" id="overtime_weekly_threshold" value="<?php echo esc_attr( $settings['overtime_weekly_threshold'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
					</div>

					<div class="flex flex-col gap-1">
						<label for="overtime_multiplier" class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Overtime multiplier', 'arc-employee-time-clock' ); ?></label>
						<input type="number" step="0.1" name="overtime_multiplier" id="overtime_multiplier" value="<?php echo esc_attr( $settings['overtime_multiplier'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
					</div>

					<div class="flex flex-col gap-1">
						<label for="time_format" class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Time format', 'arc-employee-time-clock' ); ?></label>
						<input type="text" name="time_format" id="time_format" value="<?php echo esc_attr( $settings['time_format'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
					</div>
				</div>

				<div class="flex flex-col gap-1">
					<label for="pto_default_hours" class="text-sm font-semibold text-slate-700"><?php esc_html_e( 'Default annual PTO hours', 'arc-employee-time-clock' ); ?></label>
					<input type="number" step="0.5" name="pto_default_hours" id="pto_default_hours" value="<?php echo esc_attr( $settings['pto_default_hours'] ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full md:w-1/2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none">
				</div>

				<div>
					<span class="block text-sm font-semibold text-slate-700 mb-2"><?php esc_html_e( 'Allowed roles', 'arc-employee-time-clock' ); ?></span>
					<div class="grid gap-2 md:grid-cols-2">
						<?php foreach ( $roles as $key => $role ) : ?>
							<label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
								<input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $settings['allowed_roles'], true ) ); ?> class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
								<span class="text-sm text-slate-700"><?php echo esc_html( translate_user_role( $role['name'] ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="flex flex-col gap-4">
					<label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
						<input type="checkbox" name="pto_enabled" <?php checked( $settings['pto_enabled'] ); ?> class="w-5 h-5 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
						<span class="text-sm text-slate-700"><?php esc_html_e( 'Enable PTO / Vacation tracking', 'arc-employee-time-clock' ); ?></span>
					</label>

					<label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
						<input type="checkbox" name="delete_data_on_uninstall" <?php checked( $settings['delete_data_on_uninstall'] ); ?> class="w-5 h-5 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
						<span class="text-sm text-slate-700"><?php esc_html_e( 'Delete all plugin data on uninstall', 'arc-employee-time-clock' ); ?></span>
					</label>
				</div>

				<div class="pt-4">
					<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'Save Settings', 'arc-employee-time-clock' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	public static function ajax_admin_action() {
		check_ajax_referer( 'arc_etc_admin_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'arc-employee-time-clock' ) ) );
		}

		$action   = isset( $_POST['do'] ) ? sanitize_text_field( wp_unslash( $_POST['do'] ) ) : '';
		$entry_id = isset( $_POST['entry_id'] ) ? (int) $_POST['entry_id'] : 0;

		switch ( $action ) {
			case 'approve':
				Arc_ETC_Time_Entries::approve( $entry_id, get_current_user_id() );
				Arc_ETC_Time_Entries::recalculate( $entry_id );
				wp_send_json_success( array( 'message' => __( 'Entry approved.', 'arc-employee-time-clock' ) ) );
				break;

			case 'reject':
				Arc_ETC_Time_Entries::reject( $entry_id, get_current_user_id() );
				wp_send_json_success( array( 'message' => __( 'Entry rejected.', 'arc-employee-time-clock' ) ) );
				break;

			case 'update_type':
				$type = isset( $_POST['entry_type'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_type'] ) ) : 'regular';
				Arc_ETC_Time_Entries::update_entry( $entry_id, array( 'entry_type' => $type ) );
				wp_send_json_success( array( 'message' => __( 'Type updated.', 'arc-employee-time-clock' ) ) );
				break;

			case 'delete':
				Arc_ETC_Time_Entries::delete( $entry_id );
				wp_send_json_success( array( 'message' => __( 'Entry deleted.', 'arc-employee-time-clock' ) ) );
				break;
			case 'add_holiday':
				$name = isset( $_POST['holiday_name'] ) ? sanitize_text_field( wp_unslash( $_POST['holiday_name'] ) ) : '';
				$date = isset( $_POST['holiday_date'] ) ? sanitize_text_field( wp_unslash( $_POST['holiday_date'] ) ) : '';
				$recurring = isset( $_POST['holiday_recurring'] ) ? 1 : 0;
				if ( empty( $name ) || empty( $date ) ) {
					wp_send_json_error( array( 'message' => __( 'Name and date are required.', 'arc-employee-time-clock' ) ) );
				}
				Arc_ETC_Holidays::add( $name, $date, $recurring );
				wp_send_json_success( array( 'message' => __( 'Holiday added.', 'arc-employee-time-clock' ) ) );
				break;
			case 'delete_holiday':
				$holiday_id = isset( $_POST['holiday_id'] ) ? (int) $_POST['holiday_id'] : 0;
				Arc_ETC_Holidays::delete( $holiday_id );
				wp_send_json_success( array( 'message' => __( 'Holiday deleted.', 'arc-employee-time-clock' ) ) );
				break;
			case 'approve_leave':
				$leave_id = isset( $_POST['leave_id'] ) ? (int) $_POST['leave_id'] : 0;
				Arc_ETC_Leave::update_status( $leave_id, 'approved', get_current_user_id() );
				wp_send_json_success( array( 'message' => __( 'Leave approved.', 'arc-employee-time-clock' ) ) );
				break;
			case 'reject_leave':
				$leave_id = isset( $_POST['leave_id'] ) ? (int) $_POST['leave_id'] : 0;
				Arc_ETC_Leave::update_status( $leave_id, 'rejected', get_current_user_id() );
				wp_send_json_success( array( 'message' => __( 'Leave rejected.', 'arc-employee-time-clock' ) ) );
				break;
			case 'delete_leave':
				$leave_id = isset( $_POST['leave_id'] ) ? (int) $_POST['leave_id'] : 0;
				Arc_ETC_Leave::delete( $leave_id );
				wp_send_json_success( array( 'message' => __( 'Leave deleted.', 'arc-employee-time-clock' ) ) );
				break;
		}

		wp_send_json_error( array( 'message' => __( 'Unknown action.', 'arc-employee-time-clock' ) ) );
	}

	public static function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'arc-employee-time-clock' ) );
		}

		check_admin_referer( 'arc_etc_export_csv' );

		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : date_i18n( 'Y-m-d', strtotime( '-7 days' ) );
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : date_i18n( 'Y-m-d' );
		$user_id    = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;

		$entries = Arc_ETC_Time_Entries::get_all(
			array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'user_id'    => $user_id,
			)
		);

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=employee-timeclock-report-' . $start_date . '-' . $end_date . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv(
			$output,
			array(
				__( 'Employee', 'arc-employee-time-clock' ),
				__( 'Date', 'arc-employee-time-clock' ),
				__( 'Clock In', 'arc-employee-time-clock' ),
				__( 'Clock Out', 'arc-employee-time-clock' ),
				__( 'Break Minutes', 'arc-employee-time-clock' ),
				__( 'Regular Minutes', 'arc-employee-time-clock' ),
				__( 'Overtime Minutes', 'arc-employee-time-clock' ),
				__( 'Total Minutes', 'arc-employee-time-clock' ),
				__( 'Client', 'arc-employee-time-clock' ),
				__( 'Activity', 'arc-employee-time-clock' ),
				__( 'Project', 'arc-employee-time-clock' ),
				__( 'Task', 'arc-employee-time-clock' ),
				__( 'Tags', 'arc-employee-time-clock' ),
				__( 'Billable', 'arc-employee-time-clock' ),
				__( 'Type', 'arc-employee-time-clock' ),
				__( 'Status', 'arc-employee-time-clock' ),
				__( 'Flags', 'arc-employee-time-clock' ),
				__( 'Notes', 'arc-employee-time-clock' ),
			)
		);

		foreach ( $entries as $entry ) {
			$user = get_userdata( $entry->user_id );
			fputcsv(
				$output,
				array(
					$user ? $user->display_name : '#' . $entry->user_id,
					$entry->entry_date,
					$entry->clock_in,
					$entry->clock_out,
					$entry->break_minutes,
					max( 0, $entry->total_minutes - $entry->overtime_minutes ),
					$entry->overtime_minutes,
					$entry->total_minutes,
					$entry->client,
					$entry->activity,
					$entry->project,
					$entry->task,
					$entry->tags,
					$entry->billable ? __( 'Yes', 'arc-employee-time-clock' ) : __( 'No', 'arc-employee-time-clock' ),
					$entry->entry_type,
					$entry->status,
					$entry->flags,
					$entry->notes,
				)
			);
		}

		fclose( $output );
		exit;
	}

	public static function entry_types() {
		return array(
			'regular'  => __( 'Regular', 'arc-employee-time-clock' ),
			'overtime' => __( 'Overtime', 'arc-employee-time-clock' ),
			'pto'      => __( 'PTO', 'arc-employee-time-clock' ),
			'vacation' => __( 'Vacation', 'arc-employee-time-clock' ),
			'holiday'  => __( 'Holiday', 'arc-employee-time-clock' ),
			'remote'   => __( 'Remote', 'arc-employee-time-clock' ),
		);
	}

	public static function status_badge( $status ) {
		$status = (string) $status;
		$classes = array(
			'draft'     => 'bg-amber-100 text-amber-700',
			'open'      => 'bg-emerald-100 text-emerald-700',
			'paused'    => 'bg-amber-100 text-amber-700',
			'pending'   => 'bg-blue-100 text-blue-700',
			'submitted' => 'bg-blue-100 text-blue-700',
			'approved'  => 'bg-emerald-100 text-emerald-700',
			'rejected'  => 'bg-rose-100 text-rose-700',
		);
		$class = isset( $classes[ $status ] ) ? $classes[ $status ] : 'bg-slate-100 text-slate-700';
		?>
		<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo esc_attr( $class ); ?>">
			<?php echo esc_html( ucfirst( $status ) ); ?>
		</span>
		<?php
	}

	public static function render_holidays_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['arc_etc_holiday_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arc_etc_holiday_nonce'] ) ), 'arc_etc_holiday_nonce' ) ) {
			if ( isset( $_POST['holiday_name'], $_POST['holiday_date'] ) ) {
				Arc_ETC_Holidays::add(
					sanitize_text_field( wp_unslash( $_POST['holiday_name'] ) ),
					sanitize_text_field( wp_unslash( $_POST['holiday_date'] ) ),
					isset( $_POST['holiday_recurring'] ) ? 1 : 0
				);
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Holiday saved.', 'arc-employee-time-clock' ) . '</p></div>';
			}
			if ( isset( $_POST['delete_holiday'] ) ) {
				Arc_ETC_Holidays::delete( (int) $_POST['delete_holiday'] );
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Holiday deleted.', 'arc-employee-time-clock' ) . '</p></div>';
			}
		}

		$holidays = Arc_ETC_Holidays::get_all();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Holidays', 'arc-employee-time-clock' ); ?></h1>
			<form method="post" class="arc-etc-card" style="max-width:480px;margin:20px 0;">
				<?php wp_nonce_field( 'arc_etc_holiday_nonce', 'arc_etc_holiday_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="holiday_name"><?php esc_html_e( 'Name', 'arc-employee-time-clock' ); ?></label></th>
						<td><input type="text" id="holiday_name" name="holiday_name" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="holiday_date"><?php esc_html_e( 'Date', 'arc-employee-time-clock' ); ?></label></th>
						<td><input type="date" id="holiday_date" name="holiday_date" required></td>
					</tr>
					<tr>
						<th></th>
						<td><label><input type="checkbox" name="holiday_recurring" value="1"> <?php esc_html_e( 'Recurring every year', 'arc-employee-time-clock' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button( __( 'Add Holiday', 'arc-employee-time-clock' ), 'primary' ); ?>
			</form>

			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'Name', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'Recurring', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'arc-employee-time-clock' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $holidays as $h ) : ?>
					<tr>
						<td><?php echo esc_html( $h->holiday_date ); ?></td>
						<td><?php echo esc_html( $h->name ); ?></td>
						<td><?php echo $h->recurring ? esc_html__( 'Yes', 'arc-employee-time-clock' ) : esc_html__( 'No', 'arc-employee-time-clock' ); ?></td>
						<td>
							<form method="post" style="display:inline;">
								<?php wp_nonce_field( 'arc_etc_holiday_nonce', 'arc_etc_holiday_nonce' ); ?>
								<input type="hidden" name="delete_holiday" value="<?php echo esc_attr( $h->id ); ?>">
								<?php submit_button( __( 'Delete', 'arc-employee-time-clock' ), 'small', 'submit', false ); ?>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function render_leave_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['arc_etc_leave_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arc_etc_leave_nonce'] ) ), 'arc_etc_leave_nonce' ) ) {
			if ( isset( $_POST['leave_id'], $_POST['leave_action'] ) ) {
				$action = sanitize_text_field( wp_unslash( $_POST['leave_action'] ) );
				if ( in_array( $action, array( 'approved', 'rejected' ), true ) ) {
					Arc_ETC_Leave::update_status( (int) $_POST['leave_id'], $action, get_current_user_id() );
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Request updated.', 'arc-employee-time-clock' ) . '</p></div>';
				}
			}
			if ( isset( $_POST['delete_leave'] ) ) {
				Arc_ETC_Leave::delete( (int) $_POST['delete_leave'] );
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Request deleted.', 'arc-employee-time-clock' ) . '</p></div>';
			}
		}

		$requests = Arc_ETC_Leave::get_all();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Leave Requests', 'arc-employee-time-clock' ); ?></h1>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Employee', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'Type', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'Start', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'End', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'Hours', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'Notes', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'Status', 'arc-employee-time-clock' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'arc-employee-time-clock' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $requests as $r ) :
					$user = get_userdata( $r->user_id );
				?>
					<tr>
						<td><?php echo esc_html( $user ? $user->display_name : '#' . $r->user_id ); ?></td>
						<td><?php echo esc_html( ucfirst( $r->request_type ?? '' ) ); ?></td>
						<td><?php echo esc_html( $r->start_date ?? '' ); ?></td>
						<td><?php echo esc_html( $r->end_date ?? '' ); ?></td>
						<td><?php echo esc_html( $r->hours ?? '' ); ?></td>
						<td><?php echo esc_html( $r->notes ? $r->notes : '' ); ?></td>
						<td><?php self::status_badge( $r->status ?? '' ); ?></td>
						<td>
							<?php if ( 'pending' === ( $r->status ?? '' ) ) : ?>
							<form method="post" style="display:inline;">
								<?php wp_nonce_field( 'arc_etc_leave_nonce', 'arc_etc_leave_nonce' ); ?>
								<input type="hidden" name="leave_id" value="<?php echo esc_attr( $r->id ); ?>">
								<input type="hidden" name="leave_action" value="approved">
								<?php submit_button( __( 'Approve', 'arc-employee-time-clock' ), 'primary small', 'submit', false ); ?>
							</form>
							<form method="post" style="display:inline;">
								<?php wp_nonce_field( 'arc_etc_leave_nonce', 'arc_etc_leave_nonce' ); ?>
								<input type="hidden" name="leave_id" value="<?php echo esc_attr( $r->id ); ?>">
								<input type="hidden" name="leave_action" value="rejected">
								<?php submit_button( __( 'Reject', 'arc-employee-time-clock' ), 'secondary small', 'submit', false ); ?>
							</form>
							<?php endif; ?>
							<form method="post" style="display:inline;">
								<?php wp_nonce_field( 'arc_etc_leave_nonce', 'arc_etc_leave_nonce' ); ?>
								<input type="hidden" name="delete_leave" value="<?php echo esc_attr( $r->id ); ?>">
								<?php submit_button( __( 'Delete', 'arc-employee-time-clock' ), 'small', 'submit', false ); ?>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
