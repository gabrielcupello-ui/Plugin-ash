<?php
/**
 * Native dashboard template (Tailwind CSS).
 *
 * @package Arc_Native
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="max-w-6xl mx-auto p-6 font-sans text-gray-900">
	<div class="flex items-center justify-between mb-8">
		<h1 class="text-3xl font-bold text-gray-900"><?php esc_html_e( 'ARC Native Dashboard', 'arc-native' ); ?></h1>
		<span class="text-gray-600 font-medium"><?php echo esc_html( wp_get_current_user()->display_name ); ?></span>
	</div>

	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8" data-arc-native-stats>
		<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm text-center">
			<div class="text-4xl font-bold text-sky-500" data-stat="week_hours">-</div>
			<div class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'Hours this week', 'arc-native' ); ?></div>
		</div>
		<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm text-center">
			<div class="text-4xl font-bold text-sky-500" data-stat="eod_count">-</div>
			<div class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'EODs submitted', 'arc-native' ); ?></div>
		</div>
		<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm text-center">
			<div class="text-4xl font-bold text-sky-500" data-stat="active_tasks">-</div>
			<div class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'Active tasks', 'arc-native' ); ?></div>
		</div>
		<div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm text-center">
			<div class="text-4xl font-bold text-sky-500" data-stat="candidates">-</div>
			<div class="text-sm text-gray-500 mt-1"><?php esc_html_e( 'HR candidates', 'arc-native' ); ?></div>
		</div>
	</div>

	<h2 class="text-xl font-bold text-gray-900 mb-4"><?php esc_html_e( 'Modules', 'arc-native' ); ?></h2>
	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
		<?php foreach ( $modules as $slug => $module ) : ?>
			<?php if ( empty( $module['active'] ) ) continue; ?>
			<div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
				<a class="flex items-center gap-4 no-underline text-inherit" href="#<?php echo esc_attr( $module['shortcode'] ); ?>">
					<span class="w-12 h-12 flex items-center justify-center bg-sky-50 text-sky-600 rounded-xl text-xl"><?php echo esc_html( $module['icon'] ); ?></span>
					<div>
						<h3 class="text-lg font-semibold text-gray-900"><?php echo esc_html( $module['label'] ); ?></h3>
						<p class="text-sm text-gray-500"><?php echo esc_html( $module['description'] ); ?></p>
					</div>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</div>
