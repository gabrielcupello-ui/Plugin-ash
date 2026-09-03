<?php
/**
 * Native Time Clock module template (Tailwind CSS).
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="max-w-2xl mx-auto p-6 font-sans text-gray-900">
	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
		<h2 class="text-2xl font-bold mb-2"><?php esc_html_e( 'Time Clock', 'arc-native' ); ?></h2>
		<p class="text-gray-500 mb-6"><?php esc_html_e( 'Record your clock-in and clock-out.', 'arc-native' ); ?></p>

		<div class="space-y-4">
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Client / Project', 'arc-native' ); ?></label>
				<input type="text" id="arc-native-clock-client" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" placeholder="<?php esc_attr_e( 'Optional', 'arc-native' ); ?>">
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Activity', 'arc-native' ); ?></label>
				<input type="text" id="arc-native-clock-activity" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" placeholder="<?php esc_attr_e( 'Optional', 'arc-native' ); ?>">
			</div>
			<div class="flex gap-3 pt-2">
				<button type="button" id="arc-native-clock-in" class="inline-flex items-center px-5 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition"><?php esc_html_e( 'Clock In', 'arc-native' ); ?></button>
				<button type="button" id="arc-native-clock-out" class="inline-flex items-center px-5 py-2.5 bg-slate-500 hover:bg-slate-600 text-white font-medium rounded-lg transition"><?php esc_html_e( 'Clock Out', 'arc-native' ); ?></button>
			</div>
		</div>
		<div id="arc-native-clock-message" class="hidden mt-4 p-4 rounded-lg"></div>
	</div>
</div>
