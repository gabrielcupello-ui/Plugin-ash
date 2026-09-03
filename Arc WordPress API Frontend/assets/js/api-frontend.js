/**
 * ARC API Frontend client.
 */
(function () {
	'use strict';

	function apiUrl(app, action) {
		return arcApiFrontend.restUrl + app + '/' + action;
	}

	function showMessage(el, text, type) {
		if (!el) return;
		el.textContent = text;
		el.className = 'arc-api-message show ' + (type || 'success');
	}

	function hideMessage(el) {
		if (!el) return;
		el.className = 'arc-api-message';
	}

	function apiCall(app, action, data, method) {
		method = method || 'POST';
		return fetch(apiUrl(app, action), {
			method: method,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': arcApiFrontend.nonce,
			},
			body: method === 'GET' ? null : JSON.stringify(data || {}),
		}).then(function (res) {
			return res.json().then(function (json) {
				if (!res.ok) {
					throw new Error((json && (json.message || json.error)) || 'Error en la petición');
				}
				return json;
			});
		});
	}

	function setLoading(el, isLoading) {
		if (!el) return;
		if (isLoading) {
			el.disabled = true;
			el.dataset.oldText = el.textContent;
			el.innerHTML = '<span class="arc-api-loader"></span> ' + (el.dataset.loadingText || 'Cargando...');
		} else {
			el.disabled = false;
			el.textContent = el.dataset.oldText || el.textContent;
		}
	}

	// Auto-save EOD form draft to localStorage.
	function initEODAutoSave() {
		const form = document.getElementById('arc-eod-form');
		if (!form) return;

		const key = 'arc_eod_draft_' + arcApiFrontend.userEmail;
		const draft = localStorage.getItem(key);
		if (draft) {
			try {
				const data = JSON.parse(draft);
				Object.keys(data).forEach(function (field) {
					const input = form.querySelector('[name="' + field + '"]');
					if (input) input.value = data[field];
				});
			} catch (e) {}
		}

		form.addEventListener('input', function () {
			const data = {};
			form.querySelectorAll('input, textarea, select').forEach(function (input) {
				data[input.name] = input.value;
			});
			localStorage.setItem(key, JSON.stringify(data));
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			const msg = document.getElementById('arc-eod-message');
			const btn = form.querySelector('button[type="submit"]');
			hideMessage(msg);
			setLoading(btn, true);

			const data = {};
			form.querySelectorAll('input, textarea, select').forEach(function (input) {
				if (input.name) data[input.name] = input.value;
			});

			apiCall('eod_report', 'submit', data, 'POST')
				.then(function (res) {
					showMessage(msg, res.message || 'Reporte enviado correctamente.', 'success');
					form.reset();
					localStorage.removeItem(key);
				})
				.catch(function (err) {
					showMessage(msg, err.message || 'Error al enviar.', 'error');
				})
				.finally(function () {
					setLoading(btn, false);
				});
		});
	}

	// Load dashboard stats from all configured apps.
	function initDashboard() {
		const dashboard = document.getElementById('arc-dashboard');
		if (!dashboard) return;

		const stats = dashboard.querySelectorAll('[data-stat]');
		if (!stats.length) return;

		function updateStats(res) {
			stats.forEach(function (el) {
				const key = el.dataset.stat;
				if (res[key] !== undefined) {
					el.textContent = res[key];
				}
			});
		}

		function loadStats() {
			const promises = [
				apiCall('time_clock', 'get_stats', {}, 'GET').catch(function () { return {}; }),
				apiCall('eod_report', 'get_stats', {}, 'GET').catch(function () { return {}; }),
				apiCall('task_app', 'get_stats', {}, 'GET').catch(function () { return {}; }),
				apiCall('hr', 'get_stats', {}, 'GET').catch(function () { return {}; }),
			];

			Promise.all(promises).then(function (results) {
				const merged = results.reduce(function (acc, r) {
					if (r && r.success) {
						Object.keys(r).forEach(function (k) {
							if (k !== 'success' && r[k] !== undefined) {
								acc[k] = r[k];
							}
						});
					}
					return acc;
				}, {});
				updateStats(merged);
			}).catch(function () {
				stats.forEach(function (el) {
					el.textContent = '-';
				});
			});
		}

		loadStats();
	}

	// Load tasks with filter.
	function initTasks() {
		const tasksWrap = document.getElementById('arc-tasks-list');
		if (!tasksWrap) return;

		const filter = document.getElementById('arc-task-filter');
		const tbody = tasksWrap.querySelector('tbody');

		function loadTasks(status) {
			if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;"><span class="arc-api-loader"></span></td></tr>';

			apiCall('task_app', 'get_tasks', { status: status || 'all' }, 'GET')
				.then(function (res) {
					const tasks = res.tasks || [];
					if (!tbody) return;
					tbody.innerHTML = tasks.map(function (t) {
						return '<tr>' +
							'<td>' + esc(t.Title || '') + '</td>' +
							'<td>' + esc(t.ProjectName || '') + '</td>' +
							'<td>' + esc(t.Status || '') + '</td>' +
							'<td>' + esc(t.Priority || '') + '</td>' +
						'</tr>';
					}).join('');
				})
				.catch(function () {
					if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;">Error al cargar tareas</td></tr>';
				});
		}

		if (filter) {
			filter.addEventListener('change', function () {
				loadTasks(filter.value);
			});
		}

		loadTasks(filter ? filter.value : 'all');
	}

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

	// HR application form.
	function initHR() {
		const form = document.getElementById('arc-hr-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			const msg = document.getElementById('arc-hr-message');
			const btn = form.querySelector('button[type="submit"]');
			hideMessage(msg);
			setLoading(btn, true);

			const data = {};
			form.querySelectorAll('input, textarea, select').forEach(function (input) {
				if (input.name) data[input.name] = input.value;
			});

			apiCall('hr', 'submit_application', data, 'POST')
				.then(function (res) {
					showMessage(msg, res.message || 'Aplicación enviada correctamente.', 'success');
					form.reset();
				})
				.catch(function (err) {
					showMessage(msg, err.message || 'Error al enviar.', 'error');
				})
				.finally(function () {
					setLoading(btn, false);
				});
		});
	}

	function formatDuration(ms) {
		if (!ms || ms < 0) ms = 0;
		const totalSeconds = Math.floor(ms / 1000);
		const h = Math.floor(totalSeconds / 3600);
		const m = Math.floor((totalSeconds % 3600) / 60);
		const s = totalSeconds % 60;
		return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
	}

	function initTimeClock() {
		const timerEl = document.getElementById('arc-clock-timer');
		const statusEl = document.getElementById('arc-clock-status');
		const btnClockIn = document.getElementById('arc-btn-clock-in');
		const btnClockOut = document.getElementById('arc-btn-clock-out');
		const client = document.getElementById('arc-clock-client');
		const activity = document.getElementById('arc-clock-activity');
		const clockMsg = document.getElementById('arc-clock-message');
		if (!timerEl || !btnClockIn || !btnClockOut) return;

		let startTime = null;
		let timerInterval = null;
		const storageKey = 'arc_api_clock_state';

		function updateTimer() {
			if (!startTime) return;
			timerEl.textContent = formatDuration(Date.now() - startTime.getTime());
		}

		function setClockedIn(start, remember) {
			startTime = start instanceof Date ? start : new Date(start);
			if (isNaN(startTime.getTime())) startTime = new Date();
			updateTimer();
			if (timerInterval) clearInterval(timerInterval);
			timerInterval = setInterval(updateTimer, 1000);
			btnClockIn.style.display = 'none';
			btnClockOut.style.display = 'inline-flex';
			if (statusEl) statusEl.textContent = arcApiFrontend.i18n ? (arcApiFrontend.i18n.clockedIn || 'Sesión activa') : 'Sesión activa';
			if (remember) {
				try {
					localStorage.setItem(storageKey, JSON.stringify({
						state: 'clocked_in',
						start: startTime.toISOString(),
						client: client ? client.value : '',
						activity: activity ? activity.value : '',
					}));
				} catch (e) {}
			}
		}

		function setClockedOut() {
			startTime = null;
			if (timerInterval) clearInterval(timerInterval);
			timerInterval = null;
			timerEl.textContent = '00:00:00';
			btnClockIn.style.display = 'inline-flex';
			btnClockOut.style.display = 'none';
			if (statusEl) statusEl.textContent = arcApiFrontend.i18n ? (arcApiFrontend.i18n.noSession || 'No hay sesión activa') : 'No hay sesión activa';
			try { localStorage.removeItem(storageKey); } catch (e) {}
		}

		function restoreFromStorage() {
			try {
				const raw = localStorage.getItem(storageKey);
				if (!raw) return false;
				const data = JSON.parse(raw);
				if (data && data.state === 'clocked_in' && data.start) {
					if (client && data.client) client.value = data.client;
					if (activity && data.activity) activity.value = data.activity;
					setClockedIn(new Date(data.start), false);
					return true;
				}
			} catch (e) {}
			return false;
		}

		function clockAction(btn, action, data) {
			hideMessage(clockMsg);
			setLoading(btn, true);
			apiCall('time_clock', action, data || {}, 'POST')
				.then(function (res) {
					showMessage(clockMsg, res.message || action + ' registrado.', 'success');
					if (action === 'clock_in') {
						setClockedIn(res.timestamp || res.start || new Date(), true);
					} else if (action === 'clock_out') {
						setClockedOut();
					}
				})
				.catch(function (err) {
					showMessage(clockMsg, err.message || 'Error en ' + action, 'error');
				})
				.finally(function () {
					setLoading(btn, false);
				});
		}

		btnClockIn.addEventListener('click', function () {
			clockAction(btnClockIn, 'clock_in', {
				client: client ? client.value : '',
				activity: activity ? activity.value : '',
			});
		});

		btnClockOut.addEventListener('click', function () {
			clockAction(btnClockOut, 'clock_out', {});
		});

		// Try server state first; fall back to localStorage.
		apiCall('time_clock', 'get_state', {}, 'POST')
			.then(function (res) {
				if (res && res.state === 'clocked_in' && res.start) {
					setClockedIn(res.start, true);
				} else {
					restoreFromStorage();
				}
			})
			.catch(function () {
				restoreFromStorage();
			});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initEODAutoSave();
		initDashboard();
		initTasks();
		initHR();
		initTimeClock();
	});
})();
