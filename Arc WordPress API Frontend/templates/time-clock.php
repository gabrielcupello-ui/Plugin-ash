<?php
/**
 * ARC API Frontend — Time Clock template (Tailwind CSS).
 *
 * @package Arc_API_Frontend
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// $app is provided by the render method via get_endpoints().
if ( ! isset( $app ) ) {
	$endpoints = $this->get_endpoints();
	$app       = isset( $endpoints['time_clock'] ) ? $endpoints['time_clock'] : array();
}
?>
<div class="max-w-2xl mx-auto p-6 font-sans text-gray-900" id="arc-time-clock">
	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
		<h2 class="text-2xl font-bold mb-2"><?php esc_html_e( 'Control Horario', 'arc-api-frontend' ); ?></h2>
		<p class="text-gray-500 mb-6"><?php esc_html_e( 'Registra tu entrada y salida desde WordPress.', 'arc-api-frontend' ); ?></p>

		<div id="arc-clock-timer" class="text-5xl md:text-6xl font-bold text-center text-blue-600 mb-4 tabular-nums">00:00:00</div>
		<div id="arc-clock-status" class="text-center text-sm font-medium text-slate-600 mb-6"><?php esc_html_e( 'No hay sesión activa', 'arc-api-frontend' ); ?></div>

		<div class="space-y-4">
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Cliente / Proyecto', 'arc-api-frontend' ); ?></label>
				<input type="text" id="arc-clock-client" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="<?php esc_attr_e( 'Opcional', 'arc-api-frontend' ); ?>">
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Actividad', 'arc-api-frontend' ); ?></label>
				<input type="text" id="arc-clock-activity" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="<?php esc_attr_e( 'Opcional', 'arc-api-frontend' ); ?>">
			</div>
			<div class="flex gap-3 pt-2">
				<button type="button" id="arc-btn-clock-in" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition" data-loading-text="<?php esc_attr_e( 'Registrando...', 'arc-api-frontend' ); ?>"><?php esc_html_e( 'Clock In', 'arc-api-frontend' ); ?></button>
				<button type="button" id="arc-btn-clock-out" style="display:none" class="inline-flex items-center px-5 py-2.5 bg-slate-500 hover:bg-slate-600 text-white font-medium rounded-lg transition" data-loading-text="<?php esc_attr_e( 'Registrando...', 'arc-api-frontend' ); ?>"><?php esc_html_e( 'Clock Out', 'arc-api-frontend' ); ?></button>
			</div>
		</div>
		<div id="arc-clock-message" class="hidden mt-4 p-4 rounded-lg"></div>
	</div>

	<?php if ( empty( $app['endpoint'] ) ) : ?>
		<div class="mt-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4"><?php esc_html_e( 'El endpoint de IPC Time Clock no está configurado.', 'arc-api-frontend' ); ?></div>
	<?php endif; ?>
</div>
