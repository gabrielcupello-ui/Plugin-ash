<?php
/**
 * Native HR module template (Tailwind CSS).
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="max-w-3xl mx-auto p-6 font-sans text-gray-900">
	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
		<h2 class="text-2xl font-bold mb-2"><?php esc_html_e( 'Aplicar a vacantes', 'arc-native' ); ?></h2>
		<p class="text-gray-500 mb-6"><?php esc_html_e( 'Completa tu información para postularte.', 'arc-native' ); ?></p>

		<form id="arc-native-hr-form" class="space-y-5">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Nombre', 'arc-native' ); ?></label>
					<input type="text" name="first_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" required>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Apellido', 'arc-native' ); ?></label>
					<input type="text" name="last_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" required>
				</div>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Teléfono', 'arc-native' ); ?></label>
					<input type="tel" name="phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" required>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Años de experiencia', 'arc-native' ); ?></label>
					<input type="number" name="years_experience" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
				</div>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Nivel de inglés', 'arc-native' ); ?></label>
					<select name="english_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
						<option value="Básico"><?php esc_html_e( 'Básico', 'arc-native' ); ?></option>
						<option value="Intermedio"><?php esc_html_e( 'Intermedio', 'arc-native' ); ?></option>
						<option value="Avanzado"><?php esc_html_e( 'Avanzado', 'arc-native' ); ?></option>
					</select>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Nivel de Excel', 'arc-native' ); ?></label>
					<select name="excel_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
						<option value="Básico"><?php esc_html_e( 'Básico', 'arc-native' ); ?></option>
						<option value="Intermedio"><?php esc_html_e( 'Intermedio', 'arc-native' ); ?></option>
						<option value="Avanzado"><?php esc_html_e( 'Avanzado', 'arc-native' ); ?></option>
					</select>
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Posiciones trabajadas', 'arc-native' ); ?></label>
				<textarea name="positions_worked" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Experiencia por dominio', 'arc-native' ); ?></label>
				<textarea name="domain_experience" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Software contable', 'arc-native' ); ?></label>
				<input type="text" name="accounting_software" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Resumen adicional', 'arc-native' ); ?></label>
				<textarea name="summary" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<p class="pt-2">
				<button type="submit" class="inline-flex items-center px-6 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition"><?php esc_html_e( 'Enviar aplicación', 'arc-native' ); ?></button>
			</p>
		</form>
		<div id="arc-native-hr-message" class="hidden mt-4 p-4 rounded-lg"></div>
	</div>
</div>
