<?php
/**
 * ARC API Frontend — HR application form template (Tailwind CSS).
 *
 * @package Arc_API_Frontend
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// $app is provided by the render method via get_endpoints().
if ( ! isset( $app ) ) {
	$endpoints = $this->get_endpoints();
	$app       = isset( $endpoints['hr'] ) ? $endpoints['hr'] : array();
}
?>
<div class="max-w-3xl mx-auto p-6 font-sans text-gray-900" id="arc-hr-form">
	<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
		<h2 class="text-2xl font-bold mb-2"><?php esc_html_e( 'Aplicar a vacantes', 'arc-api-frontend' ); ?></h2>
		<p class="text-gray-500 mb-6"><?php esc_html_e( 'Completa tu información para postularte.', 'arc-api-frontend' ); ?></p>

		<form id="arc-hr-form" class="space-y-5">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Nombre', 'arc-api-frontend' ); ?></label>
					<input type="text" id="arc-hr-first-name" name="first_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Apellido', 'arc-api-frontend' ); ?></label>
					<input type="text" id="arc-hr-last-name" name="last_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
				</div>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Teléfono', 'arc-api-frontend' ); ?></label>
					<input type="tel" id="arc-hr-phone" name="phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Años de experiencia', 'arc-api-frontend' ); ?></label>
					<input type="number" id="arc-hr-years" name="years_experience" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
				</div>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Nivel de inglés', 'arc-api-frontend' ); ?></label>
					<select id="arc-hr-english" name="english_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
						<option value="Básico"><?php esc_html_e( 'Básico', 'arc-api-frontend' ); ?></option>
						<option value="Intermedio"><?php esc_html_e( 'Intermedio', 'arc-api-frontend' ); ?></option>
						<option value="Avanzado"><?php esc_html_e( 'Avanzado', 'arc-api-frontend' ); ?></option>
					</select>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Nivel de Excel', 'arc-api-frontend' ); ?></label>
					<select id="arc-hr-excel" name="excel_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
						<option value="Básico"><?php esc_html_e( 'Básico', 'arc-api-frontend' ); ?></option>
						<option value="Intermedio"><?php esc_html_e( 'Intermedio', 'arc-api-frontend' ); ?></option>
						<option value="Avanzado"><?php esc_html_e( 'Avanzado', 'arc-api-frontend' ); ?></option>
					</select>
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Posiciones trabajadas', 'arc-api-frontend' ); ?></label>
				<textarea id="arc-hr-positions" name="positions_worked" rows="2" placeholder="<?php esc_attr_e( 'Contador, analista, etc.', 'arc-api-frontend' ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Experiencia por dominio', 'arc-api-frontend' ); ?></label>
				<textarea id="arc-hr-domain" name="domain_experience" rows="2" placeholder="<?php esc_attr_e( 'Impuestos, nómina, etc.', 'arc-api-frontend' ); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-y"></textarea>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Software contable', 'arc-api-frontend' ); ?></label>
					<input type="text" id="arc-hr-software" name="accounting_software" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Resumen adicional', 'arc-api-frontend' ); ?></label>
				<textarea id="arc-hr-summary" name="summary" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-y"></textarea>
			</div>
			<button type="submit" class="inline-flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition" data-loading-text="<?php esc_attr_e( 'Enviando...', 'arc-api-frontend' ); ?>"><?php esc_html_e( 'Enviar aplicación', 'arc-api-frontend' ); ?></button>
		</form>
		<div id="arc-hr-message" class="hidden mt-4 p-4 rounded-lg"></div>
	</div>

	<?php if ( empty( $app['endpoint'] ) ) : ?>
		<div class="mt-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4"><?php esc_html_e( 'El endpoint de Human Resources no está configurado.', 'arc-api-frontend' ); ?></div>
	<?php endif; ?>
</div>
