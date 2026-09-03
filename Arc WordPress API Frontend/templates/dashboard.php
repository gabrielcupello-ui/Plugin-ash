<?php
/**
 * ARC API Frontend — Dashboard template (Tailwind CSS).
 *
 * @package Arc_API_Frontend
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// $endpoints is provided by the render method via get_endpoints().
if ( ! isset( $endpoints ) ) {
	$endpoints = $this->get_endpoints();
}
?>
<div class="max-w-6xl mx-auto p-6 font-sans text-gray-900" id="arc-dashboard">
	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm mb-6">
		<h2 class="text-2xl font-bold mb-2"><?php esc_html_e( 'Dashboard ARC', 'arc-api-frontend' ); ?></h2>
		<p class="text-gray-500"><?php esc_html_e( 'Resumen centralizado de las apps conectadas.', 'arc-api-frontend' ); ?></p>

		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
			<div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center">
				<div class="text-3xl font-bold text-blue-600" data-stat="week_hours">-</div>
				<div class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'Horas esta semana', 'arc-api-frontend' ); ?></div>
			</div>
			<div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center">
				<div class="text-3xl font-bold text-blue-600" data-stat="eod_count">-</div>
				<div class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'EODs enviados', 'arc-api-frontend' ); ?></div>
			</div>
			<div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center">
				<div class="text-3xl font-bold text-blue-600" data-stat="active_tasks">-</div>
				<div class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'Tareas activas', 'arc-api-frontend' ); ?></div>
			</div>
			<div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center">
				<div class="text-3xl font-bold text-blue-600" data-stat="candidates">-</div>
				<div class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'Candidatos RRHH', 'arc-api-frontend' ); ?></div>
			</div>
		</div>

		<p class="text-sm text-gray-400 mt-6">
			<?php esc_html_e( 'Conecta los endpoints en Ajustes > ARC API Frontend para ver datos reales aquí.', 'arc-api-frontend' ); ?>
		</p>
	</div>

	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
		<h3 class="text-xl font-bold mb-4"><?php esc_html_e( 'Accesos rápidos', 'arc-api-frontend' ); ?></h3>
		<div class="flex flex-wrap gap-3">
			<a class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition" href="#arc-time-clock-section"><?php esc_html_e( 'Control Horario', 'arc-api-frontend' ); ?></a>
			<a class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition" href="#arc-eod-form"><?php esc_html_e( 'EOD Report', 'arc-api-frontend' ); ?></a>
			<a class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition" href="#arc-tasks"><?php esc_html_e( 'Task App', 'arc-api-frontend' ); ?></a>
		</div>
	</div>

	<div id="arc-time-clock-section" class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm mt-6">
		<?php echo do_shortcode( '[arc_api_time_clock]' ); ?>
	</div>
</div>
