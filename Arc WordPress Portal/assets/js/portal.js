/**
 * Ash River Collective — Integrated Portal front-end controller.
 *
 * Inspirado en panel-ui.js / panel-layout.js del plugin Intranet ARC.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		const portal = document.getElementById('arc-portal');
		if (!portal) return;

		const homeView = document.getElementById('arc-portal-home');
		const frameWrap = document.getElementById('arc-portal-frame-wrap');
		const frame = document.getElementById('arc-portal-frame');
		const title = document.getElementById('arc-portal-title');
		const status = document.getElementById('arc-portal-status');
		const buttons = portal.querySelectorAll('.arc-portal-nav-item');
		const toggle = document.getElementById('arc-portal-toggle');
		const frameError = document.getElementById('arc-portal-frame-error');
		const frameErrorReason = frameError ? frameError.querySelector('.arc-portal-frame-error-reason') : null;
		const frameErrorLink = document.getElementById('arc-portal-frame-error-link');
		const homeCards = document.querySelectorAll('.arc-portal-home-card');

		if (!frame || !title) return;

		const STORAGE_KEY = 'arc_sidebar_collapsed';

		function showHome() {
			if (homeView) homeView.classList.remove('hide');
			if (frameWrap) frameWrap.classList.add('hide');
			if (frameError) frameError.classList.add('hide');
			if (title) title.textContent = homeView ? homeView.querySelector('h2').textContent : '';
			if (status) status.textContent = '';

			buttons.forEach(function (btn) {
				btn.classList.toggle('is-active', btn.dataset.app === 'home');
			});
		}

		function showFrame(url, label) {
			if (homeView) homeView.classList.add('hide');
			if (frameWrap) frameWrap.classList.remove('hide');
			if (frameError) frameError.classList.add('hide');
			if (title) title.textContent = label || 'App';
			if (status) status.textContent = 'Cargando...';

			if (frame.src !== url) {
				frame.src = url;
			}
		}

		function setActive(key) {
			buttons.forEach(function (btn) {
				btn.classList.toggle('is-active', btn.dataset.app === key);
			});
		}

		function handleNavClick(btn) {
			const key = btn.dataset.app;
			const url = btn.dataset.url;
			const label = btn.dataset.title || btn.querySelector('.arc-portal-label')?.textContent || key;

			if (key === 'home') {
				showHome();
				return;
			}

			setActive(key);
			if (url) {
				showFrame(url, label);
			}

			if (window.innerWidth <= 768) {
				document.body.classList.remove('arc-sidebar-open');
			}
		}

		buttons.forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				if (btn.classList.contains('arc-portal-external')) {
					return; // let the anchor open normally
				}
				e.preventDefault();
				handleNavClick(btn);
			});
		});

		homeCards.forEach(function (card) {
			card.addEventListener('click', function () {
				if (card.classList.contains('arc-portal-external') || card.tagName === 'A') {
					return;
				}
				const key = card.dataset.app;
				const btn = portal.querySelector('.arc-portal-nav-item[data-app="' + key + '"]');
				if (btn) {
					handleNavClick(btn);
				}
			});
		});

		frame.addEventListener('load', function () {
			if (status) status.textContent = 'Listo';
			if (frameError) frameError.classList.add('hide');
		});

		frame.addEventListener('error', function () {
			if (status) status.textContent = 'Error al cargar';
			showFrameError('Error de carga del iframe');
		});

		function showFrameError(reason) {
			if (!frameError || !frameErrorLink) return;
			frameError.classList.remove('hide');
			if (frameErrorReason) frameErrorReason.textContent = reason || '';
			frameErrorLink.href = frame.src || '#';
		}

		// Some browsers do not fire error on cross-origin iframe load failures.
		// Use a timeout as fallback to detect a hung load.
		let loadTimeout;
		frame.addEventListener('loadstart', function () {
			if (status) status.textContent = 'Cargando...';
			clearTimeout(loadTimeout);
			loadTimeout = setTimeout(function () {
				if (status && status.textContent === 'Cargando...') {
					showFrameError('La app no responde o no permite iframe (cross-origin).');
				}
			}, 20000);
		});

		if (toggle) {
			toggle.addEventListener('click', function () {
				if (window.innerWidth <= 768) {
					document.body.classList.toggle('arc-sidebar-open');
				} else {
					const collapsed = !document.body.classList.contains('arc-sidebar-collapsed');
					document.body.classList.toggle('arc-sidebar-collapsed', collapsed);
					localStorage.setItem(STORAGE_KEY, collapsed);
				}
			});
		}

		const savedState = localStorage.getItem(STORAGE_KEY);
		if (savedState === 'true' && window.innerWidth > 768) {
			document.body.classList.add('arc-sidebar-collapsed');
		}

		// Start at home.
		showHome();
	});
})();
