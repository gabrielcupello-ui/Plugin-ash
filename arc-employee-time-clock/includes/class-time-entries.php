<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Time_Entries {

	public static function init() {
		// No-op; class is loaded.
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . ARC_ETC_TABLE;
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
	}

	public static function get_open( $user_id, $date = null ) {
		global $wpdb;
		// open or paused: either one is the currently active shift for the user.
		$sql    = 'SELECT * FROM ' . self::table() . ' WHERE user_id = %d AND status IN ("open","paused")';
		$params = array( $user_id );
		if ( $date ) {
			$sql   .= ' AND entry_date = %s';
			$params[] = $date;
		}
		$sql .= ' ORDER BY id DESC LIMIT 1';
		return $wpdb->get_row( $wpdb->prepare( $sql, $params ) );
	}

	public static function get_by_user( $user_id, $start_date = null, $end_date = null, $status = null ) {
		global $wpdb;
		$sql    = 'SELECT * FROM ' . self::table() . ' WHERE user_id = %d';
		$params = array( $user_id );

		if ( $start_date ) {
			$sql     .= ' AND entry_date >= %s';
			$params[] = $start_date;
		}
		if ( $end_date ) {
			$sql     .= ' AND entry_date <= %s';
			$params[] = $end_date;
		}
		if ( $status ) {
			$sql     .= ' AND status = %s';
			$params[] = $status;
		}
		$sql .= ' ORDER BY entry_date DESC, clock_in DESC';

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public static function get_all( $args = array() ) {
		global $wpdb;
		$defaults = array(
			'start_date' => null,
			'end_date'   => null,
			'user_id'    => null,
			'status'     => null,
			'limit'      => 1000,
			'offset'     => 0,
		);
		$args     = wp_parse_args( $args, $defaults );

		$sql    = 'SELECT * FROM ' . self::table() . ' WHERE 1=1';
		$params = array();

		if ( $args['user_id'] ) {
			$sql     .= ' AND user_id = %d';
			$params[] = $args['user_id'];
		}
		if ( $args['start_date'] ) {
			$sql     .= ' AND entry_date >= %s';
			$params[] = $args['start_date'];
		}
		if ( $args['end_date'] ) {
			$sql     .= ' AND entry_date <= %s';
			$params[] = $args['end_date'];
		}
		if ( $args['status'] ) {
			$sql     .= ' AND status = %s';
			$params[] = $args['status'];
		}
		$sql .= ' ORDER BY entry_date DESC, clock_in DESC LIMIT %d OFFSET %d';
		$params[] = $args['limit'];
		$params[] = $args['offset'];

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public static function get_total_minutes( $user_id, $start_date = null, $end_date = null, $status = null ) {
		global $wpdb;
		$sql    = 'SELECT COALESCE(SUM(total_minutes),0) FROM ' . self::table() . ' WHERE user_id = %d';
		$params = array( $user_id );

		if ( $start_date ) {
			$sql     .= ' AND entry_date >= %s';
			$params[] = $start_date;
		}
		if ( $end_date ) {
			$sql     .= ' AND entry_date <= %s';
			$params[] = $end_date;
		}
		if ( $status ) {
			$sql     .= ' AND status = %s';
			$params[] = $status;
		}

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
	}

	public static function get_pto_used( $user_id, $year = null ) {
		global $wpdb;
		$year = $year ?: current_time( 'Y' );
		$sql  = 'SELECT COALESCE(SUM(total_minutes),0) FROM ' . self::table() . ' WHERE user_id = %d AND entry_type IN ("pto","vacation") AND entry_date >= %s AND entry_date <= %s';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				$sql,
				$user_id,
				$year . '-01-01',
				$year . '-12-31'
			)
		);
	}

	public static function recent_tasks( $user_id, $limit = 6 ) {
		global $wpdb;
		$sql = 'SELECT client, activity, notes FROM ' . self::table() . ' WHERE user_id = %d AND status NOT IN ("open","paused","rejected") AND notes <> "" AND client <> "" GROUP BY client, activity, notes ORDER BY MAX(id) DESC LIMIT %d';
		return $wpdb->get_results( $wpdb->prepare( $sql, $user_id, $limit ) );
	}

	public static function today_blocks( $user_id, $today = null ) {
		global $wpdb;
		$today = $today ?: current_time( 'Y-m-d' );
		$sql   = 'SELECT * FROM ' . self::table() . ' WHERE user_id = %d AND entry_date = %s ORDER BY clock_in DESC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $user_id, $today ) );
	}

	public static function clock_in( $user_id, $data = array() ) {
		global $wpdb;

		if ( self::get_open( $user_id ) ) {
			return new WP_Error( 'already_clocked_in', __( 'Already clocked in.', 'arc-employee-time-clock' ) );
		}

		$now  = current_time( 'mysql' );
		$date = current_time( 'Y-m-d' );
		$type = 'regular';
		if ( class_exists( 'Arc_ETC_Holidays' ) && Arc_ETC_Holidays::is_holiday( $date ) ) {
			$type = 'holiday';
		}

		$client   = ! empty( $data['client'] ) ? sanitize_text_field( $data['client'] ) : '';
		$activity = ! empty( $data['activity'] ) ? sanitize_text_field( $data['activity'] ) : '';
		$project  = ! empty( $data['project'] ) ? sanitize_text_field( $data['project'] ) : '';
		$task     = ! empty( $data['task'] ) ? sanitize_text_field( $data['task'] ) : '';
		$tags     = ! empty( $data['tags'] ) ? sanitize_text_field( $data['tags'] ) : '';

		$pt = self::validate_project_task_tags( $project, $task, $tags );
		if ( is_wp_error( $pt ) ) {
			return $pt;
		}

		$week_key  = self::week_key( $now );
		$month_key = self::month_key( $now );

		if ( Arc_ETC_Locked_Weeks::is_locked( $week_key, $user_id ) ) {
			return new WP_Error( 'week_locked', __( 'This week is locked.', 'arc-employee-time-clock' ) );
		}

		$billable = self::billable_default( $client, $activity, $data );

		$insert = array(
			'user_id'       => $user_id,
			'entry_date'    => $date,
			'clock_in'      => $now,
			'entry_type'    => $type,
			'client'        => $client,
			'activity'      => $activity,
			'project'       => $pt['project'],
			'task'          => $pt['task'],
			'tags'          => $pt['tags'],
			'billable'      => $billable ? 1 : 0,
			'notes'         => ! empty( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
			'ip_address'    => self::get_ip(),
			'latitude'      => ! empty( $data['latitude'] ) ? sanitize_text_field( $data['latitude'] ) : null,
			'longitude'     => ! empty( $data['longitude'] ) ? sanitize_text_field( $data['longitude'] ) : null,
			'source'        => 'timer',
			'status'        => 'open',
			'week_key'      => $week_key,
			'month_key'     => $month_key,
		);

		$wpdb->insert( self::table(), $insert );
		return $wpdb->insert_id;
	}

	public static function pause( $entry_id ) {
		global $wpdb;
		$entry = self::get( $entry_id );
		if ( ! $entry || 'open' !== $entry->status ) {
			return new WP_Error( 'no_open_shift', __( 'No open shift found.', 'arc-employee-time-clock' ) );
		}

		$now      = current_time( 'mysql' );
		$duration = self::calculate_duration( $entry, $now );

		$wpdb->update(
			self::table(),
			array(
				'clock_out'     => $now,
				'total_minutes' => $duration,
				'status'        => 'paused',
				'flags'         => self::append_flag( $entry->flags, 'PAUSED' ),
			),
			array( 'id' => $entry_id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		return true;
	}

	public static function resume( $user_id, $entry_id ) {
		global $wpdb;
		$entry = self::get( $entry_id );
		if ( ! $entry || 'paused' !== $entry->status ) {
			return new WP_Error( 'no_paused_shift', __( 'No paused shift found.', 'arc-employee-time-clock' ) );
		}

		$open = self::get_open( $user_id );
		if ( $open && (int) $open->id !== (int) $entry_id ) {
			return new WP_Error( 'already_clocked_in', __( 'Already clocked in.', 'arc-employee-time-clock' ) );
		}

		$now       = current_time( 'mysql' );
		$date      = current_time( 'Y-m-d' );
		$week_key  = self::week_key( $now );
		$month_key = self::month_key( $now );

		$prev_hours = round( $entry->total_minutes / 60, 2 );
		$insert     = array(
			'user_id'       => $user_id,
			'entry_date'    => $date,
			'clock_in'      => $now,
			'entry_type'    => $entry->entry_type,
			'client'        => $entry->client,
			'activity'      => $entry->activity,
			'project'       => $entry->project,
			'task'          => $entry->task,
			'tags'          => $entry->tags,
			'billable'      => $entry->billable,
			'notes'         => $entry->notes,
			'ip_address'    => self::get_ip(),
			'source'        => 'timer',
			'status'        => 'open',
			'flags'         => 'RESUMED (+' . $prev_hours . 'h)',
			'week_key'      => $week_key,
			'month_key'     => $month_key,
		);

		$wpdb->insert( self::table(), $insert );
		$new_id = $wpdb->insert_id;
		if ( $new_id ) {
			$wpdb->update( self::table(), array( 'status' => 'pending' ), array( 'id' => $entry_id ), array( '%s' ), array( '%d' ) );
		}
		return $new_id;
	}

	public static function clock_out( $entry_id, $data = array() ) {
		global $wpdb;
		$entry = self::get( $entry_id );
		if ( ! $entry || ( 'open' !== $entry->status && 'paused' !== $entry->status ) ) {
			return new WP_Error( 'no_open_shift', __( 'No open or paused shift found.', 'arc-employee-time-clock' ) );
		}

		$settings = get_option( 'arc_etc_settings', array() );
		$min_len  = intval( $settings['required_notes_length'] ?? 5 );
		$notes    = ! empty( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '';
		if ( mb_strlen( trim( $notes ) ) < $min_len ) {
			return new WP_Error( 'notes_required', __( 'Describe the work before clocking out.', 'arc-employee-time-clock' ) );
		}

		$lunch_start = ! empty( $data['lunch_start'] ) ? sanitize_text_field( $data['lunch_start'] ) : '';
		$lunch_end   = ! empty( $data['lunch_end'] ) ? sanitize_text_field( $data['lunch_end'] ) : '';

		if ( 'paused' === $entry->status && $entry->clock_out ) {
			$out   = $entry->clock_out;
			$total = (int) $entry->total_minutes;
		} else {
			$out      = current_time( 'mysql' );
			$duration = self::calculate_duration( $entry, $out, $lunch_start, $lunch_end );
			if ( is_wp_error( $duration ) ) {
				return $duration;
			}
			$total = $duration;
		}

		$flags = array();
		if ( 'paused' === $entry->status ) {
			$flags[] = 'CLOSED FROM PAUSE';
		}

		$hours = $total / 60;
		if ( $hours > floatval( $settings['flag_daily_hours_over'] ?? 10 ) ) {
			$flags[] = 'LONG SHIFT ' . round( $hours, 2 ) . 'h';
		}
		if ( $hours >= 6 && self::break_minutes( $lunch_start, $lunch_end, $entry->break_minutes ) === 0 ) {
			$flags[] = 'NO BREAK';
		}
		if ( in_array( (int) date_i18n( 'w', strtotime( $entry->entry_date ) ), array( 0, 6 ), true ) ) {
			$flags[] = 'WEEKEND';
		}

		$activity = self::get_activity( $entry->activity );
		if ( $activity && ! $activity->paid ) {
			$total = 0;
			$flags[] = 'UNPAID ACTIVITY';
		}

		if ( Arc_ETC_Locked_Weeks::is_locked( $entry->week_key, $entry->user_id ) ) {
			$flags[] = 'LOCKED WEEK';
		}

		$overtime = self::calculate_overtime( $entry, $total );

		$wpdb->update(
			self::table(),
			array(
				'clock_out'        => $out,
				'lunch_start'      => $lunch_start ? $lunch_start : null,
				'lunch_end'        => $lunch_end ? $lunch_end : null,
				'break_minutes'    => self::break_minutes( $lunch_start, $lunch_end, $entry->break_minutes ),
				'total_minutes'    => $total,
				'overtime_minutes' => $overtime,
				'notes'            => $notes,
				'status'           => 'pending',
				'flags'            => implode( ', ', $flags ),
			),
			array( 'id' => $entry_id ),
			array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);
		return true;
	}

	public static function create_manual( $user_id, $data ) {
		global $wpdb;

		$client   = ! empty( $data['client'] ) ? sanitize_text_field( $data['client'] ) : '';
		$activity = ! empty( $data['activity'] ) ? sanitize_text_field( $data['activity'] ) : '';
		$project  = ! empty( $data['project'] ) ? sanitize_text_field( $data['project'] ) : '';
		$task     = ! empty( $data['task'] ) ? sanitize_text_field( $data['task'] ) : '';
		$tags     = ! empty( $data['tags'] ) ? sanitize_text_field( $data['tags'] ) : '';

		$pt = self::validate_project_task_tags( $project, $task, $tags );
		if ( is_wp_error( $pt ) ) {
			return $pt;
		}

		$clock_in  = ! empty( $data['clock_in'] ) ? sanitize_text_field( $data['clock_in'] ) : '';
		$clock_out = ! empty( $data['clock_out'] ) ? sanitize_text_field( $data['clock_out'] ) : '';

		$tz = wp_timezone();
		$start = $clock_in ? DateTime::createFromFormat( 'Y-m-d\TH:i', $clock_in, $tz ) : null;
		$end   = $clock_out ? DateTime::createFromFormat( 'Y-m-d\TH:i', $clock_out, $tz ) : null;

		if ( ! $start || ! $end || $end->getTimestamp() <= $start->getTimestamp() ) {
			return new WP_Error( 'invalid_times', __( 'Invalid start or end time.', 'arc-employee-time-clock' ) );
		}
		if ( $end->getTimestamp() > current_time( 'timestamp' ) ) {
			return new WP_Error( 'future_time', __( 'Cannot log future time.', 'arc-employee-time-clock' ) );
		}

		$date      = $start->format( 'Y-m-d' );
		$week_key  = self::week_key( $date );
		$month_key = self::month_key( $date );

		if ( Arc_ETC_Locked_Weeks::is_locked( $week_key, $user_id ) ) {
			return new WP_Error( 'week_locked', __( 'That week is locked.', 'arc-employee-time-clock' ) );
		}

		$break_min = (int) ( $data['break_minutes'] ?? 0 );
		$total     = max( 0, round( ( $end->getTimestamp() - $start->getTimestamp() ) / 60 ) - $break_min );
		if ( $total <= 0 ) {
			return new WP_Error( 'break_too_long', __( 'Break is longer than the shift.', 'arc-employee-time-clock' ) );
		}
		if ( $total > 1440 ) {
			return new WP_Error( 'too_long', __( 'Shift cannot exceed 24 hours.', 'arc-employee-time-clock' ) );
		}

		$entry = (object) array(
			'id'         => 0,
			'user_id'    => $user_id,
			'entry_date' => $date,
			'clock_in'   => $start->format( 'Y-m-d H:i:s' ),
			'clock_out'  => $end->format( 'Y-m-d H:i:s' ),
			'break_minutes' => $break_min,
			'entry_type' => $data['entry_type'] ?? 'regular',
		);
		$overtime = self::calculate_overtime( $entry, $total );

		$flags = array( 'MANUAL' );
		$age   = floor( ( current_time( 'timestamp' ) - $end->getTimestamp() ) / DAY_IN_SECONDS );
		if ( $age > 7 ) {
			$flags[] = 'BACKDATED ' . $age . 'd';
		}

		$billable = self::billable_default( $client, $activity, $data );

		$wpdb->insert(
			self::table(),
			array(
				'user_id'          => $user_id,
				'entry_date'       => $date,
				'clock_in'         => $start->format( 'Y-m-d H:i:s' ),
				'clock_out'        => $end->format( 'Y-m-d H:i:s' ),
				'break_minutes'    => $break_min,
				'total_minutes'    => $total,
				'overtime_minutes' => $overtime,
				'entry_type'       => ! empty( $data['entry_type'] ) ? sanitize_text_field( $data['entry_type'] ) : 'regular',
				'client'           => $client,
				'activity'         => $activity,
				'project'          => $pt['project'],
				'task'             => $pt['task'],
				'tags'             => $pt['tags'],
				'billable'         => $billable ? 1 : 0,
				'notes'            => ! empty( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
				'ip_address'       => self::get_ip(),
				'source'           => 'manual',
				'status'           => 'pending',
				'flags'            => implode( ', ', $flags ),
				'week_key'         => $week_key,
				'month_key'        => $month_key,
			)
		);
		return $wpdb->insert_id;
	}

	public static function update_entry( $entry_id, $data, $changed_by = 0 ) {
		global $wpdb;
		$entry = self::get( $entry_id );
		if ( ! $entry ) {
			return false;
		}

		if ( in_array( $entry->status, array( 'approved', 'rejected', 'locked' ), true ) ) {
			return new WP_Error( 'locked', __( 'This entry is already finalized.', 'arc-employee-time-clock' ) );
		}
		if ( Arc_ETC_Locked_Weeks::is_locked( $entry->week_key, $entry->user_id ) ) {
			return new WP_Error( 'week_locked', __( 'This week is locked.', 'arc-employee-time-clock' ) );
		}

		$allowed = array( 'entry_type', 'notes', 'clock_in', 'clock_out', 'total_minutes', 'overtime_minutes', 'status', 'break_minutes', 'approved_by', 'approved_at', 'submitted_at', 'client', 'activity', 'project', 'task', 'tags', 'billable', 'flags', 'lunch_start', 'lunch_end' );
		$update  = array();
		foreach ( $allowed as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$update[ $key ] = $data[ $key ];
			}
		}
		if ( empty( $update ) ) {
			return false;
		}

		$int_fields = array( 'total_minutes', 'overtime_minutes', 'break_minutes', 'approved_by', 'billable' );
		$formats    = array();
		foreach ( $update as $key => $value ) {
			$formats[] = in_array( $key, $int_fields, true ) ? '%d' : '%s';
		}

		if ( $changed_by ) {
			self::audit( $entry_id, $entry->user_id, 'update', $entry, (object) $update, $changed_by );
		}

		return $wpdb->update( self::table(), $update, array( 'id' => $entry_id ), $formats, array( '%d' ) );
	}

	public static function submit( $entry_id ) {
		return self::update_entry( $entry_id, array( 'status' => 'submitted', 'submitted_at' => current_time( 'mysql' ) ) );
	}

	public static function submit_week( $user_id, $start_date, $end_date ) {
		global $wpdb;
		$start = sanitize_text_field( $start_date );
		$end   = sanitize_text_field( $end_date );
		return $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::table() . " SET status = 'submitted', submitted_at = %s WHERE user_id = %d AND entry_date >= %s AND entry_date <= %s AND status = 'pending'",
				current_time( 'mysql' ),
				$user_id,
				$start,
				$end
			)
		);
	}

	public static function approve( $entry_id, $approver_id ) {
		return self::update_entry(
			$entry_id,
			array(
				'status'      => 'approved',
				'approved_by' => $approver_id,
				'approved_at' => current_time( 'mysql' ),
			),
			$approver_id
		);
	}

	public static function reject( $entry_id, $approver_id ) {
		return self::update_entry(
			$entry_id,
			array(
				'status'      => 'rejected',
				'approved_by' => $approver_id,
				'approved_at' => current_time( 'mysql' ),
			),
			$approver_id
		);
	}

	public static function delete( $entry_id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'id' => $entry_id ), array( '%d' ) );
	}

	public static function recalculate( $entry_id ) {
		global $wpdb;
		$entry = self::get( $entry_id );
		if ( ! $entry || ! $entry->clock_out ) {
			return false;
		}
		$total    = self::calculate_duration( $entry, $entry->clock_out );
		$overtime = self::calculate_overtime( $entry, $total );
		return $wpdb->update( self::table(), array( 'total_minutes' => $total, 'overtime_minutes' => $overtime ), array( 'id' => $entry_id ), array( '%d', '%d' ), array( '%d' ) );
	}

	private static function calculate_duration( $entry, $clock_out = null, $lunch_start = '', $lunch_end = '' ) {
		if ( ! $entry->clock_in ) {
			return 0;
		}
		$clock_out = $clock_out ?: current_time( 'mysql' );

		$tz    = wp_timezone();
		$start = DateTime::createFromFormat( 'Y-m-d H:i:s', $entry->clock_in, $tz );
		$end   = DateTime::createFromFormat( 'Y-m-d H:i:s', $clock_out, $tz );
		if ( ! $start || ! $end || $start->getTimestamp() >= $end->getTimestamp() ) {
			return 0;
		}

		$break_minutes = (int) $entry->break_minutes;
		if ( $entry->break_start ) {
			$break_start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $entry->break_start, $tz );
			$break_end_dt   = new DateTime( 'now', $tz );
			if ( $break_start_dt ) {
				$break_minutes += max( 0, round( ( $break_end_dt->getTimestamp() - $break_start_dt->getTimestamp() ) / 60 ) );
			}
		}

		$break_minutes += self::break_minutes( $lunch_start, $lunch_end, 0 );

		$total = max( 0, round( ( $end->getTimestamp() - $start->getTimestamp() ) / 60 ) - $break_minutes );
		return self::round_minutes( $total );
	}

	private static function calculate_overtime( $entry, $total_minutes ) {
		if ( in_array( $entry->entry_type, array( 'pto', 'vacation', 'holiday', 'sick' ), true ) ) {
			return 0;
		}

		$settings = get_option( 'arc_etc_settings', array() );
		$daily    = max( 0, floatval( $settings['overtime_daily_threshold'] ?? 8 ) * 60 );
		$weekly   = max( 0, floatval( $settings['overtime_weekly_threshold'] ?? 40 ) * 60 );

		$daily_ot  = max( 0, $total_minutes - $daily );

		$ts          = strtotime( $entry->entry_date );
		$day_of_week = (int) date_i18n( 'w', $ts );
		if ( ! empty( $settings['week_starts_monday'] ) ) {
			$offset = ( $day_of_week === 0 ) ? -6 : 1 - $day_of_week;
		} else {
			$offset = -$day_of_week;
		}
		$week_start = date_i18n( 'Y-m-d', strtotime( $offset . ' days', $ts ) );

		global $wpdb;
		$week_previous = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(total_minutes),0) FROM ' . self::table() . ' WHERE user_id = %d AND entry_date >= %s AND entry_date <= %s AND clock_out IS NOT NULL AND id != %d',
				$entry->user_id,
				$week_start,
				$entry->entry_date,
				$entry->id
			)
		);
		$week_total = $week_previous + $total_minutes;
		$weekly_ot  = max( 0, $week_total - $weekly );

		return (int) min( $total_minutes, max( $daily_ot, $weekly_ot ) );
	}

	private static function round_minutes( $minutes ) {
		$settings = get_option( 'arc_etc_settings', array() );
		$round    = max( 1, (int) ( $settings['round_minutes'] ?? 15 ) );
		return (int) ( round( $minutes / $round ) * $round );
	}

	private static function break_minutes( $lunch_start, $lunch_end, $default = 0 ) {
		if ( ! $lunch_start || ! $lunch_end ) {
			return (int) $default;
		}
		$pattern = '/^(\d{2}):(\d{2})$/';
		if ( ! preg_match( $pattern, $lunch_start, $s ) || ! preg_match( $pattern, $lunch_end, $e ) ) {
			return (int) $default;
		}
		$sm = (int) $s[1] * 60 + (int) $s[2];
		$em = (int) $e[1] * 60 + (int) $e[2];
		if ( $em <= $sm ) {
			return (int) $default;
		}
		return $em - $sm;
	}

	private static function validate_project_task_tags( $project, $task, $tags ) {
		$project = substr( sanitize_text_field( $project ), 0, 80 );
		$task    = substr( sanitize_text_field( $task ), 0, 80 );
		$raw     = sanitize_text_field( $tags );

		$clean = array_map( 'trim', preg_split( '/[,;]/', strtolower( $raw ) ) );
		$seen  = array();
		$out   = array();
		foreach ( $clean as $t ) {
			$t = preg_replace( '/\s+/', ' ', $t );
			if ( ! $t || in_array( $t, $seen, true ) ) {
				continue;
			}
			$seen[] = $t;
			$out[]  = $t;
		}
		if ( count( $out ) > 5 ) {
			return new WP_Error( 'too_many_tags', __( 'Too many tags (max 5).', 'arc-employee-time-clock' ) );
		}
		return array( 'project' => $project, 'task' => $task, 'tags' => implode( ', ', array_slice( $out, 0, 5 ) ) );
	}

	private static function billable_default( $client, $activity, $data ) {
		if ( isset( $data['billable'] ) ) {
			return ! empty( $data['billable'] );
		}
		$act = self::get_activity( $activity );
		if ( $act ) {
			return (bool) $act->billable_default;
		}
		$cli = self::get_client( $client );
		if ( $cli ) {
			return (bool) $cli->billable_default;
		}
		return true;
	}

	private static function get_activity( $name ) {
		global $wpdb;
		if ( ! class_exists( 'Arc_ETC_Activities' ) ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Arc_ETC_Activities::table() . ' WHERE name = %s LIMIT 1', $name ) );
	}

	private static function get_client( $name ) {
		global $wpdb;
		if ( ! class_exists( 'Arc_ETC_Clients' ) ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Arc_ETC_Clients::table() . ' WHERE name = %s LIMIT 1', $name ) );
	}

	private static function append_flag( $existing, $flag ) {
		$flags = $existing ? array_filter( array_map( 'trim', explode( ',', $existing ) ) ) : array();
		if ( ! in_array( $flag, $flags, true ) ) {
			$flags[] = $flag;
		}
		return implode( ', ', $flags );
	}

	public static function week_key( $date = null ) {
		$ts    = $date ? strtotime( $date ) : current_time( 'timestamp' );
		$start = date_i18n( 'Y-m-d', strtotime( 'monday this week', $ts ) );
		return $start;
	}

	public static function month_key( $date = null ) {
		$ts = $date ? strtotime( $date ) : current_time( 'timestamp' );
		return date_i18n( 'Y-m', $ts );
	}

	private static function audit( $entry_id, $user_id, $action, $old, $new, $changed_by ) {
		global $wpdb;
		$table = $wpdb->prefix . 'arc_etc_audit_log';
		$wpdb->insert(
			$table,
			array(
				'entry_id'   => $entry_id,
				'user_id'    => $user_id,
				'field'      => $action,
				'old_value'  => maybe_serialize( $old ),
				'new_value'  => maybe_serialize( $new ),
				'changed_by' => $changed_by,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d' )
		);
	}

	public static function get_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return $ip;
	}
}
