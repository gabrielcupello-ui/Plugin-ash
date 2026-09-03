<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Arc_ETC_Deactivator {

	public static function deactivate() {
		$events = array( 'arc_etc_auto_close', 'arc_etc_flag_sweep', 'arc_etc_pending_digest', 'arc_etc_lock_reminder', 'arc_etc_exception_report' );
		foreach ( $events as $event ) {
			wp_clear_scheduled_hook( $event );
		}
	}
}
