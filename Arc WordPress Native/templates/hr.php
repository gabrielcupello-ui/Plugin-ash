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
		<h2 class="text-2xl font-bold mb-2"><?php esc_html_e( 'Apply for vacancies', 'arc-native' ); ?></h2>
		<p class="text-gray-500 mb-6"><?php esc_html_e( 'Complete your information to apply.', 'arc-native' ); ?></p>

		<form id="arc-native-hr-form" class="space-y-5">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'First name', 'arc-native' ); ?></label>
					<input type="text" name="first_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" required>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Last name', 'arc-native' ); ?></label>
					<input type="text" name="last_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" required>
				</div>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Phone', 'arc-native' ); ?></label>
					<input type="tel" name="phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none" required>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Years of experience', 'arc-native' ); ?></label>
					<input type="number" name="years_experience" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
				</div>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'English level', 'arc-native' ); ?></label>
					<select name="english_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
						<option value="Basic"><?php esc_html_e( 'Basic', 'arc-native' ); ?></option>
						<option value="Intermediate"><?php esc_html_e( 'Intermediate', 'arc-native' ); ?></option>
						<option value="Advanced"><?php esc_html_e( 'Advanced', 'arc-native' ); ?></option>
					</select>
				</div>
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Excel level', 'arc-native' ); ?></label>
					<select name="excel_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
						<option value="Basic"><?php esc_html_e( 'Basic', 'arc-native' ); ?></option>
						<option value="Intermediate"><?php esc_html_e( 'Intermediate', 'arc-native' ); ?></option>
						<option value="Advanced"><?php esc_html_e( 'Advanced', 'arc-native' ); ?></option>
					</select>
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Positions worked', 'arc-native' ); ?></label>
				<textarea name="positions_worked" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Domain experience', 'arc-native' ); ?></label>
				<textarea name="domain_experience" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Accounting software', 'arc-native' ); ?></label>
				<input type="text" name="accounting_software" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
			</div>
			<div>
				<label class="block text-sm font-semibold text-gray-700 mb-1"><?php esc_html_e( 'Additional summary', 'arc-native' ); ?></label>
				<textarea name="summary" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none resize-y"></textarea>
			</div>
			<p class="pt-2">
				<button type="submit" class="inline-flex items-center px-6 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-medium rounded-lg transition"><?php esc_html_e( 'Submit application', 'arc-native' ); ?></button>
			</p>
		</form>
		<div id="arc-native-hr-message" class="hidden mt-4 p-4 rounded-lg"></div>
	</div>
</div>
