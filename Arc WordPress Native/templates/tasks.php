<?php
/**
 * Native Tasks module template (Tailwind CSS).
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="max-w-6xl mx-auto p-6 font-sans text-gray-900">
	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
		<div class="flex items-center justify-between mb-4">
			<h2 class="text-2xl font-bold"><?php esc_html_e( 'My Tasks', 'arc-native' ); ?></h2>
			<button type="button" id="arc-native-new-task" class="inline-flex items-center px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium rounded-lg transition"><?php esc_html_e( 'New task', 'arc-native' ); ?></button>
		</div>
		<div class="max-w-xs mb-4">
			<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Filter by status', 'arc-native' ); ?></label>
			<select id="arc-native-task-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
				<option value="all"><?php esc_html_e( 'All', 'arc-native' ); ?></option>
				<option value="To Do"><?php esc_html_e( 'To Do', 'arc-native' ); ?></option>
				<option value="In Progress"><?php esc_html_e( 'In Progress', 'arc-native' ); ?></option>
				<option value="Review"><?php esc_html_e( 'Review', 'arc-native' ); ?></option>
				<option value="Done"><?php esc_html_e( 'Done', 'arc-native' ); ?></option>
			</select>
		</div>

		<div class="overflow-x-auto">
			<table class="w-full border-collapse" id="arc-native-tasks-list">
				<thead>
					<tr class="bg-gray-50 border-b border-gray-200">
						<th class="text-left px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Task', 'arc-native' ); ?></th>
						<th class="text-left px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Project', 'arc-native' ); ?></th>
						<th class="text-left px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Status', 'arc-native' ); ?></th>
						<th class="text-left px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Priority', 'arc-native' ); ?></th>
						<th class="text-left px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Due date', 'arc-native' ); ?></th>
					</tr>
				</thead>
				<tbody class="text-sm text-gray-700">
					<tr>
						<td colspan="5" class="text-center text-gray-400 py-8"><?php esc_html_e( 'Loading tasks...', 'arc-native' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
