<?php
/**
 * ARC API Frontend — EOD Report form template (Tailwind CSS).
 *
 * @package Arc_API_Frontend
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// $app is provided by the render method via get_endpoints().
if ( ! isset( $app ) ) {
	$endpoints = $this->get_endpoints();
	$app       = isset( $endpoints['eod_report'] ) ? $endpoints['eod_report'] : array();
}
?>
<div class="max-w-3xl mx-auto p-6 font-sans text-gray-900" id="arc-eod-form">
	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
		<h2 class="text-2xl font-bold mb-6"><?php esc_html_e( 'Submit EOD Report', 'arc-api-frontend' ); ?></h2>
		<form id="arc-eod-form-frm" class="space-y-5">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Report date', 'arc-api-frontend' ); ?></label>
					<input type="date" name="report_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Hours worked', 'arc-api-frontend' ); ?></label>
					<input type="number" name="hours_worked" step="0.25" min="0" max="24" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Work summary', 'arc-api-frontend' ); ?></label>
				<textarea name="work_description" rows="3" placeholder="<?php esc_attr_e( 'Describe what you did today', 'arc-api-frontend' ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-y" required></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Shipped today', 'arc-api-frontend' ); ?></label>
				<textarea name="shipped_today" rows="2" placeholder="<?php esc_attr_e( 'Completed tasks', 'arc-api-frontend' ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'In progress', 'arc-api-frontend' ); ?></label>
				<textarea name="in_progress" rows="2" placeholder="<?php esc_attr_e( 'Tasks in progress', 'arc-api-frontend' ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Blockers / Risks', 'arc-api-frontend' ); ?></label>
				<textarea name="blockers" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Top 3 priorities for tomorrow', 'arc-api-frontend' ); ?></label>
				<textarea name="top_priorities" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-y"></textarea>
			</div>
			<button type="submit" class="inline-flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition" data-loading-text="<?php esc_attr_e( 'Submitting...', 'arc-api-frontend' ); ?>"><?php esc_html_e( 'Submit Report', 'arc-api-frontend' ); ?></button>
		</form>
		<div id="arc-eod-message" class="hidden mt-4 p-4 rounded-lg"></div>
	</div>

	<?php if ( empty( $app['endpoint'] ) ) : ?>
		<div class="mt-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4"><?php esc_html_e( 'The EOD Report endpoint is not configured.', 'arc-api-frontend' ); ?></div>
	<?php endif; ?>
</div>
