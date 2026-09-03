<?php
/**
 * Template used by the [arc_portal] shortcode.
 * Styled 100% with Tailwind CSS v4 browser CDN.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings     = $this->get_settings();
$user         = wp_get_current_user();
$logo_url     = $settings['logo_url'];
$portal_title = ! empty( $settings['portal_title'] ) ? $settings['portal_title'] : get_bloginfo( 'name' );
$home_title   = ! empty( $settings['home_title'] ) ? $settings['home_title'] : __( 'Welcome', 'arc-portal' );
$home_desc    = ! empty( $settings['home_description'] ) ? $settings['home_description'] : '';
$help_email   = ! empty( $settings['help_email'] ) ? $settings['help_email'] : 'soporte@ashrivercollective.com';
$logout_url   = ! empty( $settings['logout_url'] ) ? $settings['logout_url'] : wp_logout_url( home_url() );
$pass_email   = ! empty( $settings['pass_email'] );
$email_param  = $pass_email ? rawurlencode( $user->user_email ) : '';

$apps = $settings['apps'];

// Append ?wp_user=email if enabled.
foreach ( $apps as $key => &$app ) {
	if ( $pass_email && 'iframe' === $app['target'] ) {
		$app['url'] = add_query_arg( 'wp_user', $email_param, $app['url'] );
	} elseif ( $pass_email && 'new_tab' === $app['target'] ) {
		$app['url'] = add_query_arg( 'wp_user', $email_param, $app['url'] );
	}
}
unset( $app );

// Helper: map icon slug to inline SVG.
$arc_icon_for = static function ( $slug ) {
	$slug = strtolower( (string) $slug );
	$paths = array(
		'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
		'file-text'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
		'users'       => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
		'check-square'=> '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
		'dashboard'   => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
		'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1z"/>',
		'help-circle' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 2-3 4"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
		'log-out'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
		'grid'        => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
		'home'        => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v10h13V10"/><path d="M9.5 20v-6h5v6"/>',
		'book'        => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
	);
	$path = $paths[ $slug ] ?? $paths['grid'];
	return '<span class="inline-flex items-center justify-center w-5 h-5 text-current" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">' . $path . '</svg></span>';
};

$first_key = '';
$first_url = '';
foreach ( $apps as $key => $app ) {
	if ( empty( $first_key ) && 'iframe' === $app['target'] ) {
		$first_key = $key;
		$first_url = $app['url'];
	}
}
?>
<div id="arc-portal" class="arc-portal flex w-full min-h-[90vh] bg-slate-900 text-slate-100 rounded-none overflow-hidden font-sans">
	<aside class="arc-portal-sidebar w-64 flex-shrink-0 bg-slate-800 border-r border-slate-700 flex flex-col transition-all duration-300 ease-in-out" aria-label="<?php esc_attr_e( 'Portal menu', 'arc-portal' ); ?>">
		<div class="arc-portal-brand p-5 border-b border-slate-700 text-left whitespace-nowrap overflow-hidden">
			<?php if ( $logo_url ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $portal_title ); ?>" class="max-w-full h-auto max-h-12 align-middle">
			<?php else : ?>
				<span class="text-white font-bold text-lg leading-tight"><?php echo esc_html( $portal_title ); ?></span>
			<?php endif; ?>
		</div>

		<nav class="arc-portal-nav flex-1 p-3 flex flex-col gap-1.5">
			<button
				type="button"
				class="arc-portal-nav-item is-active flex items-center gap-3 w-full px-4 py-3 rounded-lg border-none bg-transparent text-slate-400 text-left cursor-pointer transition hover:bg-slate-700 hover:text-white whitespace-nowrap"
				data-app="home"
				data-title="<?php echo esc_attr( $home_title ); ?>"
			>
				<?php echo $arc_icon_for( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed local SVG map. ?>
				<span class="arc-portal-label"><?php esc_html_e( 'Home', 'arc-portal' ); ?></span>
			</button>
			<?php foreach ( $apps as $key => $app ) : ?>
				<?php
				$is_external = 'new_tab' === $app['target'];
				$btn_class   = 'arc-portal-nav-item flex items-center gap-3 w-full px-4 py-3 rounded-lg border-none bg-transparent text-slate-400 text-left cursor-pointer transition hover:bg-slate-700 hover:text-white whitespace-nowrap';
				if ( $is_external ) {
					$btn_class .= ' text-slate-400 no-underline';
				}
				?>
				<?php if ( $is_external ) : ?>
					<a
						class="<?php echo esc_attr( $btn_class ); ?>"
						href="<?php echo esc_url( $app['url'] ); ?>"
						target="_blank"
						rel="noopener"
						data-app="<?php echo esc_attr( $key ); ?>"
					>
						<?php echo $arc_icon_for( $app['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="arc-portal-label"><?php echo esc_html( $app['label'] ); ?></span>
						<span class="ml-auto text-sm opacity-70" aria-hidden="true">↗</span>
					</a>
				<?php else : ?>
					<button
						type="button"
						class="<?php echo esc_attr( $btn_class ); ?>"
						data-app="<?php echo esc_attr( $key ); ?>"
						data-url="<?php echo esc_url( $app['url'] ); ?>"
						data-icon="<?php echo esc_attr( $app['icon'] ); ?>"
						data-title="<?php echo esc_attr( $app['label'] ); ?>"
						aria-label="<?php echo esc_attr( $app['label'] ); ?>"
					>
						<?php echo $arc_icon_for( $app['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="arc-portal-label"><?php echo esc_html( $app['label'] ); ?></span>
					</button>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>

		<div class="arc-portal-sidebar-footer p-4 border-t border-slate-700 text-sm text-slate-400">
			<span class="block mb-2 font-medium text-slate-100"><?php echo esc_html( $user->display_name ); ?></span>
			<a class="text-red-400 hover:underline" href="<?php echo esc_url( $logout_url ); ?>">
				<?php esc_html_e( 'Log out', 'arc-portal' ); ?>
			</a>
		</div>
	</aside>

	<main class="arc-portal-main flex-1 flex flex-col min-w-0 bg-slate-900">
		<header class="arc-portal-header flex items-center justify-between px-6 py-4 border-b border-slate-700 bg-slate-800">
			<div class="arc-portal-header-left flex items-center gap-4">
				<button type="button" id="arc-portal-toggle" class="bg-transparent border-none text-slate-100 text-2xl cursor-pointer px-2 py-1 rounded-md hover:bg-slate-700 leading-none" aria-label="<?php esc_attr_e( 'Show/hide menu', 'arc-portal' ); ?>">
					☰
				</button>
				<h2 id="arc-portal-title" class="arc-portal-title m-0 text-lg font-semibold text-white"><?php echo esc_html( $home_title ); ?></h2>
			</div>
			<div class="arc-portal-header-right flex items-center gap-4">
				<a class="text-slate-400 p-1.5 rounded-md transition hover:bg-slate-700 hover:text-white" href="mailto:<?php echo esc_attr( $help_email ); ?>" title="<?php esc_attr_e( 'Help', 'arc-portal' ); ?>" aria-label="<?php esc_attr_e( 'Help', 'arc-portal' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 2-3 4"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
				</a>
				<div class="flex items-center gap-2 text-slate-100 text-sm font-medium" title="<?php echo esc_attr( $user->user_email ); ?>">
					<span class="w-8 h-8 rounded-full bg-blue-600 text-white inline-flex items-center justify-center text-sm font-bold"><?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?></span>
					<span class="arc-portal-header-user-name"><?php echo esc_html( $user->display_name ); ?></span>
				</div>
				<a class="text-slate-400 text-sm hover:text-red-400 hover:underline" href="<?php echo esc_url( $logout_url ); ?>">
					<?php esc_html_e( 'Log out', 'arc-portal' ); ?>
				</a>
			</div>
		</header>

		<div class="arc-portal-content flex-1 overflow-auto">
			<div id="arc-portal-home" class="arc-portal-home p-8">
				<div class="mb-8">
					<h2 class="text-2xl font-bold text-white mb-2"><?php echo esc_html( $home_title ); ?></h2>
					<?php if ( $home_desc ) : ?>
						<p class="text-slate-400 max-w-2xl"><?php echo esc_html( $home_desc ); ?></p>
					<?php endif; ?>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
					<?php foreach ( $apps as $key => $app ) : ?>
						<?php if ( 'new_tab' === $app['target'] ) : ?>
							<a class="arc-portal-home-card group flex flex-col items-start gap-4 p-6 bg-slate-800 border border-slate-700 rounded-xl text-slate-100 no-underline cursor-pointer transition hover:bg-slate-700 hover:border-blue-500 hover:-translate-y-0.5" href="<?php echo esc_url( $app['url'] ); ?>" target="_blank" rel="noopener">
						<?php else : ?>
							<button type="button" class="arc-portal-home-card group flex flex-col items-start gap-4 p-6 bg-slate-800 border border-slate-700 rounded-xl text-slate-100 text-left cursor-pointer transition hover:bg-slate-700 hover:border-blue-500 hover:-translate-y-0.5 border-none" data-app="<?php echo esc_attr( $key ); ?>">
						<?php endif; ?>
							<span class="text-blue-500"><?php echo $arc_icon_for( $app['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<h3 class="m-0 text-lg font-semibold text-white"><?php echo esc_html( $app['label'] ); ?></h3>
							<?php if ( ! empty( $app['description'] ) ) : ?>
								<p class="m-0 text-sm text-slate-400"><?php echo esc_html( $app['description'] ); ?></p>
							<?php else : ?>
								<p class="m-0 text-sm text-slate-400"><?php esc_html_e( 'Open app', 'arc-portal' ); ?></p>
							<?php endif; ?>
						<?php if ( 'new_tab' === $app['target'] ) : ?>
							</a>
						<?php else : ?>
							</button>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>

			<div id="arc-portal-frame-wrap" class="arc-portal-frame-wrap hide relative flex-1 min-h-0 bg-white">
				<iframe
					id="arc-portal-frame"
					class="absolute inset-0 w-full h-full border-0"
					src=""
					title="<?php esc_attr_e( 'App', 'arc-portal' ); ?>"
					allow="fullscreen"
					loading="lazy"
					data-allow="camera; microphone; geolocation"
				></iframe>
				<div id="arc-portal-frame-error" class="arc-portal-frame-error hide absolute inset-0 bg-slate-900 flex flex-col items-center justify-center p-10 text-center z-10">
					<p class="mb-3 text-white"><?php esc_html_e( 'The app could not be loaded inside the portal.', 'arc-portal' ); ?></p>
					<p class="arc-portal-frame-error-reason text-slate-400 text-sm mb-6"></p>
					<a id="arc-portal-frame-error-link" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold no-underline" href="#" target="_blank" rel="noopener"><?php esc_html_e( 'Open in new tab', 'arc-portal' ); ?></a>
				</div>
			</div>
		</div>
	</main>
</div>
