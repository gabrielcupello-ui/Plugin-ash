<?php
/**
 * ARC API Frontend — Tasks template (Tailwind CSS).
 *
 * @package Arc_API_Frontend
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// $app is provided by the render method via get_endpoints().
if ( ! isset( $app ) ) {
	$endpoints = $this->get_endpoints();
	$app       = isset( $endpoints['task_app'] ) ? $endpoints['task_app'] : array();
}
?>
<div class="max-w-6xl mx-auto p-6 font-sans text-gray-900" id="arc-tasks">
	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
		<h2 class="text-2xl font-bold mb-4"><?php esc_html_e( 'Mis Tareas', 'arc-api-frontend' ); ?></h2>
		<div class="max-w-xs mb-4">
			<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Filtrar por estado', 'arc-api-frontend' ); ?></label>
			<select id="arc-task-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
				<option value="all"><?php esc_html_e( 'Todas', 'arc-api-frontend' ); ?></option>
				<option value="To Do"><?php esc_html_e( 'To Do', 'arc-api-frontend' ); ?></option>
				<option value="In Progress"><?php esc_html_e( 'In Progress', 'arc-api-frontend' ); ?></option>
				<option value="Review"><?php esc_html_e( 'Review', 'arc-api-frontend' ); ?></option>
				<option value="Done"><?php esc_html_e( 'Done', 'arc-api-frontend' ); ?></option>
			</select>
		</div>

		<div class="overflow-x-auto">
			<table class="w-full border-collapse" id="arc-tasks-list">
				<thead>
					<tr class="bg-gray-50 border-b border-gray-200">
						<th class="text-left px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Tarea', 'arc-api-frontend' ); ?></th>
						<th class="text-left px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Proyecto', 'arc-api-frontend' ); ?></th>
						<th class="text-left px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Estado', 'arc-api-frontend' ); ?></th>
						<th class="text-left px-4 py-3 text-sm font-semibold text-gray-600"><?php esc_html_e( 'Prioridad', 'arc-api-frontend' ); ?></th>
					</tr>
				</thead>
				<tbody class="text-sm text-gray-700">
					<tr>
						<td colspan="4" class="text-center text-gray-400 py-8"><?php esc_html_e( 'Cargando tareas...', 'arc-api-frontend' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<?php if ( empty( $app['endpoint'] ) ) : ?>
		<div class="mt-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4"><?php esc_html_e( 'El endpoint de Task App no está configurado.', 'arc-api-frontend' ); ?></div>
	<?php endif; ?>
</div>
