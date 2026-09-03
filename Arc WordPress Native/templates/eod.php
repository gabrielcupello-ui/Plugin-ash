<?php
/**
 * Native EOD module template (Tailwind CSS).
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="max-w-3xl mx-auto p-6 font-sans text-gray-900">
	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
		<h2 class="text-2xl font-bold mb-6 text-gray-900"><?php esc_html_e( 'Enviar EOD Report', 'arc-native' ); ?></h2>
		<form class="space-y-5" id="arc-native-eod-form">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Fecha', 'arc-native' ); ?></label>
					<input type="date" name="report_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" required>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Horas trabajadas', 'arc-native' ); ?></label>
					<input type="number" name="hours_worked" step="0.25" min="0" max="24" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" required>
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Resumen del trabajo', 'arc-native' ); ?></label>
				<textarea name="work_description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y" required></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Entregado hoy', 'arc-native' ); ?></label>
				<textarea name="shipped_today" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'En progreso', 'arc-native' ); ?></label>
				<textarea name="in_progress" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Bloqueos / Riesgos', 'arc-native' ); ?></label>
				<textarea name="blockers" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Top 3 prioridades mañana', 'arc-native' ); ?></label>
				<textarea name="top_priorities" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<p class="pt-2">
				<button type="submit" class="inline-flex items-center px-6 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition"><?php esc_html_e( 'Enviar reporte', 'arc-native' ); ?></button>
			</p>
		</form>
	</div>
</div>
