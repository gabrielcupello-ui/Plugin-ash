<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Public {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_shortcode( 'arc_etc_clock', array( __CLASS__, 'render_clock_shortcode' ) );
		add_shortcode( 'arc_etc_timesheet', array( __CLASS__, 'render_timesheet_shortcode' ) );

		add_action( 'wp_ajax_arc_etc_action', array( __CLASS__, 'ajax_action' ) );
		add_action( 'wp_ajax_nopriv_arc_etc_action', array( __CLASS__, 'ajax_no_priv' ) );
	}

	public static function enqueue_assets() {
		global $post;
		if ( ! is_singular() || ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		$shortcodes = array( 'arc_etc_clock', 'arc_etc_timesheet' );
		$has        = false;
		foreach ( $shortcodes as $sc ) {
			if ( has_shortcode( $post->post_content, $sc ) ) {
				$has = true;
				break;
			}
		}
		if ( ! $has ) {
			return;
		}

		wp_enqueue_script(
			'arc-etc-tailwind',
			'https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4',
			array(),
			null,
			false
		);

		wp_enqueue_style(
			'arc-etc-public',
			ARC_ETC_URL . 'assets/css/time-clock.css',
			array(),
			ARC_ETC_VERSION
		);

		wp_enqueue_script(
			'arc-etc-public',
			ARC_ETC_URL . 'assets/js/time-clock.js',
			array( 'jquery', 'arc-etc-tailwind' ),
			ARC_ETC_VERSION,
			true
		);

		$clients    = class_exists( 'Arc_ETC_Clients' ) ? Arc_ETC_Clients::get_all( true ) : array();
		$activities = class_exists( 'Arc_ETC_Activities' ) ? Arc_ETC_Activities::get_all( true ) : array();

		wp_localize_script(
			'arc-etc-public',
			'arcEtc',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'arc_etc_action' ),
				'clients' => wp_list_pluck( $clients, 'name' ),
				'activities' => wp_list_pluck( $activities, 'name' ),
				'i18n'    => array(
					'clockIn'          => __( 'Clock In', 'arc-employee-time-clock' ),
					'clockOut'         => __( 'Clock Out', 'arc-employee-time-clock' ),
					'pause'            => __( 'Pause', 'arc-employee-time-clock' ),
					'resume'           => __( 'Resume', 'arc-employee-time-clock' ),
					'current'          => __( 'Current session', 'arc-employee-time-clock' ),
					'locationBlocked'  => __( 'Location access was denied.', 'arc-employee-time-clock' ),
					'confirmClockOut'  => __( 'Are you sure you want to clock out?', 'arc-employee-time-clock' ),
					'statusIn'         => __( 'You are clocked in.', 'arc-employee-time-clock' ),
					'statusBreak'      => __( 'You are paused.', 'arc-employee-time-clock' ),
					'statusOut'        => __( 'You are clocked out.', 'arc-employee-time-clock' ),
					'client'           => __( 'Client / Company', 'arc-employee-time-clock' ),
					'activity'         => __( 'Activity', 'arc-employee-time-clock' ),
				),
			)
		);
	}

	public static function render_clock_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="text-center text-slate-600 py-8">' . esc_html__( 'Please log in to use the time clock.', 'arc-employee-time-clock' ) . '</p>';
		}

		if ( ! self::user_can_clock() ) {
			return '<p class="text-center text-slate-600 py-8">' . esc_html__( 'You do not have permission to use the time clock.', 'arc-employee-time-clock' ) . '</p>';
		}

		$user_id = get_current_user_id();
		$entry   = Arc_ETC_Time_Entries::get_open( $user_id );

		$status   = 'out';
		$entry_id = 0;
		if ( $entry ) {
			$entry_id = $entry->id;
			$status   = ( 'paused' === $entry->status ) ? 'paused' : 'in';
		}

		$dot_class = 'out' === $status ? 'bg-slate-400' : 'bg-emerald-500 ring-4 ring-emerald-500/25';

		ob_start();
		?>
		<div class="arc-etc-wrap max-w-2xl mx-auto p-4" data-status="<?php echo esc_attr( $status ); ?>" data-entry="<?php echo esc_attr( $entry_id ); ?>">
			<header class="flex items-center gap-4 mb-6">
				<div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-900 to-blue-600 flex items-center justify-center text-white font-bold text-xl">
					ARC
				</div>
				<div>
					<h1 class="text-xl font-bold text-slate-900 leading-tight"><?php esc_html_e( 'Employee Time Clock', 'arc-employee-time-clock' ); ?></h1>
					<span class="text-sm text-slate-500"><?php esc_html_e( 'Clockify-style timer', 'arc-employee-time-clock' ); ?></span>
				</div>
			</header>

			<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
				<div class="text-center mb-6">
					<h2 class="text-lg font-semibold text-slate-700 mb-2"><?php esc_html_e( 'Current session', 'arc-employee-time-clock' ); ?></h2>
					<div id="arc-etc-live-time" class="text-6xl font-bold tracking-tight text-slate-900 mb-1">--:--:--</div>
					<div id="arc-etc-live-date" class="text-sm text-slate-500">--</div>
				</div>

				<div id="arc-etc-status" class="flex items-center justify-center gap-2 text-sm font-medium text-slate-600 mb-4">
					<span id="arc-etc-dot" class="dot w-2.5 h-2.5 rounded-full <?php echo esc_attr( $dot_class ); ?>"></span>
					<span id="arc-etc-status-text">
					<?php
					if ( 'in' === $status ) {
						esc_html_e( 'You are clocked in.', 'arc-employee-time-clock' );
					} elseif ( 'paused' === $status || 'break' === $status ) {
						esc_html_e( 'You are paused.', 'arc-employee-time-clock' );
					} else {
						esc_html_e( 'You are clocked out.', 'arc-employee-time-clock' );
					}
					?>
					</span>
				</div>

				<div id="arc-etc-timer" class="text-5xl md:text-6xl font-bold text-blue-600 text-center mb-8 tabular-nums">00:00:00</div>

				<div id="arc-etc-in-fields" class="space-y-4 mb-6" <?php echo ( 'out' !== $status ) ? 'style="display:none;"' : ''; ?>>
					<div id="arc-etc-recent" class="hidden">
						<label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2"><?php esc_html_e( 'Recent tasks', 'arc-employee-time-clock' ); ?></label>
						<div id="arc-etc-recent-chips" class="flex flex-wrap gap-2"></div>
					</div>
					<div class="grid md:grid-cols-2 gap-3">
						<div>
							<label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1"><?php esc_html_e( 'Client / Company', 'arc-employee-time-clock' ); ?></label>
							<select id="arc-etc-client" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white"><option value=""><?php esc_html_e( 'Select...', 'arc-employee-time-clock' ); ?></option></select>
						</div>
						<div>
							<label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1"><?php esc_html_e( 'Activity', 'arc-employee-time-clock' ); ?></label>
							<select id="arc-etc-activity" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white"><option value=""><?php esc_html_e( 'Select...', 'arc-employee-time-clock' ); ?></option></select>
						</div>
					</div>
					<div class="grid md:grid-cols-2 gap-3">
						<div>
							<label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1"><?php esc_html_e( 'Project (optional)', 'arc-employee-time-clock' ); ?></label>
							<input id="arc-etc-project" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?php esc_attr_e( 'e.g. ARC playbook', 'arc-employee-time-clock' ); ?>">
						</div>
						<div>
							<label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1"><?php esc_html_e( 'Task (optional)', 'arc-employee-time-clock' ); ?></label>
							<input id="arc-etc-task" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?php esc_attr_e( 'e.g. Recording', 'arc-employee-time-clock' ); ?>">
						</div>
					</div>
					<div>
						<label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1"><?php esc_html_e( 'Tags (optional, comma separated)', 'arc-employee-time-clock' ); ?></label>
						<input id="arc-etc-tags" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?php esc_attr_e( 'billable, onboarding', 'arc-employee-time-clock' ); ?>">
					</div>
					<div class="flex items-center gap-2">
						<input id="arc-etc-billable" type="checkbox" checked class="rounded border-slate-300 text-blue-600">
						<label for="arc-etc-billable" class="text-sm text-slate-700"><?php esc_html_e( 'Billable', 'arc-employee-time-clock' ); ?></label>
					</div>
				</div>

				<div id="arc-etc-out-fields" class="space-y-4 mb-6" <?php echo ( 'out' === $status ) ? 'style="display:none;"' : ''; ?>>
					<div class="grid md:grid-cols-2 gap-3">
						<div>
							<label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1"><?php esc_html_e( 'Lunch start (optional)', 'arc-employee-time-clock' ); ?></label>
							<input id="arc-etc-lunch-start" type="time" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
						</div>
						<div>
							<label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1"><?php esc_html_e( 'Lunch end (optional)', 'arc-employee-time-clock' ); ?></label>
							<input id="arc-etc-lunch-end" type="time" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
						</div>
					</div>
					<div>
						<label for="arc-etc-notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1"><?php esc_html_e( 'What did you work on?', 'arc-employee-time-clock' ); ?></label>
						<textarea id="arc-etc-notes" rows="2" class="w-full p-3 rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none transition resize-none"></textarea>
					</div>
				</div>

				<div class="grid grid-cols-2 gap-3 mb-6">
					<button type="button" id="arc-etc-clockin" class="arc-btn rounded-xl px-5 py-4 text-white font-semibold bg-gradient-to-r from-emerald-600 to-emerald-500 hover:brightness-105 active:scale-[0.98] transition" <?php echo ( 'out' !== $status ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Clock In', 'arc-employee-time-clock' ); ?></button>
					<button type="button" id="arc-etc-pause" class="arc-btn rounded-xl px-5 py-4 text-white font-semibold bg-gradient-to-r from-amber-600 to-amber-500 hover:brightness-105 active:scale-[0.98] transition" <?php echo ( 'in' !== $status ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Pause', 'arc-employee-time-clock' ); ?></button>
					<button type="button" id="arc-etc-resume" class="arc-btn rounded-xl px-5 py-4 text-white font-semibold bg-gradient-to-r from-amber-600 to-amber-500 hover:brightness-105 active:scale-[0.98] transition" <?php echo ( 'paused' !== $status ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Resume', 'arc-employee-time-clock' ); ?></button>
					<button type="button" id="arc-etc-clockout" class="arc-btn rounded-xl px-5 py-4 text-white font-semibold bg-gradient-to-r from-rose-700 to-rose-500 hover:brightness-105 active:scale-[0.98] transition" <?php echo ( 'out' === $status ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Clock Out', 'arc-employee-time-clock' ); ?></button>
				</div>

				<div id="arc-etc-today-blocks" class="hidden bg-slate-50 rounded-xl border border-slate-200 p-4 mb-6">
					<h3 class="text-sm font-semibold text-slate-700 mb-3"><?php esc_html_e( 'Today\'s time blocks', 'arc-employee-time-clock' ); ?></h3>
					<div id="arc-etc-today-body" class="space-y-2"></div>
				</div>

				<div id="arc-etc-message" class="hidden rounded-xl p-3 text-sm"></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function render_timesheet_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="text-center text-slate-600 py-8">' . esc_html__( 'Please log in to view your timesheet.', 'arc-employee-time-clock' ) . '</p>';
		}

		if ( ! self::user_can_clock() ) {
			return '<p class="text-center text-slate-600 py-8">' . esc_html__( 'You do not have permission to view the timesheet.', 'arc-employee-time-clock' ) . '</p>';
		}

		$user_id = get_current_user_id();
		$start   = isset( $_GET['week_start'] ) ? sanitize_text_field( wp_unslash( $_GET['week_start'] ) ) : date_i18n( 'Y-m-d', strtotime( 'monday this week' ) );
		$end     = date_i18n( 'Y-m-d', strtotime( $start . ' +6 days' ) );

		$settings    = get_option( 'arc_etc_settings', array() );
		$pto_enabled = ! empty( $settings['pto_enabled'] );
		$pto_used    = $pto_enabled ? round( Arc_ETC_Time_Entries::get_pto_used( $user_id ) / 60, 2 ) : 0;
		$pto_total   = $pto_enabled ? (float) $settings['pto_default_hours'] : 0;

		$notice = '';
		if ( isset( $_POST['arc_etc_manual_time_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arc_etc_manual_time_nonce'] ) ), 'arc_etc_manual_time_nonce' ) ) {
			$manual = array(
				'entry_date'    => isset( $_POST['entry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_date'] ) ) : current_time( 'Y-m-d' ),
				'clock_in'      => isset( $_POST['clock_in'] ) ? sanitize_text_field( wp_unslash( $_POST['clock_in'] ) ) : '',
				'clock_out'     => isset( $_POST['clock_out'] ) ? sanitize_text_field( wp_unslash( $_POST['clock_out'] ) ) : '',
				'break_minutes' => isset( $_POST['break_minutes'] ) ? (int) $_POST['break_minutes'] : 0,
				'entry_type'    => isset( $_POST['entry_type'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_type'] ) ) : 'regular',
				'client'        => isset( $_POST['client'] ) ? sanitize_text_field( wp_unslash( $_POST['client'] ) ) : '',
				'activity'      => isset( $_POST['activity'] ) ? sanitize_text_field( wp_unslash( $_POST['activity'] ) ) : '',
				'project'       => isset( $_POST['project'] ) ? sanitize_text_field( wp_unslash( $_POST['project'] ) ) : '',
				'task'          => isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '',
				'tags'          => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '',
				'billable'      => isset( $_POST['billable'] ) ? 1 : 0,
				'notes'         => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
			);
			Arc_ETC_Time_Entries::create_manual( $user_id, $manual );
			$notice = __( 'Entry saved.', 'arc-employee-time-clock' );
		}

		if ( isset( $_POST['arc_etc_leave_request_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arc_etc_leave_request_nonce'] ) ), 'arc_etc_leave_request_nonce' ) ) {
			$leave = array(
				'request_type' => isset( $_POST['request_type'] ) ? sanitize_text_field( wp_unslash( $_POST['request_type'] ) ) : 'pto',
				'start_date'   => isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : current_time( 'Y-m-d' ),
				'end_date'     => isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : current_time( 'Y-m-d' ),
				'hours'        => isset( $_POST['hours'] ) ? (float) $_POST['hours'] : 0,
				'notes'        => isset( $_POST['leave_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['leave_notes'] ) ) : '',
			);
			Arc_ETC_Leave::create( $user_id, $leave );
			$notice = __( 'Leave request submitted.', 'arc-employee-time-clock' );
		}

		if ( isset( $_POST['arc_etc_submit_week_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arc_etc_submit_week_nonce'] ) ), 'arc_etc_submit_week_nonce' ) ) {
			Arc_ETC_Time_Entries::submit_week( $user_id, $start, $end );
			$notice = __( 'Week submitted for approval.', 'arc-employee-time-clock' );
		}

		$entries = Arc_ETC_Time_Entries::get_by_user( $user_id, $start, $end );

		$week_days = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$week_days[] = date_i18n( 'Y-m-d', strtotime( $start . ' +' . $i . ' days' ) );
		}

		$by_day = array();
		foreach ( $entries as $e ) {
			$by_day[ $e->entry_date ][] = $e;
		}

		$clients    = class_exists( 'Arc_ETC_Clients' ) ? Arc_ETC_Clients::get_all( true ) : array();
		$activities = class_exists( 'Arc_ETC_Activities' ) ? Arc_ETC_Activities::get_all( true ) : array();

		ob_start();
		?>
		<div class="arc-etc-wrap max-w-4xl mx-auto p-4">
			<header class="flex items-center gap-4 mb-6">
				<div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-900 to-blue-600 flex items-center justify-center text-white font-bold text-xl">
					ARC
				</div>
				<div>
					<h1 class="text-xl font-bold text-slate-900 leading-tight"><?php esc_html_e( 'Employee Time Clock', 'arc-employee-time-clock' ); ?></h1>
					<span class="text-sm text-slate-500"><?php esc_html_e( 'My timesheet', 'arc-employee-time-clock' ); ?></span>
				</div>
			</header>

			<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
				<h2 class="text-2xl font-bold text-slate-900 mb-2"><?php esc_html_e( 'My Timesheet', 'arc-employee-time-clock' ); ?></h2>
				<p class="text-sm text-slate-500 mb-6"><?php esc_html_e( 'Review your weekly time, breaks and approvals.', 'arc-employee-time-clock' ); ?></p>

				<?php if ( $notice ) : ?>
				<div class="mb-4 rounded-lg bg-emerald-50 text-emerald-700 p-3 text-sm font-medium"><?php echo esc_html( $notice ); ?></div>
				<?php endif; ?>

				<?php if ( $pto_enabled ) : ?>
					<div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mb-6 flex items-center justify-between">
						<span class="font-semibold text-slate-700"><?php esc_html_e( 'PTO balance', 'arc-employee-time-clock' ); ?></span>
						<span class="text-slate-900 font-bold"><?php echo esc_html( $pto_used ); ?> / <?php echo esc_html( $pto_total ); ?> <?php esc_html_e( 'hours used this year', 'arc-employee-time-clock' ); ?></span>
					</div>
				<?php endif; ?>

				<p class="text-sm text-slate-500 mb-4">
					<?php
					printf(
						esc_html__( 'Week: %s to %s', 'arc-employee-time-clock' ),
						esc_html( mysql2date( 'M j, Y', $start . ' 12:00:00' ) ),
						esc_html( mysql2date( 'M j, Y', $end . ' 12:00:00' ) )
					);
					?>
				</p>

				<div class="overflow-hidden rounded-xl border border-slate-200">
					<table class="min-w-full divide-y divide-slate-200">
						<thead class="bg-slate-50">
							<tr>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Date', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'In', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Out', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Break', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Regular', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Overtime', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Total', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Client / Activity', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Type', 'arc-employee-time-clock' ); ?></th>
								<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><?php esc_html_e( 'Status', 'arc-employee-time-clock' ); ?></th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-200 bg-white">
							<?php foreach ( $week_days as $day ) : ?>
								<?php
								$day_entries = isset( $by_day[ $day ] ) ? $by_day[ $day ] : array();
								if ( empty( $day_entries ) ) :
									?>
									<tr>
										<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( mysql2date( 'D, M j', $day . ' 12:00:00' ) ); ?></td>
										<td colspan="9" class="px-4 py-3 text-sm text-slate-500"><?php esc_html_e( 'No entries', 'arc-employee-time-clock' ); ?></td>
									</tr>
								<?php else : ?>
									<?php foreach ( $day_entries as $entry ) : ?>
										<tr>
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( mysql2date( 'D, M j', $entry->entry_date . ' 12:00:00' ) ); ?></td>
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo $entry->clock_in ? esc_html( mysql2date( 'g:i A', $entry->clock_in ) ) : '-'; ?></td>
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo $entry->clock_out ? esc_html( mysql2date( 'g:i A', $entry->clock_out ) ) : '-'; ?></td>
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( self::format_minutes( $entry->break_minutes ) ); ?></td>
											<td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( self::format_minutes( max( 0, $entry->total_minutes - $entry->overtime_minutes ) ) ); ?></td>
											<td class="px-4 py-3 text-sm text-rose-600"><?php echo $entry->overtime_minutes ? esc_html( self::format_minutes( $entry->overtime_minutes ) ) : '-'; ?></td>
											<td class="px-4 py-3 text-sm font-semibold text-slate-900"><?php echo esc_html( self::format_minutes( $entry->total_minutes ) ); ?></td>
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( trim( ( $entry->client ?? '' ) . ' / ' . ( $entry->activity ?? '' ), ' / ' ) ); ?></td>
											<td class="px-4 py-3 text-sm text-slate-700"><?php echo esc_html( ucfirst( $entry->entry_type ?? '' ) ); ?></td>
											<td class="px-4 py-3 text-sm"><?php echo esc_html( ucfirst( $entry->status ?? '' ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="flex justify-end mt-4">
					<form method="post" class="inline">
						<?php wp_nonce_field( 'arc_etc_submit_week_nonce', 'arc_etc_submit_week_nonce' ); ?>
						<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'Submit Week for Approval', 'arc-employee-time-clock' ); ?></button>
					</form>
				</div>

				<div class="grid md:grid-cols-2 gap-6 mt-8">
					<div class="bg-slate-50 rounded-xl border border-slate-200 p-5">
						<h3 class="text-lg font-semibold text-slate-800 mb-4"><?php esc_html_e( 'Manual Time Entry', 'arc-employee-time-clock' ); ?></h3>
						<form method="post" class="space-y-3">
							<?php wp_nonce_field( 'arc_etc_manual_time_nonce', 'arc_etc_manual_time_nonce' ); ?>
							<div class="grid grid-cols-2 gap-3">
								<input type="date" name="entry_date" class="border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
								<select name="entry_type" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
									<option value="regular"><?php esc_html_e( 'Regular', 'arc-employee-time-clock' ); ?></option>
									<option value="overtime"><?php esc_html_e( 'Overtime', 'arc-employee-time-clock' ); ?></option>
									<option value="remote"><?php esc_html_e( 'Remote', 'arc-employee-time-clock' ); ?></option>
									<option value="pto"><?php esc_html_e( 'PTO', 'arc-employee-time-clock' ); ?></option>
									<option value="vacation"><?php esc_html_e( 'Vacation', 'arc-employee-time-clock' ); ?></option>
									<option value="holiday"><?php esc_html_e( 'Holiday', 'arc-employee-time-clock' ); ?></option>
								</select>
							</div>
							<div class="grid grid-cols-2 gap-3">
								<input type="datetime-local" name="clock_in" class="border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
								<input type="datetime-local" name="clock_out" class="border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
							</div>
							<div class="grid grid-cols-2 gap-3">
								<input type="number" name="break_minutes" placeholder="<?php esc_attr_e( 'Break minutes', 'arc-employee-time-clock' ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm" min="0">
								<select name="client" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
									<option value=""><?php esc_html_e( 'Client', 'arc-employee-time-clock' ); ?></option>
									<?php foreach ( $clients as $c ) : ?>
									<option value="<?php echo esc_attr( $c->name ); ?>"><?php echo esc_html( $c->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<select name="activity" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full">
								<option value=""><?php esc_html_e( 'Activity', 'arc-employee-time-clock' ); ?></option>
								<?php foreach ( $activities as $a ) : ?>
								<option value="<?php echo esc_attr( $a->name ); ?>"><?php echo esc_html( $a->name ); ?></option>
								<?php endforeach; ?>
							</select>
							<div class="grid grid-cols-2 gap-3">
								<input type="text" name="project" placeholder="<?php esc_attr_e( 'Project', 'arc-employee-time-clock' ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
								<input type="text" name="task" placeholder="<?php esc_attr_e( 'Task', 'arc-employee-time-clock' ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
							</div>
							<input type="text" name="tags" placeholder="<?php esc_attr_e( 'Tags', 'arc-employee-time-clock' ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full">
							<div class="flex items-center gap-2">
								<input type="checkbox" name="billable" checked class="rounded border-slate-300 text-blue-600">
								<label class="text-sm text-slate-700"><?php esc_html_e( 'Billable', 'arc-employee-time-clock' ); ?></label>
							</div>
							<textarea name="notes" rows="2" class="w-full p-3 rounded-xl border border-slate-200 bg-white text-slate-800 text-sm resize-none" placeholder="<?php esc_attr_e( 'Notes', 'arc-employee-time-clock' ); ?>"></textarea>
							<button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition"><?php esc_html_e( 'Save Entry', 'arc-employee-time-clock' ); ?></button>
						</form>
					</div>

					<div class="bg-slate-50 rounded-xl border border-slate-200 p-5">
						<h3 class="text-lg font-semibold text-slate-800 mb-4"><?php esc_html_e( 'Request Time Off', 'arc-employee-time-clock' ); ?></h3>
						<form method="post" class="space-y-3">
							<?php wp_nonce_field( 'arc_etc_leave_request_nonce', 'arc_etc_leave_request_nonce' ); ?>
							<select name="request_type" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full">
								<option value="pto"><?php esc_html_e( 'PTO', 'arc-employee-time-clock' ); ?></option>
								<option value="vacation"><?php esc_html_e( 'Vacation', 'arc-employee-time-clock' ); ?></option>
								<option value="sick"><?php esc_html_e( 'Sick', 'arc-employee-time-clock' ); ?></option>
							</select>
							<div class="grid grid-cols-2 gap-3">
								<input type="date" name="start_date" class="border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
								<input type="date" name="end_date" class="border border-slate-200 rounded-lg px-3 py-2 text-sm" required>
							</div>
							<input type="number" step="0.5" name="hours" placeholder="<?php esc_attr_e( 'Requested hours', 'arc-employee-time-clock' ); ?>" class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-full" min="0">
							<textarea name="leave_notes" rows="2" class="w-full p-3 rounded-xl border border-slate-200 bg-white text-slate-800 text-sm resize-none" placeholder="<?php esc_attr_e( 'Notes', 'arc-employee-time-clock' ); ?>"></textarea>
							<button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"><?php esc_html_e( 'Submit Request', 'arc-employee-time-clock' ); ?></button>
						</form>

						<?php $my_requests = Arc_ETC_Leave::get_by_user( $user_id ); ?>
						<?php if ( ! empty( $my_requests ) ) : ?>
						<h4 class="text-sm font-semibold text-slate-700 mt-6 mb-2"><?php esc_html_e( 'My Requests', 'arc-employee-time-clock' ); ?></h4>
						<ul class="text-sm space-y-1">
						<?php foreach ( $my_requests as $r ) : ?>
							<li class="flex justify-between border-b border-slate-200 pb-1"><span><?php echo esc_html( ( $r->start_date ?? '' ) . ' - ' . ( $r->request_type ?? '' ) ); ?></span><span class="capitalize font-medium text-slate-600"><?php echo esc_html( $r->status ?? '' ); ?></span></li>
						<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function ajax_no_priv() {
		wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'arc-employee-time-clock' ) ) );
	}

	public static function ajax_action() {
		check_ajax_referer( 'arc_etc_action', 'nonce' );

		if ( ! is_user_logged_in() || ! self::user_can_clock() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'arc-employee-time-clock' ) ) );
		}

		$action  = isset( $_POST['do'] ) ? sanitize_text_field( wp_unslash( $_POST['do'] ) ) : '';
		$user_id = get_current_user_id();

		switch ( $action ) {
			case 'bootstrap':
				wp_send_json_success( self::bootstrap( $user_id ) );
				break;

			case 'status':
				wp_send_json_success( self::current_status( $user_id ) );
				break;

			case 'clockin':
				$entry = Arc_ETC_Time_Entries::get_open( $user_id );
				if ( $entry ) {
					wp_send_json_error( array( 'message' => __( 'You are already clocked in.', 'arc-employee-time-clock' ) ) );
				}
				$data = self::entry_data_from_request();
				$id   = Arc_ETC_Time_Entries::clock_in( $user_id, $data );
				if ( is_wp_error( $id ) ) {
					wp_send_json_error( array( 'message' => $id->get_error_message() ) );
				}
				wp_send_json_success( self::bootstrap( $user_id ) );
				break;

			case 'pause':
				$entry = Arc_ETC_Time_Entries::get_open( $user_id );
				if ( ! $entry || 'open' !== $entry->status ) {
					wp_send_json_error( array( 'message' => __( 'No open shift found.', 'arc-employee-time-clock' ) ) );
				}
				$result = Arc_ETC_Time_Entries::pause( $entry->id );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( array( 'message' => $result->get_error_message() ) );
				}
				wp_send_json_success( self::bootstrap( $user_id ) );
				break;

			case 'resume':
				$entry = Arc_ETC_Time_Entries::get_open( $user_id );
				if ( $entry && 'paused' === $entry->status ) {
					$result = Arc_ETC_Time_Entries::resume( $user_id, $entry->id );
				} else {
					wp_send_json_error( array( 'message' => __( 'No paused shift found.', 'arc-employee-time-clock' ) ) );
				}
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( array( 'message' => $result->get_error_message() ) );
				}
				wp_send_json_success( self::bootstrap( $user_id ) );
				break;

			case 'clockout':
				$entry = Arc_ETC_Time_Entries::get_open( $user_id );
				if ( ! $entry ) {
					wp_send_json_error( array( 'message' => __( 'You are not clocked in.', 'arc-employee-time-clock' ) ) );
				}
				$data   = array(
					'notes'        => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
					'lunch_start'  => isset( $_POST['lunch_start'] ) ? sanitize_text_field( wp_unslash( $_POST['lunch_start'] ) ) : '',
					'lunch_end'    => isset( $_POST['lunch_end'] ) ? sanitize_text_field( wp_unslash( $_POST['lunch_end'] ) ) : '',
				);
				$result = Arc_ETC_Time_Entries::clock_out( $entry->id, $data );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( array( 'message' => $result->get_error_message() ) );
				}
				wp_send_json_success( self::bootstrap( $user_id ) );
				break;
		}

		wp_send_json_error( array( 'message' => __( 'Unknown action.', 'arc-employee-time-clock' ) ) );
	}

	private static function entry_data_from_request() {
		$location = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';
		$geo      = array();
		if ( ! empty( $location ) ) {
			$loc = json_decode( $location, true );
			if ( is_array( $loc ) && isset( $loc['lat'], $loc['lng'] ) ) {
				$geo['latitude']  = sanitize_text_field( $loc['lat'] );
				$geo['longitude'] = sanitize_text_field( $loc['lng'] );
			}
		}

		return array_merge(
			array(
				'client'   => isset( $_POST['client'] ) ? sanitize_text_field( wp_unslash( $_POST['client'] ) ) : '',
				'activity' => isset( $_POST['activity'] ) ? sanitize_text_field( wp_unslash( $_POST['activity'] ) ) : '',
				'project'  => isset( $_POST['project'] ) ? sanitize_text_field( wp_unslash( $_POST['project'] ) ) : '',
				'task'     => isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '',
				'tags'     => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '',
				'billable' => isset( $_POST['billable'] ) ? 1 : 0,
				'notes'    => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
			),
			$geo
		);
	}

	private static function bootstrap( $user_id ) {
		$open = Arc_ETC_Time_Entries::get_open( $user_id );

		$clients    = class_exists( 'Arc_ETC_Clients' ) ? Arc_ETC_Clients::get_all( true ) : array();
		$activities = class_exists( 'Arc_ETC_Activities' ) ? Arc_ETC_Activities::get_all( true ) : array();

		$today    = current_time( 'Y-m-d' );
		$blocks   = Arc_ETC_Time_Entries::today_blocks( $user_id, $today );
		$week_key = Arc_ETC_Time_Entries::week_key();

		$status       = $open ? ( 'paused' === $open->status ? 'paused' : 'in' ) : 'out';
		$since        = ( $open && 'in' === $status && $open->clock_in ) ? strtotime( $open->clock_in ) : 0;
		$elapsed_min  = ( $open && 'paused' === $status ) ? (int) $open->total_minutes : 0;

		return array(
			'status'      => $status,
			'entry_id'    => $open ? $open->id : 0,
			'since'       => $since,
			'elapsedMin'  => $elapsed_min,
			'entry'       => $open,
			'clients'     => wp_list_pluck( $clients, 'name' ),
			'activities'  => wp_list_pluck( $activities, 'name' ),
			'recentTasks' => Arc_ETC_Time_Entries::recent_tasks( $user_id ),
			'todayBlocks' => $blocks,
			'weekLocked'  => class_exists( 'Arc_ETC_Locked_Weeks' ) ? Arc_ETC_Locked_Weeks::is_locked( $week_key, $user_id ) : false,
			'time'        => current_time( 'mysql' ),
		);
	}

	public static function current_status( $user_id ) {
		$entry = Arc_ETC_Time_Entries::get_open( $user_id );
		if ( ! $entry ) {
			return array( 'status' => 'out', 'entry_id' => 0 );
		}

		$tz    = wp_timezone();
		$point = $entry->clock_in;
		$dt    = DateTime::createFromFormat( 'Y-m-d H:i:s', $point, $tz );
		$since = $dt ? $dt->getTimestamp() : time();

		return array(
			'status'    => ( 'paused' === $entry->status ) ? 'paused' : 'in',
			'entry_id'  => $entry->id,
			'since'     => $since,
			'clock_in'  => $entry->clock_in,
			'break_min' => (int) $entry->break_minutes,
		);
	}

	public static function user_can_clock() {
		$settings = get_option( 'arc_etc_settings', array() );
		$roles    = isset( $settings['allowed_roles'] ) ? $settings['allowed_roles'] : array( 'administrator', 'editor', 'author', 'subscriber' );
		$user     = wp_get_current_user();
		foreach ( $roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				return true;
			}
		}
		return false;
	}

	public static function format_minutes( $minutes ) {
		$minutes = (int) $minutes;
		$h       = floor( $minutes / 60 );
		$m       = $minutes % 60;
		return sprintf( '%02d:%02d', $h, $m );
	}
}
